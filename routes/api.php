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

Route::post('/recommendation', [ClothingController::class, 'getRecommendation'])->name('clothing.recommendation');

Route::get('/types', [TypeController::class, 'index'])->name('types.index');

Route::controller(AuthController::class)->group(function () {
    Route::post('login', 'login');
    Route::post('register', 'register');
    Route::post('logout', 'logout');
    Route::post('refresh', 'refresh');
});

Route::middleware('auth:sanctum')->group(function() {
    # all the routes that require authentication go here 
    Route::get('/clothing', [ClothingController::class, 'index']);
    Route::get('/clothing/{clothingId}', [ClothingController::class, 'show']);
    Route::get('/clothing/type/{typeId}', [ClothingController::class, 'showByType']);

    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::get('/users/{user}/favorites', [FavoriteController::class, 'show']);
    Route::post('/users/{user}/favorites/{clothing}', [FavoriteController::class, 'store']);
    Route::delete('/users/{user}/favorites/{clothing}', [FavoriteController::class, 'destroy']);
});
