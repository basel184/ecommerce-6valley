<?php

namespace App\Services;

use App\Models\ReverseTransfer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Http; // Added Http facade
use Illuminate\Support\Facades\Schema; // Added Schema facade

class ReverseTransferService
{
    protected $myFatoorahService;
    protected $tabbyService;
    protected $tamaraService;

    public function __construct(
        MyFatoorahService $myFatoorahService,
        TabbyService $tabbyService,
        TamaraService $tamaraService
    ) {
        $this->myFatoorahService = $myFatoorahService;
        $this->tabbyService = $tabbyService;
        $this->tamaraService = $tamaraService;
    }

    /**
     * إنشاء حوالة عكسية جديدة
     */
    public function createReverseTransfer($data)
    {
        try {
            DB::beginTransaction();

            // التحقق من وجود البيانات المطلوبة
            if (empty($data['amount'])) {
                throw new Exception('المبلغ مطلوب لإنشاء الحوالة العكسية');
            }

            if (empty($data['customer_id'])) {
                throw new Exception('معرف العميل مطلوب لإنشاء الحوالة العكسية');
            }

            // البحث عن الطلب برقم الهاتف وسعر الطلب (إذا كان متوفراً)
            $order = null;
            if (!empty($data['customer_phone'])) {
                $order = $this->findOrderByPhoneAndAmount($data['customer_phone'], $data['amount']);
            } elseif (!empty($data['order_id'])) {
                $order = Order::find($data['order_id']);
            }
            
            if (!$order) {
                throw new Exception('لم يتم العثور على طلب مدفوع. يرجى التأكد من صحة البيانات.');
            }
            
            // التحقق من أن الطلب مدفوع
            if ($order->payment_status !== 'paid') {
                throw new Exception('الطلب يجب أن يكون مدفوعاً لإنشاء حوالة عكسية');
            }

            // التحقق من أن المبلغ لا يتجاوز مبلغ الطلب
            if ($data['amount'] > $order->order_amount) {
                throw new Exception('مبلغ الحوالة العكسية لا يمكن أن يتجاوز مبلغ الطلب الأصلي');
            }

            // التحقق من أن العميل صاحب الطلب (إذا كان رقم الهاتف متوفراً)
            if (!empty($data['customer_phone']) && $order->customer_id != $data['customer_id']) {
                throw new Exception('رقم الهاتف لا يتطابق مع العميل المحدد');
            }

            // التحقق من عدم وجود حوالة عكسية سابقة لهذا الطلب
            $existingReverseTransfer = ReverseTransfer::where('order_id', $order->id)
                ->whereIn('status', ['pending', 'approved', 'processed'])
                ->first();

            if ($existingReverseTransfer) {
                throw new Exception('يوجد طلب حوالة عكسية سابق لهذا الطلب. يرجى الانتظار أو التواصل مع الدعم.');
            }

            // الحصول على ID المدير
            $adminId = null;
            if (auth()->check()) {
                $user = auth()->user();
                if ($user && $user->exists) {
                    $adminId = $user->id;
                }
            }

            // الحصول على معرف المعاملة الأصلية
            $originalTransactionId = $this->getOriginalTransactionId($order->id);
            
            if (empty($originalTransactionId)) {
                Log::warning('معرف المعاملة الأصلية غير متوفر للطلب', [
                    'order_id' => $order->id,
                    'payment_method' => $order->payment_method,
                    'payment_status' => $order->payment_status
                ]);
                
                // إنشاء معرف معاملة مؤقت إذا لم يكن موجوداً
                $originalTransactionId = 'ORDER_' . $order->id . '_' . time();
            }

            // إنشاء الحوالة العكسية
            $reverseTransfer = ReverseTransfer::create([
                'order_id' => $order->id,
                'customer_id' => $data['customer_id'],
                'admin_id' => $adminId,
                'amount' => $data['amount'],
                'reason' => $data['reason'],
                'status' => 'pending',
                'payment_method' => $data['payment_method'],
                'payment_gateway' => $data['payment_gateway'] ?? null,
                'refund_reason_code' => $data['refund_reason_code'] ?? null,
                'original_payment_method' => $order->payment_method,
                'original_transaction_id' => $originalTransactionId,
                'notes' => $data['notes'] ?? null,
                'bank_name' => $data['bank_name'] ?? null,
                'account_number' => $data['account_number'] ?? null,
                'account_holder_name' => $data['account_holder_name'] ?? null,
                'iban' => $data['iban'] ?? null,
                'swift_code' => $data['swift_code'] ?? null,
            ]);

            // تسجيل حالة الحوالة
            $this->logStatusChange($reverseTransfer, 'pending', 'تم إنشاء طلب الحوالة العكسية');

            // تسجيل معلومات الطلب المستخدم
            Log::info('تم إنشاء حوالة عكسية جديدة', [
                'reverse_transfer_id' => $reverseTransfer->id,
                'order_id' => $order->id,
                'customer_phone' => $data['customer_phone'] ?? ($order->customer->phone ?? 'unknown'),
                'amount' => $data['amount'],
                'payment_gateway' => $data['payment_gateway'] ?? 'local'
            ]);

            DB::commit();
            return $reverseTransfer;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('خطأ في إنشاء الحوالة العكسية: ' . $e->getMessage(), [
                'customer_phone' => $data['customer_phone'] ?? ($order->customer->phone ?? 'unknown') ?? 'unknown',
                'amount' => $data['amount'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * البحث عن الطلب برقم الهاتف وسعر الطلب
     */
    protected function findOrderByPhoneAndAmount($phone, $amount)
    {
        try {
            // تنظيف رقم الهاتف
            $cleanPhone = $this->cleanPhoneNumber($phone);
            
            // البحث عن الطلبات المدفوعة برقم الهاتف
            $orders = Order::whereHas('customer', function($query) use ($cleanPhone) {
                $query->where('phone', 'LIKE', '%' . $cleanPhone . '%');
            })
            ->where('payment_status', 'paid')
            ->where('order_amount', '>=', $amount)
            ->orderBy('created_at', 'desc')
            ->get();

            if ($orders->isEmpty()) {
                Log::warning('لم يتم العثور على طلبات مدفوعة', [
                    'phone' => $phone,
                    'clean_phone' => $cleanPhone,
                    'amount' => $amount
                ]);
                return null;
            }

            // البحث عن الطلب الأقرب للمبلغ المطلوب
            $closestOrder = null;
            $minDifference = PHP_FLOAT_MAX;

            foreach ($orders as $order) {
                $difference = abs($order->order_amount - $amount);
                if ($difference < $minDifference) {
                    $minDifference = $difference;
                    $closestOrder = $order;
                }
            }

            // التحقق من أن الفرق في المبلغ مقبول (ضمن 5 ريال)
            if ($minDifference <= 5.00) {
                Log::info('تم العثور على طلب مطابق', [
                    'phone' => $phone,
                    'requested_amount' => $amount,
                    'order_amount' => $closestOrder->order_amount,
                    'difference' => $minDifference,
                    'order_id' => $closestOrder->id
                ]);
                return $closestOrder;
            }

            Log::warning('لم يتم العثور على طلب بمبلغ مطابق', [
                'phone' => $phone,
                'requested_amount' => $amount,
                'available_orders' => $orders->pluck('order_amount')->toArray(),
                'min_difference' => $minDifference
            ]);

            return null;

        } catch (Exception $e) {
            Log::error('خطأ في البحث عن الطلب: ' . $e->getMessage(), [
                'phone' => $phone,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * البحث عن جميع الطلبات المتاحة لرقم هاتف معين
     */
    public function findAvailableOrdersByPhone($phone)
    {
        try {
            // تنظيف رقم الهاتف
            $cleanPhone = $this->cleanPhoneNumber($phone);
            
            // البحث عن جميع الطلبات المدفوعة برقم الهاتف
            $orders = Order::whereHas('customer', function($query) use ($cleanPhone) {
                $query->where('phone', 'LIKE', '%' . $cleanPhone . '%');
            })
            ->where('payment_status', 'paid')
            ->whereDoesntHave('reverseTransfers', function($query) {
                $query->whereIn('status', ['pending', 'approved', 'processed']);
            })
            ->select('id', 'order_amount', 'created_at', 'payment_method', 'transaction_reference')
            ->orderBy('created_at', 'desc')
            ->get();

            if ($orders->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'لم يتم العثور على طلبات مدفوعة متاحة للحوالة العكسية',
                    'orders' => []
                ];
            }

            // تجميع الطلبات حسب المبلغ
            $ordersByAmount = $orders->groupBy('order_amount')->map(function($group) {
                return $group->map(function($order) {
                    return [
                        'id' => $order->id,
                        'amount' => $order->order_amount,
                        'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                        'payment_method' => $order->payment_method,
                        'transaction_reference' => $order->transaction_reference
                    ];
                });
            });

            return [
                'success' => true,
                'message' => 'تم العثور على ' . $orders->count() . ' طلب متاح للحوالة العكسية',
                'orders' => $ordersByAmount,
                'total_orders' => $orders->count(),
                'available_amounts' => $orders->pluck('order_amount')->unique()->sort()->values()
            ];

        } catch (Exception $e) {
            Log::error('خطأ في البحث عن الطلبات المتاحة: ' . $e->getMessage(), [
                'phone' => $phone,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'خطأ في البحث عن الطلبات',
                'error' => $e->getMessage(),
                'orders' => []
            ];
        }
    }

    /**
     * البحث عن الطلب الأفضل لرقم هاتف ومبلغ معين
     */
    public function findBestMatchingOrder($phone, $amount)
    {
        try {
            // تنظيف رقم الهاتف
            $cleanPhone = $this->cleanPhoneNumber($phone);
            
            // البحث عن الطلبات المدفوعة برقم الهاتف
            $orders = Order::whereHas('customer', function($query) use ($cleanPhone) {
                $query->where('phone', 'LIKE', '%' . $cleanPhone . '%');
            })
            ->where('payment_status', 'paid')
            ->whereDoesntHave('reverseTransfers', function($query) {
                $query->whereIn('status', ['pending', 'approved', 'processed']);
            })
            ->orderBy('created_at', 'desc')
            ->get();

            if ($orders->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'لم يتم العثور على طلبات مدفوعة متاحة',
                    'suggestions' => []
                ];
            }

            // البحث عن الطلب الأقرب للمبلغ المطلوب
            $closestOrder = null;
            $minDifference = PHP_FLOAT_MAX;
            $suggestions = [];

            foreach ($orders as $order) {
                $difference = abs($order->order_amount - $amount);
                
                if ($difference < $minDifference) {
                    $minDifference = $difference;
                    $closestOrder = $order;
                }

                // إضافة اقتراحات للمبالغ المتاحة
                $suggestions[] = [
                    'id' => $order->id,
                    'amount' => $order->order_amount,
                    'difference' => $difference,
                    'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                    'payment_method' => $order->payment_method
                ];
            }

            // ترتيب الاقتراحات حسب الفرق
            usort($suggestions, function($a, $b) {
                return $a['difference'] <=> $b['difference'];
            });

            // التحقق من أن الفرق في المبلغ مقبول (ضمن 10 ريال)
            if ($minDifference <= 10.00) {
                return [
                    'success' => true,
                    'message' => 'تم العثور على طلب مطابق',
                    'best_match' => [
                        'id' => $closestOrder->id,
                        'amount' => $closestOrder->order_amount,
                        'difference' => $minDifference,
                        'created_at' => $closestOrder->created_at->format('Y-m-d H:i:s'),
                        'payment_method' => $closestOrder->payment_method,
                        'transaction_reference' => $closestOrder->transaction_reference
                    ],
                    'suggestions' => array_slice($suggestions, 0, 5) // أفضل 5 اقتراحات
                ];
            }

            return [
                'success' => false,
                'message' => 'لم يتم العثور على طلب بمبلغ مطابق. إليك أفضل الاقتراحات:',
                'suggestions' => array_slice($suggestions, 0, 5),
                'closest_amount' => $closestOrder ? $closestOrder->order_amount : null,
                'min_difference' => $minDifference
            ];

        } catch (Exception $e) {
            Log::error('خطأ في البحث عن أفضل طلب مطابق: ' . $e->getMessage(), [
                'phone' => $phone,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'خطأ في البحث عن الطلب',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * تنظيف رقم الهاتف
     */
    protected function cleanPhoneNumber($phone)
    {
        // إزالة جميع الأحرف غير الرقمية
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        
        // إزالة رمز البلد السعودي إذا وجد
        if (strlen($cleanPhone) >= 12 && substr($cleanPhone, 0, 3) === '966') {
            $cleanPhone = substr($cleanPhone, 3);
        }
        
        // إزالة الصفر من البداية إذا وجد
        if (strlen($cleanPhone) >= 10 && substr($cleanPhone, 0, 1) === '0') {
            $cleanPhone = substr($cleanPhone, 1);
        }
        
        return $cleanPhone;
    }

    /**
     * الحصول على معرف المعاملة الأصلية
     * الحل السريع: استخدام payment_request_id مباشرة
     */
    protected function getOriginalTransactionId($orderId)
    {
        try {
            Log::info('بدء البحث عن معرف المعاملة الأصلية', [
                'order_id' => $orderId
            ]);

            // البحث في جدول payment_requests مباشرة (الأولوية الأولى)
            if (Schema::hasTable('payment_requests')) {
                Log::info('بدء البحث في جدول payment_requests', ['order_id' => $orderId]);
                
                // البحث في عمود order_ids (JSON) - البحث المباشر
                $paymentRequest = DB::table('payment_requests')
                    ->whereJsonContains('order_ids', $orderId)
                    ->whereNotNull('id')
                    ->orderBy('id', 'desc')
                    ->first();

                // إذا لم يتم العثور، البحث في المصفوفات JSON
                if (!$paymentRequest) {
                    $paymentRequest = DB::table('payment_requests')
                        ->whereRaw("JSON_CONTAINS(order_ids, ?)", [json_encode($orderId)])
                        ->whereNotNull('id')
                        ->orderBy('id', 'desc')
                        ->first();
                }

                // البحث في order_ids كـ string
                if (!$paymentRequest) {
                    $paymentRequest = DB::table('payment_requests')
                        ->where('order_ids', 'like', '%' . $orderId . '%')
                        ->whereNotNull('id')
                        ->orderBy('id', 'desc')
                        ->first();
                }

                // إذا لم يتم العثور، البحث في additional_data (JSON)
                if (!$paymentRequest) {
                    $paymentRequest = DB::table('payment_requests')
                        ->whereJsonContains('additional_data', ['customer_id' => $orderId])
                        ->whereNotNull('id')
                        ->orderBy('id', 'desc')
                        ->first();
                }

                // إذا لم يتم العثور، البحث في payer_information (JSON) للبحث عن رقم الهاتف
                if (!$paymentRequest) {
                    $order = DB::table('orders')->where('id', $orderId)->first();
                    if ($order && !empty($order->customer_phone)) {
                        $phone = $order->customer_phone;
                        
                        $paymentRequest = DB::table('payment_requests')
                            ->whereJsonContains('payer_information', ['phone' => $phone])
                            ->whereNotNull('id')
                            ->orderBy('id', 'desc')
                            ->first();
                    }
                }

                // إذا لم يتم العثور، البحث في transaction_reference
                if (!$paymentRequest) {
                    $order = DB::table('orders')->where('id', $orderId)->first();
                    if ($order && !empty($order->transaction_reference)) {
                        $transactionRef = $order->transaction_reference;
                        
                        $paymentRequest = DB::table('payment_requests')
                            ->where('transaction_reference', $transactionRef)
                            ->whereNotNull('id')
                            ->orderBy('id', 'desc')
                            ->first();
                    }
                }

                // البحث الإضافي: البحث في جميع payment_requests للعثور على الطلب
                if (!$paymentRequest) {
                    $this->info('البحث الإضافي في جميع payment_requests...', [
                        'order_id' => $orderId
                    ]);
                    
                    // البحث في payment_requests التي تحتوي على order_ids
                    $allPaymentRequests = DB::table('payment_requests')
                        ->whereNotNull('order_ids')
                        ->where('order_ids', '!=', '')
                        ->get();
                    
                    foreach ($allPaymentRequests as $pr) {
                        if (!empty($pr->order_ids)) {
                            $orderIds = json_decode($pr->order_ids, true);
                            if (is_array($orderIds) && in_array($orderId, $orderIds)) {
                                $paymentRequest = $pr;
                                $this->info('تم العثور على payment_request في البحث الإضافي', [
                                    'payment_request_id' => $pr->id,
                                    'order_ids' => $pr->order_ids
                                ]);
                                break;
                            }
                        }
                    }
                }

                if ($paymentRequest) {
                    Log::info('تم العثور على طلب دفع في جدول payment_requests', [
                        'order_id' => $orderId,
                        'payment_request_id' => $paymentRequest->id,
                        'payment_request_data' => [
                            'id' => $paymentRequest->id,
                            'payer_id' => $paymentRequest->payer_id ?? 'غير محدد',
                            'receiver_id' => $paymentRequest->receiver_id ?? 'غير محدد',
                            'payment_amount' => $paymentRequest->payment_amount ?? 'غير محدد',
                            'payment_status' => $paymentRequest->payment_status ?? 'غير محدد',
                            'transaction_id' => $paymentRequest->transaction_id ?? 'غير محدد',
                            'transaction_reference' => $paymentRequest->transaction_reference ?? 'غير محدد',
                            'created_at' => $paymentRequest->created_at ?? 'غير محدد'
                        ]
                    ]);
                    // إعطاء الأولوية لـ transaction_id ثم transaction_reference
                    if (!empty($paymentRequest->transaction_id)) {
                        Log::info('تم العثور على transaction_id من payment_requests', [
                            'order_id' => $orderId,
                            'transaction_id' => $paymentRequest->transaction_id
                        ]);
                        return $paymentRequest->transaction_id;
                    }
                    
                    if (!empty($paymentRequest->transaction_reference)) {
                        Log::info('تم العثور على transaction_reference من payment_requests', [
                            'order_id' => $orderId,
                            'transaction_reference' => $paymentRequest->transaction_reference
                        ]);
                        return $paymentRequest->transaction_reference;
                    }
                    
                    // إذا لم يكن هناك معرف، استخدام معرف payment_requests
                    Log::info('استخدام معرف payment_requests كمعرف المعاملة', [
                        'order_id' => $orderId,
                        'payment_request_id' => $paymentRequest->id
                    ]);
                    return $paymentRequest->id;
                } else {
                    Log::info('لم يتم العثور على طلب دفع في جدول payment_requests', [
                        'order_id' => $orderId,
                        'table' => 'payment_requests',
                        'search_methods' => [
                            'order_ids_json' => 'تم البحث',
                            'additional_data_customer_id' => 'تم البحث',
                            'payer_information_phone' => 'تم البحث',
                            'transaction_reference' => 'تم البحث',
                            'additional_search' => 'تم البحث'
                        ]
                    ]);
                }
            } else {
                Log::info('جدول payment_requests غير موجود', [
                    'order_id' => $orderId
                ]);
            }

            // البحث في جدول orders
            $order = DB::table('orders')->where('id', $orderId)->first();
            if ($order) {
                if (!empty($order->transaction_reference)) {
                    Log::info('تم العثور على معرف المعاملة من جدول orders', [
                        'order_id' => $orderId,
                        'transaction_reference' => $order->transaction_reference
                    ]);
                    return $order->transaction_reference;
                }
            }

            // إنشاء معرف مؤقت كحل أخير
            $tempId = 'TEMP_' . $orderId . '_' . time();
            Log::warning('لم يتم العثور على معرف المعاملة، إنشاء معرف مؤقت', [
                'order_id' => $orderId,
                'temp_id' => $tempId,
                'note' => 'هذا معرف مؤقت ويجب مراجعته يدوياً'
            ]);
            
            return $tempId;

        } catch (Exception $e) {
            Log::error('خطأ في البحث عن معرف المعاملة الأصلية', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // إنشاء معرف مؤقت في حالة الخطأ
            $tempId = 'ERROR_' . $orderId . '_' . time();
            Log::error('إنشاء معرف مؤقت بسبب الخطأ', [
                'order_id' => $orderId,
                'temp_id' => $tempId
            ]);
            
            return $tempId;
        }
    }

    /**
     * البحث عن المدفوعات في جدول payments
     * الأولوية: payments.id (مرجع العميل)
     */
    protected function findPaymentByOrderId($orderId)
    {
        try {
            Log::info('بدء البحث في جدول payments', [
                'order_id' => $orderId,
                'schema_has_payments' => Schema::hasTable('payments')
            ]);

            // البحث في جدول payments إذا كان موجوداً
            if (Schema::hasTable('payments')) {
                Log::info('جدول payments موجود، بدء البحث...', ['order_id' => $orderId]);
                
                // البحث عن الدفعة المرتبطة بالطلب
                $payment = DB::table('payments')
                    ->where('order_id', $orderId)
                    ->whereNotNull('id')
                    ->where('id', '>', 0)
                    ->orderBy('id', 'desc') // الحصول على أحدث دفعة
                    ->first();

                if ($payment) {
                    Log::info('تم العثور على دفعة في جدول payments (مرجع العميل)', [
                        'order_id' => $orderId,
                        'payment_id' => $payment->id,
                        'payment_data' => [
                            'id' => $payment->id,
                            'order_id' => $payment->order_id,
                            'amount' => $payment->amount ?? 'غير محدد',
                            'status' => $payment->status ?? 'غير محدد',
                            'created_at' => $payment->created_at ?? 'غير محدد'
                        ]
                    ]);
                    return $payment;
                } else {
                    Log::info('لم يتم العثور على دفعة في جدول payments', [
                        'order_id' => $orderId,
                        'table' => 'payments',
                        'query_conditions' => [
                            'order_id' => $orderId,
                            'id_not_null' => true,
                            'id_greater_than_0' => true
                        ]
                    ]);

                    // فحص إضافي: البحث بدون شروط
                    $allPayments = DB::table('payments')->where('order_id', $orderId)->get();
                    Log::info('جميع المدفوعات للطلب (بدون شروط)', [
                        'order_id' => $orderId,
                        'total_payments' => $allPayments->count(),
                        'payments_data' => $allPayments->map(function($p) {
                            return [
                                'id' => $p->id,
                                'order_id' => $p->order_id,
                                'amount' => $p->amount ?? 'غير محدد',
                                'status' => $p->status ?? 'غير محدد'
                            ];
                        })->toArray()
                    ]);
                }
            } else {
                Log::info('جدول payments غير موجود', [
                    'order_id' => $orderId
                ]);
            }
            
            // البحث في جدول payment_requests إذا لم يتم العثور في payments
            Log::info('البحث في جدول payment_requests...', [
                'order_id' => $orderId,
                'schema_has_payment_requests' => Schema::hasTable('payment_requests')
            ]);

            if (Schema::hasTable('payment_requests')) {
                // البحث في عمود order_ids (JSON)
                $paymentRequest = DB::table('payment_requests')
                    ->whereJsonContains('order_ids', $orderId)
                    ->whereNotNull('id')
                    ->orderBy('id', 'desc')
                    ->first();

                // إذا لم يتم العثور، البحث في additional_data (JSON)
                if (!$paymentRequest) {
                    $paymentRequest = DB::table('payment_requests')
                        ->whereJsonContains('additional_data', ['customer_id' => $orderId])
                        ->whereNotNull('id')
                        ->orderBy('id', 'desc')
                        ->first();
                }

                // إذا لم يتم العثور، البحث في payer_information (JSON) للبحث عن رقم الهاتف
                if (!$paymentRequest) {
                    $order = DB::table('orders')->where('id', $orderId)->first();
                    if ($order && !empty($order->customer_phone)) {
                        $phone = $order->customer_phone;
                        
                        $paymentRequest = DB::table('payment_requests')
                            ->whereJsonContains('payer_information', ['phone' => $phone])
                            ->whereNotNull('id')
                            ->orderBy('id', 'desc')
                            ->first();
                    }
                }

                // إذا لم يتم العثور، البحث في transaction_reference
                if (!$paymentRequest) {
                    $order = DB::table('orders')->where('id', $orderId)->first();
                    if ($order && !empty($order->transaction_reference)) {
                        $transactionRef = $order->transaction_reference;
                        
                        $paymentRequest = DB::table('payment_requests')
                            ->where('transaction_reference', $transactionRef)
                            ->whereNotNull('id')
                            ->orderBy('id', 'desc')
                            ->first();
                    }
                }

                // البحث الإضافي: البحث في جميع payment_requests للعثور على الطلب
                if (!$paymentRequest) {
                    $this->info('البحث الإضافي في جميع payment_requests...', [
                        'order_id' => $orderId
                    ]);
                    
                    // البحث في payment_requests التي تحتوي على order_ids
                    $allPaymentRequests = DB::table('payment_requests')
                        ->whereNotNull('order_ids')
                        ->where('order_ids', '!=', '')
                        ->get();
                    
                    foreach ($allPaymentRequests as $pr) {
                        if (!empty($pr->order_ids)) {
                            $orderIds = json_decode($pr->order_ids, true);
                            if (is_array($orderIds) && in_array($orderId, $orderIds)) {
                                $paymentRequest = $pr;
                                $this->info('تم العثور على payment_request في البحث الإضافي', [
                                    'payment_request_id' => $pr->id,
                                    'order_ids' => $pr->order_ids
                                ]);
                                break;
                            }
                        }
                    }
                }

                if ($paymentRequest) {
                    Log::info('تم العثور على طلب دفع في جدول payment_requests', [
                        'order_id' => $orderId,
                        'payment_request_id' => $paymentRequest->id,
                        'payment_request_data' => [
                            'id' => $paymentRequest->id,
                            'payer_id' => $paymentRequest->payer_id ?? 'غير محدد',
                            'receiver_id' => $paymentRequest->receiver_id ?? 'غير محدد',
                            'payment_amount' => $paymentRequest->payment_amount ?? 'غير محدد',
                            'payment_status' => $paymentRequest->payment_status ?? 'غير محدد',
                            'transaction_id' => $paymentRequest->transaction_id ?? 'غير محدد',
                            'transaction_reference' => $paymentRequest->transaction_reference ?? 'غير محدد',
                            'created_at' => $paymentRequest->created_at ?? 'غير محدد'
                        ]
                    ]);
                    return $paymentRequest;
                } else {
                    Log::info('لم يتم العثور على طلب دفع في جدول payment_requests', [
                        'order_id' => $orderId,
                        'table' => 'payment_requests',
                        'search_methods' => [
                            'order_ids_json' => 'تم البحث',
                            'additional_data_customer_id' => 'تم البحث',
                            'payer_information_phone' => 'تم البحث',
                            'transaction_reference' => 'تم البحث',
                            'additional_search' => 'تم البحث'
                        ]
                    ]);
                }
            } else {
                Log::info('جدول payment_requests غير موجود', [
                    'order_id' => $orderId
                ]);
            }
            
            Log::warning('لم يتم العثور على أي مدفوعات للطلب', [
                'order_id' => $orderId,
                'tables_checked' => ['payments', 'payment_requests'],
                'search_summary' => [
                    'payments_table_exists' => Schema::hasTable('payments'),
                    'payment_requests_table_exists' => Schema::hasTable('payment_requests'),
                    'payments_found' => $payment ?? false,
                    'payment_requests_found' => $paymentRequest ?? false
                ]
            ]);
            
            return null;
        } catch (Exception $e) {
            Log::error('خطأ في البحث عن المدفوعات', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * فحص هيكل جدول payments
     */
    public function inspectPaymentsTable()
    {
        try {
            if (!Schema::hasTable('payments')) {
                return [
                    'success' => false,
                    'message' => 'جدول payments غير موجود'
                ];
            }

            // فحص أعمدة الجدول
            $columns = Schema::getColumnListing('payments');
            
            // فحص عينة من البيانات
            $sampleData = DB::table('payments')->limit(5)->get();
            
            // فحص العلاقات مع الطلبات
            $ordersWithPayments = DB::table('payments')
                ->join('orders', 'payments.order_id', '=', 'orders.id')
                ->select('payments.id as payment_id', 'orders.id as order_id', 'orders.payment_status')
                ->limit(10)
                ->get();

            return [
                'success' => true,
                'table_exists' => true,
                'columns' => $columns,
                'sample_data_count' => $sampleData->count(),
                'sample_data' => $sampleData,
                'orders_with_payments_count' => $ordersWithPayments->count(),
                'orders_with_payments' => $ordersWithPayments
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * البحث عن طلب الدفع في جدول payment_requests
     */
    protected function findPaymentRequestByOrderId($orderId)
    {
        try {
            if (Schema::hasTable('payment_requests')) {
                return DB::table('payment_requests')
                    ->where('order_id', $orderId)
                    ->whereNotNull('transaction_id')
                    ->first();
            }
            return null;
        } catch (Exception $e) {
            Log::warning('خطأ في البحث عن طلب الدفع', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * تحديث حالة الحوالة العكسية
     */
    public function updateReverseTransferStatus($reverseTransfer, $status, $notes = null)
    {
        try {
            DB::beginTransaction();

            $previousStatus = $reverseTransfer->status;
            $updateData = ['status' => $status];

            // الحصول على ID المستخدم
            $userId = null;
            if (auth()->check()) {
                $user = auth()->user();
                if ($user && $user->exists) {
                    $userId = $user->id;
                }
            }

            switch ($status) {
                case 'approved':
                    $updateData['approved_by'] = $userId;
                    $updateData['approved_at'] = now();
                    break;
                case 'rejected':
                    $updateData['rejected_by'] = $userId;
                    $updateData['rejected_at'] = now();
                    break;
                case 'processed':
                    $updateData['processed_by'] = $userId;
                    $updateData['processed_at'] = now();
                    break;
                case 'completed':
                    $updateData['processed_by'] = $userId;
                    $updateData['processed_at'] = now();
                    break;
            }

            $reverseTransfer->update($updateData);

            // تسجيل تغيير الحالة
            $this->logStatusChange($reverseTransfer, $status, $notes, $previousStatus);

            DB::commit();
            return true;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('خطأ في تحديث حالة الحوالة العكسية: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * معالجة الحوالة العكسية عبر بوابة الدفع
     */
    public function processGatewayReverseTransfer($reverseTransfer)
    {
        try {
            if (!$reverseTransfer->isGatewayPayment()) {
                throw new Exception('هذه الحوالة ليست عبر بوابة دفع');
            }

            $gatewayService = $this->getGatewayService($reverseTransfer->payment_gateway);
            if (!$gatewayService) {
                throw new Exception('خدمة بوابة الدفع غير متوفرة');
            }

            // التحقق من وجود معرف المعاملة الأصلية
            if (empty($reverseTransfer->original_transaction_id)) {
                // محاولة الحصول من الطلب
                $order = $reverseTransfer->order;
                if ($order && !empty($order->transaction_reference)) {
                    $reverseTransfer->update(['original_transaction_id' => $order->transaction_reference]);
                } else {
                    // محاولة الحصول من جدول payments
                    $payment = $this->findPaymentByOrderId($order->id);
                    if ($payment && !empty($payment->id)) {
                        $transactionId = 'PAYMENT_' . $payment->id;
                        $reverseTransfer->update(['original_transaction_id' => $transactionId]);
                    } else {
                        throw new Exception('معرف المعاملة الأصلية غير متوفر. يرجى التأكد من أن الطلب يحتوي على معرف معاملة صحيح.');
                    }
                }
            }

            // التحقق من وجود بيانات العميل
            $customer = $reverseTransfer->customer;
            if (!$customer) {
                throw new Exception('بيانات العميل غير متوفرة');
            }

            // الحصول على بيانات العميل من الطلب إذا لم تكن متوفرة
            $customerPhone = $customer->phone ?? '966500000000';
            $customerEmail = $customer->email ?? 'customer@example.com';
            $customerName = ($customer->f_name ?? 'مستخدم') . ' ' . ($customer->l_name ?? 'جديد');

            $refundData = [
                'amount' => $reverseTransfer->amount,
                'original_transaction_id' => $reverseTransfer->original_transaction_id,
                'reason' => $reverseTransfer->reason ?? 'طلب العميل',
                'customer_email' => $customerEmail,
                'customer_phone' => $customerPhone,
                'customer_name' => $customerName,
                'order_id' => $reverseTransfer->order_id,
                'reference' => 'REF_' . $reverseTransfer->id
            ];

            // تسجيل البيانات قبل الإرسال للتشخيص
            Log::info('بيانات الاسترداد المُرسلة', [
                'reverse_transfer_id' => $reverseTransfer->id,
                'gateway' => $reverseTransfer->payment_gateway,
                'refund_data' => $refundData,
                'customer_data' => [
                    'phone' => $customerPhone,
                    'email' => $customerEmail,
                    'name' => $customerName
                ]
            ]);

            // التحقق من وجود جميع الحقول المطلوبة
            $requiredFields = ['amount', 'original_transaction_id', 'customer_phone', 'customer_email'];
            $missingFields = [];
            
            foreach ($requiredFields as $field) {
                if (!isset($refundData[$field]) || empty($refundData[$field])) {
                    $missingFields[] = $field;
                }
            }
            
            if (!empty($missingFields)) {
                throw new Exception('الحقول التالية مفقودة أو فارغة: ' . implode(', ', $missingFields));
            }

            $result = $this->processRefundByGateway($reverseTransfer->payment_gateway, $gatewayService, $refundData);

            if ($result['success']) {
                $reverseTransfer->update([
                    'gateway_transaction_id' => $result['gateway_transaction_id'],
                    'gateway_status' => 'processing',
                    'gateway_response' => $result['response'] ?? null,
                ]);

                // تسجيل النجاح
                Log::info('تم إرسال طلب الاسترداد بنجاح', [
                    'reverse_transfer_id' => $reverseTransfer->id,
                    'gateway' => $reverseTransfer->payment_gateway,
                    'gateway_transaction_id' => $result['gateway_transaction_id']
                ]);

                return ['success' => true, 'message' => $result['message']];
            } else {
                $reverseTransfer->update([
                    'gateway_status' => 'failed',
                    'gateway_error_message' => $result['error'] ?? 'فشل في معالجة الحوالة العكسية',
                    'gateway_response' => $result['response'] ?? null,
                ]);

                // تسجيل الفشل
                Log::error('فشل في معالجة الاسترداد', [
                    'reverse_transfer_id' => $reverseTransfer->id,
                    'gateway' => $reverseTransfer->payment_gateway,
                    'error' => $result['error']
                ]);

                return ['success' => false, 'error' => $result['error']];
            }

        } catch (Exception $e) {
            Log::error('خطأ في معالجة الحوالة العكسية عبر البوابة: ' . $e->getMessage(), [
                'reverse_transfer_id' => $reverseTransfer->id ?? 'unknown',
                'gateway' => $reverseTransfer->payment_gateway ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * معالجة الحوالة العكسية المحلية (بدون بوابة دفع)
     */
    public function processLocalReverseTransfer($reverseTransfer)
    {
        try {
            DB::beginTransaction();

            // التحقق من أن الحوالة يمكن معالجتها
            if (!$reverseTransfer->canBeProcessed()) {
                throw new Exception('الحوالة لا يمكن معالجتها في الوقت الحالي');
            }

            // تحديث حالة الحوالة إلى "معالج"
            $reverseTransfer->update([
                'status' => 'processed',
                'processed_by' => auth()->id(),
                'processed_at' => now(),
                'gateway_status' => 'completed',
                'gateway_transaction_id' => 'LOCAL_RT_' . time(),
            ]);

            // تسجيل تغيير الحالة
            $this->logStatusChange($reverseTransfer, 'processed', 'تم معالجة الحوالة العكسية محلياً');

            // إذا كانت طريقة الدفع هي المحفظة، قم بإضافة المبلغ للمحفظة
            if ($reverseTransfer->payment_method === 'customer_wallet') {
                $this->addToCustomerWallet($reverseTransfer);
            }

            // إنشاء سجل المعاملة
            $this->createRefundTransaction($reverseTransfer);

            DB::commit();

            return [
                'success' => true,
                'message' => 'تم معالجة الحوالة العكسية بنجاح',
                'transaction_id' => $reverseTransfer->gateway_transaction_id
            ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('خطأ في معالجة الحوالة العكسية المحلية: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * الحصول على خدمة بوابة الدفع
     */
    protected function getGatewayService($gateway)
    {
        switch ($gateway) {
            case 'myfatoorah':
                return $this->myFatoorahService;
            case 'tabby':
                return $this->tabbyService;
            case 'tamara':
                return $this->tamaraService;
            default:
                return null;
        }
    }

    /**
     * معالجة الحوالة العكسية حسب بوابة الدفع
     */
    protected function processRefundByGateway($gateway, $gatewayService, $refundData)
    {
        switch ($gateway) {
            case 'myfatoorah':
                return $this->processMyFatoorahRefund($gatewayService, $refundData);
            case 'tabby':
                return $this->processTabbyRefund($gatewayService, $refundData);
            case 'tamara':
                return $this->processTamaraRefund($gatewayService, $refundData);
            default:
                throw new Exception('بوابة الدفع غير مدعومة');
        }
    }

    /**
     * معالجة الحوالة العكسية عبر MyFatoorah
     */
    protected function processMyFatoorahRefund($gatewayService, $refundData)
    {
        try {
            // التحقق من وجود مفتاح API
            if (empty(config('myfatoorah.api_key'))) {
                throw new Exception('مفتاح API الخاص بـ MyFatoorah غير متوفر');
            }

            // إصلاح معرف المعاملة إذا كان خاطئاً
            $transactionId = $refundData['original_transaction_id'];
            
            // إذا كان المعرف يبدأ بـ PAYMENT_ فهو خاطئ، نحتاج للبحث عن المعرف الصحيح
            if (strpos($transactionId, 'PAYMENT_') === 0) {
                Log::info('معرف المعاملة خاطئ، البحث عن المعرف الصحيح', [
                    'wrong_id' => $transactionId,
                    'order_id' => $refundData['order_id']
                ]);
                
                // استخراج payment_request_id من المعرف الخاطئ
                $paymentRequestId = str_replace('PAYMENT_', '', $transactionId);
                
                // البحث عن المعرف الصحيح في payment_requests
                $paymentRequest = DB::table('payment_requests')
                    ->where('id', $paymentRequestId)
                    ->first();
                
                if ($paymentRequest) {
                    // استخدام transaction_reference إذا كان موجوداً
                    if (!empty($paymentRequest->transaction_reference)) {
                        $transactionId = $paymentRequest->transaction_reference;
                        Log::info('تم العثور على المعرف الصحيح من transaction_reference', [
                            'correct_id' => $transactionId,
                            'payment_request_id' => $paymentRequestId
                        ]);
                    } else {
                        // استخدام payment_request_id مباشرة
                        $transactionId = $paymentRequestId;
                        Log::info('استخدام payment_request_id كمعرف المعاملة', [
                            'transaction_id' => $transactionId
                        ]);
                    }
                } else {
                    Log::warning('لم يتم العثور على payment_request، استخدام المعرف الأصلي', [
                        'payment_request_id' => $paymentRequestId,
                        'original_id' => $transactionId
                    ]);
                }
            }

            // إعداد بيانات الاسترداد - محاولة الاسترداد بالمعرف أولاً
            $refundPayload = [
                'Key' => $transactionId,  // استخدام المعرف المصحح
                'KeyType' => 'InvoiceId',
                'Amount' => $refundData['amount'],
                'Reason' => $refundData['reason'] ?? 'Customer request',
                'Comment' => 'Refund processed via system'
            ];

            // محاولة الاسترداد بالمعرف
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('myfatoorah.api_key'),
                'Content-Type' => 'application/json',
            ])->post(config('myfatoorah.live_api_url') . '/v2/MakeRefund', $refundPayload);

            // إذا فشل الاسترداد بالمعرف، جرب الطرق البديلة
            if (!$response->successful()) {
                Log::info('فشل الاسترداد بالمعرف، جرب الطرق البديلة', [
                    'transaction_id' => $transactionId,
                    'response_status' => $response->status(),
                    'response_body' => $response->body()
                ]);

                // الطريقة البديلة: البحث عن المعاملات برقم الهاتف والمبلغ
                $alternativePayload = [
                    'CustomerMobile' => $refundData['customer_phone'],
                    'Amount' => $refundData['amount'],
                    'Reason' => $refundData['reason'] ?? 'Customer request',
                    'Comment' => 'Refund via alternative method'
                ];

                Log::info('محاولة الاسترداد بالطريقة البديلة', [
                    'alternative_payload' => $alternativePayload
                ]);

                // استخدام نقطة نهاية بديلة للاسترداد
                $alternativeResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . config('myfatoorah.api_key'),
                    'Content-Type' => 'application/json',
                ])->post(config('myfatoorah.live_api_url') . '/v2/RefundByCustomerInfo', $alternativePayload);

                if ($alternativeResponse->successful()) {
                    $response = $alternativeResponse;
                    Log::info('نجح الاسترداد بالطريقة البديلة');
                } else {
                    Log::info('فشل الاسترداد بالطريقة البديلة أيضاً', [
                        'alternative_response_status' => $alternativeResponse->status(),
                        'alternative_response_body' => $alternativeResponse->body()
                    ]);
                }
            }

            // إرسال طلب الاسترداد إلى MyFatoorah
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('myfatoorah.api_key'),
                'Content-Type' => 'application/json',
            ])->post(config('myfatoorah.live_api_url') . '/v2/MakeRefund', $refundPayload);

            if ($response->successful()) {
                $responseData = $response->json();
                
                if (isset($responseData['IsSuccess']) && $responseData['IsSuccess']) {
        return [
            'success' => true,
                        'gateway_transaction_id' => $responseData['Data']['RefundId'] ?? 'MF_RT_' . time(),
                        'message' => 'تم إرسال طلب الحوالة العكسية إلى MyFatoorah بنجاح',
                        'response' => $responseData
                    ];
                } else {
                    throw new Exception($responseData['Message'] ?? 'فشل في معالجة الاسترداد عبر MyFatoorah');
                }
            } else {
                // إذا فشل الاسترداد عبر MyFatoorah، جرب الاسترداد المحلي
                Log::warning('فشل الاسترداد عبر MyFatoorah، جرب الاسترداد المحلي', [
                    'response_status' => $response->status(),
                    'response_body' => $response->body()
                ]);

                // محاولة الاسترداد المحلي
                $localRefundResult = $this->processLocalRefund($refundData);
                
                if ($localRefundResult['success']) {
                    Log::info('نجح الاسترداد المحلي', [
                        'local_refund_result' => $localRefundResult
                    ]);
                    
                    return [
                        'success' => true,
                        'gateway_transaction_id' => 'LOCAL_REFUND_' . time(),
                        'message' => 'تم الاسترداد محلياً بنجاح: ' . $localRefundResult['message'],
                        'response' => $localRefundResult,
                        'refund_method' => 'local'
                    ];
                } else {
                    throw new Exception('فشل في الاسترداد عبر MyFatoorah والاسترداد المحلي: ' . $response->status());
                }
            }

        } catch (Exception $e) {
            Log::error('خطأ في معالجة الاسترداد عبر MyFatoorah: ' . $e->getMessage(), [
                'refund_data' => $refundData,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'فشل في معالجة الاسترداد عبر MyFatoorah: ' . $e->getMessage(),
            'gateway_transaction_id' => 'MF_RT_' . time(),
                'response' => ['error' => $e->getMessage()]
        ];
        }
    }

    /**
     * معالجة الحوالة العكسية عبر Tabby
     */
    protected function processTabbyRefund($gatewayService, $refundData)
    {
        try {
            // استخدام خدمة Tabby المحدثة مع دعم Refund API
            if ($gatewayService instanceof \App\Services\TabbyService) {
                return $gatewayService->processRefund($refundData);
            }
            
            // fallback للطريقة القديمة إذا لم تكن الخدمة متاحة
            if (empty(config('tabby.api_key'))) {
                throw new Exception('مفتاح API الخاص بـ Tabby غير متوفر');
            }

            // إعداد بيانات الاسترداد
            $refundPayload = [
                'amount' => $refundData['amount'],
                'currency' => config('tabby.currency', 'SAR'),
                'reason' => $refundData['reason'] ?? 'Customer request',
                'reference_id' => 'REF_' . time(),
                'original_payment_id' => $refundData['original_transaction_id']
            ];

            // إرسال طلب الاسترداد إلى Tabby
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('tabby.api_key'),
                'Content-Type' => 'application/json',
            ])->post(config('tabby.live_api_url') . 'refunds', $refundPayload);

            if ($response->successful()) {
                $responseData = $response->json();
                
                if (isset($responseData['id'])) {
                    return [
                        'success' => true,
                        'gateway_transaction_id' => $responseData['id'],
                        'message' => 'تم إرسال طلب الحوالة العكسية إلى Tabby بنجاح',
                        'response' => $responseData
                    ];
                } else {
                    throw new Exception('فشل في معالجة الاسترداد عبر Tabby');
                }
            } else {
                throw new Exception('خطأ في الاتصال مع Tabby: ' . $response->status());
            }

        } catch (Exception $e) {
            Log::error('خطأ في معالجة الاسترداد عبر Tabby: ' . $e->getMessage(), [
                'refund_data' => $refundData,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'فشل في معالجة الاسترداد عبر Tabby: ' . $e->getMessage(),
                'gateway_transaction_id' => 'TB_RT_' . time(),
                'response' => ['error' => $e->getMessage()]
            ];
        }
    }

    /**
     * معالجة الحوالة العكسية عبر Tamara
     */
    protected function processTamaraRefund($gatewayService, $refundData)
    {
        try {
            // التحقق من وجود مفتاح API
            if (empty(config('tamara.api_key'))) {
                throw new Exception('مفتاح API الخاص بـ Tamara غير متوفر');
            }

            // إعداد بيانات الاسترداد
            $refundPayload = [
                'amount' => [
                    'amount' => (string) $refundData['amount'],
                    'currency' => config('tamara.currency', 'SAR')
                ],
                'reason' => $refundData['reason'] ?? 'Customer request',
                'reference_id' => 'REF_' . time(),
                'order_id' => $refundData['original_transaction_id']
            ];

            // إرسال طلب الاسترداد إلى Tamara
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('tamara.api_key'),
                'Content-Type' => 'application/json',
            ])->post((strtolower(config('tamara.mode')) === 'sandbox' ? config('tamara.sandbox_api_url') : config('tamara.live_api_url')) . 'refunds', $refundPayload);

            if ($response->successful()) {
                $responseData = $response->json();
                
                if (isset($responseData['id'])) {
        return [
            'success' => true,
                        'gateway_transaction_id' => $responseData['id'],
                        'message' => 'تم إرسال طلب الحوالة العكسية إلى Tamara بنجاح',
                        'response' => $responseData
                    ];
                } else {
                    throw new Exception('فشل في معالجة الاسترداد عبر Tamara');
                }
            } else {
                throw new Exception('خطأ في الاتصال مع Tamara: ' . $response->status());
            }

        } catch (Exception $e) {
            Log::error('خطأ في معالجة الاسترداد عبر Tamara: ' . $e->getMessage(), [
                'refund_data' => $refundData,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'فشل في معالجة الاسترداد عبر Tamara: ' . $e->getMessage(),
            'gateway_transaction_id' => 'TM_RT_' . time(),
                'response' => ['error' => $e->getMessage()]
            ];
        }
    }

    /**
     * إضافة المبلغ إلى محفظة العميل
     */
    protected function addToCustomerWallet($reverseTransfer)
    {
        try {
            $customer = $reverseTransfer->customer;
            if ($customer) {
                $currentBalance = $customer->wallet_balance ?? 0;
                $newBalance = $currentBalance + $reverseTransfer->amount;
                
                $customer->update(['wallet_balance' => $newBalance]);

                // تسجيل معاملة المحفظة
                $this->createWalletTransaction($reverseTransfer);

                Log::info('تم إضافة المبلغ إلى محفظة العميل', [
                    'customer_id' => $customer->id,
                    'amount' => $reverseTransfer->amount,
                    'old_balance' => $currentBalance,
                    'new_balance' => $newBalance
                ]);
            }
        } catch (Exception $e) {
            Log::error('خطأ في إضافة المبلغ إلى محفظة العميل: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * إنشاء معاملة المحفظة
     */
    protected function createWalletTransaction($reverseTransfer)
    {
        try {
            // إنشاء معاملة المحفظة
            $walletTransaction = new \App\Models\WalletTransaction();
            $walletTransaction->user_id = $reverseTransfer->customer_id;
            $walletTransaction->transaction_id = \Str::uuid();
            $walletTransaction->reference = 'reverse_transfer_' . $reverseTransfer->id;
            $walletTransaction->transaction_type = 'order_refund';
            $walletTransaction->payment_method = $reverseTransfer->payment_method;
            $walletTransaction->credit = $reverseTransfer->amount;
            $walletTransaction->debit = 0;
            $walletTransaction->balance = $reverseTransfer->customer->wallet_balance;
            $walletTransaction->created_at = now();
            $walletTransaction->updated_at = now();
            $walletTransaction->save();

            Log::info('تم إنشاء معاملة المحفظة للحوالة العكسية', [
                'wallet_transaction_id' => $walletTransaction->id,
                'reverse_transfer_id' => $reverseTransfer->id,
                'amount' => $reverseTransfer->amount
            ]);

        } catch (Exception $e) {
            Log::error('خطأ في إنشاء معاملة المحفظة: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * إنشاء سجل المعاملة
     */
    protected function createRefundTransaction($reverseTransfer)
    {
        try {
            $order = $reverseTransfer->order;
            
            $refundTransaction = new \App\Models\RefundTransaction();
            $refundTransaction->order_id = $reverseTransfer->order_id;
            $refundTransaction->payment_for = 'Reverse Transfer';
            $refundTransaction->payer_id = $order->seller_id ?? 1;
            $refundTransaction->payment_receiver_id = $reverseTransfer->customer_id;
            $refundTransaction->paid_by = $order->seller_is ?? 'admin';
            $refundTransaction->paid_to = 'customer';
            $refundTransaction->payment_method = $reverseTransfer->payment_method;
            $refundTransaction->payment_status = 'paid';
            $refundTransaction->amount = $reverseTransfer->amount;
            $refundTransaction->transaction_type = 'Refund';
            $refundTransaction->refund_id = $reverseTransfer->id;
            $refundTransaction->save();

            Log::info('تم إنشاء سجل المعاملة للحوالة العكسية', [
                'refund_transaction_id' => $refundTransaction->id,
                'reverse_transfer_id' => $reverseTransfer->id,
                'amount' => $reverseTransfer->amount
            ]);

        } catch (Exception $e) {
            Log::error('خطأ في إنشاء سجل المعاملة: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * تسجيل تغيير الحالة
     */
    protected function logStatusChange($reverseTransfer, $status, $notes = null, $previousStatus = null)
    {
        // التحقق من وجود المستخدم قبل استخدام ID
        $userId = null;
        if (auth()->check()) {
            $user = auth()->user();
            if ($user && $user->exists) {
                $userId = $user->id;
            }
        }
        
        \App\Models\ReverseTransferStatus::create([
            'reverse_transfer_id' => $reverseTransfer->id,
            'status' => $status,
            'changed_by' => $userId ? 'admin' : 'system',
            'changed_by_id' => $userId, // null إذا لم يكن المستخدم موجود
            'notes' => $notes,
            'previous_status' => $previousStatus,
        ]);
    }

    /**
     * البحث في الحوالات العكسية
     */
    public function search($filters)
    {
        $query = ReverseTransfer::with(['order', 'customer', 'admin']);

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['payment_gateway'])) {
            $query->where('payment_gateway', $filters['payment_gateway']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('transaction_id', 'like', "%{$search}%")
                  ->orWhere('gateway_transaction_id', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%")
                  ->orWhereHas('order', function ($orderQuery) use ($search) {
                      $orderQuery->where('id', 'like', "%{$search}%");
                  })
                  ->orWhereHas('customer', function ($customerQuery) use ($search) {
                      $customerQuery->where('f_name', 'like', "%{$search}%")
                                   ->orWhere('l_name', 'like', "%{$search}%")
                                   ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['amount_min'])) {
            $query->where('amount', '>=', $filters['amount_min']);
        }

        if (!empty($filters['amount_max'])) {
            $query->where('amount', '<=', $filters['amount_max']);
        }

        if (!empty($filters['original_payment_method'])) {
            $query->where('original_payment_method', $filters['original_payment_method']);
        }

        return $query->orderBy('created_at', 'desc');
    }

    /**
     * الحصول على إحصائيات الحوالات العكسية
     */
    public function getStatistics()
    {
        return [
            'total' => ReverseTransfer::count(),
            'pending' => ReverseTransfer::pending()->count(),
            'approved' => ReverseTransfer::approved()->count(),
            'rejected' => ReverseTransfer::rejected()->count(),
            'processed' => ReverseTransfer::processed()->count(),
            'completed' => ReverseTransfer::completed()->count(),
            'by_gateway' => [
                'myfatoorah' => ReverseTransfer::where('payment_gateway', 'myfatoorah')->count(),
                'tabby' => ReverseTransfer::where('payment_gateway', 'tabby')->count(),
                'tamara' => ReverseTransfer::where('payment_gateway', 'tamara')->count(),
                'manual' => ReverseTransfer::whereNull('payment_gateway')->count(),
            ]
        ];
    }

    /**
     * مراقبة حالة الحوالات العكسية عبر بوابات الدفع
     */
    public function monitorGatewayReverseTransfers()
    {
        try {
            // البحث عن الحوالات العكسية التي في حالة "معالج" عبر البوابة
            $pendingReverseTransfers = ReverseTransfer::where('status', 'approved')
                ->whereNotNull('payment_gateway')
                ->where('gateway_status', 'processing')
                ->get();

            $results = [];
            foreach ($pendingReverseTransfers as $reverseTransfer) {
                $result = $this->checkGatewayReverseTransferStatus($reverseTransfer);
                $results[] = [
                    'reverse_transfer_id' => $reverseTransfer->id,
                    'gateway' => $reverseTransfer->payment_gateway,
                    'result' => $result
                ];
            }

            return $results;

        } catch (Exception $e) {
            Log::error('خطأ في مراقبة الحوالات العكسية: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * فحص حالة الحوالة العكسية عبر بوابة الدفع
     */
    protected function checkGatewayReverseTransferStatus($reverseTransfer)
    {
        try {
            $gatewayService = $this->getGatewayService($reverseTransfer->payment_gateway);
            if (!$gatewayService) {
                throw new Exception('خدمة بوابة الدفع غير متوفرة');
            }

            switch ($reverseTransfer->payment_gateway) {
                case 'myfatoorah':
                    return $this->checkMyFatoorahRefundStatus($reverseTransfer, $gatewayService);
                case 'tabby':
                    return $this->checkTabbyRefundStatus($reverseTransfer, $gatewayService);
                case 'tamara':
                    return $this->checkTamaraRefundStatus($reverseTransfer, $gatewayService);
                default:
                    throw new Exception('بوابة الدفع غير مدعومة');
            }

        } catch (Exception $e) {
            Log::error('خطأ في فحص حالة الحوالة العكسية: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * فحص حالة الاسترداد عبر MyFatoorah
     */
    protected function checkMyFatoorahRefundStatus($reverseTransfer, $gatewayService)
    {
        try {
            if (empty($reverseTransfer->gateway_transaction_id)) {
                throw new Exception('معرف المعاملة غير متوفر');
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('myfatoorah.api_key'),
                'Content-Type' => 'application/json',
            ])->post(config('myfatoorah.live_api_url') . '/v2/GetRefundStatus', [
                'Key' => $reverseTransfer->gateway_transaction_id,
                'KeyType' => 'RefundId'
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                
                if (isset($responseData['IsSuccess']) && $responseData['IsSuccess']) {
                    $refundStatus = $responseData['Data']['RefundStatus'] ?? 'Unknown';
                    
                    // تحديث حالة الحوالة بناءً على استجابة البوابة
                    if ($refundStatus === 'Completed' || $refundStatus === 'Success') {
                        $reverseTransfer->update([
                            'status' => 'completed',
                            'gateway_status' => 'completed',
                            'gateway_response' => $responseData
                        ]);
                        
                        $this->logStatusChange($reverseTransfer, 'completed', 'تم إكمال الحوالة العكسية عبر MyFatoorah');
                        
                        return [
                            'success' => true,
                            'status' => 'completed',
                            'message' => 'تم إكمال الحوالة العكسية بنجاح'
                        ];
                    } elseif ($refundStatus === 'Failed' || $refundStatus === 'Rejected') {
                        $reverseTransfer->update([
                            'gateway_status' => 'failed',
                            'gateway_error_message' => 'فشل في معالجة الحوالة العكسية عبر MyFatoorah'
                        ]);
                        
                        return [
                            'success' => false,
                            'status' => 'failed',
                            'message' => 'فشل في معالجة الحوالة العكسية'
                        ];
                    } else {
                        // الحالة لا تزال معلقة
                        return [
                            'success' => true,
                            'status' => 'processing',
                            'message' => 'الحوالة العكسية قيد المعالجة'
                        ];
                    }
                }
            }

            return [
                'success' => false,
                'status' => 'unknown',
                'message' => 'فشل في الحصول على حالة الحوالة العكسية'
            ];

        } catch (Exception $e) {
            Log::error('خطأ في فحص حالة الاسترداد عبر MyFatoorah: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * فحص حالة الاسترداد عبر Tabby
     */
    protected function checkTabbyRefundStatus($reverseTransfer, $gatewayService)
    {
        try {
            if (empty($reverseTransfer->gateway_transaction_id)) {
                throw new Exception('معرف المعاملة غير متوفر');
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('tabby.api_key'),
                'Content-Type' => 'application/json',
            ])->get(config('tabby.live_api_url') . 'refunds/' . $reverseTransfer->gateway_transaction_id);

            if ($response->successful()) {
                $responseData = $response->json();
                
                $refundStatus = $responseData['status'] ?? 'Unknown';
                
                // تحديث حالة الحوالة بناءً على استجابة البوابة
                if ($refundStatus === 'completed' || $refundStatus === 'success') {
                    $reverseTransfer->update([
                        'status' => 'completed',
                        'gateway_status' => 'completed',
                        'gateway_response' => $responseData
                    ]);
                    
                    $this->logStatusChange($reverseTransfer, 'completed', 'تم إكمال الحوالة العكسية عبر Tabby');
                    
                    return [
                        'success' => true,
                        'status' => 'completed',
                        'message' => 'تم إكمال الحوالة العكسية بنجاح'
                    ];
                } elseif ($refundStatus === 'failed' || $refundStatus === 'rejected') {
                    $reverseTransfer->update([
                        'gateway_status' => 'failed',
                        'gateway_error_message' => 'فشل في معالجة الحوالة العكسية عبر Tabby'
                    ]);
                    
                    return [
                        'success' => false,
                        'status' => 'failed',
                        'message' => 'فشل في معالجة الحوالة العكسية'
                    ];
                } else {
                    // الحالة لا تزال معلقة
                    return [
                        'success' => true,
                        'status' => 'processing',
                        'message' => 'الحوالة العكسية قيد المعالجة'
                    ];
                }
            }

            return [
                'success' => false,
                'status' => 'unknown',
                'message' => 'فشل في الحصول على حالة الحوالة العكسية'
            ];

        } catch (Exception $e) {
            Log::error('خطأ في فحص حالة الاسترداد عبر Tabby: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * فحص حالة الاسترداد عبر Tamara
     */
    protected function checkTamaraRefundStatus($reverseTransfer, $gatewayService)
    {
        try {
            if (empty($reverseTransfer->gateway_transaction_id)) {
                throw new Exception('معرف المعاملة غير متوفر');
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('tamara.api_key'),
                'Content-Type' => 'application/json',
            ])->get((strtolower(config('tamara.mode')) === 'sandbox' ? config('tamara.sandbox_api_url') : config('tamara.live_api_url')) . 'refunds/' . $reverseTransfer->gateway_transaction_id);

            if ($response->successful()) {
                $responseData = $response->json();
                
                $refundStatus = $responseData['status'] ?? 'Unknown';
                
                // تحديث حالة الحوالة بناءً على استجابة البوابة
                if ($refundStatus === 'completed' || $refundStatus === 'success') {
                    $reverseTransfer->update([
                        'status' => 'completed',
                        'gateway_status' => 'completed',
                        'gateway_response' => $responseData
                    ]);
                    
                    $this->logStatusChange($reverseTransfer, 'completed', 'تم إكمال الحوالة العكسية عبر Tamara');
                    
                    return [
                        'success' => true,
                        'status' => 'completed',
                        'message' => 'تم إكمال الحوالة العكسية بنجاح'
                    ];
                } elseif ($refundStatus === 'failed' || $refundStatus === 'rejected') {
                    $reverseTransfer->update([
                        'gateway_status' => 'failed',
                        'gateway_error_message' => 'فشل في معالجة الحوالة العكسية عبر Tamara'
                    ]);
                    
                    return [
                        'success' => false,
                        'status' => 'failed',
                        'message' => 'فشل في معالجة الحوالة العكسية'
                    ];
                } else {
                    // الحالة لا تزال معلقة
                    return [
                        'success' => true,
                        'status' => 'processing',
                        'message' => 'الحوالة العكسية قيد المعالجة'
                    ];
                }
            }

            return [
                'success' => false,
                'status' => 'unknown',
                'message' => 'فشل في الحصول على حالة الحوالة العكسية'
            ];

        } catch (Exception $e) {
            Log::error('خطأ في فحص حالة الاسترداد عبر Tamara: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * التحقق من صحة بيانات الحوالة العكسية
     */
    public function validateReverseTransferData($data)
    {
        $errors = [];
        $warnings = [];

        // التحقق من وجود البيانات المطلوبة
        if (empty($data['amount']) || !is_numeric($data['amount']) || $data['amount'] <= 0) {
            $errors[] = 'المبلغ يجب أن يكون رقماً موجباً';
        }

        if (empty($data['customer_id'])) {
            $errors[] = 'معرف العميل مطلوب';
        }

        if (empty($data['reason'])) {
            $errors[] = 'سبب الحوالة العكسية مطلوب';
        }

        // التحقق من صحة رقم الهاتف (إذا كان متوفراً)
        if (!empty($data['customer_phone'])) {
            $cleanPhone = $this->cleanPhoneNumber($data['customer_phone']);
            if (strlen($cleanPhone) < 9) {
                $errors[] = 'رقم الهاتف غير صحيح';
            }
        }

        // التحقق من المبلغ
        if (!empty($data['amount'])) {
            $amount = (float) $data['amount'];
            
            // التحقق من الحد الأدنى
            $minAmount = config('reverse_transfer.min_amount', 1);
            if ($amount < $minAmount) {
                $errors[] = "المبلغ يجب أن يكون على الأقل {$minAmount} ريال";
            }

            // التحقق من الحد الأقصى
            $maxAmount = config('reverse_transfer.max_amount', 10000);
            if ($amount > $maxAmount) {
                $errors[] = "المبلغ لا يمكن أن يتجاوز {$maxAmount} ريال";
            }
        }

        // التحقق من وجود طلب مطابق (إذا كان رقم الهاتف متوفراً)
        if (!empty($data['customer_phone']) && !empty($data['amount'])) {
            $matchingOrder = $this->findBestMatchingOrder($data['customer_phone'], $data['amount']);
            
            if (!$matchingOrder['success']) {
                if (empty($matchingOrder['suggestions'])) {
                    $errors[] = 'لم يتم العثور على طلب مدفوع بهذا الرقم الهاتف';
                } else {
                    $warnings[] = $matchingOrder['message'];
                    $warnings[] = 'المبالغ المتاحة: ' . implode(', ', array_column($matchingOrder['suggestions'], 'amount'));
                }
            }
        }

        // التحقق من طريقة الدفع
        if (!empty($data['payment_method'])) {
            $validPaymentMethods = ['customer_wallet', 'bank_transfer', 'cash', 'card'];
            if (!in_array($data['payment_method'], $validPaymentMethods)) {
                $errors[] = 'طريقة الدفع غير صحيحة';
            }
        }

        // التحقق من بوابة الدفع
        if (!empty($data['payment_gateway'])) {
            $validGateways = ['myfatoorah', 'tabby', 'tamara'];
            if (!in_array($data['payment_gateway'], $validGateways)) {
                $errors[] = 'بوابة الدفع غير مدعومة';
            }
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * إنشاء حوالة عكسية مع التحقق من صحة البيانات
     */
    public function createReverseTransferWithValidation($data)
    {
        // التحقق من صحة البيانات
        $validation = $this->validateReverseTransferData($data);
        
        if (!$validation['is_valid']) {
            throw new Exception('بيانات غير صحيحة: ' . implode(', ', $validation['errors']));
        }

        // إظهار التحذيرات إذا وجدت
        if (!empty($validation['warnings'])) {
            Log::warning('تحذيرات في بيانات الحوالة العكسية', [
                'warnings' => $validation['warnings'],
                'data' => $data
            ]);
        }

        // إنشاء الحوالة العكسية
        return $this->createReverseTransfer($data);
    }

    /**
     * فحص وتحديث معرفات المعاملات المفقودة
     */
    public function fixMissingTransactionIds()
    {
        try {
            $this->info('بدء فحص معرفات المعاملات المفقودة...');

            // البحث عن الحوالات العكسية بدون معرف معاملة
            $reverseTransfers = ReverseTransfer::whereNull('original_transaction_id')
                ->orWhere('original_transaction_id', '')
                ->get();

            if ($reverseTransfers->isEmpty()) {
                $this->info('لا توجد حوالات عكسية تحتاج إلى إصلاح');
                return ['success' => true, 'message' => 'جميع الحوالات العكسية تحتوي على معرفات معاملات صحيحة'];
            }

            $fixedCount = 0;
            $errors = [];

            foreach ($reverseTransfers as $reverseTransfer) {
                try {
                    $order = $reverseTransfer->order;
                    if (!$order) {
                        $errors[] = "الحوالة العكسية #{$reverseTransfer->id}: الطلب غير موجود";
                        continue;
                    }

                    $originalTransactionId = $this->getOriginalTransactionId($order->id);
                    
                    if (!empty($originalTransactionId)) {
                        $reverseTransfer->update(['original_transaction_id' => $originalTransactionId]);
                        $fixedCount++;
                        
                        Log::info('تم إصلاح معرف المعاملة', [
                            'reverse_transfer_id' => $reverseTransfer->id,
                            'order_id' => $order->id,
                            'new_transaction_id' => $originalTransactionId
                        ]);
                    } else {
                        $errors[] = "الحوالة العكسية #{$reverseTransfer->id}: لا يمكن العثور على معرف معاملة";
                    }

                } catch (Exception $e) {
                    $errors[] = "الحوالة العكسية #{$reverseTransfer->id}: " . $e->getMessage();
                }
            }

            $result = [
                'success' => $fixedCount > 0,
                'message' => "تم إصلاح {$fixedCount} من أصل " . $reverseTransfers->count() . " حوالة عكسية",
                'fixed_count' => $fixedCount,
                'total_count' => $reverseTransfers->count(),
                'errors' => $errors
            ];

            if ($fixedCount > 0) {
                $this->info("✅ " . $result['message']);
            }

            if (!empty($errors)) {
                $this->warn("⚠️ الأخطاء:");
                foreach ($errors as $error) {
                    $this->warn("   - {$error}");
                }
            }

            return $result;

        } catch (Exception $e) {
            Log::error('خطأ في إصلاح معرفات المعاملات: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }



    /**
     * فحص حالة الحوالات العكسية
     */
    public function checkReverseTransfersStatus()
    {
        try {
            $totalReverseTransfers = ReverseTransfer::count();
            $withTransactionId = ReverseTransfer::whereNotNull('original_transaction_id')
                ->where('original_transaction_id', '!=', '')
                ->count();
            $withoutTransactionId = $totalReverseTransfers - $withTransactionId;

            // فحص الحالات
            $pending = ReverseTransfer::where('status', 'pending')->count();
            $approved = ReverseTransfer::where('status', 'approved')->count();
            $processed = ReverseTransfer::where('status', 'processed')->count();
            $completed = ReverseTransfer::where('status', 'completed')->count();
            $failed = ReverseTransfer::where('status', 'failed')->count();

            $status = [
                'total' => $totalReverseTransfers,
                'with_transaction_id' => $withTransactionId,
                'without_transaction_id' => $withoutTransactionId,
                'percentage' => $totalReverseTransfers > 0 ? round(($withTransactionId / $totalReverseTransfers) * 100, 2) : 0,
                'statuses' => [
                    'pending' => $pending,
                    'approved' => $approved,
                    'processed' => $processed,
                    'completed' => $completed,
                    'failed' => $failed
                ]
            ];

            // تسجيل الحالة
            Log::info('حالة الحوالات العكسية', $status);

            return $status;

        } catch (Exception $e) {
            Log::error('خطأ في فحص حالة الحوالات العكسية: ' . $e->getMessage());
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * معالجة الاسترداد المحلي (بديل آمن)
     */
    protected function processLocalRefund($refundData)
    {
        try {
            Log::info('بدء الاسترداد المحلي', [
                'refund_data' => $refundData
            ]);

            // البحث عن العميل
            $customer = DB::table('users')
                ->where('phone', $refundData['customer_phone'])
                ->orWhere('email', $refundData['customer_email'])
                ->first();

            if (!$customer) {
                Log::warning('لم يتم العثور على العميل للاسترداد المحلي', [
                    'phone' => $refundData['customer_phone'],
                    'email' => $refundData['customer_email']
                ]);
                
                return [
                    'success' => false,
                    'error' => 'لم يتم العثور على العميل'
                ];
            }

            // إنشاء فاتورة استرداد مباشرة
            $refundInvoiceId = DB::table('refund_invoices')->insertGetId([
                'customer_id' => $customer->id,
                'order_id' => $refundData['order_id'],
                'amount' => $refundData['amount'],
                'currency' => 'SAR',
                'reason' => $refundData['reason'] ?? 'طلب العميل',
                'status' => 'pending',
                'reference' => 'REF_' . $refundData['order_id'] . '_' . time(),
                'customer_phone' => $refundData['customer_phone'],
                'customer_email' => $refundData['customer_email'],
                'customer_name' => $refundData['customer_name'],
                'payment_method' => 'direct_refund',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // تسجيل عملية الاسترداد
            $refundRecordId = DB::table('refund_records')->insertGetId([
                'refund_invoice_id' => $refundInvoiceId,
                'customer_id' => $customer->id,
                'order_id' => $refundData['order_id'],
                'amount' => $refundData['amount'],
                'reason' => $refundData['reason'] ?? 'طلب العميل',
                'status' => 'pending',
                'refund_method' => 'direct_refund',
                'reference' => 'REF_' . $refundData['order_id'] . '_' . time(),
                'notes' => 'استرداد مباشر للطلب رقم ' . $refundData['order_id'],
                'created_at' => now(),
                'updated_at' => now()
            ]);

            Log::info('تم إنشاء فاتورة الاسترداد المباشر بنجاح', [
                'customer_id' => $customer->id,
                'refund_invoice_id' => $refundInvoiceId,
                'refund_record_id' => $refundRecordId,
                'refund_amount' => $refundData['amount'],
                'order_id' => $refundData['order_id']
            ]);

            return [
                'success' => true,
                'message' => 'تم إنشاء فاتورة استرداد مباشر بنجاح',
                'refund_invoice_id' => $refundInvoiceId,
                'refund_record_id' => $refundRecordId,
                'refund_amount' => $refundData['amount'],
                'refund_method' => 'direct_refund',
                'status' => 'pending',
                'next_step' => 'يجب معالجة الاسترداد يدوياً أو إرساله إلى الحساب المصرفي'
            ];

        } catch (Exception $e) {
            Log::error('خطأ في الاسترداد المحلي: ' . $e->getMessage(), [
                'refund_data' => $refundData,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'خطأ في الاسترداد المحلي: ' . $e->getMessage()
            ];
        }
    }
}