<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    // Esta tabla no usa created_at ni updated_at.
    public $timestamps = false;

    // Campos que se pueden guardar.
    protected $fillable = [
        'sender_id',
        'receiver_id',
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

    // El usuario que manda el dinero.
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    // El usuario que recibe el dinero.
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}
