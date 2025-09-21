<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentRequest extends Model
{
    use HasUuid;
    use HasFactory;

    protected $table = 'payment_requests';
    
    protected $fillable = [
        'payment_amount',
        'success_hook',
        'failure_hook',
        'payer_id',
        'receiver_id',
        'currency_code',
        'payment_method',
        'additional_data',
        'payer_information',
        'receiver_information',
        'external_redirect_link',
        'attribute',
        'attribute_id',
        'payment_platform',
        'payment_status',
        'transaction_reference',
        'is_paid'
    ];
    
    protected $casts = [
        'payment_amount' => 'decimal:2',
        'is_paid' => 'boolean',
        'additional_data' => 'array',
        'payer_information' => 'array',
        'receiver_information' => 'array',
    ];
}
