<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Services\TaqnyatSmsService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * خدمة إشعارات الطلبات عبر Taqnyat
 * تدعم إرسال SMS و WhatsApp عبر منصة تقنيات
 */
class OrderNotificationService
{
    protected $smsService;
    protected $adminPhone;

    public function __construct(TaqnyatSmsService $smsService)
    {
        $this->smsService = $smsService;
        $this->adminPhone = config('sms.taqnyat.admin_phone', '966548060989');
    }

    /**
     * إرسال إشعارات للطلب الجديد
     */
    public function sendOrderNotifications($orderId, $paymentData = null)
    {
        try {
            Log::info('Attempting to send order notifications', [
                'order_id' => $orderId,
                'payment_data' => $paymentData
            ]);
            
            // Try to find order directly first
            $order = Order::with(['customer', 'orderDetails.product'])->find($orderId);
            
            // If not found, try to find by ID in different format or check payment_requests table
            if (!$order) {
                Log::warning('Order not found with direct ID, trying alternative methods', ['order_id' => $orderId]);
                
                // Try to find order in payment_requests table if attribute_id was passed
                $paymentRequest = \DB::table('payment_requests')->where('attribute_id', $orderId)->first();
                if ($paymentRequest && !empty($paymentRequest->order_ids)) {
                    Log::info('Found payment_request for attribute_id', [
                        'attribute_id' => $orderId,
                        'order_ids' => $paymentRequest->order_ids
                    ]);
                    
                    // Parse order_ids (might be JSON or comma-separated)
                    $orderIds = json_decode($paymentRequest->order_ids, true);
                    if (!is_array($orderIds)) {
                        $orderIds = explode(',', $paymentRequest->order_ids);
                    }
                    
                    if (!empty($orderIds)) {
                        $realOrderId = trim($orderIds[0]);
                        $order = Order::with(['customer', 'orderDetails.product'])->find($realOrderId);
                        Log::info('Found order via payment_requests', [
                            'attribute_id' => $orderId,
                            'order_ids' => $paymentRequest->order_ids,
                            'real_order_id' => $realOrderId
                        ]);
                    }
                }
                
                // Try searching by other possible ID formats
                if (!$order) {
                    $order = Order::with(['customer', 'orderDetails.product'])
                        ->where('id', 'LIKE', "%{$orderId}%")
                        ->orWhere('order_group_id', $orderId)
                        ->first();
                    
                    if ($order) {
                        Log::info('Found order via alternative search', [
                            'search_id' => $orderId,
                            'found_order_id' => $order->id
                        ]);
                    }
                }
                
                // Try to find by customer_reference in payment_requests
                if (!$order && isset($paymentData['customer_reference'])) {
                    // البحث بـ transaction_id أو payer_information بدلاً من customer_reference
                    $paymentByRef = \DB::table('payment_requests')
                        ->where('transaction_id', $paymentData['customer_reference'])
                        ->orWhere('payer_information', 'like', '%' . $paymentData['customer_reference'] . '%')
                        ->first();
                    
                    if ($paymentByRef && !empty($paymentByRef->order_ids)) {
                        Log::info('Found payment via transaction_id/payer_information', [
                            'customer_reference' => $paymentData['customer_reference'],
                            'order_ids' => $paymentByRef->order_ids
                        ]);
                        
                        $orderIds = json_decode($paymentByRef->order_ids, true);
                        if (!is_array($orderIds)) {
                            $orderIds = explode(',', $paymentByRef->order_ids);
                        }
                        
                        if (!empty($orderIds)) {
                            $realOrderId = trim($orderIds[0]);
                            $order = Order::with(['customer', 'orderDetails.product'])->find($realOrderId);
                            Log::info('Found order via payment_requests customer_reference', [
                                'customer_reference' => $paymentData['customer_reference'],
                                'real_order_id' => $realOrderId
                            ]);
                        }
                    }
                }
            }
            
            if (!$order) {
                Log::error('Order not found after all search attempts', [
                    'order_id' => $orderId,
                    'payment_data' => $paymentData
                ]);
                return false;
            }
            
            Log::info('Order found, proceeding with notifications', [
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'order_status' => $order->order_status,
                'payment_status' => $order->payment_status
            ]);
            
            // إرسال إشعار للعميل
            $this->sendCustomerNotification($order);
            
            // إرسال إشعار للإدارة
            $this->sendAdminNotification($order, $paymentData);
            
            Log::info('Order notifications sent successfully', [
                'order_id' => $order->id
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Error sending order notifications', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * إرسال إشعار إلغاء الطلب (بدون SMS)
     */
    public function sendOrderCancellationNotification($orderId, $cancellationData = [])
    {
        try {
            Log::info('Attempting to send order cancellation notification (SMS disabled)', [
                'order_id' => $orderId,
                'cancellation_data' => $cancellationData
            ]);
            
            // البحث عن الطلب
            $order = Order::with(['customer', 'orderDetails.product'])->find($orderId);
            
            if (!$order) {
                Log::warning('Order not found for cancellation notification', ['order_id' => $orderId]);
                return false;
            }
            
            Log::info('Order cancellation notification - SMS disabled by request', [
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'reason' => $cancellationData['reason'] ?? 'غير محدد',
                'payment_method' => $cancellationData['payment_method'] ?? 'غير محدد'
            ]);
            
            // لا يتم إرسال أي SMS - فقط تسجيل في السجلات
            return true;
            
        } catch (\Exception $e) {
            Log::error('Error in order cancellation notification', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * تسجيل معلومات تشخيصية عند عدم العثور على الطلب
     */
    protected function logDiagnosticInfo($orderId, $paymentData)
    {
        try {
            Log::info('=== MISSING ORDER DIAGNOSTIC ===', ['order_id' => $orderId]);
            
            // البحث في payment_requests بـ attribute_id
            $paymentByAttribute = \DB::table('payment_requests')->where('attribute_id', $orderId)->first();
            if ($paymentByAttribute) {
                Log::warning('CRITICAL: Found payment_request but no corresponding order!', [
                    'attribute_id' => $orderId,
                    'payment_id' => $paymentByAttribute->id,
                    'order_ids' => $paymentByAttribute->order_ids ?? 'NULL',
                    'transaction_id' => $paymentByAttribute->transaction_id ?? 'NULL',
                    'payment_method' => $paymentByAttribute->payment_method ?? 'NULL',
                    'payment_status' => $paymentByAttribute->payment_status ?? 'NULL',
                    'is_paid' => $paymentByAttribute->is_paid ?? 'NULL',
                    'created_at' => $paymentByAttribute->created_at
                ]);
                
                // إذا كان order_ids فارغ، هذا يعني فشل في إنشاء الطلب
                if (empty($paymentByAttribute->order_ids) || $paymentByAttribute->order_ids === 'null') {
                    Log::critical('Payment completed but order creation FAILED!', [
                        'issue' => 'payment_without_order',
                        'attribute_id' => $orderId,
                        'transaction_id' => $paymentByAttribute->transaction_id,
                        'recommendation' => 'Check order creation logic in payment callback'
                    ]);
                    
                    // إرسال تنبيه فوري للإدارة
                    $this->sendEmergencyAlert($orderId, $paymentByAttribute);
                }
            }
            
            // آخر 5 طلبات في النظام
            $latestOrders = Order::latest()->limit(5)->pluck('id')->toArray();
            
            // آخر 5 payment_requests
            $latestPayments = \DB::table('payment_requests')
                ->latest()
                ->limit(5)
                ->get(['id', 'attribute_id', 'order_ids', 'transaction_id', 'payment_method'])
                ->toArray();
            
            Log::info('System status for comparison', [
                'latest_orders_in_system' => $latestOrders,
                'latest_payment_requests' => $latestPayments,
                'orders_count' => Order::count(),
                'payment_requests_count' => \DB::table('payment_requests')->count()
            ]);
            
            // البحث عن أي payment_request يحتوي على order_ids مشابه
            if (is_numeric($orderId)) {
                $similarPayments = \DB::table('payment_requests')
                    ->where('order_ids', 'like', "%{$orderId}%")
                    ->orWhere('attribute_id', $orderId)
                    ->get(['id', 'attribute_id', 'order_ids'])
                    ->toArray();
                
                if (!empty($similarPayments)) {
                    Log::info('Found similar payment_requests', [
                        'searched_id' => $orderId,
                        'similar_payments' => $similarPayments
                    ]);
                }
            }
            
            Log::info('=== END DIAGNOSTIC ===');
            
        } catch (\Exception $e) {
            Log::error('Error in diagnostic logging', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * إرسال تنبيه طارئ للإدارة عند فشل إنشاء الطلب
     */
    protected function sendEmergencyAlert($orderId, $paymentRequest)
    {
        $alertMessage = "🚨 تنبيه طارئ - BERN\n";
        $alertMessage .= "تم استلام دفعة بدون إنشاء طلب!\n\n";
        $alertMessage .= "التفاصيل:\n";
        $alertMessage .= "• ID: {$orderId}\n";
        $alertMessage .= "• Payment: {$paymentRequest->id}\n";
        $alertMessage .= "• Customer: {$paymentRequest->transaction_id}\n";
        $alertMessage .= "• Status: " . ($paymentRequest->payment_status ?? 'unknown') . "\n";
        $alertMessage .= "• وقت: {$paymentRequest->created_at}\n\n";
        $alertMessage .= "⚠️ يجب مراجعة منطق إنشاء الطلبات فوراً!";
        
        // إرسال SMS طارئ للإدارة
        try {
            if (config('sms.taqnyat.sms_enabled', true)) {
                $this->smsService->sendSms($this->adminPhone, $alertMessage);
                Log::info('Emergency alert sent to admin', [
                    'type' => 'payment_without_order',
                    'order_id' => $orderId,
                    'payment_id' => $paymentRequest->id
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send emergency alert', [
                'error' => $e->getMessage(),
                'order_id' => $orderId
            ]);
        }
    }

    /**
     * إرسال إشعار للإدارة
     */
    protected function sendAdminNotification($order, $paymentData)
    {
        $customerInfo = $order->customer;
        $orderTotal = number_format($order->order_amount, 2);
        
        // إعداد رسالة SMS للإدارة
        $paymentMethod = $paymentData['payment_method'] ?? 'MyFatoorah';
        $adminSmsMessage = "🔔 طلب جديد - BERN\n";
        $adminSmsMessage .= "رقم الطلب: {$order->id}\n";
        $adminSmsMessage .= "العميل: {$customerInfo->f_name} {$customerInfo->l_name}\n";
        $adminSmsMessage .= "الهاتف: {$customerInfo->phone}\n";
        $adminSmsMessage .= "المبلغ: {$orderTotal} {$order->currency_code}\n";
        $adminSmsMessage .= "طريقة الدفع: {$paymentMethod}\n";
        $adminSmsMessage .= "عدد المنتجات: " . $order->orderDetails->sum('qty') . "\n";
        
        if ($paymentData && isset($paymentData['transaction_reference'])) {
            $adminSmsMessage .= "مرجع الدفع: {$paymentData['transaction_reference']}\n";
        }
        
        $adminSmsMessage .= "تاريخ الطلب: " . $order->created_at->format('Y-m-d H:i');

        // إرسال إشعار موحد (WhatsApp أولاً، SMS كـ fallback داخلياً)
        if (config('sms.taqnyat.whatsapp_enabled', true)) {
            // محاولة WhatsApp أولاً (يحتوي على fallback تلقائي لـ SMS)
            $this->sendWhatsAppMessage($this->adminPhone, $adminSmsMessage, 'order_notification');
        } else if (config('sms.taqnyat.sms_enabled', true)) {
            // إذا كان WhatsApp معطل، استخدم SMS مباشرة
            $this->smsService->sendSms($this->adminPhone, $adminSmsMessage);
        }

        Log::info('Admin notification sent', ['order_id' => $order->id]);
    }

    /**
     * إرسال إشعار للعميل
     */
    protected function sendCustomerNotification($order)
    {
        $customerInfo = $order->customer;
        
        if (!$customerInfo->phone) {
            Log::warning('Customer phone not found', ['order_id' => $order->id]);
            return;
        }

        $orderTotal = number_format($order->order_amount, 2);
        
        // إعداد رسالة للعميل
        $customerMessage = "شكراً لك - BERN 🛍️\n";
        $customerMessage .= "تم استلام طلبك بنجاح\n";
        $customerMessage .= "رقم الطلب: {$order->id}\n";
        $customerMessage .= "المبلغ: {$orderTotal} {$order->currency_code}\n";
        $customerMessage .= "عدد المنتجات: " . $order->orderDetails->sum('qty') . "\n";
        $customerMessage .= "حالة الطلب: " . $this->getOrderStatusArabic($order->order_status) . "\n";
        $customerMessage .= "سيتم التواصل معك قريباً\n";
        $customerMessage .= "شكراً لثقتك بنا ❤️";

        // تنسيق رقم العميل
        $customerPhone = $this->formatPhoneNumber($customerInfo->phone);

        // إرسال إشعار موحد للعميل (WhatsApp أولاً، SMS كـ fallback داخلياً)
        if (config('sms.taqnyat.whatsapp_enabled', true)) {
            // محاولة WhatsApp أولاً (يحتوي على fallback تلقائي لـ SMS)
            $this->sendWhatsAppMessage($customerPhone, $customerMessage, 'order_notification');
        } else if (config('sms.taqnyat.sms_enabled', true)) {
            // إذا كان WhatsApp معطل، استخدم SMS مباشرة
            $this->smsService->sendSms($customerPhone, $customerMessage);
        }

        Log::info('Customer notification sent', [
            'order_id' => $order->id,
            'customer_phone' => substr($customerPhone, 0, 5) . '****'
        ]);
    }

    /**
     * إرسال رسالة WhatsApp (باستخدام template message حسب التوثيق)
     */
    protected function sendWhatsAppMessage($phoneNumber, $message, $templateName = 'order_notification')
    {
        try {
            // استخدام Taqnyat لإرسال WhatsApp كـ template message (حسب التوثيق الجديد)
            $result = $this->smsService->sendWhatsApp($phoneNumber, $message, $templateName, false);
            
            if ($result['success']) {
                Log::info('WhatsApp template message sent successfully via Taqnyat', [
                    'phone' => substr($phoneNumber, 0, 5) . '****',
                    'template' => $templateName,
                    'message_id' => $result['message_id'] ?? null
                ]);
                return true;
            } else {
                Log::warning('WhatsApp template message failed via Taqnyat', [
                    'phone' => substr($phoneNumber, 0, 5) . '****',
                    'template' => $templateName,
                    'error' => $result['message']
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('WhatsApp send error', [
                'phone' => substr($phoneNumber, 0, 5) . '****',
                'template' => $templateName,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * تنسيق رقم الهاتف للسعودية
     */
    protected function formatPhoneNumber($phone)
    {
        // إزالة المسافات والرموز الخاصة
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // إذا كان الرقم يبدأ بـ 05، استبدلها بـ 9665
        if (substr($phone, 0, 2) === '05') {
            $phone = '9665' . substr($phone, 2);
        }
        // إذا كان يبدأ بـ 5 فقط، أضف 96655
        elseif (substr($phone, 0, 1) === '5' && strlen($phone) === 8) {
            $phone = '9665' . $phone;
        }
        // إذا لم يبدأ بـ 966، أضفها
        elseif (substr($phone, 0, 3) !== '966') {
            $phone = '966' . $phone;
        }
        
        return $phone;
    }

    /**
     * ترجمة حالة الطلب للعربية
     */
    protected function getOrderStatusArabic($status)
    {
        $statuses = [
            'pending' => 'قيد الانتظار',
            'confirmed' => 'مؤكد',
            'processing' => 'قيد التحضير',
            'out_for_delivery' => 'خرج للتسليم',
            'delivered' => 'تم التسليم',
            'returned' => 'مرتجع',
            'failed' => 'فشل',
            'canceled' => 'ملغي'
        ];

        return $statuses[$status] ?? $status;
    }
}
