<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $fillable = ['name', 'details', 'category', 'price', 'available', 'image_path'];

    protected function casts(): array
    {
        return [
            'price'     => 'decimal:2',
            'available' => 'boolean',
        ];
    }
}
