<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Investment extends Model
{
    use HasFactory;

    // Esta tabla no usa created_at ni updated_at.
    public $timestamps = false;
    
    // Campos que se pueden guardar.
    protected $fillable = [
        'user_id',
        'stock_id',
        'quantity',
        'buy_price',
        'sell_price',
        'is_sold',
    ];

    // Tipos automáticos para precios y booleano.
    protected function casts(): array
    {
        return [
            'buy_price' => 'decimal:2',
            'sell_price' => 'decimal:2',
            'is_sold' => 'boolean',
        ];
    }

    // Usuario dueño de esta inversión.
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Stock de esta inversión.
    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    // Trades de esta inversión.
    public function trades(): HasMany
    {
        return $this->hasMany(InvestmentTrade::class);
    }
}
