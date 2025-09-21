<?php

use App\Models\Currency;

if (!function_exists('loadCurrency')) {
    /**
     * @return void
     */
    function loadCurrency(): void
    {
        $defaultCurrency = getWebConfig(name: 'system_default_currency');
        $currentCurrencyInfo = session('system_default_currency_info');
        if (!session()->has('system_default_currency_info') || $defaultCurrency != $currentCurrencyInfo['id']) {
            $id = getWebConfig(name: 'system_default_currency');
            $currency = Currency::find($id);
            session()->put('system_default_currency_info', $currency);
            session()->put('currency_code', $currency->code);
            session()->put('currency_symbol', $currency->symbol);
            session()->put('currency_exchange_rate', $currency->exchange_rate);
            session()->forget('usd');
            session()->forget('default');
            $usd = exchangeRate(USD);
            session()->put('usd', $usd);
        }
    }
}

if (!function_exists('currencyConverter')) {
    /** system default currency to usd convert
     * @param float|null $amount
     * @param string $to
     * @return float|int
     */
    function currencyConverter(float|null $amount = 0, string $to = USD): float|int
    {
        $amount = is_null($amount) ? 0 : $amount;
        $currencyModel = getWebConfig('currency_model');
        if ($currencyModel == MULTI_CURRENCY) {
            $default = Currency::find(getWebConfig('system_default_currency'))->exchange_rate;
            $exchangeRate = exchangeRate($to);
            $rate = $default / $exchangeRate;
            if ($amount == 0 || floatval($rate) == 0.0) {
                $value = $amount;
            } else {
                $value = $amount / floatval($rate);
            }
        } else {
            $value = $amount;
        }
        return $value;
    }
}

if (!function_exists('usdToDefaultCurrency')) {
    /**
     * system usd currency to default convert
     * @param float|int|null $amount
     * @return float|int
     */
    function usdToDefaultCurrency(float|int|null $amount = 0): float|int
    {
        $currencyModel = getWebConfig('currency_model');
        if ($currencyModel == MULTI_CURRENCY) {
            if (session()->has('default')) {
                $default = session('default');
            } else {
                $default = Currency::find(getWebConfig('system_default_currency'))->exchange_rate;
                session()->put('default', $default);
            }

            if (session()->has('usd')) {
                $usd = session('usd');
            } else {
                $usd = exchangeRate(USD);
                session()->put('usd', $usd);
            }

            $rate = $default / $usd;
            $value = $amount * floatval($rate);
        } else {
            $value = $amount;
        }

        $decimalPointSettings = getWebConfig('decimal_point_settings') ?? 2;
        return round($value, $decimalPointSettings);
    }
}

if (!function_exists('webCurrencyConverter')) {
    /**
     * currency convert for web panel
     * @param string|int|float|null $amount
     * @return float|string
     */
    function webCurrencyConverter(string|int|float|null $amount = 0): float|string
    {
        loadCurrency();
        $currencyModel = getWebConfig('currency_model');
        if ($currencyModel == MULTI_CURRENCY) {
            if (session()->has('usd')) {
                $usd = session('usd');
            } else {
                $usd = Currency::where(['code' => 'USD'])->first()->exchange_rate;
                session()->put('usd', $usd);
            }
            $myCurrency = \session('currency_exchange_rate');
            $rate = $myCurrency / $usd;
        } else {
            $rate = 1;
        }
        $decimalPointSettings = getWebConfig('decimal_point_settings') ?? 2;
        return setCurrencySymbol(amount: round($amount * $rate, $decimalPointSettings), currencyCode: getCurrencyCode(type: 'web'), type: 'web');
    }
}

if (!function_exists('localToDefaultCurrency')) {
    /** system default currency to usd convert
     * @param float|null $amount
     * @param string $type
     * @return float|int|string
     */
    function localToDefaultCurrency(float|null $amount = 0, string $type = 'default'): float|int|string
    {
        $value = is_null($amount) || $amount != 0 ? $amount / session('currency_exchange_rate') : 0;
        if ($type == 'web') {
            return setCurrencySymbol(amount: $value, currencyCode: getCurrencyCode(type: 'web'), type: 'web');
        }
        return $value;
    }
}

if (!function_exists('loyaltyPointToLocalCurrency')) {
    /** system default currency to usd convert
     * @param float|null $amount
     * @param string $type
     * @return string
     */
    function loyaltyPointToLocalCurrency(float|null $amount = 0, string $type = 'default'): string
    {
        $loyaltyPointExchangeRate = getWebConfig(name: 'loyalty_point_exchange_rate');
        $value = ((session('currency_exchange_rate') * 1) / $loyaltyPointExchangeRate) * $amount;
        if ($type == 'web') {
            return setCurrencySymbol(amount: $value, currencyCode: session('currency_code'), type: 'web');
        }
        return $value;
    }
}

if (!function_exists('webCurrencyConverterOnlyDigit')) {
    /**
     * currency convert for web panel
     * @param string|int|float|null $amount
     * @return float|string
     */
    function webCurrencyConverterOnlyDigit(string|int|float|null $amount = 0): float|string
    {
        loadCurrency();
        $currencyModel = getWebConfig('currency_model');
        if ($currencyModel == MULTI_CURRENCY) {
            if (session()->has('usd')) {
                $usd = session('usd');
            } else {
                $usd = Currency::where(['code' => 'USD'])->first()->exchange_rate;
                session()->put('usd', $usd);
            }
            $myCurrency = \session('currency_exchange_rate');
            $rate = $myCurrency / $usd;
        } else {
            $rate = 1;
        }

        $decimalPointSettings = getWebConfig('decimal_point_settings') ?? 2;
        return round($amount * $rate, $decimalPointSettings);
    }
}

if (!function_exists('usdToAnotherCurrencyConverter')) {
    /**
     * currency convert for web panel
     * @param string $currencyCode
     * @param string|int|float|null $amount
     * @return float|string
     */
    function usdToAnotherCurrencyConverter(string $currencyCode, string|int|float|null $amount = 0): float|string
    {
        if ($currencyCode == 'USD') {
            return $amount;
        }
        $usd = Currency::where(['code' => 'USD'])->first()->exchange_rate;
        $myCurrency = Currency::where(['code' => $currencyCode])->first()->exchange_rate;
        $rate = $myCurrency / $usd;
        $decimalPointSettings = getWebConfig('decimal_point_settings') ?? 2;
        return round($amount * $rate, $decimalPointSettings);
    }
}

if (!function_exists('exchangeRate')) {
    /**
     * @param string $currencyCode
     * @return float|int
     */
    function exchangeRate(string $currencyCode = USD): float|int
    {
        return Currency::where('code', $currencyCode)->first()->exchange_rate ?? 1;
    }
}

if (!function_exists('getCurrencySymbol')) {
    /**
     * @param string $currencyCode
     * @param string $type
     * @return float|int|string
     */
    function getCurrencySymbol(string $currencyCode = USD, string $type = 'default'): float|int|string
    {
        loadCurrency();
        if ($type == 'web' && session()->has('currency_symbol')) {
            $currentSymbol = session('currency_symbol');
        } else {
            $systemDefaultCurrencyInfo = session('system_default_currency_info');
            $currentSymbol = $systemDefaultCurrencyInfo->symbol;
        }
        return $currentSymbol;
    }
}

if (!function_exists('setCurrencySymbol')) {
    /**
     * @param string|int|float $amount
     * @param string $currencyCode
     * @param string $type
     * @return string
     */
    function setCurrencySymbol(string|int|float $amount, string $currencyCode = USD, string $type = 'default'): string
    {
        $decimalPointSettings = getWebConfig('decimal_point_settings');
        $position = getWebConfig('currency_symbol_position');
        if ($position === 'left') {
            $string = getCurrencySymbol(currencyCode: $currencyCode, type: $type) . '' . number_format($amount, (!empty($decimalPointSettings) ? $decimalPointSettings : 0));
        } else {
            $string = number_format($amount, !empty($decimalPointSettings) ? $decimalPointSettings : 0) . '' . getCurrencySymbol(currencyCode: $currencyCode, type: $type);
        }
        return $string;
    }
}

if (!function_exists('getCurrencyCode')) {
    /**
     * @param string $type default,web
     * @return string
     */
    function getCurrencyCode(string $type = 'default'): string
    {
        if ($type == 'web') {
            $currencyCode = session('currency_code');
        } else {
            if (session()->has('system_default_currency_info')) {
                $currencyCode = session('system_default_currency_info')->code;
            } else {
                $currencyId = getWebConfig('system_default_currency');
                $currencyCode = Currency::where('id', $currencyId)->first()->code;
            }
        }
        return $currencyCode;
    }
}

if (!function_exists('getFormatCurrency')) {
    /**
     * @param string|int|float $amount
     * @return string
     */
    function getFormatCurrency(string|int|float $amount): string
    {
        $suffixes = ["1t+" => 1000000000000, "B+" => 1000000000, "M+" => 1000000, "K+" => 1000];
        foreach ($suffixes as $suffix => $factor) {
            if ($amount >= $factor) {
                $div = $amount / $factor;
                $formattedValue = number_format($div, 1) . $suffix;
                break;
            }
        }

        if (!isset($formattedValue)) {
            $formattedValue = number_format($amount, 2);
        }

        return $formattedValue;
    }
}


if (!function_exists('getProductPriceByType')) {
    function getProductPriceByType($product, $type, $result = 'value', $price = 0, $from = 'web'): float|int|string
    {
        if ($type == 'discount') {
            if ((isset($product['clearanceSale']) && $product['clearanceSale']) || isset($product['clearance_sale']) && $product['clearance_sale']) {
                $clearanceSale = $product['clearanceSale'] ?? $product['clearance_sale'];
                if ($clearanceSale['discount_type'] == 'percentage') {
                    $amount = round($clearanceSale['discount_amount'], (!empty($decimalPointSettings) ? $decimalPointSettings: 0));
                    return $result == 'value' ? $amount : $amount.'%';
                } else if ($clearanceSale['discount_type'] =='flat') {
                    return $result == 'value' ? $clearanceSale['discount_amount'] : webCurrencyConverter(amount: $clearanceSale['discount_amount']);
                }
            } else if ($product['discount_type'] == 'percent') {
                $amount = round($product['discount'], (!empty($decimalPointSettings) ? $decimalPointSettings: 0));
                return $result == 'value' ? $amount : $amount.'%';
            } else if ($product['discount_type'] =='flat') {
                return $result == 'value' ? $product['discount'] : webCurrencyConverter(amount: $product['discount']);
            }
        }

        if ($type == 'discount_type') {
            $discountType = $product['discount_type'];
            if ((isset($product['clearanceSale']) && $product['clearanceSale']) || isset($product['clearance_sale']) && $product['clearance_sale']) {
                $clearanceSale = $product['clearanceSale'] ?? $product['clearance_sale'];
                $discountType = $clearanceSale['discount_type'];
            }
            return $discountType;
        }

        if ($type == 'discounted_unit_price') {
            $unitPrice = $price != 0 ? $price : $product['unit_price'];
            if ((isset($product['clearanceSale']) && $product['clearanceSale']) || isset($product['clearance_sale']) && $product['clearance_sale']) {
                $amount = $unitPrice - getProductPriceByType(product: $product, type: 'discounted_amount', result: 'value', price: $unitPrice);
            } else {
                $amount = $unitPrice - (getProductDiscount(product: $product, price: $unitPrice));
            }

            if ($from == 'panel') {
                return $result == 'value' ? $amount : setCurrencySymbol(amount: usdToDefaultCurrency(amount: $amount), currencyCode: getCurrencyCode());
            }
            
            // Check if result should include image symbol for web display
            if ($result == 'string_with_image') {
                return webCurrencyConverterWithImage(amount: $amount);
            }
            
            return $result == 'value' ? $amount : webCurrencyConverter(amount: $amount);
        }

        if ($type == 'discounted_amount') {
            if ((isset($product['clearanceSale']) && $product['clearanceSale']) || isset($product['clearance_sale']) && $product['clearance_sale']) {
                $clearanceSale = $product['clearanceSale'] ?? $product['clearance_sale'];
                $discountAmount = 0;
                if ($clearanceSale['discount_type'] == 'percentage') {
                    $discountAmount = ($price * getProductPriceByType(product: $product, type: 'discount', result: 'value')) / 100;
                } else if ($clearanceSale['discount_type'] =='flat') {
                    $discountAmount =  $clearanceSale['discount_amount'];
                }

                $amount = floatval($discountAmount);
            } else {
                $amount = getProductDiscount(product: $product, price: $price);
            }
            if ($from == 'panel') {
                return $result == 'value' ? $amount : setCurrencySymbol(amount: usdToDefaultCurrency(amount: $amount), currencyCode: getCurrencyCode());
            }
            return $result == 'value' ? $amount : webCurrencyConverter(amount: $amount);
        }

        return 0;
    }
}

// Saudi Riyal Symbol Helper Functions
if (!function_exists('getSaudiRiyalSymbol')) {
    /**
     * Get the new Saudi Riyal symbol
     * @param string $size small|normal|large
     * @return string
     */
    function getSaudiRiyalSymbol($size = 'normal'): string
    {
        $sizeClass = '';
        switch ($size) {
            case 'small':
                $sizeClass = ' saudi-riyal-symbol-small';
                break;
            case 'large':
                $sizeClass = ' saudi-riyal-symbol-large';
                break;
            default:
                $sizeClass = '';
        }
        
        return '<span class="saudi-riyal-symbol' . $sizeClass . '" title="ريال سعودي"></span>';
    }
}

if (!function_exists('formatPriceWithNewSaudiSymbol')) {
    /**
     * Format price with new Saudi Riyal symbol if currency is SAR
     * @param string|int|float|null $amount
     * @param string $size
     * @return string
     */
    function formatPriceWithNewSaudiSymbol(string|int|float|null $amount = 0, string $size = 'normal'): string
    {
        loadCurrency();
        $currencyModel = getWebConfig('currency_model');
        if ($currencyModel == MULTI_CURRENCY) {
            if (session()->has('usd')) {
                $usd = session('usd');
            } else {
                $usd = Currency::where(['code' => 'USD'])->first()->exchange_rate;
                session()->put('usd', $usd);
            }
            $myCurrency = \session('currency_exchange_rate');
            $rate = $myCurrency / $usd;
        } else {
            $rate = 1;
        }
        
        $decimalPointSettings = getWebConfig('decimal_point_settings') ?? 2;
        $amount = round($amount * $rate, $decimalPointSettings);
        $formattedAmount = number_format($amount, $decimalPointSettings);
        
        // Check if current currency is SAR
        $currencyCode = getCurrencyCode(type: 'web');
        if (strtoupper($currencyCode) === 'SAR') {
            $position = getWebConfig('currency_symbol_position');
            if ($position === 'left') {
                return getSaudiRiyalSymbol($size) . ' ' . $formattedAmount;
            } else {
                return $formattedAmount . ' ' . getSaudiRiyalSymbol($size);
            }
        }
        
        // For other currencies, use the default function
        return setCurrencySymbol(amount: $amount, currencyCode: $currencyCode, type: 'web');
    }
}

// Simple Saudi Riyal Helper Functions - Always display new symbol
if (!function_exists('saudiPrice')) {
    /**
     * Display price with new Saudi Riyal symbol
     * @param string|int|float|null $amount
     * @param string $size
     * @return string
     */
    function saudiPrice($amount = 0, $size = 'normal'): string
    {
        if (is_null($amount) || $amount === '') {
            $amount = 0;
        }
        
        $decimalPointSettings = getWebConfig('decimal_point_settings') ?? 2;
        $formattedAmount = number_format((float)$amount, $decimalPointSettings);
        
        $sizeClass = '';
        switch ($size) {
            case 'small':
                $sizeClass = ' saudi-riyal-symbol-small';
                break;
            case 'large':
                $sizeClass = ' saudi-riyal-symbol-large';
                break;
            default:
                $sizeClass = '';
        }
        
        $symbol = '<span class="saudi-riyal-symbol' . $sizeClass . '" title="ريال سعودي"></span>';
        
        $position = getWebConfig('currency_symbol_position') ?? 'right';
        if ($position === 'left') {
            return $symbol . ' ' . $formattedAmount;
        } else {
            return $formattedAmount . ' ' . $symbol;
        }
    }
}

if (!function_exists('webCurrencyConverterSaudi')) {
    /**
     * Currency converter that always shows Saudi Riyal symbol
     * @param string|int|float|null $amount
     * @param string $size
     * @return string
     */
    function webCurrencyConverterSaudi($amount = 0, $size = 'normal'): string
    {
        loadCurrency();
        $currencyModel = getWebConfig('currency_model');
        if ($currencyModel == MULTI_CURRENCY) {
            if (session()->has('usd')) {
                $usd = session('usd');
            } else {
                $usd = Currency::where(['code' => 'USD'])->first()->exchange_rate ?? 1;
                session()->put('usd', $usd);
            }
            $myCurrency = \session('currency_exchange_rate') ?? 1;
            $rate = $myCurrency / $usd;
        } else {
            $rate = 1;
        }
        
        $convertedAmount = $amount * $rate;
        return saudiPrice($convertedAmount, $size);
    }
}

// Final Saudi Riyal Solution - Simple and Reliable
if (!function_exists('saudiRiyalFinal')) {
    /**
     * Display price with Saudi Riyal symbol - FINAL SOLUTION
     * @param string|int|float|null $amount
     * @return string
     */
    function saudiRiyalFinal($amount = 0): string
    {
        // Handle null or empty values
        if (is_null($amount) || $amount === '') {
            $amount = 0;
        }
        
        // Convert to float
        $numericAmount = floatval($amount);
        
        $decimalPointSettings = getWebConfig('decimal_point_settings') ?? 2;
        $formattedAmount = number_format($numericAmount, $decimalPointSettings);
        
        // Return ONLY one riyal symbol - no images, no complications
        return $formattedAmount . ' ريال';
    }
}

if (!function_exists('webCurrencyConverterFinal')) {
    /**
     * Currency converter - FINAL SOLUTION 
     * @param string|int|float|null $amount
     * @return string
     */
    function webCurrencyConverterFinal($amount = 0): string
    {
        // Handle null or empty values
        if (is_null($amount) || $amount === '') {
            $amount = 0;
        }
        
        loadCurrency();
        $currencyModel = getWebConfig('currency_model');
        if ($currencyModel == MULTI_CURRENCY) {
            if (session()->has('usd')) {
                $usd = session('usd');
            } else {
                $usd = Currency::where(['code' => 'USD'])->first()->exchange_rate ?? 1;
                session()->put('usd', $usd);
            }
            $myCurrency = \session('currency_exchange_rate') ?? 1;
            $rate = $myCurrency / $usd;
        } else {
            $rate = 1;
        }
        
        $convertedAmount = $amount * $rate;
        return saudiRiyalFinal($convertedAmount);
    }
}

// Saudi Riyal Function with New Official Symbol Image
if (!function_exists('saudiRiyalWithNewSymbol')) {
    /**
     * Display price with new Saudi Riyal symbol image
     * @param string|int|float|null $amount
     * @return string
     */
    function saudiRiyalWithNewSymbol($amount = 0): string
    {
        // Handle null or empty values
        if (is_null($amount) || $amount === '') {
            $amount = 0;
        }
        
        // Convert to float and handle negative values
        $numericAmount = floatval($amount);
        
        $decimalPointSettings = getWebConfig('decimal_point_settings') ?? 2;
        $formattedAmount = number_format($numericAmount, $decimalPointSettings);
        
        // Create HTML with new Saudi Riyal symbol image
        $symbolImage = '<img src="' .'/public/assets/themes/theme_fashion/assets/images/saudi-riyal-symbol.png' . '" alt="ريال سعودي" class="saudi-riyal-new-symbol" style="width: 16px; height: 16px; margin-left: 3px; vertical-align: middle;">';
        
        // Return formatted amount with new symbol image
        return $formattedAmount . ' ' . $symbolImage;
    }
}

if (!function_exists('webCurrencyConverterWithNewSymbol')) {
    /**
     * Currency converter with new Saudi Riyal symbol image
     * @param string|int|float|null $amount
     * @return string
     */
    function webCurrencyConverterWithNewSymbol($amount = 0): string
    {
        // Handle null or empty values
        if (is_null($amount) || $amount === '') {
            $amount = 0;
        }
        
        loadCurrency();
        $currencyModel = getWebConfig('currency_model');
        if ($currencyModel == MULTI_CURRENCY) {
            if (session()->has('usd')) {
                $usd = session('usd');
            } else {
                $usd = Currency::where(['code' => 'USD'])->first()->exchange_rate ?? 1;
                session()->put('usd', $usd);
            }
            $myCurrency = \session('currency_exchange_rate') ?? 1;
            $rate = $myCurrency / $usd;
        } else {
            $rate = 1;
        }
        
        $convertedAmount = $amount * $rate;
        return saudiRiyalWithNewSymbol($convertedAmount);
    }
}

// Very Simple Saudi Riyal Function - Direct Symbol (NO DUPLICATES)
if (!function_exists('saudiRiyalSimple')) {
    /**
     * Display price with simple Saudi Riyal symbol (ONE SYMBOL ONLY)
     * @param string|int|float|null $amount
     * @return string
     */
    function saudiRiyalSimple($amount = 0): string
    {
        // Handle null or empty values
        if (is_null($amount) || $amount === '') {
            $amount = 0;
        }
        
        // Convert to float and handle negative values
        $numericAmount = floatval($amount);
        
        $decimalPointSettings = getWebConfig('decimal_point_settings') ?? 2;
        $formattedAmount = number_format($numericAmount, $decimalPointSettings);
        
        // Always show Saudi Riyal symbol on the right - ONE SYMBOL ONLY
        return $formattedAmount . ' ريال';
    }
}

if (!function_exists('webCurrencyConverterSimple')) {
    /**
     * Simple currency converter with Saudi Riyal symbol (SINGLE SYMBOL ONLY)
     * @param string|int|float|null $amount
     * @return string
     */
    function webCurrencyConverterSimple($amount = 0): string
    {
        // Handle null or empty values
        if (is_null($amount) || $amount === '') {
            $amount = 0;
        }
        
        loadCurrency();
        $currencyModel = getWebConfig('currency_model');
        if ($currencyModel == MULTI_CURRENCY) {
            if (session()->has('usd')) {
                $usd = session('usd');
            } else {
                $usd = Currency::where(['code' => 'USD'])->first()->exchange_rate ?? 1;
                session()->put('usd', $usd);
            }
            $myCurrency = \session('currency_exchange_rate') ?? 1;
            $rate = $myCurrency / $usd;
        } else {
            $rate = 1;
        }
        
        $convertedAmount = $amount * $rate;
        $decimalPointSettings = getWebConfig('decimal_point_settings') ?? 2;
        $formattedAmount = number_format((float)$convertedAmount, $decimalPointSettings);
        
        // Return ONLY formatted amount with ONE Saudi Riyal symbol
        return $formattedAmount . ' ريال';
    }
}

// NEW SAUDI RIYAL FUNCTIONS - Using New Symbol ريال (2025 Update)
if (!function_exists('newSaudiRiyal')) {
    /**
     * Display price with NEW Saudi Riyal symbol ريال (2025 Official Update)
     * @param string|int|float|null $amount
     * @return string
     */
    function newSaudiRiyal($amount = 0): string
    {
        if (is_null($amount) || $amount === '') {
            $amount = 0;
        }
        
        $numericAmount = floatval($amount);
        $decimalPointSettings = getWebConfig('decimal_point_settings') ?? 2;
        $formattedAmount = number_format($numericAmount, $decimalPointSettings);
        
        // Use NEW Saudi Riyal symbol ريال - Official 2025 update
        return $formattedAmount . ' ريال';
    }
}

if (!function_exists('webCurrencyConverterNew')) {
    /**
     * Currency converter with NEW Saudi Riyal symbol ريال (2025 Official Update)
     * @param string|int|float|null $amount
     * @return string
     */
    function webCurrencyConverterNew($amount = 0): string
    {
        if (is_null($amount) || $amount === '') {
            $amount = 0;
        }
        
        loadCurrency();
        $currencyModel = getWebConfig('currency_model');
        if ($currencyModel == MULTI_CURRENCY) {
            if (session()->has('usd')) {
                $usd = session('usd');
            } else {
                $usd = Currency::where(['code' => 'USD'])->first()->exchange_rate ?? 1;
                session()->put('usd', $usd);
            }
            $myCurrency = \session('currency_exchange_rate') ?? 1;
            $rate = $myCurrency / $usd;
        } else {
            $rate = 1;
        }
        
        $convertedAmount = $amount * $rate;
        return newSaudiRiyal($convertedAmount);
    }
}

// LEGACY FUNCTIONS UPDATE - Replace old "ر.س" with new "ريال"
if (!function_exists('replaceLegacyCurrencySymbols')) {
    /**
     * Replace legacy currency symbols with new Saudi Riyal symbol
     * @param string $text
     * @return string
     */
    function replaceLegacyCurrencySymbols($text): string
    {
        // Replace old symbols with new Saudi Riyal symbol
        $text = str_replace('ر.س', 'ريال', $text);
        $text = str_replace('SAR', 'ريال', $text);
        $text = str_replace('SR', 'ريال', $text);
        
        // Clean multiple symbols to single symbol
        while (strpos($text, 'ريالريال') !== false) {
            $text = str_replace('ريالريال', 'ريال', $text);
        }
        
        return $text;
    }
}

// FINAL RECOMMENDED FUNCTION - Simple & Clean
if (!function_exists('finalSaudiPrice')) {
    /**
     * FINAL RECOMMENDED: Display price with Saudi Riyal symbol ريال
     * This is the cleanest, simplest solution for Saudi Riyal display
     * @param string|int|float|null $amount
     * @return string
     */
    function finalSaudiPrice($amount = 0): string
    {
        if (is_null($amount) || $amount === '') {
            $amount = 0;
        }
        
        $numericAmount = floatval($amount);
        $decimalPointSettings = getWebConfig('decimal_point_settings') ?? 2;
        
        // Simple, clean format with new Saudi Riyal symbol
        return number_format($numericAmount, $decimalPointSettings) . ' ريال';
    }
}

if (!function_exists('finalWebCurrencyConverter')) {
    /**
     * FINAL RECOMMENDED: Currency converter with Saudi Riyal symbol ريال
     * @param string|int|float|null $amount
     * @return string
     */
    function finalWebCurrencyConverter($amount = 0): string
    {
        if (is_null($amount) || $amount === '') {
            $amount = 0;
        }
        
        loadCurrency();
        $currencyModel = getWebConfig('currency_model');
        if ($currencyModel == MULTI_CURRENCY) {
            if (session()->has('usd')) {
                $usd = session('usd');
            } else {
                $usd = Currency::where(['code' => 'USD'])->first()->exchange_rate ?? 1;
                session()->put('usd', $usd);
            }
            $myCurrency = \session('currency_exchange_rate') ?? 1;
            $rate = $myCurrency / $usd;
        } else {
            $rate = 1;
        }
        
        $convertedAmount = $amount * $rate;
        return finalSaudiPrice($convertedAmount);
    }
}

// Currency converter with IMAGE symbol for Saudi Riyal
if (!function_exists('webCurrencyConverterWithImage')) {
    /**
     * Currency converter that displays Saudi Riyal symbol as IMAGE
     * @param string|int|float|null $amount
     * @return string
     */
    function webCurrencyConverterWithImage($amount = 0): string
    {
        if (is_null($amount) || $amount === '') {
            $amount = 0;
        }
        
        loadCurrency();
        $currencyModel = getWebConfig('currency_model');
        if ($currencyModel == MULTI_CURRENCY) {
            if (session()->has('usd')) {
                $usd = session('usd');
            } else {
                $usd = Currency::where(['code' => 'USD'])->first()->exchange_rate ?? 1;
                session()->put('usd', $usd);
            }
            $myCurrency = \session('currency_exchange_rate') ?? 1;
            $rate = $myCurrency / $usd;
        } else {
            $rate = 1;
        }
        
        $convertedAmount = $amount * $rate;
        $decimalPointSettings = getWebConfig('decimal_point_settings') ?? 2;
        $formattedAmount = number_format((float)$convertedAmount, $decimalPointSettings);
        
        // Check if current currency is SAR, then show image symbol
        $currencyCode = getCurrencyCode(type: 'web');
        if (strtoupper($currencyCode) === 'SAR') {
            // Determine image path based on current theme
            $currentTheme = theme_root_path();
            if ($currentTheme === 'theme_fashion') {
                $imagePath = '/public/assets/themes/theme_fashion/assets/images/saudi-riyal-symbol.png';
            } else {
                $imagePath = '/public/assets/front-end/img/saudi-riyal-symbol.png';
            }
            
            // Create Saudi Riyal symbol as image
            $symbolImage = '<img src="' . $imagePath . '" alt="ريال سعودي" class="saudi-riyal-symbol-img" style="width: 18px; height: 18px; margin-left: 4px; vertical-align: middle; display: inline-block;">';
            
            $position = getWebConfig('currency_symbol_position');
            if ($position === 'left') {
                return $symbolImage . ' ' . $formattedAmount;
            } else {
                return $formattedAmount . ' ' . $symbolImage;
            }
        }
        
        // For other currencies, use regular symbol
        return setCurrencySymbol(amount: $convertedAmount, currencyCode: $currencyCode, type: 'web');
    }
}

// Simple function to create Saudi Riyal image symbol
if (!function_exists('getSaudiRiyalImageSymbol')) {
    /**
     * Get Saudi Riyal symbol as image
     * @param string $size small|normal|large
     * @return string
     */
    function getSaudiRiyalImageSymbol($size = 'normal'): string
    {
        $width = '18px';
        $height = '18px';
        
        switch ($size) {
            case 'small':
                $width = '14px';
                $height = '14px';
                break;
            case 'large':
                $width = '24px';
                $height = '24px';
                break;
            default:
                $width = '18px';
                $height = '18px';
        }
        
        return '<img src="' .'/public/assets/themes/theme_fashion/assets/images/saudi-riyal-symbol.png' . '" alt="ريال سعودي" class="saudi-riyal-symbol-img" style="width: ' . $width . '; height: ' . $height . '; margin-left: 4px; vertical-align: middle; display: inline-block;">';
    }
}

// Display price with Saudi Riyal IMAGE symbol
if (!function_exists('saudiPriceWithImage')) {
    /**
     * Display price with Saudi Riyal symbol as IMAGE
     * @param string|int|float|null $amount
     * @param string $size
     * @return string
     */
    function saudiPriceWithImage($amount = 0, $size = 'normal'): string
    {
        if (is_null($amount) || $amount === '') {
            $amount = 0;
        }
        
        $numericAmount = floatval($amount);
        $decimalPointSettings = getWebConfig('decimal_point_settings') ?? 2;
        $formattedAmount = number_format($numericAmount, $decimalPointSettings);
        
        $symbolImage = getSaudiRiyalImageSymbol($size);
        
        $position = getWebConfig('currency_symbol_position') ?? 'right';
        if ($position === 'left') {
            return $symbolImage . ' ' . $formattedAmount;
        } else {
            return $formattedAmount . ' ' . $symbolImage;
        }
    }
}

