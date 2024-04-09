<?php

use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\TypeController;
use App\Http\Controllers\api\AuthController;
use App\Http\Controllers\ClothingController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\OpenAIServiceController;

Route::get('/clothing', [ClothingController::class, 'index']);
Route::get('/clothing/{clothingId}', [ClothingController::class, 'show']);

Route::post('/recommendation', [ClothingController::class, 'getRecommendation'])->name('clothing.recommendation');
// routes/api.php
// Route::post('/upload-image', [ClothingController::class, 'upload']);

Route::get('/types', [TypeController::class, 'index'])->name('types.index');

Route::controller(AuthController::class)->group(function () {
    Route::post('login', 'login');
    Route::post('register', 'register');
    Route::post('logout', 'logout');
    Route::post('refresh', 'refresh');
});

Route::middleware('auth:sanctum')->group(function() {
	# all the routes that require authentication go here 
});

// Route::get('/favorites', [FavoriteController::class, 'index']);
Route::get('/users/{user}/favorites', [FavoriteController::class, 'show']);
Route::post('/users/{user}/favorites/{clothing}', [FavoriteController::class, 'store']);
Route::delete('/users/{user}/favorites/{clothing}', [FavoriteController::class, 'destroy']);


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
    
