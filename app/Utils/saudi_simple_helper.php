<?php

// Simple Saudi Riyal Helper Function
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
        
        $position = getWebConfig('currency_symbol_position');
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
