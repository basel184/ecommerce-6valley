<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReverseTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'customer_id', 'admin_id', 'amount', 'reason', 'status', 'payment_method',
        'transaction_id', 'notes', 'approved_by', 'approved_at', 'rejected_by', 'rejected_at',
        'rejection_reason', 'processed_by', 'processed_at', 'bank_name', 'account_number',
        'account_holder_name', 'iban', 'swift_code', 'transfer_date', 'reference_number',
        // حقول جديدة لبوابات الدفع
        'payment_gateway', 'gateway_transaction_id', 'gateway_response', 'gateway_status',
        'gateway_error_message', 'gateway_callback_data', 'gateway_webhook_data',
        'original_payment_method', 'original_transaction_id', 'refund_reason_code'
    ];

    protected $casts = [
        'order_id' => 'integer',
        'customer_id' => 'integer',
        'admin_id' => 'integer',
        'amount' => 'float',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'processed_at' => 'datetime',
        'transfer_date' => 'date',
        'gateway_response' => 'array',
        'gateway_callback_data' => 'array',
        'gateway_webhook_data' => 'array'
    ];

    // العلاقات
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(ReverseTransferStatus::class);
    }

    // النطاقات (Scopes)
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    // Accessors
    public function getStatusBadgeAttribute()
    {
        return match($this->status) {
            'pending' => 'badge-warning',
            'approved' => 'badge-primary',
            'rejected' => 'badge-danger',
            'processed' => 'badge-info',
            'completed' => 'badge-success',
            default => 'badge-secondary'
        };
    }

    public function getStatusTextAttribute()
    {
        return match($this->status) {
            'pending' => translate('pending'),
            'approved' => translate('approved'),
            'rejected' => translate('rejected'),
            'processed' => translate('processed'),
            'completed' => translate('completed'),
            default => $this->status
        };
    }

    public function getGatewayDisplayNameAttribute()
    {
        if ($this->isGatewayPayment()) {
            return match($this->payment_gateway) {
                'myfatoorah' => 'MyFatoorah',
                'tabby' => 'Tabby',
                'tamara' => 'Tamara',
                default => $this->payment_gateway
            };
        }
        
        return translate($this->payment_method);
    }

    public function getOriginalPaymentDisplayNameAttribute()
    {
        if ($this->original_payment_method) {
            return match($this->original_payment_method) {
                'cash_on_delivery' => translate('cash_on_delivery'),
                'card' => translate('card'),
                'wallet' => translate('wallet'),
                'bank_transfer' => translate('bank_transfer'),
                'cash' => translate('cash'),
                'check' => translate('check'),
                default => translate($this->original_payment_method)
            };
        }
        
        return 'N/A';
    }

    public function getCustomerNameAttribute()
    {
        if ($this->customer) {
            return $this->customer->f_name . ' ' . $this->customer->l_name;
        }
        return 'N/A';
    }

    public function getCustomerPhoneAttribute()
    {
        return $this->customer?->phone ?? 'N/A';
    }

    public function getCustomerEmailAttribute()
    {
        return $this->customer?->email ?? 'N/A';
    }

    public function getAdminNameAttribute()
    {
        if ($this->admin) {
            return $this->admin->f_name . ' ' . $this->admin->l_name;
        }
        return 'N/A';
    }

    public function getApprovedByNameAttribute()
    {
        if ($this->approvedBy) {
            return $this->approvedBy->f_name . ' ' . $this->approvedBy->l_name;
        }
        return 'N/A';
    }

    public function getRejectedByNameAttribute()
    {
        if ($this->rejectedBy) {
            return $this->rejectedBy->f_name . ' ' . $this->rejectedBy->l_name;
        }
        return 'N/A';
    }

    public function getProcessedByNameAttribute()
    {
        if ($this->processedBy) {
            return $this->processedBy->f_name . ' ' . $this->processedBy->l_name;
        }
        return 'N/A';
    }

    // Methods
    public function isGatewayPayment(): bool
    {
        return in_array($this->payment_gateway, ['myfatoorah', 'tabby', 'tamara']);
    }

    public function canBeDeleted(): bool
    {
        return $this->status === 'pending';
    }

    public function canBeApproved(): bool
    {
        return in_array($this->status, ['pending', 'rejected']);
    }

    public function canBeRejected(): bool
    {
        return in_array($this->status, ['pending', 'approved']);
    }

    public function canBeProcessed(): bool
    {
        return $this->status === 'approved';
    }

    public function canBeCompleted(): bool
    {
        return $this->status === 'processed';
    }
}