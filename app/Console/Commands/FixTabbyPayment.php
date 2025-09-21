<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PaymentRequest;
use App\Models\Order;
use App\Services\OrderNotificationService;
use App\Services\TabbyService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class FixTabbyPayment extends Command
{
    protected $signature = 'fix:tabby-payment {payment_id?} {--force}';
    protected $description = 'Fix missing Tabby payments and create orders';

    public function handle()
    {
        $paymentId = $this->argument('payment_id');
        $force = $this->option('force');

        if ($paymentId) {
            $this->fixSpecificPayment($paymentId, $force);
        } else {
            $this->fixAllMissingPayments($force);
        }

        return Command::SUCCESS;
    }

    private function fixSpecificPayment($paymentId, $force = false)
    {
        $this->info("🔧 إصلاح الدفع المحدد: {$paymentId}");

        $payment = PaymentRequest::where('id', $paymentId)->first();
        if (!$payment) {
            $this->error("❌ الدفع غير موجود: {$paymentId}");
            return;
        }

        $this->info("📋 تفاصيل الدفع:");
        $this->line("   Payment ID: {$payment->id}");
        $this->line("   Amount: {$payment->payment_amount}");
        $this->line("   Method: {$payment->payment_method}");
        $this->line("   Status: " . ($payment->payment_status ?? 'NULL'));
        $this->line("   Is Paid: " . ($payment->is_paid ? 'Yes' : 'No'));
        $this->line("   Order IDs: " . ($payment->order_ids ?? 'NULL'));

        // التحقق من حالة الدفع في Tabby
        if (!$force) {
            $this->info("\n🔍 التحقق من حالة الدفع في Tabby...");
            $tabbyService = app(TabbyService::class);
            
            // البحث عن session_id في السجلات
            $sessionId = $this->findSessionIdFromLogs($payment->id);
            
            if ($sessionId) {
                $this->line("   Session ID: {$sessionId}");
                $paymentStatus = $tabbyService->getPaymentStatus($sessionId);
                
                if ($paymentStatus['success']) {
                    $this->info("   ✅ الدفع ناجح في Tabby");
                    $this->line("   Status: " . ($paymentStatus['status'] ?? 'unknown'));
                } else {
                    $this->warn("   ⚠️ الدفع غير ناجح في Tabby");
                    if (!$this->confirm('هل تريد المتابعة رغم ذلك؟')) {
                        return;
                    }
                }
            } else {
                $this->warn("   ⚠️ لم يتم العثور على Session ID");
                if (!$this->confirm('هل تريد المتابعة رغم ذلك؟')) {
                    return;
                }
            }
        }

        // إصلاح الدفع
        $this->fixPayment($payment);
    }

    private function fixAllMissingPayments($force = false)
    {
        $this->info("🔧 البحث عن دفعات Tabby المفقودة...");

        $payments = PaymentRequest::where('payment_method', 'tabby')
            ->where(function($query) {
                $query->whereNull('payment_status')
                      ->orWhere('payment_status', '!=', 'success')
                      ->orWhere('is_paid', 0)
                      ->orWhereNull('order_ids');
            })
            ->where('created_at', '>=', now()->subDays(7))
            ->get();

        $this->info("📊 تم العثور على {$payments->count()} دفعة تحتاج إصلاح");

        if ($payments->isEmpty()) {
            $this->info("✅ لا توجد دفعات تحتاج إصلاح");
            return;
        }

        foreach ($payments as $payment) {
            $this->newLine();
            $this->info("🔧 معالجة الدفع: {$payment->id}");
            $this->fixPayment($payment);
        }
    }

    private function fixPayment($payment)
    {
        try {
            DB::beginTransaction();

            // 1. تحديث حالة الدفع
            $payment->payment_status = 'success';
            $payment->is_paid = 1;
            $payment->save();

            $this->info("✅ تم تحديث حالة الدفع");

            // 2. إنشاء الطلب إذا لم يكن موجوداً
            if (empty($payment->order_ids)) {
                $this->info("📦 إنشاء الطلب...");
                
                // التحقق من وجود منتجات في السلة
                $additionalData = json_decode($payment->additional_data, true);
                $customerId = $additionalData['customer_id'] ?? null;
                $isGuest = $additionalData['is_guest'] ?? 0;
                
                $cartItems = \App\Models\Cart::where('customer_id', $customerId)
                    ->where('is_guest', $isGuest)
                    ->where('is_checked', 1)
                    ->get();
                
                if ($cartItems->isEmpty()) {
                    $this->warn("⚠️ السلة فارغة، محاولة إعادة إنشاء الطلب من البيانات المتوفرة...");
                    
                    // محاولة إعادة إنشاء الطلب من البيانات المتوفرة
                    $orderId = $this->recreateOrderFromPaymentData($payment);
                    
                    if ($orderId) {
                        $payment->order_ids = json_encode([$orderId]);
                        $payment->save();
                        $this->info("✅ تم إعادة إنشاء الطلب: {$orderId}");
                    } else {
                        $this->error("❌ فشل في إعادة إنشاء الطلب");
                        DB::rollBack();
                        return;
                    }
                } else {
                    // استدعاء دالة النجاح لإنشاء الطلب
                    if (function_exists($payment->success_hook)) {
                        call_user_func($payment->success_hook, $payment);
                        
                        // إعادة تحميل البيانات للحصول على order_ids المحدثة
                        $payment->refresh();
                        
                        if (!empty($payment->order_ids)) {
                            $this->info("✅ تم إنشاء الطلب: {$payment->order_ids}");
                        } else {
                            $this->error("❌ فشل في إنشاء الطلب");
                            DB::rollBack();
                            return;
                        }
                    } else {
                        $this->error("❌ دالة النجاح غير موجودة: {$payment->success_hook}");
                        DB::rollBack();
                        return;
                    }
                }
            }

            // 3. إرسال الإشعارات
            $this->info("📱 إرسال الإشعارات...");
            
            $orderIds = json_decode($payment->order_ids, true);
            if (!is_array($orderIds)) {
                $orderIds = explode(',', $payment->order_ids);
            }

            foreach ($orderIds as $orderId) {
                $orderId = trim($orderId);
                $order = Order::find($orderId);
                
                if ($order) {
                    // إطلاق Event لإرسال الإشعارات وإنشاء الشحنة
                    event(new \App\Events\OrderPlacedEvent((object)['order' => $order]));
                    
                    // إرسال إشعارات SMS
                    $notificationService = app(OrderNotificationService::class);
                    $notificationService->sendOrderNotifications($orderId, [
                        'transaction_reference' => $payment->transaction_reference ?? 'MANUAL_FIX',
                        'payment_amount' => $payment->payment_amount,
                        'payment_method' => 'Tabby'
                    ]);
                    
                    $this->info("✅ تم إرسال إشعارات للطلب: {$orderId}");
                } else {
                    $this->warn("⚠️ الطلب غير موجود: {$orderId}");
                }
            }

            DB::commit();
            $this->info("🎉 تم إصلاح الدفع بنجاح!");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ خطأ في إصلاح الدفع: " . $e->getMessage());
            Log::error('FixTabbyPayment Error', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * إعادة إنشاء الطلب من بيانات الدفع عندما تكون السلة فارغة
     */
    private function recreateOrderFromPaymentData($payment)
    {
        try {
            $additionalData = json_decode($payment->additional_data, true);
            $customerId = $additionalData['customer_id'] ?? null;
            $isGuest = $additionalData['is_guest'] ?? 0;
            
            if (!$customerId) {
                $this->error("❌ لا يمكن العثور على معرف العميل");
                return null;
            }

            // البحث عن آخر منتج تم شراؤه من هذا العميل
            $lastOrder = Order::where('customer_id', $customerId)
                ->where('order_amount', $payment->payment_amount)
                ->latest()
                ->first();

            if (!$lastOrder) {
                $this->warn("⚠️ لا توجد طلبات سابقة، إنشاء طلب بسيط...");
                return $this->createSimpleOrder($payment, $customerId);
            }

            $this->info("📋 إعادة إنشاء الطلب من الطلب السابق: {$lastOrder->id}");

            // إنشاء طلب جديد بناءً على الطلب السابق
            $newOrder = $lastOrder->replicate();
            $newOrder->id = \App\Utils\OrderManager::generateUniqueOrderID();
            $newOrder->order_group_id = \App\Utils\OrderManager::generateUniqueOrderID();
            $newOrder->payment_status = 'paid';
            $newOrder->order_status = 'confirmed';
            $newOrder->transaction_ref = $payment->transaction_reference ?? 'MANUAL_FIX_' . time();
            $newOrder->created_at = now();
            $newOrder->updated_at = now();
            $newOrder->save();

            // نسخ تفاصيل الطلب
            $orderDetails = $lastOrder->orderDetails;
            foreach ($orderDetails as $detail) {
                $newDetail = $detail->replicate();
                $newDetail->order_id = $newOrder->id;
                $newDetail->save();
            }

            $this->info("✅ تم إنشاء الطلب الجديد: {$newOrder->id}");
            return $newOrder->id;

        } catch (\Exception $e) {
            $this->error("❌ خطأ في إعادة إنشاء الطلب: " . $e->getMessage());
            Log::error('RecreateOrderFromPaymentData Error', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * إنشاء طلب بسيط عندما لا توجد طلبات سابقة
     */
    private function createSimpleOrder($payment, $customerId)
    {
        try {
            $additionalData = json_decode($payment->additional_data, true);
            
            // إنشاء طلب بسيط
            $order = new Order();
            $order->id = \App\Utils\OrderManager::generateUniqueOrderID();
            $order->customer_id = $customerId;
            $order->order_amount = $payment->payment_amount;
            $order->paid_amount = $payment->payment_amount;
            $order->discount_amount = $additionalData['coupon_discount'] ?? 0;
            $order->coupon_code = $additionalData['coupon_code'] ?? null;
            $order->order_status = 'confirmed';
            $order->payment_status = 'paid';
            $order->payment_method = $payment->payment_method;
            $order->transaction_ref = $payment->transaction_reference ?? 'MANUAL_FIX_' . time();
            $order->order_note = $additionalData['order_note'] ?? null;
            $order->order_type = 'default_type';
            $order->shipping_address = $additionalData['address_id'] ?? null;
            $order->billing_address = $additionalData['billing_address_id'] ?? null;
            $order->shipping_cost = 0;
            $order->extra_discount = 0;
            $order->is_guest = $additionalData['is_guest'] ?? 0;
            $order->customer_type = 'customer';
            $order->created_at = now();
            $order->updated_at = now();
            $order->save();

            // إنشاء تفاصيل الطلب البسيط
            $orderDetail = new \App\Models\OrderDetail();
            $orderDetail->order_id = $order->id;
            $orderDetail->product_id = 1; // منتج افتراضي
            $orderDetail->product_details = json_encode([
                'id' => 1,
                'name' => 'منتج تم إصلاحه يدوياً',
                'slug' => 'manual-fix-product'
            ]);
            $orderDetail->qty = 1;
            $orderDetail->price = $payment->payment_amount;
            $orderDetail->tax = 0;
            $orderDetail->discount = 0;
            $orderDetail->discount_type = 'amount';
            $orderDetail->created_at = now();
            $orderDetail->updated_at = now();
            $orderDetail->save();

            $this->info("✅ تم إنشاء طلب بسيط: {$order->id}");
            return $order->id;

        } catch (\Exception $e) {
            $this->error("❌ خطأ في إنشاء الطلب البسيط: " . $e->getMessage());
            Log::error('CreateSimpleOrder Error', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    private function findSessionIdFromLogs($paymentId)
    {
        // البحث في السجلات عن session_id لهذا الدفع
        $logFile = storage_path('logs/laravel.log');
        if (!file_exists($logFile)) {
            return null;
        }

        $content = file_get_contents($logFile);
        $lines = explode("\n", $content);
        
        foreach ($lines as $line) {
            if (strpos($line, $paymentId) !== false && strpos($line, 'Tabby Payment Redirect') !== false) {
                // استخراج session_id من السجل
                if (preg_match('/"session_id":"([^"]+)"/', $line, $matches)) {
                    return $matches[1];
                }
            }
        }
        
        return null;
    }
} 