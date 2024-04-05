<?php

namespace Database\Seeders;

use App\Models\Type;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clothingCategories = [
            'tops', 'bottoms', 'skirts', 'shoes', 'bags'
        ];

        foreach ($clothingCategories as $category) {
            Type::create([
                'type' => $category
            ]);
        }
    }
}
