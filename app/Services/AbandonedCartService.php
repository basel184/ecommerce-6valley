<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\AbandonedCartReminder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AbandonedCartExport;

class AbandonedCartService
{
    /**
     * Mark cart items as abandoned
     */
    public function markAsAbandoned($cartGroupId, $customerId = null, $isGuest = false): void
    {
        $cartItems = Cart::where('cart_group_id', $cartGroupId)->get();
        
        foreach ($cartItems as $cartItem) {
            $cartItem->markAsAbandoned();
        }
    }

    /**
     * Send reminder to customer via WhatsApp (Twilio). Falls back to email if enabled and WA disabled.
     */
    public function sendReminder(Cart $abandonedCart, ?string $templateKey = null, ?string $contentSid = null): bool
    {
        // Prefer WhatsApp via Twilio per requirement
        $sent = $this->sendWhatsAppReminder($abandonedCart, $templateKey, $contentSid);

        if ($sent) {
            return true;
        }

        // Optional: fallback to email if WA disabled or failed
        return $this->sendEmailReminderFallback($abandonedCart);
    }

    protected function sendWhatsAppReminder(Cart $abandonedCart, ?string $templateKey = null, ?string $contentSid = null): bool
    {
        try {
            $customer = $abandonedCart->customer;
            if (!$customer || empty($customer->phone)) {
                return false;
            }

            // Gather cart group items
            $cartItems = Cart::abandoned()
                ->where('cart_group_id', $abandonedCart->cart_group_id)
                ->with(['productWithDetails'])
                ->get();

            $totalValue = $cartItems->sum(fn($item) => $item->price * $item->quantity);

            $storeName = config('app.name', 'متجرك');
            $cartLink = url('/cart?abandoned=' . urlencode($abandonedCart->cart_group_id)); // Assumption: cart page can handle restoration

            // Choose template based on number already sent (0=>first,1=>second,>=2=>discount) unless provided
            if (!$templateKey) {
                $templateKey = 'abandoned_cart_first';
                if ($abandonedCart->reminder_sent === 1) {
                    $templateKey = 'abandoned_cart_second';
                } elseif ($abandonedCart->reminder_sent >= 2) {
                    $templateKey = 'abandoned_cart_discount';
                }
            }

            // Pick a product image if any (first available)
            $imageUrl = null;
            $firstWithImage = $cartItems->first(function ($item) {
                return !empty($item->productWithDetails?->thumbnail);
            });
            if ($firstWithImage) {
                $thumb = ltrim($firstWithImage->productWithDetails->thumbnail, '/');
                $imageUrl = route('storage.public', ['path' => 'product/' . $thumb]);
            }

            $vars = [
                'first_name'    => trim($customer->f_name ?? '') ?: translate('Customer'),
                'customer_name' => trim(($customer->f_name ?? '') . ' ' . ($customer->l_name ?? '')) ?: translate('Customer'),
                'cart_value'    => \App\Utils\Helpers::currency_converter($totalValue),
                'cart_link'     => $cartLink,
                'store_name'    => $storeName,
                'image_url'     => $imageUrl,
            ];

            // Send via Twilio
            $twilio = new \App\Services\TwilioWhatsAppService();
            // If a specific Content Template SID was selected from UI, attach it to the config at runtime
            if ($contentSid) {
                $tpl = config("twilio.templates.$templateKey", []);
                $tpl['content_sid'] = $contentSid;
                config(["twilio.templates.$templateKey" => $tpl]);
            }

            $resp = $twilio->sendTemplate($customer->phone, $templateKey, $vars);

            // Log attempt
            AbandonedCartReminder::create([
                'cart_id' => $abandonedCart->id,
                'cart_group_id' => $abandonedCart->cart_group_id,
                'channel' => 'whatsapp',
                'provider' => 'twilio',
                'template_key' => $templateKey,
                'to_phone' => $customer->phone,
                'message_body' => $resp['success'] ? ($resp['body'] ?? null) : $this->buildPreviewMessage($templateKey, $vars),
                'status' => $resp['success'] ? 'sent' : 'failed',
                'provider_message_sid' => $resp['sid'] ?? null,
                'error' => $resp['success'] ? null : ($resp['message'] ?? 'Unknown error'),
                'sent_at' => $resp['success'] ? now() : null,
            ]);

            if ($resp['success']) {
                $abandonedCart->incrementReminder();

                Log::info('Abandoned cart WhatsApp reminder sent', [
                    'customer_id' => $customer->id ?? null,
                    'cart_group_id' => $abandonedCart->cart_group_id,
                    'reminder_count' => $abandonedCart->reminder_sent,
                    'sid' => $resp['sid'] ?? null,
                ]);
                return true;
            }

            Log::warning('Abandoned cart WhatsApp reminder failed', [
                'cart_id' => $abandonedCart->id,
                'error' => $resp['message'] ?? 'Unknown error',
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::error('Exception sending WhatsApp reminder', [
                'cart_id' => $abandonedCart->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    protected function sendEmailReminderFallback(Cart $abandonedCart): bool
    {
        try {
            if (!function_exists('config') || !config('mail.mailers.smtp.host')) {
                // mail not configured
                return false;
            }

            $customer = $abandonedCart->customer;
            if (!$customer || !$customer->email) {
                return false;
            }

            $cartItems = Cart::abandoned()->where('cart_group_id', $abandonedCart->cart_group_id)
                ->with(['product'])
                ->get();

            $totalValue = $cartItems->sum(fn($item) => $item->price * $item->quantity);

            $emailData = [
                'customer_name' => trim(($customer->f_name ?? '') . ' ' . ($customer->l_name ?? '')),
                'cart_items' => $cartItems,
                'total_value' => $totalValue,
                'cart_group_id' => $abandonedCart->cart_group_id,
                'abandoned_at' => optional($abandonedCart->abandoned_at)->format('Y-m-d H:i:s'),
            ];

            Mail::send('email-templates.abandoned-cart-reminder', $emailData, function ($message) use ($customer) {
                $message->to($customer->email, trim(($customer->f_name ?? '') . ' ' . ($customer->l_name ?? '')))
                    ->subject(translate('complete_your_purchase'));
            });

            $abandonedCart->incrementReminder();
            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to send email fallback for abandoned cart', [
                'error' => $e->getMessage(),
                'cart_id' => $abandonedCart->id,
            ]);
            return false;
        }
    }

    protected function buildPreviewMessage(string $templateKey, array $vars): string
    {
        $tpl = config("twilio.templates.$templateKey");
        $body = $tpl['body'] ?? '';
        foreach ($vars as $k => $v) {
            $body = str_replace('{' . $k . '}', (string) $v, $body);
        }
        return $body;
    }

    /**
     * Send SMS reminder to customer
     */
    public function sendSmsReminder(Cart $abandonedCart): bool
    {
        try {
            $customer = $abandonedCart->customer;
            
            if (!$customer || !$customer->phone) {
                return false;
            }

            $cartItems = Cart::abandoned()->where('cart_group_id', $abandonedCart->cart_group_id)
                ->with(['product'])
                ->get();

            $totalValue = $cartItems->sum(function ($item) {
                return $item->price * $item->quantity;
            });

            $message = translate('abandoned_cart_sms_reminder', [
                'customer_name' => $customer->f_name,
                'total_value' => $totalValue,
                'cart_group_id' => $abandonedCart->cart_group_id
            ]);

            // Send SMS using your SMS service
            // $this->sendSms($customer->phone, $message);

            // Update reminder count
            $abandonedCart->incrementReminder();

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send SMS reminder', [
                'error' => $e->getMessage(),
                'cart_id' => $abandonedCart->id
            ]);
            return false;
        }
    }

    /**
     * Restore abandoned cart to active cart
     */
    public function restoreCart($cartGroupId, $customerId): bool
    {
        try {
            DB::beginTransaction();

            $abandonedCartItems = Cart::abandoned()->where('cart_group_id', $cartGroupId)->get();

            foreach ($abandonedCartItems as $abandonedItem) {
                // Check if product still exists and is available
                if ($abandonedItem->product && $abandonedItem->product->status == 1) {
                    // Mark as active again
                    $abandonedItem->markAsActive();
                }
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to restore abandoned cart', [
                'error' => $e->getMessage(),
                'cart_group_id' => $cartGroupId
            ]);
            return false;
        }
    }

    /**
     * Get abandoned cart statistics
     */
    public function getStatistics(): array
    {
        $totalAbandonedCarts = Cart::abandoned()->count();
        $totalValue = Cart::abandoned()->sum(DB::raw('price * quantity'));
        $cartsWithReminders = Cart::abandoned()->where('reminder_sent', '>', 0)->count();
        $cartsWithoutReminders = Cart::abandoned()->where('reminder_sent', 0)->count();
        $recentCarts = Cart::abandoned()->where('abandoned_at', '>=', Carbon::now()->subDays(7))->count();
        $oldCarts = Cart::abandoned()->where('abandoned_at', '<', Carbon::now()->subDays(30))->count();

        return [
            'total' => $totalAbandonedCarts,
            'total_value' => $totalValue,
            'with_reminders' => $cartsWithReminders,
            'without_reminders' => $cartsWithoutReminders,
            'recent' => $recentCarts,
            'old' => $oldCarts,
        ];
    }

    /**
     * Export abandoned carts to Excel
     */
    public function exportToExcel($abandonedCarts)
    {
        return Excel::download(new AbandonedCartExport($abandonedCarts), 'abandoned-carts-' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Clean old abandoned carts (older than 90 days)
     */
    public function cleanOldCarts(): int
    {
        $deletedCount = Cart::abandoned()->where('abandoned_at', '<', Carbon::now()->subDays(90))->delete();
        
        Log::info('Cleaned old abandoned carts', ['deleted_count' => $deletedCount]);
        
        return $deletedCount;
    }

    /**
     * Get conversion rate (carts that were restored and completed)
     */
    public function getConversionRate($days = 30): float
    {
        $totalAbandoned = Cart::abandoned()->where('abandoned_at', '>=', Carbon::now()->subDays($days))->count();
        
        if ($totalAbandoned == 0) {
            return 0;
        }

        // This would need to be implemented based on your order tracking logic
        // For now, we'll return a placeholder
        return 0;
    }
} 