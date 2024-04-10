<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clothing extends Model
{
    public function type()
    {
        return $this->belongsTo(Type::class, 'type_id');
    }

    public function likedByUsers()
    {
        return $this->belongsToMany(User::class, 'favorites');
    }

    public function getIsFavoriteAttribute()
    {
        return $this->likedByUsers->contains('id', auth()->id());
    }
}
