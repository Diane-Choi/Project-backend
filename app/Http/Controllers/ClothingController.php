<?php

namespace App\Http\Controllers;

use OpenAI\Laravel\Facades\OpenAI;
use App\Models\Clothing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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
        dd($imageURLs);
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
        // dd($responses);
        return $responses;
    }

    public function showUploadForm()
    {
        return view('upload');
    }

    public function upload(Request $request)
    {
        // Validate the uploaded image file
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
    
        // Retrieve the uploaded image file
        $userImage = $request->file('image');
    
        // Get image URLs of clothing items
        $baseURL = 'https://github.com/Alfrey-Chan/Project-backend/blob/main/public/clothing_images/';
        $clothings = Clothing::all();
        $imageURLs = $clothings->map(function ($clothing) use ($baseURL) {
            return $baseURL . $clothing->image_path; // Adjust this based on your actual image path in the Clothing model
        });
    
        // Create an array to store OpenAI responses
        $responses = [];
    
        // Prompt OpenAI to compare each image URL with the uploaded image
        foreach ($imageURLs as $imageURL) {
            $response = OpenAI::chat()->create([
                'model' => 'clip',
                'max_tokens' => 100,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => 'Based on the provided image and this clothing item, how well do they match? Choose one image_url and explain why it matches well.'
                    ],
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'image_url',
                                'image_url' => $imageURL,
                            ]
                        ]
                    ],
                    [
                        'role' => 'user',
                        'content' => 'Explanation why it matches well with the uploaded image.'
                    ],
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'image_file',
                                'image_file' => [
                                    'url' => $userImage->getPathname(),
                                ]
                            ]
                        ]
                    ]
                ]
            ]);
            
            // Store the response in the array
            $responses[] = $response;
        }
    
        // Return the responses
        return $responses;
    }
    
    
}
