<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ProductCompare;
use App\Models\User;
use App\Models\Wishlist;
use App\Services\TaqnyatSmsService;
use App\Utils\CartManager;
use App\Utils\Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Brian2694\Toastr\Facades\Toastr;

class PhoneLoginController extends Controller
{
    protected $smsService;
    
    public function __construct(TaqnyatSmsService $smsService)
    {
        $this->smsService = $smsService;
    }
    
    /**
     * Request OTP for phone login
     */
    public function requestOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ]);
        }
        
        $phone = $request->phone;
        
        // Validate Saudi phone number
        if (!$this->smsService->isValidSaudiNumber($phone)) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a valid Saudi phone number.'
            ]);
        }
        
        // Format phone to Saudi format
        $formattedPhone = $this->smsService->formatSaudiPhoneNumber($phone);
        
        // Check if user can resend OTP
        $resendStatus = $this->smsService->canResendOtp($formattedPhone);
        if (!$resendStatus['can_resend']) {
            return response()->json([
                'success' => false,
                'message' => 'Please wait ' . $resendStatus['wait_seconds'] . ' seconds before requesting another OTP.',
                'wait_seconds' => $resendStatus['wait_seconds']
            ]);
        }
        
        // Generate and send OTP
        $result = $this->smsService->sendOtp($formattedPhone);
        
        if (!$result['success']) {
            Log::error('Failed to send OTP', [
                'phone' => $phone,
                'error' => $result['message'] ?? 'Unknown error'
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send OTP. Please try again later.'
            ]);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully.'
        ]);
    }
    
    /**
     * Verify OTP and login user
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
            'otp' => 'required|digits:4',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ]);
        }
        
        $phone = $request->phone;
        $otp = $request->otp;
        
        // Format phone to Saudi format
        $formattedPhone = $this->smsService->formatSaudiPhoneNumber($phone);
        
        // Verify OTP
        if (!$this->smsService->verifyOtp($formattedPhone, $otp)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP code. Please try again.'
            ]);
        }
        
        // Check if user exists
        $user = User::where('phone', $formattedPhone)->first();
        
        // If user doesn't exist, create one
        if (!$user) {
            // Generate a proper name based on phone
            $phoneDigits = substr($formattedPhone, -4);
            $defaultFirstName = 'مستخدم';
            $defaultLastName = $phoneDigits;
            
            // Generate a proper email
            $defaultEmail = 'user_' . substr($formattedPhone, -9) . '@doorwqossor.com';
            
            // Create new user with proper data for payment gateways
            $user = new User();
            $user->name = $defaultFirstName . ' ' . $defaultLastName;
            $user->f_name = $defaultFirstName;
            $user->l_name = $defaultLastName;
            $user->email = $defaultEmail;
            $user->phone = $formattedPhone;
            $user->password = Hash::make(uniqid()); // Random password
            $user->is_phone_verified = 1;
            $user->is_active = 1;
            
            // Add default address data for payment gateways
            $user->city = 'الرياض';
            $user->zip = '11564';
            $user->country = 'SA';
            
            $user->created_at = now();
            $user->updated_at = now();
            $user->save();
            
            Log::info('New user created via phone login with proper data', [
                'user_id' => $user->id,
                'phone' => $formattedPhone,
                'name' => $user->name
            ]);
        } else {
            // Update existing user data if missing required fields
            $needsUpdate = false;
            
            if (empty($user->l_name)) {
                $user->l_name = substr($user->phone, -4);
                $needsUpdate = true;
            }
            
            if (empty($user->city)) {
                $user->city = 'الرياض';
                $needsUpdate = true;
            }
            
            if (empty($user->country)) {
                $user->country = 'SA';
                $needsUpdate = true;
            }
            
            if ($needsUpdate) {
                $user->save();
                Log::info('Updated existing user data for payment compatibility', [
                    'user_id' => $user->id
                ]);
            }
            // Update phone verification status if needed
            if (!$user->is_phone_verified) {
                $user->is_phone_verified = 1;
                $user->save();
            }
        }
        
        // Check if user account is active
        if (!$user->is_active) {
            Log::warning('Inactive account login attempt', ['phone' => $formattedPhone]);
            return response()->json([
                'success' => false,
                'message' => translate('your_account_is_suspended')
            ]);
        }
        
        // Login the user with remember me option
        auth('customer')->login($user, true);
        
        // Update login attempt counters
        $user->login_hit_count = 0;
        $user->is_temp_blocked = 0;
        $user->temp_block_time = null;
        $user->updated_at = now();
        $user->save();
        
        // Set wish list and compare list in session
        $wish_list = Wishlist::whereHas('wishlistProduct', function ($q) {
            return $q;
        })->where('customer_id', auth('customer')->user()->id)->pluck('product_id')->toArray();

        $compare_list = ProductCompare::where('user_id', auth('customer')->id())->pluck('product_id')->toArray();

        session()->forget('wish_list');
        session()->forget('compare_list');
        session()->put('wish_list', $wish_list);
        session()->put('compare_list', $compare_list);
        
        // Transfer cart from session to database
        CartManager::cartListSessionToDatabase();
        
        // Log the successful login
        Log::info('User logged in via phone OTP', [
            'user_id' => $user->id,
            'phone' => $formattedPhone
        ]);
        
        return response()->json([
            'success' => true,
            'message' => translate('login_successful'),
            'redirect' => url('/')
        ]);
    }
    
    /**
     * Resend OTP
     */
    public function resendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ]);
        }
        
        $phone = $request->phone;
        
        // Format phone to Saudi format
        $formattedPhone = $this->smsService->formatSaudiPhoneNumber($phone);
        
        // Check if user can resend OTP
        $resendStatus = $this->smsService->canResendOtp($formattedPhone);
        if (!$resendStatus['can_resend']) {
            return response()->json([
                'success' => false,
                'message' => 'Please wait ' . $resendStatus['wait_seconds'] . ' seconds before requesting another OTP.',
                'wait_seconds' => $resendStatus['wait_seconds']
            ]);
        }
        
        // Generate and send OTP
        $result = $this->smsService->sendOtp($formattedPhone);
        
        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to resend OTP. Please try again later.'
            ]);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'OTP resent successfully.'
        ]);
    }
}
