<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\AbandonedCartReminder;

/**
 * Class CartItem
 *
 * @property int $id Primary
 * @property int $customer_id
 * @property string $cart_group_id
 * @property int $product_id
 * @property string $product_type
 * @property string $digital_product_type
 * @property string $color
 * @property array $choices
 * @property array $variations
 * @property array $variant
 * @property int $quantity
 * @property float $price
 * @property float $tax
 * @property integer $is_checked
 * @property float $discount
 * @property string $tax_model
 * @property string $slug
 * @property string $name
 * @property string $thumbnail
 * @property int $seller_id
 * @property string $seller_is
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string $shop_info
 * @property float $shipping_cost
 * @property string $shipping_type
 * @property int $is_guest
 *
 * @package App\Models
 */
class Cart extends Model
{

    protected $casts = [
        'id' => 'integer',
        'customer_id' => 'integer',
        'product_id' => 'integer',
//        'choices' => 'array',
//        'variations' => 'array',
//        'variant' => 'array',
        'quantity' => 'integer',
        'price' => 'float',
        'tax' => 'float',
        'is_checked' => 'integer',
        'discount' => 'float',
        'seller_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'shipping_cost' => 'float',
        'is_guest' => 'integer',
        'is_abandoned' => 'boolean',
        'abandoned_at' => 'datetime',
        'reminder_sent' => 'integer',
        'last_reminder_sent_at' => 'datetime',
    ];

    protected $fillable = [
        'customer_id',
        'cart_group_id',
        'product_id',
        'product_type',
        'digital_product_type',
        'color',
        'choices',
        'variations',
        'variant',
        'quantity',
        'price',
        'tax',
        'discount',
        'tax_model',
        'is_checked',
        'slug',
        'name',
        'thumbnail',
        'seller_id',
        'seller_is',
        'shop_info',
        'shipping_cost',
        'shipping_type',
        'is_guest',
        'is_abandoned',
        'abandoned_at',
        'reminder_sent',
        'last_reminder_sent_at',
    ];

    public function cartShipping(): HasOne
    {
        return $this->hasOne(CartShipping::class,'cart_group_id','cart_group_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->where('status', 1);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
    
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }
    
    // New relationships for abandoned cart display (including inactive products and deleted sellers)
    public function productWithDetails(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
    
    public function sellerWithDetails(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }
    
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'seller_id', 'seller_id');
    }

    public function allProducts(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Reminder logs related to this cart item (by id or group)
     */
    public function reminderLogs(): HasMany
    {
        return $this->hasMany(AbandonedCartReminder::class, 'cart_id', 'id');
    }

    /**
     * Get abandoned carts by customer
     */
    public static function getAbandonedByCustomer($customerId)
    {
        return self::where('customer_id', $customerId)
            ->where('is_abandoned', true)
            ->where('is_guest', 0)
            ->get();
    }

    /**
     * Get abandoned carts by guest
     */
    public static function getAbandonedByGuest($cartGroupId)
    {
        return self::where('cart_group_id', $cartGroupId)
            ->where('is_abandoned', true)
            ->where('is_guest', 1)
            ->get();
    }

    /**
     * Get abandoned carts that need reminder
     */
    public static function getAbandonedForReminder($hours = 24)
    {
        $maxReminders = (int) config('abandoned_cart.max_reminders', 3);
        return self::where('is_abandoned', true)
            ->where('abandoned_at', '<=', Carbon::now()->subHours($hours))
            ->where('reminder_sent', '<', $maxReminders)
            ->get();
    }

    /**
     * Get carts due for immediate reminder using system defaults.
     */
    public static function getDueForReminderDefault()
    {
        // If business later wants minutes-level cadence, wire it here.
        return self::getAbandonedForReminder(1); // due after 1 hour by default in UI uses
    }

    /**
     * Scope for abandoned carts
     */
    public function scopeAbandoned($query)
    {
        return $query->where('is_abandoned', true);
    }

    /**
     * Scope for active carts
     */
    public function scopeActive($query)
    {
        return $query->where('is_abandoned', false);
    }

    /**
     * Mark cart as abandoned
     */
    public function markAsAbandoned()
    {
        $this->update([
            'is_abandoned' => true,
            'abandoned_at' => Carbon::now(),
        ]);
    }

    /**
     * Mark cart as active
     */
    public function markAsActive()
    {
        $this->update([
            'is_abandoned' => false,
            'abandoned_at' => null,
            'reminder_sent' => 0,
            'last_reminder_sent_at' => null,
        ]);
    }

    /**
     * Increment reminder count
     */
    public function incrementReminder()
    {
        $this->update([
            'reminder_sent' => $this->reminder_sent + 1,
            'last_reminder_sent_at' => Carbon::now(),
        ]);
    }
}
