<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Database\Seeders\CategorySeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Create or update a test user so seeding is safe to run multiple times
        User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'course' => 'Computer Science',
                'school' => 'Example University',
                'department' => 'Engineering',
            ]
        );

        // Seed categories
        $this->call(CategorySeeder::class);
        // Seed admin
        $this->call(AdminSeeder::class);
    }
}
