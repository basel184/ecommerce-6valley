<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ session()->get('direction') }}">

<head>
<meta name="google-site-verification" content="wG3-U3Wpi5sKn1J2Rsk2NcVC5PvPOIKyU61TWMftGec" />
    <meta charset="UTF-8">
    <meta name="base-url" content="{{ url('/') }}">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index, follow">
    <meta name="csrf-token" content="{{csrf_token()}}">
    <meta property="og:site_name" content="{{ $web_config['company_name'] }}" />

    <title>@yield('title')</title>

    <meta name="google-site-verification" content="{{getWebConfig('google_search_console_code')}}">
    <meta name="msvalidate.01" content="{{getWebConfig('bing_webmaster_code')}}">
    <meta name="baidu-site-verification" content="{{getWebConfig('baidu_webmaster_code')}}">
    <meta name="yandex-verification" content="{{getWebConfig('yandex_webmaster_code')}}">

    <link rel="apple-touch-icon" sizes="180x180" href="{{$web_config['fav_icon']['path']}}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{$web_config['fav_icon']['path']}}">

    <link rel="stylesheet" type="text/css" href="{{ theme_asset('assets/css/'.(session()->get('direction') == 'rtl' ? 'bootstrap-rtl.min.css': 'bootstrap.css' )) }}" />

    <link rel="stylesheet" media="screen" href="{{ theme_asset('assets/plugins/font-awesome-4.7.0/css/font-awesome.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ theme_asset('assets/css/roboto-font.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ theme_asset('assets/css/bootstrap-icons.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ theme_asset('assets/css/owl.min.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ theme_asset('assets/css/nouislider.min.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ theme_asset('assets/css/select2.min.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ theme_asset('assets/plugins/magnific-popup-1.1.0/magnific-popup.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ theme_asset('assets/plugins/sweet_alert/sweetalert2.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ theme_asset('assets/css/toastr.css')}}" />
    <link rel="stylesheet" type="text/css" href="{{ theme_asset('assets/css/main.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ theme_asset('assets/plugins/intl-tel-input/css/intlTelInput.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ theme_asset('assets/css/custom.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ theme_asset('assets/css/saudi-riyal-fixed.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ theme_asset('assets/css/order-summary-fix.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ theme_asset('assets/css/payment-selection.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ theme_asset('assets/css/gif-banners.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ theme_asset('assets/css/brand-slider.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ theme_asset('assets/css/promotional-banners.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ theme_asset('assets/css/signature-slider.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ theme_asset('assets/css/signature-force.css') }}" />
    @if(session()->get('direction') == 'rtl')
    <link rel="stylesheet" type="text/css" href="{{ theme_asset('assets/css/rtl-banner.css') }}" />
    @endif

    <link rel="shortcut icon" href="{{$web_config['fav_icon']['path']}}" type="image/x-icon" />

    @stack('css_or_js')
    @include(VIEW_FILE_NAMES['robots_meta_content_partials'])

    <style>
        .proceed-cart-btn button , .btn-base  {
            background: #76d671;
        }
        @media screen and (max-width: 1199.98px) {
            
            .mobile-search-form-wrapper .input-group{
                padding-inline-start: 0;
            }
            .mobile-search-form-wrapper .input-group::before {
                display: none;
            }
            .most-visited-category-wrapper .most-visited-item img {
                height: 100px;
            }
        }
        
        :root {
            --base: {{ $web_config['primary_color'] }};
            --base-rgb: {{ getHexToRGBColorCode($web_config['primary_color']) }};
            --base-3: {{ $web_config['primary_color'] }};
            --base-4: {{ $web_config['primary_color_light'] }};
            --base-2: {{ $web_config['secondary_color'] }};
            --bs-2-rgb: {{ getHexToRGBColorCode($web_config['secondary_color']) }};

            --bs-btn-disabled-bg:{{ $web_config['primary_color'] }} ;
            --bs-btn-disabled-border-color: {{ $web_config['primary_color'] }};
            --bs-btn-disabled-color: #fff;
        }
        .secondary-color,
        .btn-base.secondary-color {
            --base: {{ $web_config['secondary_color'] }};
            --base-rgb: {{ getHexToRGBColorCode($web_config['secondary_color']) }};
            --border: {{ $web_config['secondary_color'] }};
            --bs-2-rgb: {{ getHexToRGBColorCode($web_config['secondary_color']) }};

            --bs-btn-disabled-color: #00000085;
            --bs-btn-disabled-bg:{{ $web_config['secondary_color'] }} ;
            --bs-btn-disabled-border-color: {{ $web_config['secondary_color'] }}75;
        }
        .__btn-outline-warning.secondary-color {
            --base: {{ $web_config['primary_color'] }};
            --base-rgb: {{ getHexToRGBColorCode($web_config['primary_color']) }};
            --base-3: {{ $web_config['primary_color'] }};
            --base-4: {{ $web_config['primary_color_light'] }};
            --base-2: {{ $web_config['secondary_color'] }};
            --bs-2-rgb: {{ getHexToRGBColorCode($web_config['secondary_color']) }};
            --warning: {{ $web_config['secondary_color'] }};
            --warning-rgb: {{ getHexToRGBColorCode($web_config['secondary_color']) }};

            --bs-btn-disabled-color: #00000085;
            --bs-btn-disabled-bg: transparent ;
            --bs-btn-disabled-border-color: {{ $web_config['secondary_color'] }}75;
        }
        @if (isset($web_config['announcement']) && $web_config['announcement']['status']==1)
            .offer-bar {
                background-color : {{ $web_config['announcement']['color'] }};
                color : {{ $web_config['announcement']['text_color'] }};
            }
        @endif
        .header-middle {
            display: none ;
        }
    </style>

    <script src="{{ theme_asset('assets/js/jquery-3.7.1.min.js') }}"></script>

    {!! getSystemDynamicPartials(type: 'analytics_script') !!}
</head>

<body>

    <script>
        "use strict";
        function setThemeMode() {
            if (localStorage.getItem('theme') === null) {
                document.body.setAttribute('theme', 'light');
            } else {
                document.body.setAttribute('theme', localStorage.getItem('theme'));
            }
        }
        setThemeMode();
    </script>

    <div class="overlay"></div>
    <div class="preloader d--none" id="loading">
        <img loading="lazy" width="200" alt="{{ translate('loader') }}"
             src="{{ getStorageImages(path: getWebConfig(name: 'loader_gif'), type: 'source', source: theme_asset('assets/img/loader.gif')) }}">
    </div>

    @include('theme-views.layouts.partials._header')
    @include('theme-views.layouts.partials.modal._quick-view')
    @include('theme-views.layouts.partials.modal._buy-now')
    <div id="login-and-register-modal-section"></div>

    @php($cookie = $web_config['cookie_setting'] ? json_decode($web_config['cookie_setting']['value'], true):null)
    @if($cookie && $cookie['status']==1)
        <section class="cookie-section" id="cookie-section"></section>
    @endif

    @include('theme-views.layouts.partials._alert-message')

    @include('theme-views.layouts.partials.modal._initial')
    @include('theme-views.layouts.partials.modal._alert')

    @yield('content')

    <div class="app-bar px-sm-2 d-xl-none" id="mobile_app_bar">
        @include('theme-views.layouts.partials._app-bar')
    </div>

    @include('theme-views.layouts.partials._footer')

    <span class="system-default-country-code" data-value="{{ getWebConfig(name: 'country_code') ?? 'us' }}"></span>
    <span id="get-login-modal-data" data-route="{{route('customer.auth.get-login-modal-data')}}"></span>
    <span id="get-current-route-name" data-route="{{ Route::currentRouteName() }}"></span>
    <span id="update_nav_cart_url" data-url="{{route('cart.nav-cart')}}"></span>
    <span id="update_floating_nav_cart_url" data-url="{{route('cart.floating-nav-cart-items')}}"></span>
    <span id="update_quantity_url" data-url="{{route('cart.updateQuantity.guest')}}"></span>
    <span id="update_nav_cart_url" data-url="{{route('cart.nav-cart')}}"></span>
    <span id="remove_from_cart_url" data-url="{{ route('cart.remove') }}"></span>
    <span id="update_quantity_basic_url" data-url="{{route('cart.updateQuantity')}}"></span>
    <span id="order_again_url" data-url="{{ route('cart.order-again') }}"></span>
    <span id="store_wishlist_url" data-url="{{ route('store-wishlist') }}"></span>
    <span id="quick_view_url" data-url="{{ route('quick-view') }}"></span>
    <span id="delete_wishlist_url" data-url="{{ route('delete-wishlist') }}"></span>
    <span id="order_note_url" data-url="{{ route('order_note') }}"></span>
    <span id="store_compare_list_url" data-url="{{ route('product-compare.index') }}"></span>
    <span id="digital_product_download_otp_reset" data-route="{{ route('digital-product-download-otp-reset') }}"></span>
    <span id="set_shipping_url" data-url="{{url('/')}}/customer/set-shipping-method"></span>
    <span id="authentication-status" data-auth="{{ auth('customer')->check() ? 'true' : 'false' }}"></span>
    <span class="cannot_use_zero" data-text="{{ translate('cannot_Use_0_only') }}"></span>
    <span class="out_of_stock" data-text="{{ translate('Out_Of_Stock') }}"></span>
    <span class="minimum_order_quantity_msg" data-text="{{ translate('minimum_order_quantity_cannot_be_less_than') }}"></span>
    <span class="item_has_been_removed_from_cart" data-text="{{ translate('item_has_been_removed_from_cart') }}"></span>
    <span class="please_fill_out_this_field" data-text="{{ translate('please_fill_out_this_field') }}"></span>
    <span class="text-wishList" data-text="{{ translate('wishlist') }}"></span>
    <span id="route-cart-variant-price" data-url="{{ route('cart.variant_price') }}"></span>
    <span id="route-checkout-details" data-url="{{ route('checkout-details') }}"></span>
    <span id="route-checkout-payment" data-url="{{ route('checkout-payment') }}"></span>
    <span class="text-otp-related"
          data-otpsendagain="{{ translate('OTP_has_been_sent_again') }}"
          data-otpnewcode="{{ translate('please_wait_for_new_code') }}"
    ></span>
    <span class="login-warning" data-login-warning-message = "{{translate('please_login_your_account')}}"> </span>
    <span class="system-default-country-code" data-value="{{ getWebConfig(name: 'country_code') ?? 'us' }}"></span>
    <span id="password-error-message" data-max-character="{{translate('at_least_8_characters').'.'}}" data-uppercase-character="{{translate('at_least_one_uppercase_letter_').'(A...Z)'.'.'}}" data-lowercase-character="{{translate('at_least_one_uppercase_letter_').'(a...z)'.'.'}}"
          data-number="{{translate('at_least_one_number').'(0...9)'.'.'}}" data-symbol="{{translate('at_least_one_symbol').'(!...%)'.'.'}}"></span>
    <span id="message-please-check-recaptcha" data-text="{{ translate('please_check_the_recaptcha') }}"></span>
    <span id="route-product-restock-request" data-url="{{ route('cart.product-restock-request') }}"></span>
    <span id="message-copied-failed" data-text="{{ translate('copied_failed') }}"></span>
    <span id="exceeds10MBSizeLimit" data-text="{{ translate('File_exceeds_10MB_size_limit') }}"></span>

    <span class="text-custom-storage"
        data-textno="{{ translate('no') }}"
        data-textyes="{{ translate('yes') }}"
        data-textnow="{{ translate('now') }}"
        data-textsuccessfullycopied="{{ translate('successfully_copied') }}"
        data-text-no-discount="{{ translate('no_discount') }}"
        data-stock-available="{{ translate('stock_available') }}"
        data-stock-not-available="{{ translate('stock_not_available') }}"
        data-update-this-address="{{ translate('update_this_Address') }}"
        data-password-characters-limit="{{ translate('your_password_must_be_at_least_8_characters') }}"
        data-password-not-match="{{ translate('password_does_not_Match') }}"
        data-textpleaseselectpaymentmethods="{{ translate('please_select_a_payment_Methods') }}"
        data-reviewmessage="{{ translate('you_can_review_after_the_product_is_delivered') }}"
        data-refundmessage="{{ translate('you_can_refund_request_after_the_product_is_delivered') }}"
        data-textshoptemporaryclose="{{ translate('This_shop_is_temporary_closed_or_on_vacation').' '.translate('You_cannot_add_product_to_cart_from_this_shop_for_now') }}"
    ></span>

    @php($whatsapp = getWebConfig(name: 'whatsapp'))
    <div class="social-chat-icons">
        @if(isset($whatsapp['status']) && $whatsapp['status'] == 1 )
            <div class="">
                <a href="https://wa.me/{{ $whatsapp['phone'] }}?text=Hello%20there!" target="_blank">
                    <img loading="lazy" src="{{theme_asset('assets/img/whatsapp.svg')}}" width="35" class="chat-image-shadow"
                         alt="{{ translate('Chat_with_us_on_WhatsApp') }}">
                </a>
            </div>
        @endif
    </div>

    <script src="{{ theme_asset('assets/js/owl.min.js') }}"></script>
    <script src="{{ theme_asset('assets/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ theme_asset('assets/js/countdown.js') }}"></script>
    <script src="{{ theme_asset('assets/js/nouislider.min.js') }}"></script>
    <script src="{{ theme_asset('assets/js/select2.min.js') }}"></script>
    <script src="{{ theme_asset('assets/js/easyzoom.js') }}"></script>
    <script src="{{ theme_asset('assets/plugins/magnific-popup-1.1.0/jquery.magnific-popup.js') }}"></script>
    <script src="{{ theme_asset('assets/plugins/sweet_alert/sweetalert2.js') }}"></script>

    <script src="{{ theme_asset('assets/js/main.js') }}"></script>
    <script src="{{ theme_asset('assets/js/toastr.js') }}"></script>

    <script src="{{ theme_asset('assets/plugins/intl-tel-input/js/intlTelInput.js') }}"></script>
    <script src="{{ theme_asset('assets/js/country-picker-init.js') }}"></script>

    <script src="{{ theme_asset('assets/js/product-view.js') }}"></script>
    <script src="{{ theme_asset('assets/js/custom.js') }}"></script>

    {!! Toastr::message() !!}

    @include('theme-views.layouts._firebase-script')

    <script>
        "use strict";
        
        // Setup CSRF token for all AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        @if ($errors->any())
            @foreach($errors->all() as $error)
                toastr.error('{{$error}}', Error, {
                    CloseButton: true,
                    ProgressBar: true
                });
            @endforeach
        @endif

        @if(Request::is('/') &&  \Illuminate\Support\Facades\Cookie::has('popup_banner')==false)
            $(window).on('load', function () {
                $('#initialModal').modal('show');
            });
            @php(\Illuminate\Support\Facades\Cookie::queue('popup_banner', 'off', 1))
        @endif

        @php($cookie = $web_config['cookie_setting'] ? json_decode($web_config['cookie_setting']['value'], true):null)
        let cookie_content = `<div class="container">
                                <div class="d-flex flex-wrap align-items-center justify-content-between column-gap-4 row-gap-3">
                                    <div class="text-wrapper">
                                        <h5 class="title">{{translate('Your_Privacy_Matter')}}</h5>
                                        <div>{{ $cookie ? $cookie['cookie_text'] : '' }}</div>
                                    </div>
                                    <div class="btn-wrapper">
                                        <button type="button" class="btn absolute-white btn-link" id="cookie-reject">{{translate('no')}}, {{translate('thanks')}}</button>
                                        <button type="button" class="btn btn-success cookie-accept" id="cookie-accept">{{translate('yes')}}, {{translate('accept_All_Cookies')}}</button>
                                    </div>
                                </div>
                            </div>`;

        @if(!auth('customer')->check())
            $(document).ready(function() {
                const currentUrl = new URL(window.location.href);
                const referralCodeParameter = new URLSearchParams(currentUrl.search).get("referral_code");

                if (referralCodeParameter) {
                    customerSignUpModalCall();
                    let referralCodeElement = $('#referral_code');
                    if (referralCodeElement.length) {
                        referralCodeElement.val(referralCodeParameter);
                    }
                }
            });
        @endif
    </script>

    @if(env('APP_MODE') == 'demo')
        <script>
            'use strict'
            function checkDemoResetTime() {
                let currentMinute = new Date().getMinutes();
                if (currentMinute > 55 && currentMinute <= 60) {
                    $('#demo-reset-warning').addClass('active');
                } else {
                    $('#demo-reset-warning').removeClass('active');
                }
            }
            checkDemoResetTime();
            setInterval(checkDemoResetTime, 60000);
        </script>
    @endif

    @stack('script')

    {{-- تثبيت شريط التطبيق المحمول - إزالة خاصية الإخفاء عند التمرير --}}
    
    <style>
        /* تثبيت شريط التطبيق المحمول */
        #mobile_app_bar {
            position: fixed !important;
            bottom: 0 !important;
            left: 0 !important;
            right: 0 !important;
            background-color: var(--body-bg) !important;
            box-shadow: 0 -2px 15px rgba(0, 0, 0, 0.15) !important;
            border-radius: 15px 15px 0 0 !important;
            /* إزالة أي تأثيرات انتقالية أو تحويلات */
            transform: translateY(0) !important;
            transition: none !important;
            opacity: 1 !important;
            visibility: visible !important;
        }
        
        @media (max-width: 1199.98px) {
            .app-count {
                background: red !important;
            }
            
            .offcanvas-end .offcanvas-body{
                padding-bottom: 80px !important;
            }
            /* إضافة padding للمحتوى لتجنب تداخل شريط التطبيق */
            body {
                padding-bottom: 80px !important;
            }
        }
        
        @media (min-width: 1200px) {
            #mobile_app_bar {
                display: none !important;
            }
            
            body {
                padding-bottom: 0 !important;
            }
        }
        .product-card .cont .price span {
            font-size: 18px !important;
        }
        .product-card .cont .title {
            font-size: 15px !important;
            font-weight: 800 !important;
        }
        .product-card .cont .title {
            line-height: 1.5rem !important;
            margin-bottom: 0 !important;
        }
        @media (max-width: 575px) {
            .product-wrapper .product-card {
                padding: 15px !important
            }
            .product-card .cont .price span 
            , .product-card .cont .title {
                font-size: 15px !important;
                font-weight: 600 !important;
            }
        }
        .brand-slider-container {
            margin: auto !important;
        }
        .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable, 
        .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable:hover {
            color: #000 !important;
        }
        /*.container {
            max-width: 80.714286rem !important;
        }*/
        .price h3 ,.price span , .cart_total_amount,
         #cart_items .info .text-secondary{
            color : red !important;
        }
        .discounted-badge-element .discounted_badge {
            color: rgb(0 122 255) !important
        }
        @media (max-width: 480px) {
            .container.mt-4 {
                padding-left: 20px !important;
                padding-right: 20px !important;
            }
        }
        @media screen and (max-width: 575px) {
            .container {
                padding-inline: 20px;
            }
        }
        .product-card .cont .price span {
            color: #eb3223 !important;
        }
        .total-cost-info .price-red , .price-red {
            color: #eb3223 !important;
        }
        .product_blank_banner {
            background: url('/storage/app/public/company/2025-06-23-68593de055326.gif') no-repeat center center !important;
            background-size: contain !important;
            height: 100% !important;
            width: 100% !important;
        }
        .cookie-section {
            z-index: 99999;
        }
        /* شارة الخصم على الصورة */
        .discount-badge-on-image {
            position: absolute;
            top: 10px;
            right: 0;
            z-index: 3;
        }
        
        .discount-badge-on-image .badge {
            font-size: 12px !important;
            font-weight: bold !important;
            padding: 6px 10px !important;
            border-radius: 50px !important;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.4);
            border: 2px solid white;
        }
        
        @media (max-width: 575px) {
            .discount-badge-on-image {
                top: 8px;
                right: 0;
            }
            
            .discount-badge-on-image .badge {
                font-size: 10px !important;
                padding: 4px 8px !important;
            }
        }
        
    </style>
    
    {{-- إضافة حدث النقر المفقود لتسجيل الدخول --}}
    <script>
        $(document).ready(function() {
            // إضافة حدث النقر على customer_login_register_modal
            $(document).on('click', '.customer_login_register_modal2', function(e) {
                e.preventDefault();
                customerLoginRegisterModalCall();
            });
        });
        $(document).ready(function() {
            // إضافة حدث النقر على customer_login_register_modal
            $(document).on('click', '.customer_login_register_modal', function(e) {
                e.preventDefault();
                customerLoginRegisterModalCall();
            });
        });
    </script>
    
    <script> 
        $('.owl-stage').owlCarousel({
            loop:true,
            margin:10,
            nav:true,
            responsive:{
                0:{
                    items:3
                },
                600:{
                    items:3
                },
                1000:{
                    items:5
                }
            }
        })
    </script>
     <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=GT-PL9DVTTX"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', 'GT-PL9DVTTX');
    </script>
</body>

</html>

    <!--<script>
        // Force 3 items in mobile sliders
        $(document).ready(function() {
            // Function to reinitialize sliders with 3 items for mobile
            function forceThreeItemsOnMobile() {
                if ($(window).width() <= 575) {
                    // Destroy existing sliders and reinitialize with 3 items
                    const sliderSelectors = [
                        '.flash-deal-slider',
                        '.clearance-sell-slider', 
                        '.recommended-slider',
                        '.recent-shop-slider',
                        '.signature-products-slider',
                        '.others-store-slider'
                    ];
                    
                    sliderSelectors.forEach(function(selector) {
                        const $slider = $(selector);
                        if ($slider.length && $slider.hasClass('owl-loaded')) {
                            $slider.trigger('destroy.owl.carousel');
                            $slider.removeClass('owl-loaded owl-drag owl-grab');
                            
                            // Reinitialize with forced 3 items for mobile
                            $slider.owlCarousel({
                                items: 3,
                                responsiveClass: true,
                                nav: false,
                                dots: false,
                                loop: true,
                                autoplay: true,
                                autoplayTimeout: 2000,
                                autoplayHoverPause: true,
                                smartSpeed: 600,
                                rtl: $('html').attr('dir') === 'rtl',
                                responsive: {
                                    0: { items: 2, margin: 0 },
                                    480: { items: 2, margin: 0 },
                                    768: { items: 4, margin: 16 },
                                    992: { items: 5, margin: 20 },
                                    1200: { items: 6, margin: 26 }
                                }
                            });
                        }
                    });
                }
            }
            
            // Apply on page load
            setTimeout(forceThreeItemsOnMobile, 1000);
            
            // Apply on window resize
            $(window).on('resize', function() {
                setTimeout(forceThreeItemsOnMobile, 300);
            });
        });
    </script>-->
    <style>
        .rating i {
            color: #e9ba17;
        }
        .product-card .img img {
            object-fit: contain !important;
        }
        .product-single-thumb .main-thumb a img {
            object-fit: contain !important;
        }
    </style>