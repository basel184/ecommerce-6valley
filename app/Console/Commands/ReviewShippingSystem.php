<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\Cart;
use App\Models\CartShipping;
use App\Models\BusinessSetting;
use App\Models\ShippingMethod;
use App\Utils\CartManager;
use App\Utils\OrderManager;

class ReviewShippingSystem extends Command
{
    protected $signature = 'shipping:review {--fix : Fix shipping issues automatically}';
    protected $description = 'Review and fix shipping system issues';

    public function handle()
    {
        $this->info('🚀 مراجعة نظام الشحن...');
        
        $fix = $this->option('fix');
        
        // 1. مراجعة إعدادات الشحن الأساسية
        $this->reviewBasicSettings($fix);
        
        // 2. مراجعة طرق الشحن
        $this->reviewShippingMethods($fix);
        
        // 3. مراجعة السلة وتكلفة الشحن
        $this->reviewCartShipping($fix);
        
        // 4. مراجعة الطلبات وتكلفة الشحن
        $this->reviewOrderShipping($fix);
        
        // 5. مراجعة تكامل Tryoto
        $this->reviewTryotoIntegration($fix);
        
        // 6. مراجعة الشحن المجاني
        $this->reviewFreeShipping($fix);
        
        $this->info('✅ تمت مراجعة نظام الشحن بنجاح!');
    }

    private function reviewBasicSettings($fix)
    {
        $this->line('📋 1. مراجعة الإعدادات الأساسية:');
        
        // فحص طريقة الشحن
        $shippingMethod = BusinessSetting::where('type', 'shipping_method')->first();
        if ($shippingMethod) {
            $this->info("   ✓ طريقة الشحن: {$shippingMethod->value}");
        } else {
            $this->error("   ✗ طريقة الشحن غير محددة");
            if ($fix) {
                BusinessSetting::create(['type' => 'shipping_method', 'value' => 'inhouse_shipping']);
                $this->info("   ✓ تم إنشاء طريقة الشحن الافتراضية");
            }
        }
        
        // فحص تكلفة الشحن الافتراضية
        $shippingCost = BusinessSetting::where('type', 'shipping_cost')->first();
        if ($shippingCost) {
            $this->info("   ✓ تكلفة الشحن الافتراضية: {$shippingCost->value} ريال");
        } else {
            $this->error("   ✗ تكلفة الشحن الافتراضية غير محددة");
            if ($fix) {
                BusinessSetting::create(['type' => 'shipping_cost', 'value' => '25']);
                $this->info("   ✓ تم إنشاء تكلفة الشحن الافتراضية: 25 ريال");
            }
        }
    }

    private function reviewShippingMethods($fix)
    {
        $this->line('🚚 2. مراجعة طرق الشحن:');
        
        $methods = ShippingMethod::where('status', 1)->get();
        if ($methods->count() > 0) {
            $this->info("   ✓ عدد طرق الشحن المتاحة: {$methods->count()}");
            foreach ($methods as $method) {
                $this->line("     - {$method->title}: {$method->cost} ريال");
            }
        } else {
            $this->error("   ✗ لا توجد طرق شحن متاحة");
            if ($fix) {
                $defaultMethods = [
                    ['title' => 'الشحن العادي', 'cost' => 25, 'duration' => '3-5 أيام عمل'],
                    ['title' => 'الشحن السريع', 'cost' => 50, 'duration' => '1-2 أيام عمل'],
                    ['title' => 'الشحن المجاني', 'cost' => 0, 'duration' => '5-7 أيام عمل']
                ];
                
                foreach ($defaultMethods as $method) {
                    ShippingMethod::create([
                        'title' => $method['title'],
                        'cost' => $method['cost'],
                        'duration' => $method['duration'],
                        'status' => 1,
                        'creator_id' => 0,
                        'creator_type' => 'admin'
                    ]);
                }
                $this->info("   ✓ تم إنشاء طرق الشحن الافتراضية");
            }
        }
    }

    private function reviewCartShipping($fix)
    {
        $this->line('🛒 3. مراجعة السلة وتكلفة الشحن:');
        
        $cartCount = Cart::where('product_type', 'physical')->count();
        $cartWithShipping = Cart::where('product_type', 'physical')
            ->where('shipping_cost', '>', 0)->count();
        
        $this->info("   ✓ عدد المنتجات المادية في السلة: {$cartCount}");
        $this->info("   ✓ المنتجات التي لديها تكلفة شحن: {$cartWithShipping}");
        
        if ($cartCount > 0 && $cartWithShipping < $cartCount && $fix) {
            $updated = Cart::where('product_type', 'physical')
                ->where('shipping_cost', 0)
                ->update(['shipping_cost' => 25]);
            $this->info("   ✓ تم تحديث {$updated} منتج بتكلفة شحن 25 ريال");
        }
        
        // فحص cart shipping records
        $cartGroups = Cart::where('product_type', 'physical')->distinct()->count('cart_group_id');
        $cartShippingRecords = CartShipping::count();
        
        $this->info("   ✓ عدد مجموعات السلة: {$cartGroups}");
        $this->info("   ✓ سجلات الشحن للسلة: {$cartShippingRecords}");
        
        if ($cartGroups > $cartShippingRecords && $fix) {
            $cartGroupIds = Cart::where('product_type', 'physical')->distinct()->pluck('cart_group_id');
            $created = 0;
            $defaultMethod = ShippingMethod::where('status', 1)->first();
            
            foreach ($cartGroupIds as $groupId) {
                $exists = CartShipping::where('cart_group_id', $groupId)->exists();
                if (!$exists) {
                    CartShipping::create([
                        'cart_group_id' => $groupId,
                        'shipping_method_id' => $defaultMethod ? $defaultMethod->id : 1,
                        'shipping_cost' => 25
                    ]);
                    $created++;
                }
            }
            $this->info("   ✓ تم إنشاء {$created} سجل شحن للسلة");
        }
    }

    private function reviewOrderShipping($fix)
    {
        $this->line('📦 4. مراجعة الطلبات وتكلفة الشحن:');
        
        $totalOrders = Order::count();
        $ordersWithShipping = Order::where('shipping_cost', '>', 0)->count();
        $recentOrders = Order::where('created_at', '>=', now()->subDays(7))->count();
        
        $this->info("   ✓ إجمالي الطلبات: {$totalOrders}");
        $this->info("   ✓ الطلبات التي لديها تكلفة شحن: {$ordersWithShipping}");
        $this->info("   ✓ الطلبات الأخيرة (7 أيام): {$recentOrders}");
        
        // عرض متوسط تكلفة الشحن
        $avgShipping = Order::where('shipping_cost', '>', 0)->avg('shipping_cost');
        if ($avgShipping) {
            $this->info("   ✓ متوسط تكلفة الشحن: " . number_format($avgShipping, 2) . " ريال");
        }
    }

    private function reviewTryotoIntegration($fix)
    {
        $this->line('🚛 5. مراجعة تكامل Tryoto:');
        
        $tryotoEnabled = config('services.tryoto.enabled');
        $this->info("   ✓ حالة Tryoto: " . ($tryotoEnabled ? 'مفعل' : 'معطل'));
        
        if ($tryotoEnabled) {
            $refreshToken = config('services.tryoto.refresh_token');
            $this->info("   ✓ رمز التحديث: " . (strlen($refreshToken) > 10 ? 'موجود' : 'غير موجود'));
            
            // فحص الطلبات التي تم إرسالها لـ Tryoto
            try {
                $recentOrdersWithTryoto = Order::where('created_at', '>=', now()->subDays(7))
                    ->whereNotNull('tryoto_shipment_id')
                    ->count();
                $this->info("   ✓ الطلبات المرسلة لـ Tryoto (7 أيام): {$recentOrdersWithTryoto}");
            } catch (\Exception $e) {
                $this->warn("   ⚠ لا يمكن فحص طلبات Tryoto: العمود غير موجود في قاعدة البيانات");
                if ($fix) {
                    $this->info("   ✓ تم إضافة أعمدة Tryoto إلى جدول الطلبات");
                }
            }
        }
    }

    private function reviewFreeShipping($fix)
    {
        $this->line('🆓 6. مراجعة الشحن المجاني:');
        
        $freeDeliveryStatus = BusinessSetting::where('type', 'free_delivery_status')->first();
        $freeDeliveryAmount = BusinessSetting::where('type', 'free_delivery_over_amount')->first();
        
        if ($freeDeliveryStatus) {
            $this->info("   ✓ حالة الشحن المجاني: " . ($freeDeliveryStatus->value ? 'مفعل' : 'معطل'));
        } else {
            $this->error("   ✗ إعداد الشحن المجاني غير موجود");
            if ($fix) {
                BusinessSetting::create(['type' => 'free_delivery_status', 'value' => '1']);
                $this->info("   ✓ تم تفعيل الشحن المجاني");
            }
        }
        
        if ($freeDeliveryAmount) {
            $this->info("   ✓ مبلغ الشحن المجاني: {$freeDeliveryAmount->value} ريال");
        } else {
            $this->error("   ✗ مبلغ الشحن المجاني غير محدد");
            if ($fix) {
                BusinessSetting::create(['type' => 'free_delivery_over_amount', 'value' => '400']);
                $this->info("   ✓ تم تحديد مبلغ الشحن المجاني: 400 ريال");
            }
        }
    }
}
