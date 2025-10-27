<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = ['Technology', 'Health', 'Environment', 'Education'];

        foreach ($categories as $name) {
            // Use firstOrCreate to make the seeder idempotent (safe to run multiple times)
            Category::firstOrCreate(['name' => $name]);
        }
    }
}
