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
}
