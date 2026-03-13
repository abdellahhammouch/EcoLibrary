<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\BookCopy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookCopy>
 */
class BookCopyFactory extends Factory
{
    protected $model = BookCopy::class;

    public function definition(): array
    {
        return [
            'book_id' => Book::factory(),
            'reference_code' => fake()->unique()->bothify('COPY-####'),
            'status' => fake()->randomElement([BookCopy::STATUS_AVAILABLE, BookCopy::STATUS_UNAVAILABLE]),
            'physical_state' => fake()->randomElement([BookCopy::PHYSICAL_GOOD, BookCopy::PHYSICAL_DEGRADED]),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
