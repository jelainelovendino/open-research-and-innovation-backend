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
            // idempotent: only create when missing
            Category::firstOrCreate(['name' => $name]);
        }
    }
}
