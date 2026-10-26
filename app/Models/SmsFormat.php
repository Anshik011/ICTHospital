<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsFormat extends Model
{
    use HasFactory;

    protected $table = 'smsFormat';
    public $timestamps = false;

    protected $fillable = [
        'type',
        'sender',
        'message',
    ];
}
