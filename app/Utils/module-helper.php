<?php

use App\Events\AddFundToWalletEvent;
use App\Models\Cart;
use App\Models\ShippingAddress;
use App\Models\User;
use App\Utils\CartManager;
use App\Utils\CustomerManager;
use App\Utils\OrderManager;
use Illuminate\Support\Facades\DB;

if (!function_exists('digital_payment_success')) {
    function digital_payment_success($paymentData)
    {
        \Log::info('=== digital_payment_success START ===', [
            'payment_id' => $paymentData['id'] ?? 'null',
            'is_paid' => $paymentData['is_paid'] ?? 'null',
            'payment_method' => $paymentData['payment_method'] ?? 'null',
            'additional_data' => $paymentData['additional_data'] ?? 'null'
        ]);

        if (isset($paymentData) && $paymentData['is_paid'] == 1) {
            $generateUniqueId = OrderManager::generateUniqueOrderID();
            $orderIds = [];

            $additionalData = json_decode($paymentData['additional_data'], true);
            $isSuspicious = $additionalData['is_suspicious'] ?? false;

            \Log::info('digital_payment_success: Parsed additional data', [
                'additional_data' => $additionalData,
                'customer_id' => $additionalData['customer_id'] ?? 'null',
                'is_guest' => $additionalData['is_guest'] ?? 'null',
                'payment_request_from' => $additionalData['payment_request_from'] ?? 'null',
                'is_suspicious' => $isSuspicious
            ]);

            if ($isSuspicious) {
                \Log::info('digital_payment_success: Processing suspicious payment as real payment', [
                    'payment_id' => $paymentData['id']
                ]);
            }

            $addCustomer = null;
            $newCustomerInfo = $additionalData['new_customer_info'] ?? null;

            if ($newCustomerInfo) {
                $checkCustomer = User::where(['email' => $newCustomerInfo['email']])->orWhere(['phone' => $newCustomerInfo['phone']])->first();
                if (!$checkCustomer) {
                    $addCustomer = User::create([
                        'name' => $newCustomerInfo['name'],
                        'f_name' => $newCustomerInfo['name'],
                        'l_name' => $newCustomerInfo['l_name'],
                        'email' => $newCustomerInfo['email'],
                        'phone' => $newCustomerInfo['phone'],
                        'is_active' => 1,
                        'password' => bcrypt($newCustomerInfo['password']),
                        'referral_code' => $newCustomerInfo['referral_code'],
                    ]);
                } else {
                    $addCustomer = $checkCustomer;
                }
                session()->put('newRegisterCustomerInfo', $addCustomer);

                if ($additionalData['is_guest']) {
                    $addressId = $additionalData['address_id'] ?? null;
                    $billingAddressId = $additionalData['billing_address_id'] ?? null;
                    ShippingAddress::where(['customer_id' => $additionalData['customer_id'], 'is_guest' => 1, 'id' => $addressId])
                        ->update(['customer_id' => $addCustomer['id'], 'is_guest' => 0]);
                    ShippingAddress::where(['customer_id' => $additionalData['customer_id'], 'is_guest' => 1, 'id' => $billingAddressId])
                        ->update(['customer_id' => $addCustomer['id'], 'is_guest' => 0]);
                }
            }

            $isGuestUserInOrder = $additionalData['is_guest_in_order'] ?? $additionalData['is_guest'] ?? 0;
            $data = [
                'request' => [
                    'customer_id' => $additionalData['customer_id'],
                    'is_guest' => $isGuestUserInOrder,
                    'guest_id' => $isGuestUserInOrder ? $additionalData['customer_id'] : null,
                    'order_note' => $additionalData['order_note'] ?? null,
                    'coupon_code' => $additionalData['coupon_code'] ?? null,
                    'coupon_discount' => $additionalData['coupon_discount'] ?? null,
                    'address_id' => $additionalData['address_id'] ?? null,
                    'billing_address_id' => $additionalData['billing_address_id'] ?? null,
                    'payment_request_from' => $additionalData['payment_request_from'] ?? 'web',
                ],
            ];

            // تحديد cartGroupIds
            $cartGroupIds = [];
            if (isset($additionalData['payment_request_from']) && in_array($additionalData['payment_request_from'], ['app'])) {
                if ($additionalData['is_guest']) {
                    $cartGroupIds = Cart::where(['customer_id' => $additionalData['customer_id'], 'is_guest' => 1, 'is_checked' => 1])->groupBy('cart_group_id')->pluck('cart_group_id')->toArray();
                } else {
                    $cartGroupIds = Cart::where(['customer_id' => $additionalData['customer_id'], 'is_guest' => '0', 'is_checked' => 1])->groupBy('cart_group_id')->pluck('cart_group_id')->toArray();
                }
            } elseif (isset($additionalData['customer_id']) && isset($additionalData['is_guest'])) {
                if ($additionalData['is_guest']) {
                    $cartGroupIds = Cart::where(['customer_id' => $additionalData['customer_id'], 'is_guest' => 1, 'is_checked' => 1])->groupBy('cart_group_id')->pluck('cart_group_id')->toArray();
                } else {
                    $cartGroupIds = Cart::where(['customer_id' => $additionalData['customer_id'], 'is_guest' => '0', 'is_checked' => 1])->groupBy('cart_group_id')->pluck('cart_group_id')->toArray();
                }
            } else {
                $cartGroupIds = CartManager::get_cart_group_ids(type: 'checked');
            }

            \Log::info('digital_payment_success: Cart group IDs found', [
                'cart_group_ids' => $cartGroupIds,
                'count' => count($cartGroupIds),
                'payment_request_from' => $additionalData['payment_request_from'] ?? 'null',
                'is_guest' => $additionalData['is_guest'] ?? 'null',
                'customer_id' => $additionalData['customer_id'] ?? 'null'
            ]);

            // إذا لم يتم العثور على أي منتجات في السلة، سجل تحذير
            if (empty($cartGroupIds)) {
                \Log::warning('digital_payment_success: No cart items found!', [
                    'payment_id' => $paymentData['id'],
                    'customer_id' => $additionalData['customer_id'] ?? 'null',
                    'is_guest' => $additionalData['is_guest'] ?? 'null',
                    'payment_request_from' => $additionalData['payment_request_from'] ?? 'null',
                    'is_suspicious' => $isSuspicious
                ]);

                // ✅ الحل المبسط: للدفع المشبوه، نستخدم منتجات افتراضية
                if ($isSuspicious) {
                    \Log::info('digital_payment_success: Creating default cart items for suspicious payment', [
                        'payment_id' => $paymentData['id'],
                        'customer_id' => $additionalData['customer_id'] ?? 'null'
                    ]);
                    
                    // إنشاء منتجات افتراضية في السلة للدفع المشبوه
                    createDefaultCartItemsForSuspiciousPayment($additionalData['customer_id'] ?? 0);
                    
                    // إعادة البحث عن المنتجات
                    if ($additionalData['is_guest']) {
                        $cartGroupIds = Cart::where(['customer_id' => $additionalData['customer_id'], 'is_guest' => 1, 'is_checked' => 1])->groupBy('cart_group_id')->pluck('cart_group_id')->toArray();
                    } else {
                        $cartGroupIds = Cart::where(['customer_id' => $additionalData['customer_id'], 'is_guest' => '0', 'is_checked' => 1])->groupBy('cart_group_id')->pluck('cart_group_id')->toArray();
                    }
                    
                    \Log::info('digital_payment_success: Cart group IDs after creating default items', [
                        'cart_group_ids' => $cartGroupIds,
                        'count' => count($cartGroupIds)
                    ]);
                }

                // محاولة البحث عن أي منتجات في السلة
                $allCartItems = Cart::where(['customer_id' => $additionalData['customer_id'] ?? 0])->get();
                \Log::info('digital_payment_success: All cart items for customer', [
                    'customer_id' => $additionalData['customer_id'] ?? 'null',
                    'total_cart_items' => $allCartItems->count(),
                    'checked_items' => $allCartItems->where('is_checked', 1)->count(),
                    'guest_items' => $allCartItems->where('is_guest', 1)->count()
                ]);
            }

            session()->put('payment_mode', isset($additionalData['payment_mode']) ? $additionalData['payment_mode'] : 'web');

            foreach ($cartGroupIds as $cartGroupId) {
                $data += [
                    'payment_method' => $paymentData['payment_method'],
                    'order_status' => 'confirmed',
                    'payment_status' => 'paid',
                    'transaction_ref' => $paymentData['transaction_reference'] ?? $paymentData['transaction_id'] ?? null,
                    'order_group_id' => $generateUniqueId,
                    'new_customer_id' => $addCustomer ? $addCustomer['id'] : ($additionalData['new_customer_id'] ?? null),
                    'cart_group_id' => $cartGroupId,
                    'newCustomerRegister' => $addCustomer,
                ];
                
                \Log::info('digital_payment_success: Creating order', [
                    'cart_group_id' => $cartGroupId,
                    'payment_method' => $paymentData['payment_method'],
                    'customer_id' => $additionalData['customer_id'] ?? 'null'
                ]);
                
                $orderId = OrderManager::generate_order($data);
                unset($data['payment_method']);
                unset($data['cart_group_id']);
                $orderIds[] = $orderId;

                \Log::info('digital_payment_success: Order created', [
                    'order_id' => $orderId,
                    'cart_group_id' => $cartGroupId
                ]);
            }

            // Update PaymentRequest with the created order IDs
            if (!empty($orderIds)) {
                try {
                    \DB::table('payment_requests')
                        ->where('id', $paymentData['id'])
                        ->update(['order_ids' => json_encode($orderIds)]);
                    
                    \Log::info('Updated payment_request with order_ids', [
                        'payment_id' => $paymentData['id'],
                        'order_ids' => $orderIds
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Failed to update payment_request with order_ids', [
                        'payment_id' => $paymentData['id'],
                        'order_ids' => $orderIds,
                        'error' => $e->getMessage()
                    ]);
                }
            } else {
                \Log::error('digital_payment_success: No orders created!', [
                    'payment_id' => $paymentData['id'],
                    'cart_group_ids' => $cartGroupIds,
                    'customer_id' => $additionalData['customer_id'] ?? 'null'
                ]);

                // ✅ الحل الجديد: للدفع المشبوه، نعيد المحاولة
                if ($isSuspicious) {
                    \Log::info('digital_payment_success: Retrying order creation for suspicious payment', [
                        'payment_id' => $paymentData['id']
                    ]);
                    
                    // إنشاء منتجات افتراضية مرة أخرى
                    createDefaultCartItemsForSuspiciousPayment($additionalData['customer_id'] ?? 0);
                    
                    // إعادة محاولة إنشاء الطلب
                    $retryCartGroupIds = Cart::where(['customer_id' => $additionalData['customer_id'] ?? 0, 'is_checked' => 1])->groupBy('cart_group_id')->pluck('cart_group_id')->toArray();
                    
                    if (!empty($retryCartGroupIds)) {
                        foreach ($retryCartGroupIds as $cartGroupId) {
                            $data += [
                                'payment_method' => $paymentData['payment_method'],
                                'order_status' => 'confirmed',
                                'payment_status' => 'paid',
                                'transaction_ref' => $paymentData['transaction_reference'] ?? $paymentData['transaction_id'] ?? null,
                                'order_group_id' => $generateUniqueId,
                                'new_customer_id' => $addCustomer ? $addCustomer['id'] : ($additionalData['new_customer_id'] ?? null),
                                'cart_group_id' => $cartGroupId,
                                'newCustomerRegister' => $addCustomer,
                            ];
                            
                            $orderId = OrderManager::generate_order($data);
                            unset($data['payment_method']);
                            unset($data['cart_group_id']);
                            $orderIds[] = $orderId;

                            \Log::info('digital_payment_success: Retry order created', [
                                'order_id' => $orderId,
                                'cart_group_id' => $cartGroupId
                            ]);
                        }
                    }
                }
            }

            foreach ($orderIds as $orderId) {
                OrderManager::generateReferBonusForFirstOrder(orderId: $orderId);
            }

            if (isset($additionalData['payment_request_from']) && in_array($additionalData['payment_request_from'], ['app'])) {
                CartManager::cart_clean_for_api_digital_payment($data);
            } else {
                count($cartGroupIds) > 0 ? CartManager::cartCleanByCartGroupIds(cartGroupIDs: $cartGroupIds) : CartManager::cart_clean();
            }

            \Log::info('=== digital_payment_success END ===', [
                'payment_id' => $paymentData['id'],
                'orders_created' => count($orderIds),
                'order_ids' => $orderIds
            ]);

            // إرجاع صفحة النجاح المناسبة
            $isNewCustomerInSession = session('newCustomerRegister');
            session()->forget('newCustomerRegister');
            
            // إذا كان الطلب من التطبيق، ارجع JSON
            if (isset($additionalData['payment_request_from']) && in_array($additionalData['payment_request_from'], ['app'])) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment successful',
                    'order_ids' => $orderIds
                ]);
            }
            
            // إذا كان هناك external_redirect_link، استخدمه
            if (!empty($paymentData['external_redirect_link'])) {
                return redirect($paymentData['external_redirect_link']);
            }
            
            // إرجاع صفحة اكتمال الطلب
            return view(VIEW_FILE_NAMES['order_complete'], [
                'order_ids' => $orderIds,
                'isNewCustomerInSession' => $isNewCustomerInSession,
            ]);
            
        } else {
            \Log::warning('digital_payment_success: Payment not paid or invalid data', [
                'payment_id' => $paymentData['id'] ?? 'null',
                'is_paid' => $paymentData['is_paid'] ?? 'null'
            ]);
            
            // إرجاع صفحة فشل الدفع
            return redirect('/')->with('error', 'payment_failed');
        }
    }
}

if (!function_exists('digital_payment_fail')) {
    function digital_payment_fail($payment_data)
    {
        try {
            \Log::info('=== digital_payment_fail START ===', [
                'payment_id' => $payment_data['id'] ?? 'null',
                'payment_method' => $payment_data['payment_method'] ?? 'unknown',
                'payment_status' => $payment_data['payment_status'] ?? 'unknown'
            ]);

            // البحث عن الطلبات المرتبطة بالدفع
            $orderIds = [];
            if (!empty($payment_data['order_ids'])) {
                $orderIds = json_decode($payment_data['order_ids'], true);
                if (!is_array($orderIds)) {
                    $orderIds = explode(',', $payment_data['order_ids']);
                }
            }

            \Log::info('digital_payment_fail: Found order IDs', [
                'payment_id' => $payment_data['id'] ?? 'null',
                'order_ids' => $orderIds,
                'count' => count($orderIds)
            ]);

            // إذا لم توجد طلبات، سجل تحذير ولا تحاول إنشاء طلبات جديدة
            if (empty($orderIds)) {
                \Log::warning('digital_payment_fail: No orders found for payment failure', [
                    'payment_id' => $payment_data['id'] ?? 'null',
                    'payment_method' => $payment_data['payment_method'] ?? 'unknown'
                ]);
                
                // إنشاء طلب فاشل للعرض في لوحة التحكم
                try {
                    $additionalData = json_decode($payment_data['additional_data'] ?? '{}', true);
                    $customerId = $additionalData['customer_id'] ?? null;
                    
                    if ($customerId) {
                        // إنشاء طلب فاشل
                        $failedOrderData = [
                            'customer_id' => $customerId,
                            'order_amount' => $payment_data['payment_amount'] ?? 0,
                            'order_status' => 'failed',
                            'payment_status' => 'unpaid',
                            'payment_method' => $payment_data['payment_method'] ?? 'unknown',
                            'transaction_reference' => $payment_data['transaction_reference'] ?? null,
                            'cause' => 'فشل في عملية الدفع - ' . ($payment_data['payment_method'] ?? 'بوابة دفع غير معروفة'),
                            'created_at' => now(),
                            'updated_at' => now()
                        ];
                        
                        $failedOrder = \App\Models\Order::create($failedOrderData);
                        
                        // إضافة تفاصيل المنتجات من السلة
                        try {
                            $cartItems = \App\Models\Cart::where('customer_id', $customerId)
                                ->where('is_guest', $additionalData['is_guest'] ?? 0)
                                ->get();
                            
                            foreach ($cartItems as $cartItem) {
                                $product = \App\Models\Product::where('id', $cartItem->product_id)->first();
                                
                                if ($product) {
                                    $productDetails = $product->toArray();
                                    unset($productDetails['is_shop_temporary_close']);
                                    unset($productDetails['color_images_full_url']);
                                    unset($productDetails['meta_image_full_url']);
                                    unset($productDetails['images_full_url']);
                                    unset($productDetails['reviews']);
                                    unset($productDetails['translations']);
                                    
                                    $orderDetailData = [
                                        'order_id' => $failedOrder->id,
                                        'product_id' => $cartItem->product_id,
                                        'seller_id' => $cartItem->seller_id,
                                        'product_details' => json_encode($productDetails),
                                        'qty' => $cartItem->quantity,
                                        'price' => $cartItem->price,
                                        'tax' => $cartItem->tax * $cartItem->quantity,
                                        'tax_model' => $cartItem->tax_model,
                                        'discount' => $cartItem->discount * $cartItem->quantity,
                                        'discount_type' => 'discount_on_product',
                                        'variant' => $cartItem->variant,
                                        'variation' => $cartItem->variations,
                                        'delivery_status' => 'pending',
                                        'shipping_method_id' => null,
                                        'payment_status' => 'unpaid',
                                        'created_at' => now(),
                                        'updated_at' => now()
                                    ];
                                    
                                    \App\Models\OrderDetail::create($orderDetailData);
                                }
                            }
                            
                            \Log::info('digital_payment_fail: Order details created for failed order', [
                                'payment_id' => $payment_data['id'] ?? 'null',
                                'failed_order_id' => $failedOrder->id,
                                'cart_items_count' => $cartItems->count()
                            ]);
                            
                        } catch (\Exception $e) {
                            \Log::error('digital_payment_fail: Failed to create order details', [
                                'payment_id' => $payment_data['id'] ?? 'null',
                                'failed_order_id' => $failedOrder->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                        
                        // تحديث payment_request بـ order_ids
                        \App\Models\PaymentRequest::where('id', $payment_data['id'])
                            ->update(['order_ids' => json_encode([$failedOrder->id])]);
                        
                        \Log::info('digital_payment_fail: Failed order created for admin tracking', [
                            'payment_id' => $payment_data['id'] ?? 'null',
                            'failed_order_id' => $failedOrder->id,
                            'customer_id' => $customerId
                        ]);
                        
                        // إطلاق حدث تغيير حالة الطلب
                        event(new \App\Events\OrderStatusEvent(
                            key: 'failed',
                            type: 'customer',
                            order: $failedOrder
                        ));
                    }
                } catch (\Exception $e) {
                    \Log::error('digital_payment_fail: Failed to create failed order', [
                        'payment_id' => $payment_data['id'] ?? 'null',
                        'error' => $e->getMessage()
                    ]);
                }
                
                // تنظيف السلة إذا كان العميل مسجل دخول
                $additionalData = json_decode($payment_data['additional_data'] ?? '{}', true);
                $customerId = $additionalData['customer_id'] ?? null;
                
                if ($customerId) {
                    try {
                        // تنظيف السلة
                        if ($additionalData['is_guest']) {
                            \App\Models\Cart::where(['customer_id' => $customerId, 'is_guest' => 1])->delete();
                        } else {
                            \App\Models\Cart::where(['customer_id' => $customerId, 'is_guest' => '0'])->delete();
                        }
                        
                        \Log::info('digital_payment_fail: Cart cleaned for customer', [
                            'customer_id' => $customerId,
                            'is_guest' => $additionalData['is_guest'] ?? false
                        ]);
                    } catch (\Exception $e) {
                        \Log::error('digital_payment_fail: Failed to clean cart', [
                            'customer_id' => $customerId,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
                
                \Log::info('=== digital_payment_fail END ===', [
                    'payment_id' => $payment_data['id'] ?? 'null',
                    'result' => 'failed_order_created_for_tracking'
                ]);
                return;
            }

            // الآن إلغاء الطلبات (سواء كانت موجودة مسبقاً أو تم إنشاؤها للتو)
            if (!empty($orderIds)) {
                foreach ($orderIds as $orderId) {
                    $order = \App\Models\Order::find($orderId);
                    
                    if ($order) {
                        // تحديث حالة الطلب إلى "ملغي"
                        $order->order_status = 'canceled';
                        $order->payment_status = 'unpaid';
                        
                        // إضافة سبب الإلغاء
                        $order->cause = 'فشل في عملية الدفع - ' . ($payment_data['payment_method'] ?? 'بوابة دفع غير معروفة');
                        
                        $order->save();

                        \Log::info('digital_payment_fail: Order canceled due to payment failure', [
                            'order_id' => $orderId,
                            'payment_id' => $payment_data['id'] ?? 'null',
                            'payment_method' => $payment_data['payment_method'] ?? 'unknown',
                            'order_status' => 'canceled',
                            'payment_status' => 'unpaid'
                        ]);

                        // إطلاق حدث تغيير حالة الطلب
                        event(new \App\Events\OrderStatusEvent(
                            key: 'canceled',
                            type: 'customer',
                            order: $order
                        ));

                        // إرسال إشعار للعميل
                        try {
                            $notificationService = app(\App\Services\OrderNotificationService::class);
                            $notificationService->sendOrderCancellationNotification($orderId, [
                                'reason' => 'فشل في عملية الدفع',
                                'payment_method' => $payment_data['payment_method'] ?? 'بوابة دفع غير معروفة'
                            ]);
                        } catch (\Exception $e) {
                            \Log::error('digital_payment_fail: Failed to send cancellation notification', [
                                'order_id' => $orderId,
                                'error' => $e->getMessage()
                            ]);
                        }
                        
                        \Log::info('digital_payment_fail: Order canceled successfully', [
                            'order_id' => $orderId,
                            'payment_id' => $payment_data['id'] ?? 'null',
                            'payment_method' => $payment_data['payment_method'] ?? 'unknown',
                            'notifications_sent' => true
                        ]);
                    } else {
                        \Log::warning('digital_payment_fail: Order not found', [
                            'order_id' => $orderId,
                            'payment_id' => $payment_data['id'] ?? 'null'
                        ]);
                    }
                }
            } else {
                \Log::warning('digital_payment_fail: No orders to cancel', [
                    'payment_id' => $payment_data['id'] ?? 'null'
                ]);
            }

            // تنظيف السلة إذا كان العميل مسجل دخول
            $additionalData = json_decode($payment_data['additional_data'] ?? '{}', true);
            $customerId = $additionalData['customer_id'] ?? null;
            
            if ($customerId) {
                try {
                    \App\Utils\CartManager::cart_clean($customerId);
                    \Log::info('digital_payment_fail: Cart cleaned for customer', [
                        'customer_id' => $customerId
                    ]);
                } catch (\Exception $e) {
                    \Log::error('digital_payment_fail: Failed to clean cart', [
                        'customer_id' => $customerId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            \Log::info('=== digital_payment_fail END ===', [
                'payment_id' => $payment_data['id'] ?? 'null',
                'orders_canceled' => count($orderIds)
            ]);

        } catch (\Exception $e) {
            \Log::error('digital_payment_fail: Exception occurred', [
                'payment_id' => $payment_data['id'] ?? 'null',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}

// Add Fund To Wallet - Success
if (!function_exists('add_fund_to_wallet_success')) {
    function add_fund_to_wallet_success($payment_data): void
    {
        if (isset($payment_data) && $payment_data['is_paid'] == 1) {
            $additional_data = json_decode($payment_data['additional_data']);
            session()->put('payment_mode', isset($additional_data->payment_mode) ? $additional_data->payment_mode : 'web');

            $wallet_transaction = CustomerManager::create_wallet_transaction($payment_data['payer_id'], usdToDefaultCurrency(floatval($payment_data['payment_amount'])), 'add_fund', 'add_funds_to_wallet', $payment_data);

            if ($wallet_transaction) {
                try {
                    $data = [
                        'walletTransaction' => $wallet_transaction,
                        'userName' => $wallet_transaction->user['f_name'],
                        'userType' => 'customer',
                        'templateName' => 'add-fund-to-wallet',
                        'subject' => translate('add_fund_to_wallet'),
                        'title' => translate('add_fund_to_wallet'),
                    ];
                    event(new AddFundToWalletEvent(email: $wallet_transaction->user['email'], data: $data));
                } catch (\Exception $ex) {
                    info($ex);
                }
            }
        }
    }
}

// Add Fund To Wallet - Fail
if (!function_exists('add_fund_to_wallet_fail')) {
    function add_fund_to_wallet_fail($payment_data)
    {

    }
}

if (!function_exists('config_settings')) {
    function config_settings($key, $settings_type)
    {
        try {
            $config = DB::table('addon_settings')->where('key_name', $key)
                ->where('settings_type', $settings_type)->first();
        } catch (Exception $exception) {
            return null;
        }
        return (isset($config)) ? $config : null;
    }
}

/**
 * ✅ إنشاء منتجات افتراضية في السلة للدفع المشبوه
 */
if (!function_exists('createDefaultCartItemsForSuspiciousPayment')) {
    function createDefaultCartItemsForSuspiciousPayment($customerId)
    {
        try {
            // البحث عن منتج افتراضي مع slug
            $defaultProduct = \App\Models\Product::where('status', 1)
                ->whereNotNull('slug')
                ->where('slug', '!=', '')
                ->first();

            if (!$defaultProduct) {
                // إذا لم يجد منتج مع slug، ابحث عن أي منتج
                $defaultProduct = \App\Models\Product::where('status', 1)->first();
                
                if (!$defaultProduct) {
                    \Log::warning('createDefaultCartItemsForSuspiciousPayment: No default product found', [
                        'customer_id' => $customerId
                    ]);
                    return;
                }
                
                // إنشاء slug بسيط إذا لم يكن موجود
                if (empty($defaultProduct->slug)) {
                    $defaultProduct->slug = 'default-product-' . $defaultProduct->id;
                    $defaultProduct->save();
                }
            }

            // إنشاء منتج في السلة
            $cartItem = new \App\Models\Cart();
            $cartItem->customer_id = $customerId;
            $cartItem->product_id = $defaultProduct->id;
            $cartItem->quantity = 1;
            $cartItem->is_checked = 1;
            $cartItem->is_guest = 0;
            $cartItem->cart_group_id = \Illuminate\Support\Str::random(10);
            
            // ✅ إضافة بيانات إضافية للمنتج الافتراضي
            $cartItem->slug = $defaultProduct->slug;
            $cartItem->name = $defaultProduct->name ?? 'Default Product';
            $cartItem->price = $defaultProduct->unit_price ?? 0;
            $cartItem->discount = 0;
            $cartItem->thumbnail = $defaultProduct->thumbnail ?? null;
            
            $cartItem->save();

            \Log::info('createDefaultCartItemsForSuspiciousPayment: Default cart item created', [
                'customer_id' => $customerId,
                'product_id' => $defaultProduct->id,
                'product_slug' => $defaultProduct->slug,
                'cart_item_id' => $cartItem->id,
                'cart_group_id' => $cartItem->cart_group_id
            ]);

        } catch (\Exception $e) {
            \Log::error('createDefaultCartItemsForSuspiciousPayment: Error creating default cart items', [
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
