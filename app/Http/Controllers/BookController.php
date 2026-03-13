<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class BookController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->integer('per_page', 15), 100);

        $books = $this->baseBookQuery($request)
            ->paginate($perPage);

        return response()->json($books);
    }

    public function categoryBooks(Request $request, Category $category): JsonResponse
    {
        $perPage = min($request->integer('per_page', 15), 100);

        $books = $this->baseBookQuery($request)
            ->where('category_id', $category->id)
            ->when(
                $request->boolean('available_only', true),
                fn (Builder $query) => $query->whereHas(
                    'copies',
                    fn (Builder $copiesQuery) => $copiesQuery->where('status', BookCopy::STATUS_AVAILABLE)
                )
            )
            ->paginate($perPage);

        return response()->json($books);
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
        ]);

        $books = $this->baseBookQuery($request)
            ->paginate(min($request->integer('per_page', 15), 100));

        return response()->json($books);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'acquired_at' => ['required', 'date'],
        ]);

        $validated['slug'] = $this->generateUniqueSlug($validated['title']);
        $validated['consultations_count'] = 0;

        $book = Book::query()->create($validated);

        return response()->json($book->load('category'), 201);
    }

    public function show(Book $book): JsonResponse
    {
        $book->increment('consultations_count');

        $book = $book->fresh()->load('category')
            ->loadCount([
                'copies as total_copies_count',
                'copies as available_copies_count' => fn (Builder $query) => $query->where('status', BookCopy::STATUS_AVAILABLE),
                'copies as degraded_copies_count' => fn (Builder $query) => $query->where('physical_state', BookCopy::PHYSICAL_DEGRADED),
            ]);

        return response()->json($book);
    }

    public function update(Request $request, Book $book): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'acquired_at' => ['required', 'date'],
        ]);

        $validated['slug'] = $validated['title'] === $book->title
            ? $book->slug
            : $this->generateUniqueSlug($validated['title'], $book->id);

        $book->update($validated);

        return response()->json($book->fresh()->load('category'));
    }

    public function destroy(Book $book): Response
    {
        $book->delete();

        return response()->noContent();
    }

    private function baseBookQuery(Request $request): Builder
    {
        $searchTerm = trim((string) $request->query('q', ''));
        $categoryFilter = trim((string) $request->query('category', ''));
        $sort = $request->query('sort', 'new');

        return Book::query()
            ->with('category')
            ->withCount([
                'copies as total_copies_count',
                'copies as available_copies_count' => fn (Builder $query) => $query->where('status', BookCopy::STATUS_AVAILABLE),
                'copies as degraded_copies_count' => fn (Builder $query) => $query->where('physical_state', BookCopy::PHYSICAL_DEGRADED),
            ])
            ->when($searchTerm !== '', function (Builder $query) use ($searchTerm): void {
                $query->where(function (Builder $nestedQuery) use ($searchTerm): void {
                    $nestedQuery->where('title', 'like', '%'.$searchTerm.'%')
                        ->orWhere('author', 'like', '%'.$searchTerm.'%')
                        ->orWhereHas('category', fn (Builder $categoryQuery) => $categoryQuery->where('name', 'like', '%'.$searchTerm.'%'));
                });
            })
            ->when($categoryFilter !== '', function (Builder $query) use ($categoryFilter): void {
                $query->whereHas('category', function (Builder $categoryQuery) use ($categoryFilter): void {
                    $categoryQuery->where('slug', $categoryFilter)
                        ->orWhere('name', 'like', '%'.$categoryFilter.'%');
                });
            })
            ->when(
                $request->boolean('available_only', false),
                fn (Builder $query) => $query->whereHas(
                    'copies',
                    fn (Builder $copiesQuery) => $copiesQuery->where('status', BookCopy::STATUS_AVAILABLE)
                )
            )
            ->when(
                $sort === 'popular',
                fn (Builder $query) => $query->orderByDesc('consultations_count'),
                fn (Builder $query) => $query->orderByDesc('acquired_at')
            );
    }

    private function generateUniqueSlug(string $title, ?int $ignoreBookId = null): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug;
        $iteration = 1;

        while (
            Book::query()
                ->when($ignoreBookId, fn ($query) => $query->where('id', '!=', $ignoreBookId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$iteration;
            $iteration++;
        }

        return $slug;
    }
}
