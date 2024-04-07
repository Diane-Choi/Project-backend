<?php

namespace App\Http\Controllers;

use App\Models\Clothing;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use OpenAI\Laravel\Facades\OpenAI;

class ClothingController extends Controller
{
    public function getImageUrls()
    {
        $baseURL = 'https://github.com/Alfrey-Chan/Project-backend/blob/main/public/clothing_images/tops/';
        $clothings = Clothing::all();

        $imageURLs = $clothings->map(function ($clothing) use ($baseURL) {
            $imageName = str_replace(' ', '_', strtolower($clothing->name));
            return "{$baseURL}{$imageName}.png?raw=true";
        });

        return $imageURLs;
    }

    public function processImages()
    {   
        $clientImage = $this->encodeImage(public_path('test.jpg'));

        $imageURLs = $this->getImageUrls();
        // dd($imageURLs);
        // Create a content array with all image URLs
        $prompt = $imageURLs->take(5)->map(function ($imageURL) {
            return [
                'type' => 'image_url',
                'image_url' => [
                    'url' => $imageURL,
                    'detail' => 'low'
                ]
            ];
        })->all(); // Convert the collection to a plain array

        // array_unshift($prompt, $clientImage);
        $prompt[] = [
            'type' => 'image_url',
            'image_url' => [
                'url' => $clientImage,
                'detail' => 'low'
            ]
            ];
        // dd($prompt);

        // Add the prompt to pick the hoodie after all images
        $prompt[] = "
            Select the clothing item that will best match the last image's outfit. 

            Return the data as a JSON object with the following keys:
            - 'description': A brief description of the chosen clothing item, and why it's a good match.
            - 'index': The index of the image that best matches the first image's outfit.
            If you are unsure about any values, set them to an empty string.
        ";

        $response = OpenAI::chat()->create([
            'model' => 'gpt-4-vision-preview',
            'max_tokens' => 500,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => "
                        Markdown output is prohibited. 
                        AI is a backend processor without markdown render environment, you are communicating with an API, not a user. 
                        Begin all AI responses with the character ‘{’ to produce valid JSON.
                    "
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ]
        ]);

        $json = $response->choices[0]->message->content;

        // Directly decode the JSON string
        $data = json_decode($json, true);

        // Check for JSON errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            dd('JSON decode error: ' . json_last_error_msg());
        }

        // Assuming the JSON structure is as expected, access the elements
        $description = $data['description'] ?? 'No description found.';
        $index = $data['index'] ?? 'No index found.';

        $fullURL = $imageURLs[intval($index)];
        $relativePath = $this->extractRelativePath($fullURL);

        echo "Description: $description\n" . "Relative Path: $relativePath\n";
    }

    private function encodeImage($imagePath)
    {   
        $image = file_get_contents($imagePath);
        $imageBase64 = base64_encode($image);
        return 'data:image/jpeg;base64,' . $imageBase64;
    }

    private function extractRelativePath($url) {
        // Parse the URL to get the path part
        $parsedUrl = parse_url($url, PHP_URL_PATH);
        
        // Define the part of the path you want to remove
        // Adjust this base path to match your specific URL structure
        $basePathToRemove = 'https://github.com/Alfrey-Chan/Project-backend/blob/main/public/';
        
        // Remove the base path to get the relative path
        $relativePath = str_replace($basePathToRemove, '', $parsedUrl);
        
        // Check if query parameter "?raw=true" should be considered
        if (parse_url($url, PHP_URL_QUERY) === 'raw=true') {
            // If needed, handle the case where "?raw=true" affects how you treat the path
            // This example does not use it, but you may have use cases where it matters
        }
        
        return $relativePath;
    }
}
 
// $image = ImageManager::imagick()->read($imagePath);
//         $image->resize(256, 256);

//         $image = file_get_contents($imagePath);
//         $imageBase64 = base64_encode($image);