<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// app/Models/OrderItem.php
class OrderItem extends Model
{
    protected $fillable = ['order_id', 'menu_id', 'quantity', 'price'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }
}
