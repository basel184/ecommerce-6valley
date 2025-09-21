@php use App\Utils\Helpers; @endphp
@extends('theme-views.layouts.app')

@section('title', translate('payment').' | '.$web_config['company_name'].' '.translate('ecommerce'))

@section('content')

    <section class="breadcrumb-section pt-20px">
        <div class="container">
            <div class="section-title mb-4">
                <div
                    class="d-flex flex-wrap justify-content-between row-gap-3 column-gap-2 align-items-center search-page-title">
                    <ul class="breadcrumb">
                        <li>
                            <a href="{{route('home')}}">{{translate('home')}}</a>
                        </li>
                        <li>
                            <a href="{{route('shop-cart')}}">{{translate('cart')}}</a>
                        </li>
                        <li>
                            <a href="{{route('checkout-details')}}">{{translate('checkout')}}</a>
                        </li>
                        <li>
                            <a href="{{url()->current()}}">{{translate('payment')}}</a>
                        </li>
                    </ul>
                    <div class="ms-auto ms-md-0">
                        @if(auth('customer')->check())
                            <a href="{{route('shop-cart')}}" class="text-base custom-text-link">
                                {{ translate('check_All_CartList') }}
                            </a>
                        @else
                            <a href="javascript:" class="text-base custom-text-link customer_login_register_modal">
                                {{ translate('check_All_CartList') }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="payment-section section-gap pt-4">
        <div class="container">
            <h3 class="d-flex justify-content-center justify-content-sm-start mb-3 mb-lg-4">{{translate('payment')}}</h3>
            <div class="row g-4 payment-method-list-page">
                <div class="col-md-8 col-12 col-sm-12">
                    <ul class="checkout-flow">
                        <li class="checkout-flow-item active">
                            <a href="{{route('shop-cart')}}">
                                <span class="serial">{{ translate('1') }}</span>
                                <span class="icon">
                                    <i class="bi bi-check"></i>
                                </span>
                                <span class="text">{{translate('cart')}}</span>
                            </a>
                        </li>
                        <li class="line"></li>
                        <li class="checkout-flow-item active">
                            <a href="{{route('checkout-details')}}">
                                <span class="serial">{{ translate('2') }}</span>
                                <span class="icon">
                                    <i class="bi bi-check"></i>
                                </span>
                                <span class="text text-capitalize">{{translate('shipping_details')}}</span>
                            </a>
                        </li>
                        <li class="line"></li>
                        <li class="checkout-flow-item current">
                            <a href="javascript:">
                                <span class="serial">{{ translate('3') }}</span>
                                <span class="icon">
                                    <i class="bi bi-check"></i>
                                </span>
                                <span class="text">{{translate('payment')}}</span>
                            </a>
                        </li>
                    </ul>

                    @if(!$activeMinimumMethods)
                        <div class="d-flex justify-content-center py-5 align-items-center">
                            <div class="text-center">
                                <img src="{{ theme_asset(path: 'assets/img/icons/nodata.svg') }}" alt="" class="mb-4" width="70">
                                <h5 class="fs-14 text-muted py-4">{{ translate('payment_methods_are_not_available_at_this_time.') }}</h5>
                            </div>
                        </div>
                    @else
                        <div class="delivery-information">
                            <h6 class="font-bold letter-spacing-0 title mb-20px text-capitalize">
                                {{ translate('select_payment_method') }}
                            </h6>
                            
                            <!-- Payment Service Status Messages -->
                            @if(session('payment_error'))
                                <div class="alert alert-warning mb-4" role="alert">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                        <div>
                                            <strong>{{ translate('Payment Service Notice') }}</strong><br>
                                            {{ session('message', translate('Some payment services are temporarily unavailable. Please try another payment method.')) }}
                                        </div>
                                    </div>
                                </div>
                            @elseif(isset($payment_statuses) && !$payment_statuses['tamara_available'])
                                <div class="alert alert-info mb-4" role="alert">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-info-circle-fill me-2"></i>
                                        <div>
                                            <strong>{{ translate('Service Status Update') }}</strong><br>
                                            {{ translate('Tamara payment service is currently experiencing technical difficulties. Please use MyFatoorah or Tabby for payment.') }}
                                            <br><small class="text-muted">{{ translate('Service will be automatically restored when available.') }}</small>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            <div class="payment-area">
                                @if($cashOnDeliveryBtnShow && $cash_on_delivery['status'])
                                    <label class="payment-item">
                                        <form action="{{route('checkout-complete')}}" method="get" class="checkout-payment-form payment-method-form checkout-cash-on-payment">
                                            <input type="radio" hidden name="payment_method" value="cash_on_delivery" data-form=".checkout-cash-on-payment">
                                            <input type="hidden" class="form-control" name="bring_change_amount" id="bring_change_amount_value">
                                            <div class="payment-item-card payment-method_parent bg-transparent next-btn-enable cash_on_delivery">
                                                <img loading="lazy" src="{{theme_asset('assets/img/checkout/cash-on.png')}}"
                                                     class="icon"
                                                     alt="{{ translate('checkout') }}">
                                                <button class="content bg-transparent border-0">
                                                    <h6 class="subtitle text-start">{{translate('cash_on_delivery')}}</h6>
                                                    <p class="text-start">
                                                        {{translate('please_contact_with_deliveryman_to_confirm_your_pay_and_receive_your_order.')}}
                                                    </p>
                                                </button>
                                            </div>
                                        </form>
                                    </label>

                                    <div class="collapse py-12" id="bring_change_amount" data-more="{{ translate('See_More') }}" data-less="{{ translate('See_Less') }}">
                                        <div class="bring_change_amount_details row justify-content-start align-items-center rounded g-2 p-3">
                                            <div class="col-sm-6">
                                                <h6 class="fs-14 fw-semibold">
                                                    {{ translate('Bring_Change_Instruction') }}
                                                </h6>
                                                <p class="mb-0 fs-14 title-color opacity-50">
                                                    {{ translate('Insert_amount_if_you_need_deliveryman_to_bring') }}
                                                </p>
                                            </div>
                                            <div class="col-sm-6">
                                                <label class="fs-14 fw-semibold title-color mb-1" for="bring_change_amount_input">
                                                    {{ translate('Change_Amount') }} ({{ getCurrencySymbol(type: 'web') }})
                                                </label>
                                                <input type="text" class="form-control max-w-210px only-integer-input-field" id="bring_change_amount_input" placeholder="{{ translate('Amount') }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-center bring-change-amount-container d--none">
                                        <a id="bring_change_amount_btn" class="btn btn-link text-primary text-center text-capitalize text-decoration-none shadow-none border-0 fs-14 p-0 mb-10px" data-bs-toggle="collapse" href="#bring_change_amount" role="button" aria-expanded="false" aria-controls="change_amount">
                                            {{ translate('see_more') }}
                                        </a>
                                    </div>

                                @endif

                                @if(auth('customer')->check() && $wallet_status==1)
                                    <label class="payment-item">
                                        <div class="payment-item-card payment-method_parent">
                                            <img loading="lazy"
                                                 src="{{theme_asset('assets/img/checkout/digital-wallet.png')}}"
                                                 class="icon"
                                                 alt="{{ translate('checkout') }}">
                                            <button class="content bg-transparent border-0"
                                                    type="submit" data-bs-toggle="modal"
                                                    data-bs-target="#wallet_submit_button">
                                                <h6 class="subtitle text-start">{{translate('wallet')}}</h6>
                                                <p class="text-start">
                                                    {{translate('Payment_using_your_wallet_balance')}}
                                                </p>
                                            </button>
                                        </div>
                                    </label>
                                @endif

                                @if(isset($offline_payment) && $offline_payment['status'] && count($offline_payment_methods) > 0)
                                    <label class="payment-item">
                                        <form  action="{{route('offline-payment-checkout-complete')}}" method="get">
                                            <div class="payment-item-card payment-method_parent">
                                                <img loading="lazy"
                                                src="{{ theme_asset('assets/img/icons/cash-payment.png') }}"
                                                class="dark-support icon"
                                                alt="{{ translate('offline_payments') }}">
                                                <span class="content bg-transparent border-0"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#offline_payment_submit_button">
                                                    <h6 class="subtitle text-start">{{translate('pay_offilne')}}</h6>
                                                    <p class="text-start">
                                                        {{translate('please_contact_with_deliveryman_to_confirm_your_pay_and_receive_your_order')}}
                                                    </p>
                                                </span>
                                            </div>
                                        </form>
                                    </label>
                                @endif

                                @if($digital_payment['status'] == 1)
                                    @if(count($payment_gateways_list) > 0 || (isset($offline_payment) && $offline_payment['status'] && count($offline_payment_methods) > 0))
                                        <label class="payment-item">
                                            <div class="payment-item-card payment-method_parent">
                                                <div class="content">
                                                    <div id="digital_payment">
                                                        {{-- نموذج الدفع المشترك --}}
                                                        <form method="post" id="mainPaymentForm" class="w-100" action="{{ route('customer.web-payment-request') }}">
                                                            @csrf
                                                            <input type="hidden" name="user_id" value="{{ auth('customer')->check() ? auth('customer')->user()->id : session('guest_id') }}">
                                                            <input type="hidden" name="customer_id" value="{{ auth('customer')->check() ? auth('customer')->user()->id : session('guest_id') }}">
                                                            <input type="hidden" name="payment_method" id="selectedPaymentMethod" value="fatoorah">
                                                            <input type="hidden" name="payment_platform" value="web">
                                                            <input type="hidden" name="payment_request_from" value="web">
                                                            <input type="hidden" name="is_guest" value="{{ auth('customer')->check() ? '0' : '1' }}">
                                                            <input type="hidden" name="callback" value="">
                                                            <input type="hidden" name="external_redirect_link" value="{{ route('web-payment-success') }}">
                                                            
                                                            <div class="mt-3 row g-3 justify-content-center">
                                                                <!-- بوابة دفع MyFatoorah -->
                                                                <div class="col-xl-4 col-md-6 col-12">
                                                                    <div class="digital-payment-card card d-flex align-items-center justify-content-center overflow-hidden modern-payment-card payment-option selected" data-payment="fatoorah">
                                                                        <!-- علامة التحديد -->
                                                                        <div class="payment-selection-indicator">
                                                                            <i class="bi bi-check-circle-fill"></i>
                                                                        </div>
                                                                        
                                                                        <div class="payment-card-content w-100 h-100 d-flex align-items-center justify-content-center px-3 py-4">
                                                                            <img loading="lazy" width="120"
                                                                                 class="dark-support mw-100"
                                                                                 alt="{{ translate('MyFatoorah') }}"
                                                                                 src="{{ asset('/public/assets/back-end/img/modal/payment-methods/all-pays.png') }}">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                
                                                                <!-- بوابة دفع Tabby -->
                                                                <div class="col-xl-4 col-md-6 col-6">
                                                                    <div class="digital-payment-card card d-flex align-items-center justify-content-center overflow-hidden modern-payment-card payment-option" data-payment="tabby">
                                                                        <!-- علامة التحديد -->
                                                                        <div class="payment-selection-indicator">
                                                                            <i class="bi bi-check-circle-fill"></i>
                                                                        </div>
                                                                        
                                                                        <div class="payment-card-content w-100 h-100 d-flex align-items-center justify-content-center px-3 py-4">
                                                                            <img loading="lazy" width="120"
                                                                                 class="dark-support mw-100"
                                                                                 alt="{{ translate('Tabby') }}"
                                                                                 src="{{ asset('/public/assets/back-end/img/modal/payment-methods/tappy.webp') }}">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                
                                                                <!-- بوابة دفع Tamara -->
                                                                <div class="col-xl-4 col-md-6 col-6">
                                                                    <div class="digital-payment-card card d-flex align-items-center justify-content-center overflow-hidden modern-payment-card payment-option" data-payment="tamara">
                                                                        <!-- علامة التحديد -->
                                                                        <div class="payment-selection-indicator">
                                                                            <i class="bi bi-check-circle-fill"></i>
                                                                        </div>
                                                                        <div class="payment-card-content w-100 h-100 d-flex align-items-center justify-content-center px-3 py-4">
                                                                            <img loading="lazy" width="120"
                                                                                 class="dark-support mw-100"
                                                                                 alt="{{ translate('Tamara') }}"
                                                                                 src="{{ asset('/public/assets/back-end/img/modal/payment-methods/tamara.png') }}">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        
                                                        {{-- زر الدفع الرئيسي --}}
                                                        <div class="row mt-4">
                                                            <div class="col-12 text-center">
                                                                <button type="submit" id="proceedPaymentBtn" class="btn btn-primary btn-lg px-5 py-3 text-capitalize">
                                                                    <i class="bi bi-credit-card me-2"></i>
                                                                    <span id="paymentButtonText">الدفع عبر ماي فاتورة</span>
                                                                </button>
                                                            </div>
                                                        </div>
                                                        
                                                    </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </label>
                                    @endif
                                @endif

                                @if(auth('customer')->check() && $wallet_status==1)
                                    <div class="modal fade" id="wallet_submit_button">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header px-sm-5">
                                                    <h5 class="modal-title"
                                                        id="exampleModalLongTitle">{{translate('wallet_payment')}}</h5>
                                                    <button type="button" class="btn-close outside"
                                                            data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                @php($customer_balance = auth('customer')->user()->wallet_balance)
                                                @php($remain_balance = $customer_balance - $amount)
                                                <form action="{{route('checkout-complete-wallet')}}" method="get"
                                                      class="needs-validation checkout-wallet-payment-form">
                                                    @csrf
                                                    <div class="modal-body px-sm-5">
                                                        <div class="form-group mb-2">
                                                            <label
                                                                class="form-label mb-2">{{translate('your_current_balance')}}</label>
                                                            <input class="form-control" type="text"
                                                                   value="{{webCurrencyConverter($customer_balance)}}"
                                                                   readonly>
                                                        </div>

                                                        <div class="form-group mb-2">
                                                            <label
                                                                class="form-label mb-2">{{translate('order_amount')}}</label>
                                                            <input class="form-control" type="text"
                                                                   value="{{webCurrencyConverter($amount)}}"
                                                                   readonly>
                                                        </div>
                                                        <div class="form-group mb-2">
                                                            <label
                                                                class="form-label mb-2">{{translate('remaining_balance')}}</label>
                                                            <input class="form-control" type="text"
                                                                   value="{{webCurrencyConverter($remain_balance)}}"
                                                                   readonly>
                                                            @if ($remain_balance<0)
                                                                <label
                                                                    class="__color-crimson">{{translate('you_do_not_have_sufficient_balance_for_pay_this_order!!')}}</label>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer py-2 gap-1 px-sm-5">
                                                        <button type="button"
                                                                class="update_cart_button fs-16 btn btn-base secondary-color"
                                                                data-bs-dismiss="modal">
                                                            {{translate('close')}}
                                                        </button>
                                                        <button type="submit"
                                                                class="update_cart_button update_wallet_cart_button fs-16 btn btn-base" {{$remain_balance>0? '':'disabled'}}>
                                                            {{translate('submit')}}
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                @if ($digital_payment['status']==1)
                                    <div class="modal fade offline-payment-modal" id="offline_payment_submit_button">
                                        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header px-sm-5">
                                                    <h5 class="modal-title"
                                                        id="exampleModalLongTitle">{{translate('offline_payment')}}</h5>
                                                    <button type="button" class="btn-close outside" data-bs-dismiss="modal"
                                                            aria-label="Close"></button>
                                                </div>
                                                <form action="{{route('offline-payment-checkout-complete')}}" method="post"
                                                      class="needs-validation form-loading-button-form">
                                                    @csrf
                                                    <div class="modal-body p-3 p-md-5">

                                                        <div class="text-center px-5">
                                                            <img loading="lazy"
                                                                 src="{{ theme_asset('assets/img/offline-payments.png') }}"
                                                                 alt="{{ translate('offline_payments') }}">
                                                            <p class="py-2">
                                                                {{ translate('pay_your_bill_using_any_of_the_payment_method_below_and_input_the_required_information_in_the_form') }}
                                                            </p>
                                                        </div>

                                                        <div class="">
                                                            <select class="form-select" id="pay_offline_method"
                                                                    name="payment_by" required>
                                                                <option
                                                                    value="">{{ translate('select_Payment_Method') }}</option>
                                                                @foreach ($offline_payment_methods as $method)
                                                                    <option
                                                                        value="{{ $method->id }}">{{ translate('payment_Method') }}
                                                                        :
                                                                        {{ $method->method_name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>

                                                        <div class="" id="method-filed__div">
                                                            <div class="text-center py-5">
                                                                <img loading="lazy" class="pt-5"
                                                                     src="{{ theme_asset('assets/img/offline-payments-vectors.png') }}"
                                                                     alt="{{ translate('offline_payments') }}">
                                                                <p class="py-2 pb-5 text-muted">{{ translate('select_a_payment_method_first') }}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                            </div>
                        </div>
                    @endif


                </div>

                <div class="d-none col-md-4 col-sm-12">
                    @include('theme-views.partials._order-summery')
                </div>
            </div>
        </div>
    </section>

    <span id="route-pay-offline-method-list" data-route="{{ route('pay-offline-method-list') }}"></span>
    <span id="text-redirecting-to-the-payment" data-text="{{ translate('Redirecting_to_the_payment') }}"></span>

    <!-- Payment option selection script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var paymentOptions = document.querySelectorAll('.payment-option');
            var selectedField = document.getElementById('selectedPaymentMethod');
            var buttonTextElem = document.getElementById('paymentButtonText');
            var texts = {
                fatoorah: '{{ translate("الدفع عبر ماي فاتورة") }}',
                tabby: '{{ translate("الدفع عبر تابِّي") }}',
                tamara: '{{ translate("الدفع عبر تمارا") }}'
            };
            paymentOptions.forEach(function(option) {
                option.addEventListener('click', function() {
                    if(option.classList.contains('payment-disabled')) return;
                    paymentOptions.forEach(function(el){ el.classList.remove('selected'); });
                    option.classList.add('selected');
                    var method = option.getAttribute('data-payment');
                    if(selectedField) selectedField.value = method;
                    if(buttonTextElem && texts[method]) buttonTextElem.textContent = texts[method];
                });
            });
        });
    </script>
@endsection

<style>
    .total-cost-wrapper {
        top: 0 !important;
    }
    /* تحسين تصميم بوابات الدفع */
    .modern-payment-card {
        background: linear-gradient(70deg, rgba(234,220,210,1) 0%, rgba(179,148,129,1) 100%);
        border: 2px solid rgba(255,255,255,0.3);
        border-radius: 15px;
        box-shadow: 0 8px 25px rgba(179,148,129,0.3);
        transition: all 0.3s ease;
        min-height: 120px;
        position: relative;
        overflow: hidden;
    }
    
    .modern-payment-card:before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: left 0.5s;
    }
    
    .modern-payment-card:hover:before {
        left: 100%;
    }
    
    .modern-payment-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(179,148,129,0.4);
        border-color: rgba(255,255,255,0.5);
    }
    
    .modern-payment-card img {
        filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));
        transition: transform 0.3s ease;
        width: 20%;
    }
    
    .modern-payment-card:hover img {
        transform: scale(1.05);
    }
    
    /* تحسين تخطيط الصفحة */
    .payment-section {
        background: linear-gradient(135deg, rgba(247,250,252,1) 0%, rgba(243,246,249,1) 100%);
        min-height: 100vh;
    }
    
    .payment-area {
        background: rgba(255,255,255,0.8);
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    
    .payment-item-card {
        background: rgba(255,255,255,0.9);
        border: 2px solid rgba(179,148,129,0.2);
        border-radius: 15px;
        transition: all 0.3s ease;
    }
    
    .payment-item-card:hover {
        border-color: rgba(179,148,129,0.4);
        box-shadow: 0 8px 20px rgba(179,148,129,0.2);
    }
    
    /* تحسين العناوين */
    .payment-section h3,
    .payment-section h6 {
        color: #8B4513;
        font-weight: bold;
    }
    
    /* تحسين الأزرار */
    .digital-payment-card_btn:hover {
        background: rgba(255,255,255,0.1) !important;
    }
    
    /* تحسين التصميم المتجاوب */
    @media (max-width: 768px) {
        .modern-payment-card {
            min-height: 100px;
        }
        
        .modern-payment-card img {
            width: 200px !important;
        }
        
        .payment-area {
            padding: 20px;
        }
    }
    
    /* تحسين checkout flow */
    .checkout-flow-item.active .serial,
    .checkout-flow-item.current .serial {
        background: linear-gradient(45deg, rgba(179,148,129,1), rgba(160,82,45,1));
        color: white;
    }
    
    .checkout-flow-item.active .icon,
    .checkout-flow-item.current .icon {
        background: linear-gradient(45deg, rgba(179,148,129,1), rgba(160,82,45,1));
        color: white;
    }
    
    /* Payment Service Status Styles */
    .payment-disabled {
        opacity: 0.6;
        pointer-events: none;
        position: relative;
    }
    
    .payment-disabled::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255,255,255,0.7);
        z-index: 1;
    }
    

    
    .payment-status-overlay i {
        font-size: 24px;
        margin-bottom: 5px;
        display: block;
    }
    
    .payment-status-overlay .status-text {
        font-size: 12px;
        font-weight: 600;
        color: #856404;
    }
    
    .alert-warning {
        background: linear-gradient(135deg, rgba(255,193,7,0.1), rgba(255,193,7,0.05));
        border: 1px solid rgba(255,193,7,0.3);
        border-radius: 10px;
        color: #856404;
    }
    
    .alert-warning i {
        color: #ffc107;
    }
    
    /* Payment Selection Indicator */
    .payment-selection-indicator {
        position: absolute;
        top: 10px;
        right: 10px;
        z-index: 10;
        color: #28a745;
        font-size: 20px;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .payment-option.selected .payment-selection-indicator {
        opacity: 1;
    }
    
    .payment-option {
        cursor: pointer;
        position: relative;
    }
    
    .payment-option:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    }
    
    /* Digital Payment Card Styles */
    .digital-payment-card {
        min-height: 120px;
        border: 2px solid #e9ecef;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .digital-payment-card.selected {
        border-color: #28a745;
        background: rgba(40, 167, 69, 0.05);
    }
    
    .payment-card-content img {
        max-width: 120px;
        height: auto;
    }
    #mobile_app_bar {
        z-index: 0 !important;
    }
</style>
