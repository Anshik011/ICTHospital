<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentGateway extends Model
{
    use HasFactory;

    protected $table = 'paymentGateway';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'merchant_key',
        'salt',
        'x',
        'y',
        'APIUsername',
        'APIPassword',
        'APISignature',
        'status',
    ];

    /**
     * The attributes that should be cast to native types.
     * Secrets are encrypted at rest using Laravel application key.
     *
     * @var array
     */
    protected $casts = [
        'merchant_key' => 'encrypted',
        'salt'         => 'encrypted',
        'APIUsername'  => 'encrypted',
        'APIPassword'  => 'encrypted',
        'APISignature' => 'encrypted',
    ];
}
