<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestmentTrade extends Model
{
    use HasFactory;

    // Tabla real en DB.
    protected $table = 'investment_trade';

    // Esta tabla no usa created_at ni updated_at.
    public $timestamps = false;

    // Campos que se pueden guardar.
    protected $fillable = [
        'investment_id',
        'type',
        'date',
    ];

    // date se lee como fecha.
    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    // Inversión a la que pertenece este trade.
    public function investment(): BelongsTo
    {
        return $this->belongsTo(Investment::class);
    }
}
