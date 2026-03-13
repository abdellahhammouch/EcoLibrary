<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookCopy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class StatsController extends Controller
{
    public function index(): JsonResponse
    {
        $totalBooks = Book::query()->count();
        $totalCopies = BookCopy::query()->count();
        $degradedCopies = BookCopy::query()
            ->where('physical_state', BookCopy::PHYSICAL_DEGRADED)
            ->count();

        $globalConditionScore = $totalCopies > 0
            ? round((($totalCopies - $degradedCopies) / $totalCopies) * 100, 2)
            : 100.0;

        $mostConsultedBooks = Book::query()
            ->select(['id', 'title', 'slug', 'consultations_count'])
            ->with('category:id,name,slug')
            ->orderByDesc('consultations_count')
            ->limit(5)
            ->get();

        $degradedCopiesByBook = Book::query()
            ->select(['id', 'title', 'slug'])
            ->whereHas(
                'copies',
                fn (Builder $query) => $query->where('physical_state', BookCopy::PHYSICAL_DEGRADED)
            )
            ->withCount([
                'copies as degraded_copies_count' => fn (Builder $query) => $query->where('physical_state', BookCopy::PHYSICAL_DEGRADED),
            ])
            ->orderByDesc('degraded_copies_count')
            ->get();

        return response()->json([
            'totals' => [
                'books' => $totalBooks,
                'copies' => $totalCopies,
                'degraded_copies' => $degradedCopies,
            ],
            'global_condition_score' => $globalConditionScore,
            'most_consulted_books' => $mostConsultedBooks,
            'degraded_copies_by_book' => $degradedCopiesByBook,
        ]);
    }
}
