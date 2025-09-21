<?php

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

if (!function_exists('webCurrencyConverterWithNewSymbol')) {
    /**
     * Currency converter with new Saudi Riyal symbol
     * @param string|int|float|null $amount
     * @param string $size
     * @return float|string
     */
    function webCurrencyConverterWithNewSymbol(string|int|float|null $amount = 0, string $size = 'normal'): float|string
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

if (!function_exists('formatPriceWithNewSaudiSymbol')) {
    /**
     * Format price with new Saudi Riyal symbol if currency is SAR
     * @param string|int|float|null $amount
     * @param string $size
     * @return string
     */
    function formatPriceWithNewSaudiSymbol(string|int|float|null $amount = 0, string $size = 'normal'): string
    {
        $currencyCode = getCurrencyCode(type: 'web');
        
        if (strtoupper($currencyCode) === 'SAR') {
            $decimalPointSettings = getWebConfig('decimal_point_settings') ?? 2;
            $formattedAmount = number_format($amount, $decimalPointSettings);
            $position = getWebConfig('currency_symbol_position');
            
            if ($position === 'left') {
                return getSaudiRiyalSymbol($size) . ' ' . $formattedAmount;
            } else {
                return $formattedAmount . ' ' . getSaudiRiyalSymbol($size);
            }
        }
        
        return webCurrencyConverter($amount);
    }
}
