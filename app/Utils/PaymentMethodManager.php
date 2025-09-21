<?php

namespace App\Utils;

use App\Services\TamaraService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PaymentMethodManager
{
    /**
     * Get available payment methods based on service status
     */
    public static function getAvailablePaymentMethods($orderAmount = null)
    {
        $methods = [];
        
        // MyFatoorah - always available and working well
        $methods['fatoorah'] = [
            'name' => 'MyFatoorah',
            'display_name' => 'الدفع الإلكتروني',
            'available' => true,
            'status' => 'active',
            'icon' => 'myfatoorah-icon',
            'description' => 'دفع آمن بالفيزا والماستركارد',
            'priority' => 1, // Highest priority since it's working
            'supports_amount' => true,
            'min_amount' => 1,
            'max_amount' => 50000
        ];
        
        // Tamara - conditional availability
        $tamaraStatus = self::checkTamaraAvailability($orderAmount);
        
        $methods['tamara'] = [
            'name' => 'Tamara',
            'display_name' => 'ادفع لاحقاً مع تمارا',
            'available' => $tamaraStatus['available'],
            'status' => $tamaraStatus['status'],
            'icon' => 'tamara-icon',
            'description' => $tamaraStatus['description'],
            'priority' => $tamaraStatus['available'] ? 2 : 10, // Lower priority if issues
            'supports_amount' => true,
            'min_amount' => 100,
            'max_amount' => 10000,
            'error_message' => $tamaraStatus['error_message'] ?? null,
            'fallback_message' => $tamaraStatus['fallback_message'] ?? null
        ];
        
        // Add other payment methods if available
        if (config('app.enable_cash_on_delivery', false)) {
            $methods['cod'] = [
                'name' => 'Cash on Delivery',
                'display_name' => 'الدفع عند الاستلام',
                'available' => true,
                'status' => 'active',
                'icon' => 'cod-icon',
                'description' => 'ادفع نقداً عند استلام الطلب',
                'priority' => 3,
                'supports_amount' => true,
                'min_amount' => 1,
                'max_amount' => 1000
            ];
        }
        
        // Sort by priority (lower number = higher priority)
        uasort($methods, function($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });
        
        return $methods;
    }
    
    /**
     * Check Tamara service availability
     */
    private static function checkTamaraAvailability($orderAmount = null)
    {
        try {
            // Check circuit breaker status
            $failureCount = Cache::get('tamara_failure_count', 0);
            $lastFailure = Cache::get('tamara_last_failure');
            $isBlocked = $failureCount >= 3 && $lastFailure && now()->diffInMinutes($lastFailure) < 10;
            
            if ($isBlocked) {
                $minutesLeft = 10 - now()->diffInMinutes($lastFailure);
                
                return [
                    'available' => false,
                    'status' => 'temporarily_unavailable',
                    'description' => 'خدمة تمارا غير متاحة مؤقتاً',
                    'error_message' => "سيتم إعادة تفعيل الخدمة خلال {$minutesLeft} دقيقة",
                    'fallback_message' => 'يمكنك استخدام MyFatoorah للدفع الآمن'
                ];
            }
            
            // Check if amount is supported
            if ($orderAmount && ($orderAmount < 100 || $orderAmount > 10000)) {
                return [
                    'available' => false,
                    'status' => 'amount_not_supported',
                    'description' => 'تمارا غير متاح لهذا المبلغ',
                    'error_message' => 'تمارا متاح للمبالغ بين 100-10,000 ريال',
                    'fallback_message' => 'استخدم طريقة دفع أخرى'
                ];
            }
            
            // If service is available
            return [
                'available' => true,
                'status' => 'active',
                'description' => 'ادفع لاحقاً مع تمارا - بدون فوائد'
            ];
            
        } catch (\Exception $e) {
            Log::error('Error checking Tamara availability', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'available' => false,
                'status' => 'error',
                'description' => 'خدمة تمارا غير متاحة حالياً',
                'error_message' => 'حدث خطأ في فحص الخدمة',
                'fallback_message' => 'استخدم MyFatoorah للدفع الآمن'
            ];
        }
    }
    
    /**
     * Get recommended payment method based on current status
     */
    public static function getRecommendedPaymentMethod($orderAmount = null)
    {
        $methods = self::getAvailablePaymentMethods($orderAmount);
        
        // Return first available method (highest priority)
        foreach ($methods as $key => $method) {
            if ($method['available']) {
                return [
                    'method' => $key,
                    'data' => $method
                ];
            }
        }
        
        return null;
    }
    
    /**
     * Log payment method selection for analytics
     */
    public static function logPaymentMethodSelection($method, $orderAmount, $userId = null)
    {
        Log::info('Payment method selected', [
            'method' => $method,
            'order_amount' => $orderAmount,
            'user_id' => $userId,
            'timestamp' => now(),
            'tamara_available' => TamaraService::isServiceAvailable()
        ]);
    }
}
