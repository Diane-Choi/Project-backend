<?php

use App\Http\Controllers\ClothingController;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\OpenAIServiceController;

Route::get('/ai', [ClothingController::class, 'processImages']);

// Route::get('/ai', function() {

//     $baseURL = 'https://github.com/Alfrey-Chan/Project-backend/blob/main/public/clothing_images/';
//     $imageURL = "{$baseURL}cream_pullover_hoodie.png?raw=true";
    

//     $response = OpenAI::chat()->create([
//         'model' => 'gpt-4-vision-preview',
//         'max_tokens' => 100,
//         'messages' => [
//             [
//                 'role' => 'user',
//                 'content' => [
//                     [
//                         'type' => 'image_url',
//                         'image_url' => [
//                             'url' => $imageURL,
//                             'detail' => 'low'
//                         ]
//                     ]
//                 ]
//             ],
//             [
//                 'role' => 'user',
//                 'content' => 'Describe the image for me in one sentence'
//             ]
//         ]
//     ]); 

//     echo $response->choices[0]->message->content;
// }); 
    
