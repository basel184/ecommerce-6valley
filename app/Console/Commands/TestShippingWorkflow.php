<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\Cart;
use App\Models\CartShipping;
use App\Models\BusinessSetting;
use App\Models\ShippingMethod;
use App\Services\TryotoShippingService;

class TestShippingWorkflow extends Command
{
    protected $signature = 'shipping:test-workflow {order_id?}';
    protected $description = 'Test complete shipping workflow from cart to delivery';

    public function handle()
    {
        $this->info('🧪 اختبار دورة الشحن الكاملة...');
        
        $orderId = $this->argument('order_id');
        
        if ($orderId) {
            $this->testOrderShipping($orderId);
        } else {
            $this->testCartShipping();
        }
        
        $this->testTryotoIntegration();
        $this->showShippingReport();
    }

    private function testCartShipping()
    {
        $this->line('🛒 اختبار تكلفة الشحن في السلة:');
        
        $cart = Cart::where('product_type', 'physical')->first();
        if (!$cart) {
            $this->error('   ✗ لا توجد منتجات في السلة للاختبار');
            return;
        }
        
        // Test shipping cost calculation
        $cartTotal = Cart::where('cart_group_id', $cart->cart_group_id)
            ->sum(\DB::raw('price * quantity'));
        
        $shippingCost = \App\Utils\CartManager::get_shipping_cost(
            groupId: $cart->cart_group_id, 
            type: 'checked'
        );
        
        $this->info("   ✓ مجموعة السلة: {$cart->cart_group_id}");
        $this->info("   ✓ إجمالي المنتجات: {$cartTotal} ريال");
        $this->info("   ✓ تكلفة الشحن: {$shippingCost} ريال");
        
        // Test free shipping eligibility
        $freeDeliveryStatus = \App\Utils\OrderManager::getFreeDeliveryOrderAmountArray($cart->cart_group_id);
        if ($freeDeliveryStatus['status']) {
            if ($freeDeliveryStatus['amount_need'] <= 0) {
                $this->info("   ✅ مؤهل للشحن المجاني!");
            } else {
                $this->info("   ⏳ يحتاج {$freeDeliveryStatus['amount_need']} ريال إضافية للشحن المجاني");
            }
        }
    }

    private function testOrderShipping($orderId)
    {
        $this->line("📦 اختبار شحن الطلب رقم: {$orderId}");
        
        $order = Order::find($orderId);
        if (!$order) {
            $this->error('   ✗ الطلب غير موجود');
            return;
        }
        
        $this->info("   ✓ حالة الطلب: {$order->order_status}");
        $this->info("   ✓ تكلفة الشحن: {$order->shipping_cost} ريال");
        $this->info("   ✓ طريقة الدفع: {$order->payment_method}");
        $this->info("   ✓ العنوان: " . ($order->shippingAddress ? $order->shippingAddress->city : 'غير محدد'));
        
        // Check Tryoto integration
        if ($order->tryoto_shipment_id) {
            $this->info("   ✅ تم إرسال الطلب لـ Tryoto - معرف الشحنة: {$order->tryoto_shipment_id}");
        } else {
            $this->warn("   ⚠ لم يتم إرسال الطلب لـ Tryoto بعد");
        }
    }

    private function testTryotoIntegration()
    {
        $this->line('🚛 اختبار تكامل Tryoto:');
        
        if (!config('services.tryoto.enabled')) {
            $this->warn('   ⚠ Tryoto معطل في الإعدادات');
            return;
        }
        
        try {
            $tryotoService = new TryotoShippingService();
            
            // Get the latest order for testing
            $latestOrder = Order::latest()->first();
            if ($latestOrder) {
                $this->info("   ✓ جاري اختبار إرسال الطلب رقم {$latestOrder->id} لـ Tryoto...");
                
                $result = $tryotoService->createOrder($latestOrder->id);
                if ($result) {
                    $this->info('   ✅ نجح إرسال الطلب لـ Tryoto');
                } else {
                    $this->error('   ✗ فشل إرسال الطلب لـ Tryoto');
                }
            } else {
                $this->warn('   ⚠ لا توجد طلبات للاختبار');
            }
        } catch (\Exception $e) {
            $this->error("   ✗ خطأ في Tryoto: {$e->getMessage()}");
        }
    }

    private function showShippingReport()
    {
        $this->line('📊 تقرير نظام الشحن:');
        $this->line('');
        
        // Current settings
        $this->table(['الإعداد', 'القيمة'], [
            ['طريقة الشحن', getWebConfig('shipping_method') ?: 'غير محدد'],
            ['تكلفة الشحن الافتراضية', getWebConfig('shipping_cost') . ' ريال'],
            ['الشحن المجاني مفعل', getWebConfig('free_delivery_status') ? 'نعم' : 'لا'],
            ['مبلغ الشحن المجاني', getWebConfig('free_delivery_over_amount') . ' ريال'],
            ['تكامل Tryoto', config('services.tryoto.enabled') ? 'مفعل' : 'معطل'],
        ]);
        
        $this->line('');
        
        // Statistics
        $totalOrders = Order::count();
        $ordersWithShipping = Order::where('shipping_cost', '>', 0)->count();
        $avgShipping = Order::where('shipping_cost', '>', 0)->avg('shipping_cost');
        $cartItems = Cart::where('product_type', 'physical')->count();
        
        $this->table(['الإحصائية', 'القيمة'], [
            ['إجمالي الطلبات', $totalOrders],
            ['الطلبات مع شحن', $ordersWithShipping],
            ['متوسط تكلفة الشحن', number_format($avgShipping, 2) . ' ريال'],
            ['منتجات في السلة', $cartItems],
            ['طرق الشحن المتاحة', ShippingMethod::where('status', 1)->count()],
        ]);
        
        $this->info('✅ تم إنتهاء اختبار نظام الشحن!');
    }
}
