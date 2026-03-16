<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_builds_a_coherent_library_catalog(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('categories', 5);
        $this->assertDatabaseCount('users', 10);
        $this->assertSame(1, User::query()->where('role', 'admin')->count());
        $this->assertSame(9, User::query()->where('role', 'lecteur')->count());

        $categoryBookCounts = Category::query()->withCount('books')->get()->pluck('books_count');
        $this->assertTrue($categoryBookCounts->every(fn (int $count) => $count >= 4 && $count <= 5));

        $bookCopyCounts = Book::query()->withCount('copies')->get()->pluck('copies_count');
        $this->assertTrue($bookCopyCounts->every(fn (int $count) => $count >= 2 && $count <= 3));

        $this->assertTrue(
            Book::query()->get()->every(
                fn (Book $book) => $book->copies()->where('status', BookCopy::STATUS_AVAILABLE)->exists()
            )
        );
    }
}
