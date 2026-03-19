<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentRequest extends Model
{
    use HasFactory;

    // Esta tabla no usa created_at ni updated_at.
    public $timestamps = false;

    // Campos que se pueden guardar.
    protected $fillable = [
        'requester_id',
        'target_id',
        'amount',
        'date',
    ];

    // Tipos automáticos para amount y date.
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'date' => 'date',
        ];
    }

    // Usuario que pide el dinero.
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    // Usuario al que le piden el dinero.
    public function target(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_id');
    }
}
