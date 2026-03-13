<?php

namespace App\Http\Controllers;

use App\Models\BookCopy;
use App\Models\User;
use App\Notifications\BookCopyMarkedDegraded;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class BookCopyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->integer('per_page', 15), 100);

        $copies = BookCopy::query()
            ->with('book.category')
            ->when(
                $request->filled('book_slug'),
                fn (Builder $query) => $query->whereHas(
                    'book',
                    fn (Builder $bookQuery) => $bookQuery->where('slug', $request->string('book_slug')->toString())
                )
            )
            ->when(
                $request->filled('physical_state'),
                fn (Builder $query) => $query->where('physical_state', $request->string('physical_state')->toString())
            )
            ->latest()
            ->paginate($perPage);

        return response()->json($copies);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'book_id' => ['required', 'exists:books,id'],
            'reference_code' => ['required', 'string', 'max:255', 'unique:book_copies,reference_code'],
            'status' => ['required', Rule::in([BookCopy::STATUS_AVAILABLE, BookCopy::STATUS_UNAVAILABLE])],
            'physical_state' => ['required', Rule::in([BookCopy::PHYSICAL_GOOD, BookCopy::PHYSICAL_DEGRADED])],
            'notes' => ['nullable', 'string'],
        ]);

        $copy = BookCopy::query()->create($validated);

        if ($copy->physical_state === BookCopy::PHYSICAL_DEGRADED) {
            $this->notifyAdminsForDegradedCopy($copy);
        }

        return response()->json($copy->load('book.category'), 201);
    }

    public function update(Request $request, BookCopy $bookCopy): JsonResponse
    {
        $validated = $request->validate([
            'book_id' => ['required', 'exists:books,id'],
            'reference_code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('book_copies', 'reference_code')->ignore($bookCopy->id),
            ],
            'status' => ['required', Rule::in([BookCopy::STATUS_AVAILABLE, BookCopy::STATUS_UNAVAILABLE])],
            'physical_state' => ['required', Rule::in([BookCopy::PHYSICAL_GOOD, BookCopy::PHYSICAL_DEGRADED])],
            'notes' => ['nullable', 'string'],
        ]);

        $wasDegraded = $bookCopy->physical_state === BookCopy::PHYSICAL_DEGRADED;
        $bookCopy->update($validated);

        if (! $wasDegraded && $bookCopy->physical_state === BookCopy::PHYSICAL_DEGRADED) {
            $this->notifyAdminsForDegradedCopy($bookCopy);
        }

        return response()->json($bookCopy->fresh()->load('book.category'));
    }

    public function destroy(BookCopy $bookCopy): Response
    {
        $bookCopy->delete();

        return response()->noContent();
    }

    private function notifyAdminsForDegradedCopy(BookCopy $bookCopy): void
    {
        $admins = User::query()->where('role', 'admin')->get();

        if ($admins->isEmpty()) {
            return;
        }

        $admins->each(fn (User $admin) => $admin->notify(new BookCopyMarkedDegraded($bookCopy)));
    }
}
