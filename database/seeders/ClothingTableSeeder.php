<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ClothingTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $json = File::get(database_path('seeders/clothing.json'));
        $data = json_decode($json);

        foreach ($data as $item) {
            DB::table('clothing')->insert([
                'name' => $item->name,
                'brand' => $item->brand,
                'colour' => $item->colour,
                'type_id' => $item->type_id,
                'description' => $item->description,
                'image_path' => $item->image_path,
            ]);
        }
    }
}
