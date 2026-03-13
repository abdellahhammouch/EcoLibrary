<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
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
    }
}
