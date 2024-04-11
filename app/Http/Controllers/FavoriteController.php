<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\ClothingController;
use App\Models\Clothing;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    // show all the items with the user who liked them
    public function index()
    {
        $clothings = Clothing::with('likedByUsers')->get();
        return response()->json($clothings);
    }

    // show all the items liked by a specific user
    public function show($userId)
    {
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json($user->favoriteClothings);
    }

    public function store($userId, $clothingId)
    {
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $clothing = Clothing::find($clothingId);
        if (!$clothing) {
            return response()->json(['error' => 'Clothing item not found'], 404);
        }

        $isFavorite = $user->favoriteClothings()->where('clothing_id', $clothingId)->exists();

        if (!$isFavorite) {
            // Add to favorites
            $user->favoriteClothings()->attach($clothingId);
            return response()->json(['success' => 'Clothing item added to favorites']);
        } else {
            // Already in favorites
            return response()->json(['error' => 'Clothing item is already in favorites'], 400);
        }
    }

    public function destroy($userId, $clothingId)
    {
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $clothing = Clothing::find($clothingId);
        if (!$clothing) {
            return response()->json(['error' => 'Clothing item not found'], 404);
        }

        // Check if the clothing item is in the user's favorites
        if (!$user->favoriteClothings()->where('clothing_id', $clothingId)->exists()) {
            return response()->json(['error' => 'This item is not in your favorites'], 400);
        }

        // Remove the clothing item from favorites
        $user->favoriteClothings()->detach($clothingId);

        return response()->json(['success' => 'Removed from favorites'], 200);
    }
}
