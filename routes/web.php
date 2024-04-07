<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClothingController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/upload', [ClothingController::class, 'showUploadForm']);
Route::post('/upload', [ClothingController::class, 'upload']);