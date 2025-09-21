<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReverseTransferStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'reverse_transfer_id',
        'status',
        'changed_by',
        'changed_by_id',
        'notes',
        'previous_status'
    ];

    protected $casts = [
        'reverse_transfer_id' => 'integer',
        'changed_by_id' => 'integer'
    ];

    public function reverseTransfer(): BelongsTo
    {
        return $this->belongsTo(ReverseTransfer::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_id');
    }

    /**
     * الحصول على اسم من قام بتغيير الحالة
     */
    public function getChangedByNameAttribute()
    {
        if ($this->changedBy) {
            return $this->changedBy->f_name . ' ' . $this->changedBy->l_name;
        }
        return 'System';
    }

    /**
     * الحصول على نص الحالة المترجم
     */
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

    /**
     * الحصول على لون شارة الحالة
     */
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
}