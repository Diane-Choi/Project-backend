<?php

namespace App\Http\Controllers;

use OpenAI\Laravel\Facades\OpenAI;
use App\Models\Clothing;
use Illuminate\Http\Request;

class ClothingController extends Controller
{
    public function getImageUrls()
    {
        $baseURL = 'https://github.com/Alfrey-Chan/Project-backend/blob/main/public/clothing_images/';
        $clothings = Clothing::all();

        $imageURLs = $clothings->map(function ($clothing) use ($baseURL) {
            $imageName = str_replace(' ', '_', strtolower($clothing->name));
            return "{$baseURL}{$imageName}.png?raw=true";
        });

        return $imageURLs;
    }

    public function processImages()
    {
        $imageURLs = $this->getImageUrls();
        // dd($imageURLs);
        $responses = $imageURLs->map(function ($imageURL) {
            return OpenAI::chat()->create([
                'model' => 'gpt-4-vision-preview',
                'max_tokens' => 100,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => $imageURL,
                                    'detail' => 'low'
                                ]
                            ]
                        ]
                    ],
                    [
                        'role' => 'user',
                        'content' => 'Describe the image for me in one sentence'
                    ]
                ]
            ]); 
        });
        dd($responses);
        return $responses;
    }
}
