<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Category;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create categories if they don't exist
        $categories = ['Technology', 'Health', 'Environment', 'Education'];
        foreach ($categories as $name) {
            Category::firstOrCreate(['name' => $name]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't delete categories on rollback
    }
};
