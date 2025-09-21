<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MyFatoorahService
{
    protected $apiKey;
    protected $apiURL;
    protected $mode;
    protected $countryCode;

    public function __construct()
    {
        $this->apiKey = config('myfatoorah.api_key');
        $this->mode = config('myfatoorah.mode');
        $this->apiURL = ($this->mode === 'test') 
            ? config('myfatoorah.test_api_url') 
            : config('myfatoorah.live_api_url');
        $this->countryCode = config('myfatoorah.account_country_code');
        
        // تسجيل معلومات التهيئة (مع إخفاء معظم مفتاح API للأمان)
        $apiKeyStart = substr($this->apiKey, 0, 10);
        $apiKeyEnd = substr($this->apiKey, -10);
        $apiKeyLength = strlen($this->apiKey);
        $maskedApiKey = $apiKeyStart . str_repeat('*', $apiKeyLength - 20) . $apiKeyEnd;
        
        \Log::info('MyFatoorah Service Initialization', [
            'mode' => $this->mode,
            'country' => $this->countryCode,
            'apiUrl' => $this->apiURL,
            'apiKeyPartial' => $maskedApiKey,
            'apiKeyLength' => $apiKeyLength
        ]);
    }

    /**
     * Initialize payment and get payment URL
     *
     * @param float $amount Amount to be paid
     * @param string $customerName Customer name
     * @param string $customerEmail Customer email
     * @param string $customerPhone Customer phone
     * @param string $callbackUrl URL to redirect after payment
     * @param string $errorUrl URL to redirect if payment fails
     * @param string $reference Your system's reference ID
     * @return array Payment URL and invoice ID
     */
    public function initiatePayment($amount, $customerName, $customerEmail, $customerPhone, $callbackUrl, $errorUrl, $reference = '')
    {
        
        // معالجة رقم الهاتف - إزالة رمز البلد السعودي +966 تحديداً
        // ثم إزالة أي أحرف غير رقمية وتقليم الطول
        $formattedPhone = $customerPhone;
        
        // إزالة +966 من بداية الرقم إذا وجد
        if (strpos($formattedPhone, '+966') === 0) {
            $formattedPhone = substr($formattedPhone, 4); // إزالة '+966' من بداية الرقم
        } else if (strpos($formattedPhone, '966') === 0) {
            $formattedPhone = substr($formattedPhone, 3); // إزالة '966' من بداية الرقم
        }
        
        // إزالة أي أحرف غير رقمية (مثل المسافات أو الشرطات)
        $formattedPhone = preg_replace('/[^0-9]/', '', $formattedPhone);
        
        // تقليم الطول إلى 11 رقم كحد أقصى إذا لزم الأمر
        if (strlen($formattedPhone) > 11) {
            $formattedPhone = substr($formattedPhone, -11);
        }
        // تحديد العملة بناءً على رمز البلد
        $currencyIso = 'KWD'; // افتراضي: دينار كويتي
        if ($this->countryCode === 'SA') {
            $currencyIso = 'SAR'; // ريال سعودي
        } elseif ($this->countryCode === 'AE') {
            $currencyIso = 'AED'; // درهم إماراتي
        } elseif ($this->countryCode === 'BH') {
            $currencyIso = 'BHD'; // دينار بحريني
        } elseif ($this->countryCode === 'QA') {
            $currencyIso = 'QAR'; // ريال قطري
        } elseif ($this->countryCode === 'OM') {
            $currencyIso = 'OMR'; // ريال عماني
        }

        $postData = [
            'CustomerName' => $customerName,
            'NotificationOption' => 'LNK',
            'InvoiceValue' => $amount,
            'DisplayCurrencyIso' => $currencyIso,
            'CustomerEmail' => $customerEmail,
            'CallBackUrl' => $callbackUrl,
            'ErrorUrl' => $errorUrl,
            'CustomerMobile' => $formattedPhone, // استخدام رقم الهاتف المعدل
            'Language' => 'AR',
            'CustomerReference' => $reference,
            'SourceInfo' => 'Laravel App',
        ];

        // تسجيل بيانات الطلب للتصحيح
        \Log::info('MyFatoorah API Request', [
            'endpoint' => "{$this->apiURL}/v2/SendPayment",
            'mode' => $this->mode,
            'country' => $this->countryCode,
            'apiUrl' => $this->apiURL,
            'data' => $postData
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->post("{$this->apiURL}/v2/SendPayment", $postData);

            // تسجيل الاستجابة للتصحيح
            $responseData = $response->status() === 401 ? ['error' => 'Authentication failed. Invalid API key or unauthorized access.'] : $response->json();
            
            \Log::info('MyFatoorah API Response', [
                'status' => $response->status(),
                'body' => $responseData
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                if (isset($responseData['IsSuccess']) && $responseData['IsSuccess']) {
                    return [
                        'success' => true,
                        'payment_url' => $responseData['Data']['InvoiceURL'],
                        'invoice_id' => $responseData['Data']['InvoiceId'],
                    ];
                }
            }

            // معالجة خاصة للخطأ 401
            if ($response->status() === 401) {
                return [
                    'success' => false,
                    'error' => 'Authentication failed. Invalid API key or unauthorized access.',
                    'details' => ['status' => 401]
                ];
            }

            // تفاصيل أكثر عن الخطأ
            return [
                'success' => false,
                'error' => $response->json()['ValidationErrors'][0]['Error'] ?? $response->json()['Message'] ?? 'Unknown error occurred',
                'details' => $response->json()
            ];
        } catch (\Exception $e) {
            // تسجيل أي استثناء يحدث أثناء الاتصال
            \Log::error('MyFatoorah API Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => 'Connection error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get payment status by invoice ID
     *
     * @param string $invoiceId MyFatoorah invoice ID
     * @return array Payment status details
     */
    public function getPaymentStatus($invoiceId)
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type' => 'application/json',
        ])->post("{$this->apiURL}/v2/GetPaymentStatus", [
            'Key' => $invoiceId,
            'KeyType' => 'InvoiceId',
        ]);

        if ($response->successful()) {
            $responseData = $response->json();
            if (isset($responseData['IsSuccess']) && $responseData['IsSuccess']) {
                return [
                    'success' => true,
                    'data' => $responseData['Data'],
                ];
            }
        }

        return [
            'success' => false,
            'error' => $response->json()['ValidationErrors'] ?? $response->json()['Message'] ?? 'Unknown error occurred'
        ];
    }

    /**
     * Verify webhook signature
     *
     * @param string $requestBody Raw request body
     * @param string $signature Signature from header
     * @return bool Verification result
     */
    public function verifyWebhook($requestBody, $signature)
    {
        $secret = config('myfatoorah.webhook_secret');
        if (empty($secret)) {
            return false;
        }

        $computedSignature = hash_hmac('sha256', $requestBody, $secret);
        return hash_equals($computedSignature, $signature);
    }
}
