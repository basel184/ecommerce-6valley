<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AbandonedCartReminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'cart_group_id',
        'channel',
        'provider',
        'template_key',
        'to_phone',
        'message_body',
        'status',
        'provider_message_sid',
        'error',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }
}
