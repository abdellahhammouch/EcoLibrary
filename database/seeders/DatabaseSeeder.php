<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $faker = fake();

        User::query()->create([
            'name' => 'Admin User',
            'email' => 'admin@ecolibrary.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        User::query()->create([
            'name' => 'Reader User',
            'email' => 'reader@ecolibrary.test',
            'password' => Hash::make('password'),
            'role' => 'lecteur',
        ]);

        User::factory()
            ->lecteur()
            ->count(8)
            ->create();

        $categories = [
            [
                'name' => 'Zero dechet',
                'description' => 'Livres pratiques pour reduire les dechets au quotidien.',
            ],
            [
                'name' => 'Compostage',
                'description' => 'Guides et conseils autour du compost et des sols vivants.',
            ],
            [
                'name' => 'Biodiversite',
                'description' => 'Ouvrages sur la faune, la flore et les ecosystemes.',
            ],
            [
                'name' => 'Energie verte',
                'description' => 'Ressources pour comprendre les energies renouvelables.',
            ],
            [
                'name' => 'Consommation responsable',
                'description' => 'Lectures sur les achats durables et l impact environnemental.',
            ],
        ];

        foreach ($categories as $categoryData) {
            $category = Category::query()->create([
                'name' => $categoryData['name'],
                'slug' => Str::slug($categoryData['name']),
                'description' => $categoryData['description'],
            ]);

            $books = Book::factory()
                ->count($faker->numberBetween(4, 5))
                ->create([
                    'category_id' => $category->id,
                ]);

            $books->each(function (Book $book) use ($faker): void {
                BookCopy::factory()
                    ->available()
                    ->good()
                    ->create([
                        'book_id' => $book->id,
                    ]);

                $remainingCopies = $faker->numberBetween(1, 2);

                for ($index = 0; $index < $remainingCopies; $index++) {
                    $factory = BookCopy::factory();

                    if ($faker->boolean(65)) {
                        $factory = $factory->available();
                    } else {
                        $factory = $factory->unavailable();
                    }

                    if ($faker->boolean(25)) {
                        $factory = $factory->degraded();
                    } else {
                        $factory = $factory->good();
                    }

                    $factory->create([
                        'book_id' => $book->id,
                    ]);
                }
            });
        }
    }
}
