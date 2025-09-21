<?php

namespace App\Http\Controllers\Customer;

use App\Models\User;
use App\Utils\Helpers;
use App\Http\Controllers\Controller;
use App\Models\ShippingAddress;
use App\Models\ShippingMethod;
use App\Models\CartShipping;
use App\Traits\CommonTrait;
use App\Utils\CartManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SystemController extends Controller
{
    use CommonTrait;

    public function setPaymentMethod($name): JsonResponse
    {
        if (auth('customer')->check() || session()->has('mobile_app_payment_customer_id')) {
            session()->put('payment_method', $name);
            return response()->json(['status' => 1]);
        }
        return response()->json(['status' => 0]);
    }

    public function setShippingMethod(Request $request): JsonResponse
    {
        if ($request['cart_group_id'] == 'all_cart_group') {
            foreach (CartManager::get_cart_group_ids() as $groupId) {
                $request['cart_group_id'] = $groupId;
                self::insertIntoCartShipping($request);
            }
        } else {
            self::insertIntoCartShipping($request);
        }
        return response()->json(['status' => 1]);
    }

    public static function insertIntoCartShipping($request): void
    {
        $shipping = CartShipping::where(['cart_group_id' => $request['cart_group_id']])->first();
        if (isset($shipping) == false) {
            $shipping = new CartShipping();
        }
        $shipping['cart_group_id'] = $request['cart_group_id'];
        $shipping['shipping_method_id'] = $request['id'];
        $shipping['shipping_cost'] = ShippingMethod::find($request['id'])->cost;
        $shipping->save();
    }

    /*
     * Unified method for handling shipping addresses for all themes
     * Replaces both getChooseShippingAddress and getChooseShippingAddressOther
     * @return json
     */
    public function getChooseShippingAddress(Request $request): JsonResponse
    {
        return $this->processShippingAddress($request);
    }

    /*
     * For backward compatibility - redirects to unified method
     * @return json
     */
    public function getChooseShippingAddressOther(Request $request): JsonResponse
    {
        return $this->processShippingAddress($request);
    }

    /*
     * Unified shipping address processing method
     * @return json
     */
    private function processShippingAddress(Request $request): JsonResponse
    {
        $shipping = [];
        $billing = [];
        parse_str($request['shipping'], $shipping);
        parse_str($request['billing'], $billing);

        // Set default values for missing or empty fields
        $this->setDefaultValuesForBilling($billing);
        $this->setDefaultValuesForShipping($shipping);

        // NO phone validation - all phones are now optional for both shipping and billing

        $physicalProduct = $request['physical_product'];
        $zipRestrictStatus = getWebConfig(name: 'delivery_zip_code_area_restriction');
        $countryRestrictStatus = getWebConfig(name: 'delivery_country_restriction');
        $billingInputByCustomer = getWebConfig(name: 'billing_input_by_customer');
        $isGuestCustomer = !auth('customer')->check();

        // === SHIPPING ADDRESS PROCESSING ===
        $addressId = $this->processShippingData($shipping, $request, $countryRestrictStatus, $zipRestrictStatus, $isGuestCustomer);
        
        if (is_array($addressId)) {
            return response()->json($addressId, 403); // Return error response
        }

        // === BILLING ADDRESS PROCESSING ===
        $billingAddressId = $this->processBillingData($billing, $request, $addressId, $countryRestrictStatus, $zipRestrictStatus, $billingInputByCustomer, $isGuestCustomer, $physicalProduct);
        
        if (is_array($billingAddressId)) {
            return response()->json($billingAddressId, 403); // Return error response
        }

        // === CUSTOMER REGISTRATION PROCESSING ===
        $registrationResult = $this->processCustomerRegistration($request, $shipping, $billing, $isGuestCustomer);
        
        if (is_array($registrationResult)) {
            return response()->json($registrationResult, 403); // Return error response
        }



        session()->put('address_id', $addressId);
        session()->put('billing_address_id', $billingAddressId);

        return response()->json([], 200);
    }

    /**
     * Process shipping address data
     */
    private function processShippingData($shipping, $request, $countryRestrictStatus, $zipRestrictStatus, $isGuestCustomer)
    {
        $addressId = $shipping['shipping_method_id'] ?? 0;

        // Validation for shipping data
        if (isset($shipping['shipping_method_id'])) {
            if ($shipping['contact_person_name'] == null || !isset($shipping['address_type']) || $shipping['address'] == null || $shipping['city'] == null || !isset($shipping['zip']) || $shipping['zip'] == null || !isset($shipping['country']) || $shipping['country'] == null || ($isGuestCustomer && $shipping['email'] == null)) {
                return ['errors' => translate('Fill_all_required_fields_of_shipping_address')];
            } elseif ($countryRestrictStatus && !self::delivery_country_exist_check($shipping['country'])) {
                return ['errors' => translate('Delivery_unavailable_in_this_country.')];
            } elseif ($zipRestrictStatus && !self::delivery_zipcode_exist_check($shipping['zip'])) {
                return ['errors' => translate('Delivery_unavailable_in_this_zip_code_area')];
            }
        }

        // Handle different shipping scenarios
        if (isset($shipping['save_address']) && $shipping['save_address'] == 'on') {
            $addressId = ShippingAddress::insertGetId([
                'customer_id' => auth('customer')->id() ?? ((session()->has('guest_id') ? session('guest_id') : 0)),
                'is_guest' => auth('customer')->check() ? 0 : (session()->has('guest_id') ? 1 : 0),
                'contact_person_name' => $shipping['contact_person_name'],
                'address_type' => $shipping['address_type'],
                'address' => $shipping['address'],
                'city' => $shipping['city'],
                'zip' => $shipping['zip'],
                'country' => $shipping['country'],
                'phone' => $shipping['phone'],
                'latitude' => $shipping['latitude'] ?? '',
                'longitude' => $shipping['longitude'] ?? '',
                'email' => auth('customer')->check() ? null : $shipping['email'],
                'is_billing' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } elseif (isset($shipping['update_address']) && $shipping['update_address'] == 'on') {
            $getShipping = ShippingAddress::find($addressId);
            if ($getShipping) {
                $getShipping->contact_person_name = $shipping['contact_person_name'];
                $getShipping->address_type = $shipping['address_type'];
                $getShipping->address = $shipping['address'];
                $getShipping->city = $shipping['city'];
                $getShipping->zip = $shipping['zip'];
                $getShipping->country = $shipping['country'];
                $getShipping->phone = $shipping['phone'];
                $getShipping->latitude = $shipping['latitude'] ?? '';
                $getShipping->longitude = $shipping['longitude'] ?? '';
                $getShipping->save();
            }
        } elseif (isset($shipping['shipping_method_id']) && !isset($shipping['update_address']) && !isset($shipping['save_address'])) {
            // Check if it's an existing address
            $existingAddress = ShippingAddress::find($shipping['shipping_method_id']);
            if ($existingAddress) {
                // Validate existing address
                if (!$existingAddress->country || !$existingAddress->zip) {
                    return ['errors' => translate('Please_update_country_and_zip_for_this_shipping_address')];
                }
                if ($countryRestrictStatus && !self::delivery_country_exist_check($existingAddress->country)) {
                    return ['errors' => translate('Delivery_unavailable_in_this_country')];
                }
                if ($zipRestrictStatus && !self::delivery_zipcode_exist_check($existingAddress->zip)) {
                    return ['errors' => translate('Delivery_unavailable_in_this_zip_code_area')];
                }
                $addressId = $shipping['shipping_method_id'];
            } else {
                // Create new address
                $addressId = ShippingAddress::insertGetId([
                    'customer_id' => auth('customer')->id() ?? ((session()->has('guest_id') ? session('guest_id') : 0)),
                    'is_guest' => auth('customer')->check() ? 0 : (session()->has('guest_id') ? 1 : 0),
                    'contact_person_name' => $shipping['contact_person_name'],
                    'address_type' => $shipping['address_type'],
                    'address' => $shipping['address'],
                    'city' => $shipping['city'],
                    'zip' => $shipping['zip'],
                    'country' => $shipping['country'],
                    'phone' => $shipping['phone'],
                    'email' => auth('customer')->check() ? null : $shipping['email'],
                    'latitude' => $shipping['latitude'] ?? '',
                    'longitude' => $shipping['longitude'] ?? '',
                    'is_billing' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return $addressId;
    }

    /**
     * Process billing address data
     */
    private function processBillingData($billing, $request, $addressId, $countryRestrictStatus, $zipRestrictStatus, $billingInputByCustomer, $isGuestCustomer, $physicalProduct)
    {
        $billingAddressId = $addressId ?? 0;

        if ($request['billing_addresss_same_shipping'] == 'false' && isset($billing['billing_method_id']) && $billingInputByCustomer) {
            $billingAddressId = $billing['billing_method_id'];

            // Validation for billing data
            if ($billing['billing_contact_person_name'] == null || !isset($billing['billing_address_type']) || !isset($billing['billing_address']) || $billing['billing_address'] == null || $billing['billing_city'] == null || !isset($billing['billing_zip']) || $billing['billing_zip'] == null || !isset($billing['billing_country']) || $billing['billing_country'] == null || ($isGuestCustomer && $billing['billing_contact_email'] == null)) {
                return ['errors' => translate('Fill_all_required_fields_of_billing_address')];
            } elseif ($countryRestrictStatus && !self::delivery_country_exist_check($billing['billing_country'])) {
                return ['errors' => translate('Delivery_unavailable_in_this_country')];
            } elseif ($zipRestrictStatus && !self::delivery_zipcode_exist_check($billing['billing_zip'])) {
                return ['errors' => translate('Delivery_unavailable_in_this_zip_code_area')];
            }

            // Handle different billing scenarios
            if (isset($billing['save_address_billing']) && $billing['save_address_billing'] == 'on') {
                $billingAddressId = ShippingAddress::insertGetId([
                    'customer_id' => auth('customer')->id() ?? ((session()->has('guest_id') ? session('guest_id') : 0)),
                    'is_guest' => auth('customer')->check() ? 0 : (session()->has('guest_id') ? 1 : 0),
                    'contact_person_name' => $billing['billing_contact_person_name'],
                    'address_type' => $billing['billing_address_type'],
                    'address' => $billing['billing_address'],
                    'city' => $billing['billing_city'],
                    'zip' => $billing['billing_zip'],
                    'country' => $billing['billing_country'],
                    'phone' => $billing['billing_phone'],
                    'email' => auth('customer')->check() ? null : $billing['billing_contact_email'],
                    'latitude' => $billing['billing_latitude'] ?? '',
                    'longitude' => $billing['billing_longitude'] ?? '',
                    'is_billing' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } elseif (isset($billing['update_billing_address']) && $billing['update_billing_address'] == 'on') {
                $getBilling = ShippingAddress::find($billingAddressId);
                if ($getBilling) {
                    $getBilling->contact_person_name = $billing['billing_contact_person_name'];
                    $getBilling->address_type = $billing['billing_address_type'];
                    $getBilling->address = $billing['billing_address'];
                    $getBilling->city = $billing['billing_city'];
                    $getBilling->zip = $billing['billing_zip'];
                    $getBilling->country = $billing['billing_country'];
                    $getBilling->phone = $billing['billing_phone'];
                    $getBilling->latitude = $billing['billing_latitude'] ?? '';
                    $getBilling->longitude = $billing['billing_longitude'] ?? '';
                    $getBilling->save();
                }
            } elseif (!isset($billing['update_billing_address']) && !isset($billing['save_address_billing'])) {
                // Check if it's an existing address
                $existingBillingAddress = ShippingAddress::find($billing['billing_method_id']);
                if ($existingBillingAddress) {
                    // Validate existing billing address for physical products
                    if ($physicalProduct == 'yes') {
                        if (!$existingBillingAddress->country || !$existingBillingAddress->zip) {
                            return ['errors' => translate('Update_country_and_zip_for_this_billing_address')];
                        }
                        if ($countryRestrictStatus && !self::delivery_country_exist_check($existingBillingAddress->country)) {
                            return ['errors' => translate('Delivery_unavailable_in_this_country')];
                        }
                        if ($zipRestrictStatus && !self::delivery_zipcode_exist_check($existingBillingAddress->zip)) {
                            return ['errors' => translate('Delivery_unavailable_in_this_zip_code_area')];
                        }
                    }
                    $billingAddressId = $billing['billing_method_id'];
                } else {
                    // Create new billing address
                    $billingAddressId = ShippingAddress::insertGetId([
                        'customer_id' => auth('customer')->id() ?? ((session()->has('guest_id') ? session('guest_id') : 0)),
                        'is_guest' => auth('customer')->check() ? 0 : (session()->has('guest_id') ? 1 : 0),
                        'contact_person_name' => $billing['billing_contact_person_name'],
                        'address_type' => $billing['billing_address_type'],
                        'address' => $billing['billing_address'],
                        'city' => $billing['billing_city'],
                        'zip' => $billing['billing_zip'],
                        'country' => $billing['billing_country'],
                        'phone' => $billing['billing_phone'],
                        'email' => auth('customer')->check() ? null : $billing['billing_contact_email'],
                        'latitude' => $billing['billing_latitude'] ?? '',
                        'longitude' => $billing['billing_longitude'] ?? '',
                        'is_billing' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        } elseif ($request['billing_addresss_same_shipping'] == 'false' && !isset($billing['billing_method_id']) && $physicalProduct != 'yes') {
            return ['errors' => translate('Fill_all_required_fields_of_billing_address')];
        }

        return $billingAddressId;
    }

    /**
     * Process customer registration if requested
     */
    private function processCustomerRegistration($request, $shipping, $billing, $isGuestCustomer)
    {
        if ($request['is_check_create_account'] && $isGuestCustomer) {
            if (empty($request['customer_password']) || empty($request['customer_confirm_password'])) {
                return ['errors' => translate('The_password_or_confirm_password_can_not_be_empty')];
            }
            if ($request['customer_password'] != $request['customer_confirm_password']) {
                return ['errors' => translate('The_password_and_confirm_password_must_match')];
            }
            if (strlen($request['customer_password']) < 7 || strlen($request['customer_confirm_password']) < 7) {
                return ['errors' => translate('The_password_must_be_at_least_8_characters')];
            }

            if ($request['shipping']) {
                $newCustomerAddress = [
                    'name' => $shipping['contact_person_name'],
                    'email' => $shipping['email'],
                    'phone' => $shipping['phone'],
                    'password' => $request['customer_password'],
                ];
            } else {
                $newCustomerAddress = [
                    'name' => $billing['billing_contact_person_name'],
                    'email' => $billing['billing_contact_email'],
                    'phone' => $billing['billing_phone'],
                    'password' => $request['customer_password'],
                ];
            }

            if (User::where(['email' => $newCustomerAddress['email']])->orWhere(['phone' => $newCustomerAddress['phone']])->first()) {
                return ['errors' => translate('Already_registered')];
            } else {
                $newCustomerRegister = self::getRegisterNewCustomer(request: $request, address: $newCustomerAddress);
                session()->put('newCustomerRegister', $newCustomerRegister);
            }
        } else {
            session()->forget('newCustomerRegister');
            session()->forget('newRegisterCustomerInfo');
        }

        return true; // Success
    }

    function getRegisterNewCustomer($request, $address): array
    {
        return [
            'name' => $address['name'],
            'f_name' => $address['name'],
            'l_name' => '',
            'email' => $address['email'],
            'phone' => $address['phone'],
            'is_active' => 1,
            'password' => $address['password'],
            'referral_code' => Helpers::generate_referer_code(),
            'shipping_id' => session('address_id'),
            'billing_id' => session('billing_address_id'),
        ];
    }

    /**
     * Set default values for missing billing fields
     */
    private function setDefaultValuesForBilling(&$billing): void
    {
        $defaults = [
            'billing_contact_person_name' => 'Customer',
            'billing_address_type' => 'permanent',
            'billing_address' => 'Default Address',
            'billing_city' => 'Riyadh',
            'billing_zip' => '12345',
            'billing_country' => 'Saudi Arabia',
            'billing_phone' => '', // Phone is now completely optional
            'billing_contact_email' => 'customer@example.com',
            'billing_latitude' => '24.7136',
            'billing_longitude' => '46.6753'
        ];

        foreach ($defaults as $key => $defaultValue) {
            if (!isset($billing[$key]) || empty($billing[$key])) {
                $billing[$key] = $defaultValue;
            }
        }
    }

    /**
     * Set default values for missing shipping fields
     */
    private function setDefaultValuesForShipping(&$shipping): void
    {
        $defaults = [
            'contact_person_name' => 'Customer',
            'address_type' => 'permanent',
            'address' => 'Default Address',
            'city' => 'Riyadh',
            'zip' => '12345',
            'country' => 'Saudi Arabia',
            'phone' => '', // Phone is now optional for shipping too
            'email' => 'customer@example.com',
            'latitude' => '24.7136',
            'longitude' => '46.6753'
        ];

        foreach ($defaults as $key => $defaultValue) {
            if (!isset($shipping[$key]) || empty($shipping[$key])) {
                $shipping[$key] = $defaultValue;
            }
        }
    }
}
