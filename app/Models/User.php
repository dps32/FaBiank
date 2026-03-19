<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{   
    // Esto sirve para factories y notificaciones.
    use HasFactory, Notifiable;

    // Esta tabla no tiene created_at/updated_at.
    public $timestamps = false;

    // Campos que se pueden guardar con create().
    protected $fillable = [
        'balance',
        'username',
        'password',
        'phone_number',
        'is_verified',
        'sms_code',
    ];

    // Esto se oculta cuando devuelves el user.
    protected $hidden = [
        'password',
    ];

    // Tipos automáticos.
    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'is_verified' => 'boolean',
            'password' => 'hashed',
        ];
    }

    // Transacciones que este usuario manda.
    public function sentTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'sender_id');
    }

    // Transacciones que este usuario recibe.
    public function receivedTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'receiver_id');
    }

    // Solicitudes de pago que crea este usuario.
    public function requestedPayments(): HasMany
    {
        return $this->hasMany(PaymentRequest::class, 'requester_id');
    }

    // Solicitudes de pago que llegan a este usuario.
    public function targetedPaymentRequests(): HasMany
    {
        return $this->hasMany(PaymentRequest::class, 'target_id');
    }

    // Inversiones de este usuario.
    public function investments(): HasMany
    {
        return $this->hasMany(Investment::class);
    }
}
