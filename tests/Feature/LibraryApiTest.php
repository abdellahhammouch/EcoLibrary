<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LibraryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_receive_api_token(): void
    {
        $password = 'secret123';
        $user = User::factory()->create([
            'email' => 'reader@test.dev',
            'password' => Hash::make($password),
            'role' => 'lecteur',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => $password,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'token',
                'user' => ['id', 'name', 'email', 'role'],
            ]);
    }

    public function test_reader_can_list_available_books_in_a_category(): void
    {
        $reader = $this->userWithToken('lecteur');
        $category = Category::factory()->create(['slug' => 'zero-dechet']);

        $availableBook = Book::factory()->create([
            'category_id' => $category->id,
            'title' => 'Guide Compost',
            'slug' => 'guide-compost',
        ]);

        $unavailableBook = Book::factory()->create([
            'category_id' => $category->id,
            'title' => 'Livre Rare',
            'slug' => 'livre-rare',
        ]);

        BookCopy::factory()->create([
            'book_id' => $availableBook->id,
            'status' => BookCopy::STATUS_AVAILABLE,
            'physical_state' => BookCopy::PHYSICAL_GOOD,
        ]);

        BookCopy::factory()->create([
            'book_id' => $unavailableBook->id,
            'status' => BookCopy::STATUS_UNAVAILABLE,
            'physical_state' => BookCopy::PHYSICAL_GOOD,
        ]);

        $response = $this->withToken($reader['token'])->getJson('/api/categories/zero-dechet/books?available_only=1');

        $response->assertOk();
        $response->assertJsonFragment(['slug' => 'guide-compost']);
        $response->assertJsonMissing(['slug' => 'livre-rare']);
    }

    public function test_reader_cannot_access_admin_category_creation(): void
    {
        $reader = $this->userWithToken('lecteur');

        $response = $this->withToken($reader['token'])->postJson('/api/categories', [
            'name' => 'Biodiversite',
            'description' => 'Nature et ecosystemes',
        ]);

        $response->assertForbidden();
    }

    public function test_admin_stats_include_degraded_copies_totals(): void
    {
        $admin = $this->userWithToken('admin');
        $category = Category::factory()->create();

        $book = Book::factory()->create([
            'category_id' => $category->id,
            'consultations_count' => 42,
        ]);

        BookCopy::factory()->create([
            'book_id' => $book->id,
            'physical_state' => BookCopy::PHYSICAL_DEGRADED,
            'status' => BookCopy::STATUS_UNAVAILABLE,
        ]);

        BookCopy::factory()->create([
            'book_id' => $book->id,
            'physical_state' => BookCopy::PHYSICAL_GOOD,
            'status' => BookCopy::STATUS_AVAILABLE,
        ]);

        $response = $this->withToken($admin['token'])->getJson('/api/admin/stats');

        $response->assertOk()
            ->assertJsonPath('totals.books', 1)
            ->assertJsonPath('totals.copies', 2)
            ->assertJsonPath('totals.degraded_copies', 1);
    }

    private function userWithToken(string $role): array
    {
        $user = User::factory()->create([
            'role' => $role,
        ]);

        return [
            'user' => $user,
            'token' => $user->createToken('test-token')->plainTextToken,
        ];
    }
}
