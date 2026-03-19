<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stock extends Model
{
    use HasFactory;

    // Esta tabla no usa created_at ni updated_at.
    public $timestamps = false;

    // Campos que se pueden guardar.
    protected $fillable = [
        'ticker',
        'name',
        'current_price',
    ];

    // current_price se lee como decimal.
    protected function casts(): array
    {
        return [
            'current_price' => 'decimal:2',
        ];
    }

    // Inversiones que usan este stock.
    public function investments(): HasMany
    {
        return $this->hasMany(Investment::class);
    }
}
