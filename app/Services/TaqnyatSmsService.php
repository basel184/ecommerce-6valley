<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TaqnyatSmsService
{
    protected $apiKey;
    protected $sender;
    protected $baseUrl;
    protected $whatsappBaseUrl;

    public function __construct()
    {
        $this->apiKey = config('sms.taqnyat.api_key');
        $this->sender = config('sms.taqnyat.sender');
        $this->baseUrl = 'https://api.taqnyat.sa/v1';
        // WhatsApp uses different base URL according to documentation
        $this->whatsappBaseUrl = 'https://api.taqnyat.sa/wa/v2/';
    }

    /**
     * Send OTP code via SMS
     * 
     * @param string $phoneNumber Saudi phone number
     * @param string $otp The OTP code
     * @return array Response with status and message
     */
    public function sendOtp($phoneNumber, $otp = null)
    {
        // Format the phone number to Saudi format
        $phoneNumber = $this->formatSaudiPhoneNumber($phoneNumber);
        
        // Generate OTP if not provided
        if (!$otp) {
            $otp = $this->generateOtp();
        }
        
        // Store OTP in cache with 5 minutes expiry
        $this->storeOtp($phoneNumber, $otp);
        
        // Prepare the message
        $message = "رمز التحقق الخاص بك من BERN هو: {$otp}. يرجى عدم مشاركة هذا الرمز مع أي شخص.";
        
        return $this->sendSms($phoneNumber, $message);
    }
    
    /**
     * Send a SMS message via Taqnyat API
     * 
     * @param string $phoneNumber Saudi phone number
     * @param string $message The message to send
     * @return array Response with status and message
     */
    public function sendSms($phoneNumber, $message)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/messages', [
                'recipients' => [$phoneNumber],
                'sender' => $this->sender,
                'body' => $message,
            ]);
            
            $responseData = $response->json();
            
            Log::info('Taqnyat SMS Response', [
                'phone' => $this->maskPhoneNumber($phoneNumber),
                'status' => $response->status(),
                'response' => $responseData
            ]);
            
            if ($response->successful() && isset($responseData['statusCode']) && $responseData['statusCode'] == 201) {
                return [
                    'success' => true,
                    'message' => 'OTP sent successfully'
                ];
            }
            
            return [
                'success' => false,
                'message' => $responseData['message'] ?? 'Failed to send SMS',
                'details' => $responseData
            ];
        } catch (\Exception $e) {
            Log::error('Taqnyat SMS Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Error sending SMS: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Verify OTP code
     * 
     * @param string $phoneNumber Saudi phone number
     * @param string $otp The OTP code to verify
     * @return bool Whether the OTP is valid
     */
    public function verifyOtp($phoneNumber, $otp)
    {
        // Format the phone number to Saudi format
        $phoneNumber = $this->formatSaudiPhoneNumber($phoneNumber);
        
        // Get stored OTP from cache
        $storedOtp = Cache::get('otp_' . $phoneNumber);
        
        if (!$storedOtp) {
            return false;
        }
        
        // Compare the OTPs
        return $otp == $storedOtp;
    }
    
    /**
     * Generate a random OTP code
     * 
     * @param int $digits Number of digits for OTP
     * @return string The generated OTP
     */
    protected function generateOtp($digits = 4)
    {
        // Generate a random number with specified number of digits
        $min = pow(10, $digits - 1);
        $max = pow(10, $digits) - 1;
        return (string) random_int($min, $max);
    }
    
    /**
     * Store OTP in cache
     * 
     * @param string $phoneNumber Saudi phone number
     * @param string $otp The OTP code to store
     * @param int $minutes Minutes before the OTP expires
     * @return void
     */
    protected function storeOtp($phoneNumber, $otp, $minutes = 5)
    {
        Cache::put('otp_' . $phoneNumber, $otp, now()->addMinutes($minutes));
        
        // Also store timestamp for resend limitation
        Cache::put('otp_sent_' . $phoneNumber, now()->timestamp, now()->addMinutes($minutes));
    }
    
    /**
     * Check if user can resend OTP
     * 
     * @param string $phoneNumber Saudi phone number
     * @param int $waitSeconds Seconds to wait before allowing resend
     * @return array Status and time remaining if applicable
     */
    public function canResendOtp($phoneNumber, $waitSeconds = 60)
    {
        // Format the phone number to Saudi format
        $phoneNumber = $this->formatSaudiPhoneNumber($phoneNumber);
        
        $lastSent = Cache::get('otp_sent_' . $phoneNumber);
        
        if (!$lastSent) {
            return [
                'can_resend' => true,
                'wait_seconds' => 0
            ];
        }
        
        $timePassed = now()->timestamp - $lastSent;
        $timeRemaining = max(0, $waitSeconds - $timePassed);
        
        return [
            'can_resend' => $timeRemaining <= 0,
            'wait_seconds' => $timeRemaining
        ];
    }
    
    /**
     * Format phone number to Saudi format (966xxxxxxxxx)
     * 
     * @param string $phoneNumber Input phone number
     * @return string Formatted Saudi phone number
     */
    public function formatSaudiPhoneNumber($phoneNumber)
    {
        // Remove any non-digit characters
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // If starts with 0, remove it
        if (substr($phoneNumber, 0, 1) == '0') {
            $phoneNumber = substr($phoneNumber, 1);
        }
        
        // If doesn't start with 966, add it
        if (substr($phoneNumber, 0, 3) != '966') {
            $phoneNumber = '966' . $phoneNumber;
        }
        
        // Ensure the number is valid Saudi format (966 + 9 digits)
        if (strlen($phoneNumber) != 12) {
            // Try to correct by taking last 9 digits and adding 966
            $phoneNumber = '966' . substr($phoneNumber, -9);
        }
        
        return $phoneNumber;
    }
    
    /**
     * Validate if a phone number is a valid Saudi number
     * 
     * @param string $phoneNumber Phone number to validate
     * @return bool Whether the phone number is valid
     */
    public function isValidSaudiNumber($phoneNumber)
    {
        // Remove any non-digit characters
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Check if starts with 05 (Saudi format) or 9665
        if (substr($phoneNumber, 0, 2) == '05' && strlen($phoneNumber) == 10) {
            return true;
        }
        
        // Check international format with 966
        if (substr($phoneNumber, 0, 4) == '9665' && strlen($phoneNumber) == 12) {
            return true;
        }
        
        // Check if it's 9 digits (mobile number without prefix)
        if (strlen($phoneNumber) == 9 && substr($phoneNumber, 0, 1) == '5') {
            return true;
        }
        
        return false;
    }
    
    /**
     * Mask phone number for logging
     * 
     * @param string $phoneNumber Phone number to mask
     * @return string Masked phone number
     */
    protected function maskPhoneNumber($phoneNumber)
    {
        if (strlen($phoneNumber) < 8) {
            return '****' . substr($phoneNumber, -4);
        }
        
        return substr($phoneNumber, 0, 4) . '****' . substr($phoneNumber, -4);
    }
    
    /**
     * Send WhatsApp message via Taqnyat API
     * According to official documentation, all conversations must start with template messages
     * 
     * @param string $phoneNumber Saudi phone number
     * @param string $message The message to send
     * @param string $templateName Optional template name for template messages
     * @param bool $isConversation Whether this is a conversation message (requires active session)
     * @param bool $allowFallback Whether to fallback to SMS on failure
     * @return array Response with status and message
     */
    public function sendWhatsApp($phoneNumber, $message, $templateName = null, $isConversation = false, $allowFallback = true)
    {
        // Check if WhatsApp is available before trying
        if (!$this->isWhatsAppAvailable()) {
            Log::info('WhatsApp marked as unavailable, using SMS fallback directly');
            
            if ($allowFallback) {
                return $this->sendSms($phoneNumber, "📱 " . $message);
            }
            
            return [
                'success' => false,
                'message' => 'WhatsApp unavailable and fallback disabled'
            ];
        }
        
        // Format phone number for international format (required by WhatsApp API)
        $formattedPhone = $this->formatSaudiPhoneNumber($phoneNumber);
        
        try {
            // Determine if we should send template or conversation message
            if ($isConversation) {
                return $this->sendWhatsAppConversationMessage($formattedPhone, $message, $phoneNumber, $allowFallback);
            } else {
                return $this->sendWhatsAppTemplateMessage($formattedPhone, $message, $templateName, $phoneNumber, $allowFallback);
            }
            
        } catch (\Exception $e) {
            Log::error('Taqnyat WhatsApp Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Mark as unavailable and fallback to SMS
            $this->markWhatsAppUnavailable();
            Log::info('WhatsApp failed, using SMS as fallback');
            
            if ($allowFallback) {
                return $this->sendSms($phoneNumber, "📱 " . $message);
            }
            
            return [
                'success' => false,
                'message' => 'WhatsApp exception and fallback disabled'
            ];
        }
    }
    
    /**
     * Send WhatsApp template message (required to start conversation)
     * According to Taqnyat documentation
     */
    protected function sendWhatsAppTemplateMessage($phoneNumber, $message, $templateName = null, $originalPhone = null, $allowFallback = true)
    {
        // Use default template if none provided
        if (!$templateName) {
            $templateName = config('sms.taqnyat.whatsapp_default_template', 'order_notification');
        }
        
        $payload = [
            'to' => [$phoneNumber],
            'template' => [
                'name' => $templateName
            ],
            'code' => 'ar' // Language code
        ];
        
        // If template supports dynamic content, add it
        if ($message) {
            $payload['template']['components'] = [
                [
                    'type' => 'text',
                    'text' => $message
                ]
            ];
        }
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post($this->whatsappBaseUrl . 'messages', $payload);
        
        return $this->handleWhatsAppResponse($response, $phoneNumber, 'template', $originalPhone ?: $phoneNumber, $message, $allowFallback);
    }
    
    /**
     * Send WhatsApp conversation message (only works during active 24h session)
     * According to Taqnyat documentation
     */
    protected function sendWhatsAppConversationMessage($phoneNumber, $message, $originalPhone = null, $allowFallback = true)
    {
        $payload = [
            'to' => [$phoneNumber],
            'type' => 'text',
            'text' => $message,
            'preview_url' => false
        ];
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post($this->whatsappBaseUrl . 'messages', $payload);
        
        return $this->handleWhatsAppResponse($response, $phoneNumber, 'conversation', $originalPhone ?: $phoneNumber, $message, $allowFallback);
    }
    
    /**
     * Handle WhatsApp API response
     */
    protected function handleWhatsAppResponse($response, $phoneNumber, $messageType, $originalPhone = null, $message = '', $allowFallback = true)
    {
        $responseData = $response->json();
        
        Log::info("Taqnyat WhatsApp {$messageType} Response", [
            'phone' => $this->maskPhoneNumber($phoneNumber),
            'status' => $response->status(),
            'response' => $responseData
        ]);
        
        // Check response according to documentation
        if ($response->successful()) {
            // Check for specific success indicators
            if ($responseData && (
                (isset($responseData['statusCode']) && $responseData['statusCode'] == 201) ||
                (isset($responseData['status']) && $responseData['status'] === 'success') ||
                (isset($responseData['message_id'])) // Message ID indicates successful queueing
            )) {
                return [
                    'success' => true,
                    'message' => "WhatsApp {$messageType} message sent successfully",
                    'message_id' => $responseData['message_id'] ?? null
                ];
            }
            
            // Check for opt-in related errors
            if (isset($responseData['error']) && 
                (strpos($responseData['error'], 'no_opt_in') !== false || 
                 strpos($responseData['error'], 'opt') !== false)) {
                Log::warning('WhatsApp opt-in required', [
                    'phone' => $this->maskPhoneNumber($phoneNumber),
                    'error' => $responseData['error']
                ]);
                
                if ($allowFallback) {
                    return $this->sendSms($originalPhone ?: $phoneNumber, "📱 " . $message);
                }
                
                return [
                    'success' => false,
                    'message' => 'WhatsApp opt-in required and fallback disabled'
                ];
            }
            
            // If response is null but status is 200, service might not be configured
            if ($responseData === null) {
                Log::info('WhatsApp endpoint returned null response, marking as unavailable');
                $this->markWhatsAppUnavailable();
                
                if ($allowFallback) {
                    return $this->sendSms($originalPhone ?: $phoneNumber, "📱 " . $message);
                }
                
                return [
                    'success' => false,
                    'message' => 'WhatsApp unavailable and fallback disabled'
                ];
            }
        }
        
        // If WhatsApp endpoint doesn't work properly, mark as unavailable and fall back to SMS
        Log::warning('WhatsApp endpoint not working properly, marking as unavailable', [
            'status' => $response->status(),
            'response' => $responseData
        ]);
        $this->markWhatsAppUnavailable();
        
        if ($allowFallback) {
            return $this->sendSms($originalPhone ?: $phoneNumber, "📱 " . $message);
        }
        
        return [
            'success' => false,
            'message' => 'WhatsApp failed and fallback disabled'
        ];
    }
    
    /**
     * Register customer opt-in for WhatsApp
     * Required before sending any WhatsApp messages
     */
    public function registerWhatsAppOptIn($phoneNumber)
    {
        try {
            $formattedPhone = $this->formatSaudiPhoneNumber($phoneNumber);
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->whatsappBaseUrl . 'opt-in', [
                'recipient' => $formattedPhone
            ]);
            
            $responseData = $response->json();
            
            Log::info('WhatsApp Opt-in Response', [
                'phone' => $this->maskPhoneNumber($formattedPhone),
                'status' => $response->status(),
                'response' => $responseData
            ]);
            
            return [
                'success' => $response->successful(),
                'message' => $responseData['message'] ?? 'Opt-in request processed',
                'data' => $responseData
            ];
            
        } catch (\Exception $e) {
            Log::error('WhatsApp Opt-in Error', [
                'message' => $e->getMessage(),
                'phone' => $this->maskPhoneNumber($phoneNumber)
            ]);
            
            return [
                'success' => false,
                'message' => 'Error registering WhatsApp opt-in: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Register customer opt-out for WhatsApp
     */
    public function registerWhatsAppOptOut($phoneNumber)
    {
        try {
            $formattedPhone = $this->formatSaudiPhoneNumber($phoneNumber);
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->whatsappBaseUrl . 'opt-out', [
                'recipient' => $formattedPhone
            ]);
            
            $responseData = $response->json();
            
            Log::info('WhatsApp Opt-out Response', [
                'phone' => $this->maskPhoneNumber($formattedPhone),
                'status' => $response->status(),
                'response' => $responseData
            ]);
            
            return [
                'success' => $response->successful(),
                'message' => $responseData['message'] ?? 'Opt-out request processed',
                'data' => $responseData
            ];
            
        } catch (\Exception $e) {
            Log::error('WhatsApp Opt-out Error', [
                'message' => $e->getMessage(),
                'phone' => $this->maskPhoneNumber($phoneNumber)
            ]);
            
            return [
                'success' => false,
                'message' => 'Error registering WhatsApp opt-out: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Check if WhatsApp service is available
     * Uses caching to avoid repeated failed attempts
     */
    protected function isWhatsAppAvailable()
    {
        $cacheKey = 'taqnyat_whatsapp_available';
        $cached = Cache::get($cacheKey);
        
        // If we've recently determined WhatsApp is not available, skip trying
        if ($cached === false) {
            return false;
        }
        
        return true; // Default to trying WhatsApp
    }
    
    /**
     * Mark WhatsApp as unavailable for a period
     */
    protected function markWhatsAppUnavailable()
    {
        Cache::put('taqnyat_whatsapp_available', false, now()->addMinutes(30));
        Log::info('Marked Taqnyat WhatsApp as unavailable for 30 minutes');
    }
}