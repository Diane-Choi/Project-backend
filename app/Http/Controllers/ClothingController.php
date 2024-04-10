<?php

namespace App\Http\Controllers;

use App\Models\Type;
use App\Models\Clothing;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Http;


class ClothingController extends Controller
{
    public function index() {
        $clothing = Clothing::all();
        return response()->json($clothing);
    }

    public function show($clothingId) {
        $clothing = Clothing::find($clothingId);
        return response()->json($clothing);
    }

    private function getClothingByType(int $type_id)
    {   
        // Get the clothing type
        $clothing_type = Type::find($type_id)->type;

        $base_url = "https://github.com/Alfrey-Chan/Project-backend/blob/main/public/clothing_images/$clothing_type/";
        $url_end = '.png?raw=true';

        $clothing_items = Clothing::where('type_id', $type_id)->get();

        // Construct the image URL for each item
        $image_urls = $clothing_items->map(function ($item) use ($base_url, $url_end) {
            $item_name = str_replace(' ', '_', strtolower($item->name));
            return [
                'id' => $item->id,
                'url' => $base_url . $item_name . $url_end
            ];
        });

        return $image_urls;
    }

    private function buildPrompt(Collection $image_urls, string $uploaded_image): array
    {
        // Initialize the prompt array with the instruction text
        $prompt = [
            [
                'type' => 'text',
                'text' => "
                    The last image is the uploaded image of an outfit that you want to match.
                    Select the clothing item that will best match the uploaded image.
                    Refer to the images by their index number starting with 0 for the first image.
                    Return the data as a JSON object with the following keys:
                    - 'description': A brief description of the chosen clothing item, and why it's a good match but without referring to its sequence position (e.g., 'fifth image', 'first image', 'last image', etc).
                    - 'index': The index of the image that best matches the outfit.
                "
            ]
        ];

        // Add each image URL to the prompt
        foreach ($image_urls as $url) {
            $prompt[] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => $url, // Make sure this is a correct URL or base64-encoded image
                    'detail' => 'low'
                ]
            ];
        }

        // Add the uploaded image to the prompt
        $prompt[] = [
            'type' => 'image_url',
            'image_url' => [
                'url' => $uploaded_image,
                'detail' => 'low'
            ]
        ];

        return $prompt;
    }

    // protected function createImageContents($imageUrls, $uploadedImage): array
    // {
    //     // Create a content array with all image URLs
    //     $imageContents = $imageUrls->map(function ($url) {
    //         return [
    //             'type' => 'image_url',
    //             'image_url' => [
    //                 'url' => $url, // URL must be a public image URL or a base64-encoded image
    //                 'detail' => 'low' // Keep the detail low to use fewer tokens and speed up the response
    //             ]
    //         ];
    //     })->all(); // Convert the collection to a plain array for the prompt

    //     // Add the uploaded image to image contents
    //     $imageContents[] = [
    //         'type' => 'image_url',
    //         'image_url' => [
    //             'url' => $uploadedImage, // Base64-encoded image
    //             'detail' => 'low'
    //         ]
    //     ];

    //     return $imageContents;
    // }

    private function callOpenAI(array $prompt)
    {
        return OpenAI::chat()->create([
            'model' => 'gpt-4-vision-preview',
            'max_tokens' => 250,
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
                    'content' => json_encode($prompt)
                ]
            ]
        ]);
    }

    private function processOpenAIResponse($response, $clothingItems)
    {
        $jsonString = $response->choices[0]->message->content;

        // Decode the JSON string into an associative array
        $data = json_decode($jsonString, true);

        // Check for JSON errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['error' => 'JSON decode error: ' . json_last_error_msg()];
        }

        $index = $data['index'] ?? null;
        if (null === $index || !isset($clothingItems[intval($index)])) {
            return ['error' => 'Unable to find a matching clothing item. Please try again.'];
        }

        $item = $clothingItems[intval($index)];
        $description = $data['description'];
        $imageId = $item['id'];
        $recommendedItemImage = $this->encodeImage($item['url']); // Ensure this function exists

        return [
            'id' => $imageId,
            'description' => $description,
            'recommended_item_image' => $recommendedItemImage
        ];
    }

    public function getRecommendation(Request $request)
    {   
        $type_id = $request->input('type_id');
        $uploaded_image = $request->input('uploaded_image');

        $clothing_items = $this->getClothingByType($type_id);
        $image_urls = collect($clothing_items)->pluck('url');

        // $image_contents = $this->createImageContents($image_urls, $uploaded_image);

        $prompt = $this->buildPrompt($image_urls, $uploaded_image);

        $response = $this->callOpenAI($prompt);

        $result = $this->processOpenAIResponse($response, $clothing_items);

        if (isset($result['error'])) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }

    private function encodeImage($imagePath)
    {   
        $image = file_get_contents($imagePath);
        $imageBase64 = base64_encode($image);
        return 'data:image/jpeg;base64,' . $imageBase64;
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

    public function showByType($typeId)
    {
        $clothings = Clothing::where('type_id', $typeId)->get();
        return response()->json($clothings);
    }
}
