{{-- نستخدم القالب بطريقة مختلفة بما أن مسار القالب غير واضح --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>{{ function_exists('translate') ? translate('Alternative Payment Methods') : 'Alternative Payment Methods' }}</title>
    <meta name="description" content="Alternative Payment Methods">
    <link rel="stylesheet" href="{{ asset('public/assets/front-end/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/front-end/css/style.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px 0;
        }
        .card {
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card-header {
            border-radius: 8px 8px 0 0;
            font-weight: bold;
        }
        .btn-primary {
            background-color: #3490dc;
            border-color: #3490dc;
        }
        .btn-primary:hover {
            background-color: #2779bd;
            border-color: #2779bd;
        }
        .alert {
            border-radius: 8px;
        }
    </style>
</head>
<body>

<div class="container py-4 mb-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-light">
                    <h4 class="mb-0">{{ function_exists('translate') ? translate('Choose an Alternative Payment Method') : 'Choose an Alternative Payment Method' }}</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="fa fa-exclamation-triangle mr-2"></i>
                        <p class="mb-0">{{ function_exists('translate') ? translate('The payment gateway') : 'The payment gateway' }} <strong>{{ $failed_gateway }}</strong> {{ function_exists('translate') ? translate('is temporarily unavailable. Please choose another payment method to continue.') : 'is temporarily unavailable. Please choose another payment method to continue.' }}</p>
                    </div>
                    
                    <div class="alert alert-info mt-3">
                        <i class="fa fa-info-circle mr-2"></i>
                        <p class="mb-0">{{ function_exists('translate') ? translate('Your order details have been saved. You only need to select a payment method to complete your purchase.') : 'Your order details have been saved. You only need to select a payment method to complete your purchase.' }}</p>
                    </div>
                    
                    @php
                        $payment_methods = [
                            'sslcommerz' => 'SSL Commerz',
                            'stripe' => 'Stripe',
                            'paypal' => 'PayPal',
                            'razor-pay' => 'Razor Pay',
                            'paytm' => 'Paytm',
                                'paytabs' => 'PayTabs',
                                'flutterwave-v3' => 'Flutterwave',
                                'paystack' => 'Paystack',
                                'senang-pay' => 'SenangPay',
                                'bkash' => 'bKash',
                                'mercadopago' => 'MercadoPago',
                                'liqpay' => 'LiqPay',
                                'paymob' => 'Paymob',
                                'fatoorah' => 'MyFatoorah',
                                'tabby' => 'Tabby',
                                'tamara' => 'Tamara',
                            ];
                            
                            // Remove the failed gateway
                            if (isset($payment_methods[$failed_gateway])) {
                                unset($payment_methods[$failed_gateway]);
                            }
                        @endphp
                        
                        @if(is_array($payment_methods) && count($payment_methods) > 6)
                        <div class="input-group mt-3 mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fa fa-search"></i></span>
                            </div>
                            <input type="text" id="paymentMethodSearch" class="form-control" placeholder="{{ function_exists('translate') ? translate('Search payment methods...') : 'Search payment methods...' }}" onkeyup="filterPaymentMethods()">
                        </div>
                        @endif
                        
                        <div class="row mt-4">
                        @if(empty($payment_methods))
                            <div class="col-12">
                                <div class="alert alert-danger text-center">
                                    <i class="fa fa-exclamation-circle mb-2" style="font-size: 24px;"></i>
                                    <p>{{ function_exists('translate') ? translate('No alternative payment methods are available at the moment. Please try again later or contact customer support.') : 'No alternative payment methods are available at the moment. Please try again later or contact customer support.' }}</p>
                                </div>
                            </div>
                        @else
                            @foreach($payment_methods as $method_key => $method_name)
                            <div class="col-md-4 mb-3">
                                <div class="card h-100">
                                    <div class="card-body d-flex flex-column align-items-center justify-content-center">
                                        <img src="{{ asset('public/assets/front-end/img/payment-methods/'.$method_key.'.png') }}"
                                            onerror="this.src='{{ asset('public/assets/front-end/img/payment-methods/placeholder.png') }}'"
                                            class="mb-2" style="height: 40px;">
                                        <h5 class="text-center">{{ $method_name }}</h5>
                                        
                                        @if($method_key == 'fatoorah')
                                            {{-- MyFatoorah uses a different route name --}}
                                            <a href="{{ route('myfatoorah.form', ['payment_id' => $payment_data->id]) }}" 
                                               class="btn btn-primary btn-sm mt-2">{{ function_exists('translate') ? translate('Pay with') : 'Pay with' }} {{ $method_name }}</a>
                                        @elseif($method_key == 'bkash')
                                            {{-- bKash uses make-payment instead of pay --}}
                                            <a href="{{ route('bkash.make-payment', ['payment_id' => $payment_data->id]) }}" 
                                               class="btn btn-primary btn-sm mt-2">{{ function_exists('translate') ? translate('Pay with') : 'Pay with' }} {{ $method_name }}</a>
                                        @elseif($method_key == 'liqpay')
                                            {{-- LiqPay uses payment instead of pay --}}
                                            <a href="{{ route('liqpay.payment', ['payment_id' => $payment_data->id]) }}" 
                                               class="btn btn-primary btn-sm mt-2">{{ function_exists('translate') ? translate('Pay with') : 'Pay with' }} {{ $method_name }}</a>
                                        @else
                                            {{-- Standard route pattern for most payment gateways --}}
                                            <a href="{{ route($method_key.'.pay', ['payment_id' => $payment_data->id]) }}" 
                                               class="btn btn-primary btn-sm mt-2">{{ function_exists('translate') ? translate('Pay with') : 'Pay with' }} {{ $method_name }}</a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        @endif
                    </div>
                    
                    <div class="text-center mt-4">
                        <a href="{{ url('/') }}" class="btn btn-outline-secondary">
                            <i class="fa fa-arrow-left"></i> {{ function_exists('translate') ? translate('Go Back to Homepage') : 'Go Back to Homepage' }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function filterPaymentMethods() {
        try {
            var input, filter, cards, card, i;
            input = document.getElementById("paymentMethodSearch");
            
            if (!input) {
                return; // Element not found
            }
            
            filter = input.value.toUpperCase();
            cards = document.querySelectorAll(".card-body h5");
            
            for (i = 0; i < cards.length; i++) {
                card = cards[i];
                var parentColumn = card.closest(".col-md-4");
                
                if (parentColumn) {
                    if (card.textContent.toUpperCase().indexOf(filter) > -1) {
                        parentColumn.style.display = "";
                    } else {
                        parentColumn.style.display = "none";
                    }
                }
            }
        } catch (error) {
            console.error("Error filtering payment methods:", error);
        }
    }
</script>
</body>
</html>
