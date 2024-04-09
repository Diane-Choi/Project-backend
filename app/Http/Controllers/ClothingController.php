<?php

namespace App\Http\Controllers;

use App\Models\Clothing;
use App\Models\Type;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use OpenAI\Laravel\Facades\OpenAI;


class ClothingController extends Controller
{
    public function getImageUrls(int $type_id)
    {   
        // Get the clothing type
        $clothing_type = Type::find($type_id)->type;

        $base_url = "https://github.com/Alfrey-Chan/Project-backend/blob/main/public/clothing_images/$clothing_type/";
        $url_end = '.png?raw=true';

        $clothing_items = Clothing::where('type_id', $type_id)->get();

        // Construct the image URL for each item
        $image_urls = $clothing_items->map(function ($item) use ($base_url, $url_end) {
            $item_name = str_replace(' ', '_', strtolower($item->name));
            return "{$base_url}{$item_name}{$url_end}";
        });

        return $image_urls;
    }

    // private function getClothingType(int $type_id)
    // {
    //     return Type::find($type_id)->type;
    // }

    public function getRecommendation(Request $request)
    {   
        $type_id = $request->input('type_id');
        $uploaded_image = $request->input('uploaded_image');

        $image_URLs = $this->getImageUrls($type_id);
        // dd($imageURLs);

        // Create a content array with all image URLs
        $prompt = $image_URLs->map(function ($image_URL) {
            return [
                'type' => 'image_url', // AI model only accepts types 'image_url' and 'text'
                'image_url' => [
                    'url' => $image_URL, // url must be a public image URL or a base64-encoded image
                    'detail' => 'low' // Keep the detail low to use less tokens and speed up the response
                ]
            ];
        })->all(); // Convert the collection to a plain array for the prompt

        // $prompt[] = [
        //     'type' => 'image_url',
        //     'image_url' => [
        //         'url' => $uploaded_image, // base64-encoded image
        //         'detail' => 'low'
        //     ]
        // ];

        // Add the uploaded image to the beginning of the array
        array_unshift($prompt, [
            'type' => 'image_url',
            'image_url' => [
                'url' => $uploaded_image, // base64-encoded image
                'detail' => 'low'
            ]
        ]); 

        // TODO: Place the actual prompt in another section of messages
        // Add the prompt to pick the hoodie after all images
        $prompt[] = "
            Select the clothing item that will best match the first image's outfit. 

            Return the data as a JSON object with the following keys:
            - 'description': A brief description of the chosen clothing item, and why it's a good match.
            - 'index': The index of the image that best matches the first image's outfit. Note: The index starts from 0.
            If you are unsure about any values, set them to an empty string.
        ";

        $response = OpenAI::chat()->create([
            'model' => 'gpt-4-vision-preview',
            'max_tokens' => 250,
            'messages' => [
                [   
                    // Set context for the AI model
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

        $json_string = $response->choices[0]->message->content;

        // dd($json);

        // Directly decode the JSON string
        $data = json_decode($json_string, true);

        // Check for JSON errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            dd('JSON decode error: ' . json_last_error_msg());
        }

        $description = $data['description'];
        $index = $data['index'];

        if (empty($description) || empty($index)) {
            return response()->json([
                'error' => 'Unable to find a matching clothing item. Please try again.'
            ], 400);
        }

        $recommended_item_base64_encoded =  $this->encodeImage($image_URLs[intval($index)]);

        return response()->json([
            'description' => $description,
            'recommended_item_image' => $recommended_item_base64_encoded
        ]);

        // echo "Description: $description\n" . "Index: $index\n";
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
        
        return $relativePath;
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
