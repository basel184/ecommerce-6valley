<!-- شريط الشحن المجاني -->
<div class="free-shipping-bar bg-success text-white py-2">
    <div class="container">
        <div class="d-flex align-items-center justify-content-center gap-2 position-relative">
            <i class="bi bi-truck text-white"></i>
            <span class="fw-semibold">
                <span class="d-none d-md-inline">🚚 شحن مجاني للطلبات من </span>
                <span class="d-md-none">🚚 شحن مجاني من </span>
                <span class="price-highlight">399 ريال</span>
                <span class="d-none d-sm-inline"> فأكثر</span>
            </span>
            <i class="bi bi-gift text-white d-none d-sm-inline"></i>
            
            <!-- شريط التقدم التفاعلي (اختياري) -->
            <div class="shipping-progress d-none" id="shipping-progress">
                <div class="progress-text">باقي <span id="remaining-amount">399</span> ريال للشحن المجاني</div>
                <div class="progress-bar-wrapper">
                    <div class="progress-bar" id="progress-fill"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@if (isset($web_config['announcement']) && $web_config['announcement']['status']==1)
    <div class="offer-bar d--none" data-bg-img="{{theme_asset('assets/img/media/top-offer-bg.png')}}">
        <div class="d-flex py-2 gap-2 align-items-center">
            <div class="offer-bar-close px-2">
                <i class="bi bi-x-lg"></i>
            </div>
            <div class="top-offer-text flex-grow-1 d-flex justify-content-center fw-semibold text-center">
                {{ $web_config['announcement']['announcement'] }}
            </div>
        </div>
    </div>
@endif
<header class="bg--dark header-middle">
    <div class="container">
        <div class="text-capitalize">
            <ul class="header-right-icons d-flex justiy-content-between">
                <li class="me-auto">
                    <a href="tel:{{ $web_config['phone'] }}" class="text-dark d-flex align-items-center gap-1 direction-ltr">
                        <img class="invert" loading="lazy" width="14" src="{{ theme_asset('assets/img/footer/address/phone.png') }}" alt="{{ translate('address') }}">
                        <span>{{ $web_config['phone'] }}</span>
                    </a>
                </li>
                <li>
                    <div class="darkLight-switcher d-none d-xl-block">
                        <button type="button" title="{{ translate('Dark_Mode') }}" class="dark_button">
                            <img loading="lazy" class="svg" src="{{theme_asset('assets/img/icons/dark.svg')}}"
                                    alt="{{ translate('dark_Mode') }}">
                        </button>
                        <button type="button" title="{{ translate('Light_Mode') }}" class="light_button">
                            <img loading="lazy" class="svg" src="{{theme_asset('assets/img/icons/light.svg')}}"
                                    alt="{{ translate('light_Mode') }}">
                        </button>
                    </div>
                </li>
                @if ($web_config['business_mode'] == 'multi' && $web_config['seller_registration'])
                    <li class="d-none d-sm-block">
                        <div class="d-flex">
                            <a href="{{route('vendor.auth.registration.index')}}" class="btn __btn-outline bg-transparent text-white">{{translate('become_a_vendor').'.'}}</a>
                        </div>
                    </li>
                @endif
            </ul>
        </div>
    </div>
</header>
<header class="header-section bg-base">
    <div class="container">
        <div class="header-wrapper">
            <a href="{{route('home')}}" class="logo me-xl-5">
                <img class="d-sm-none mobile-logo-cs"
                     src="{{ getStorageImages(path: $web_config['mob_logo'], type: 'logo') }}" alt="{{ translate('logo') }}">
                <img class="d-none d-sm-block"
                     src="{{ getStorageImages(path: $web_config['web_logo'], type: 'logo') }}" alt="{{ translate('logo') }}">
            </a>
            <div class="menu-area text-capitalize flex-grow-1 ps-xl-4">
                <ul class="menu me-xl-4">
                    <li>
                        <a href="{{route('home')}}"
                           class="{{ Request::is('/')?'active':'' }}">{{ translate('home') }}</a>
                    </li>
                    @php($categories = \App\Utils\CategoryManager::getCategoriesWithCountingAndPriorityWiseSorting())
                    <li>
                        <a href="javascript:">{{ translate('all_categories')}}</a>
                        <ul class="submenu">
                            @foreach($categories as $key => $category)
                                @if ($key <= 10)
                                    <li>
                                        <a class="py-2"
                                           href="{{route('products',['category_id'=> $category['id'],'data_from'=>'category','page'=>1])}}">{{$category['name']}}</a>
                                    </li>
                                @endif
                            @endforeach

                            @if ($categories->count() > 10)
                                <li>
                                    <a href="{{route('products')}}" class="btn-text">{{ translate('view_all') }}</a>
                                </li>
                            @endif
                        </ul>
                    </li>
                    <li>
                        <a href="/blog?category=نصائح%20عطرية"
                           class="{{ Request::is('blog')?'active':'' }}">
                         نصائح عطرية
                        </a>
                    </li>
                    <li>
                        <a 
                        class="{{ Request::is('track-order')?'active':'' }}"
                        href="{{ route('track-order.index') }}">
                            {{ translate('track_order') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{route('products',['offer_type'=>'discounted','page'=>1])}}"
                           class="{{ request('data_from')=='discounted'?'active':'' }}">
                            {{ translate('offers') }}
                            <div class="offer-count flower-bg d-flex justify-content-center align-items-center offer-count-custom ">
                                {{ ($web_config['total_discount_products'] < 100 ? $web_config['total_discount_products']:'99+') }}
                            </div>
                        </a>
                    </li>

                    @if ($web_config['digital_product_setting'] && count($web_config['publishing_houses']) == 1)
                        <li>
                            <a href="{{ route('products',['publishing_house_id' => 0, 'product_type' => 'digital', 'page'=>1]) }}">
                                {{ translate('Publication_House') }}
                            </a>
                        </li>
                    @elseif ($web_config['digital_product_setting'] && count($web_config['publishing_houses']) > 1)
                        <li>
                            <a href="javascript:">{{ translate('Publication_House')}}</a>
                            <ul class="submenu">
                                @php($publishingHousesIndex=0)
                                @foreach($web_config['publishing_houses'] as $publishingHouseItem)
                                    @if($publishingHousesIndex < 10 && $publishingHouseItem['name'] != 'Unknown')
                                        @php($publishingHousesIndex++)
                                        <li>
                                            <a class="py-2"
                                               href="{{ route('products',['publishing_house_id'=> $publishingHouseItem['id'], 'product_type' => 'digital', 'page'=>1]) }}">
                                                {{ $publishingHouseItem['name'] }}
                                            </a>
                                        </li>
                                    @endif
                                @endforeach

                                <li>
                                    <a href="{{ route('products', ['product_type' => 'digital', 'page' => 1]) }}" class="btn-text">
                                        {{ translate('view_all') }}
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif

                    @if($web_config['business_mode'] == 'multi')
                        <li>
                            <a href="{{route('vendors')}}"
                               class="{{ Request::is('vendors')?'active':'' }}">{{translate('vendors')}}</a>
                        </li>

                        @if ($web_config['seller_registration'])
                            <li class="d-sm-none">
                                <a href="{{route('vendor.auth.registration.index')}}"
                                   class="{{ Request::is('vendor/auth/registration/index')?'active':'' }}">{{translate('vendor_reg').'.'}}</a>
                            </li>
                        @endif
                    @endif

                </ul>

                <ul class="header-right-icons ms-auto">
                    <li class="d-none d-xl-block">
                        @if(auth('customer')->check())
                            <a href="{{ route('wishlists') }}">
                                <div class="position-relative mt-1 px-8px">
                                    <i class="bi bi-heart"></i>
                                    <span class="btn-status wishlist_count_status">{{session()->has('wish_list')?count(session('wish_list')):0}}</span>
                                </div>
                            </a>
                        @else
                            <a href="javascript:" class="customer_login_register_modal">
                                <div class="position-relative mt-1 px-8px">
                                    <i class="bi bi-heart"></i>
                                    <span class="btn-status">0</span>
                                </div>
                            </a>
                        @endif
                    </li>
                    <li id="cart_items" class="d-none d-xl-block">
                        @include('theme-views.layouts.partials._cart')
                    </li>
                    <li class="d-xl-none">
                        <a href="javascript:" class="search-toggle">
                            <i class="bi bi-search"></i>
                        </a>
                    </li>
                    @if(auth('customer')->check())
                        <li class="me-2 me-sm-0 dropdown">
                            <a href="javascript:" data-bs-toggle="dropdown">
                                <i class="bi bi-person d-none d-xl-inline-block"></i>
                                <i class="bi bi-person-circle d-xl-none"></i>
                                <span class="mx-1 d-none d-md-block">{{auth('customer')->user()->f_name}}</span>
                                <i class="ms-1 text-small bi bi-chevron-down d-none d-md-block"></i>
                            </a>
                            <div class="dropdown-menu __dropdown-menu">
                                <ul class="language">
                                    <li class="thisIsALinkElement" data-linkpath="{{route('account-orders')}}">
                                        <img loading="lazy" src="{{ theme_asset('assets/img/user/shopping-bag.svg') }}"
                                             alt="{{ translate('user') }}">
                                        <span>{{ translate('my_order') }}</span>
                                    </li>
                                    <li class="thisIsALinkElement" data-linkpath="{{route('user-profile')}}">
                                        <img loading="lazy" src="{{ theme_asset('assets/img/user/profile.svg') }}"
                                             alt="{{ translate('user') }}">
                                        <span>{{ translate('my_profile') }}</span>
                                    </li>
                                    <li class="thisIsALinkElement" data-linkpath="{{route('customer.auth.logout')}}">
                                        <img loading="lazy" src="{{ theme_asset('assets/img/user/logout.svg') }}"
                                             alt="{{ translate('user') }}">
                                        <span>{{translate('sign_Out')}}</span>
                                    </li>
                                </ul>
                            </div>
                        </li>
                    @else
                        <li class="me-2 me-sm-0">
                            <a href="javascript:" class="customer_login_register_modal">
                                <i class="bi bi-person d-none d-xl-inline-block"></i>
                                <i class="bi bi-person-circle d-xl-none"></i>
                                <span class="mx-1 d-none d-md-block">{{ translate('login') }} / {{ translate('register') }}</span>
                            </a>
                        </li>
                    @endif

                    <li class="nav-toggle d-xl-none" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRight"
                        aria-controls="offcanvasRight">
                        <span></span>
                        <span></span>
                        <span></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <div class="mobile-search-form-wrapper">
        <div class="search-form-header d-xl-none">
            <div class="d-flex w-100 align-items-center">
                <form class="search-form m-0 p-0 sidebar-search-form" action="{{route('products')}}" type="submit">
                    <div class="input-group search_input_group">
                        <!-- البحث للموبايل - التعديل الصحيح -->
                        <input type="search" class="form-control action-global-search-mobile" id="input-value-mobile"
                            placeholder="{{ translate('search_for_items_or_store') }}..." name="name" autocomplete="off">

                        <button class="btn btn-base" type="submit"><i class="bi bi-search"></i></button>
                        <div class="card search-card position-absolute z-99 w-100 bg-white d-none top-100 start-0 search-result-box-mobile"></div>
                    </div>
                    <!-- المعاملات الضرورية للبحث -->
                    <input name="data_from" value="search" hidden>
                    <input name="page" value="1" hidden>
                    <!-- عنصر مخفي للمقترحات -->
                    <input type="hidden" id="search_category_value_mobile" value="all">
                </form>
            </div>
        </div>
    </div>
</header>

<div class="search-toggle search-togle-overlay"></div>

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasRight" aria-labelledby="offcanvasRightLabel">
    <div class="offcanvas-header justify-content-end">
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body text-capitalize d-flex flex-column">
        <div>
            <ul class="menu scrollY-60 ">
                <li>
                    <a href="{{route('home')}}">{{ translate('home') }}</a>
                </li>
                @foreach($categories as $key => $category)
                    @if ($key <= 10)
                        <li>
                            <a class="py-2"
                               href="{{route('products',['category_id'=> $category['id'],'data_from'=>'category','page'=>1])}}">{{$category['name']}}</a>
                        </li>
                    @endif
                @endforeach
                <li>
                    <a href="/blog?category=نصائح%20عطرية"
                    class="py-2">
                     نصائح عطرية
                    </a>
                </li>
                <li>
                    <a 
                    class="py-2"
                    href="{{ route('track-order.index') }}">
                        {{ translate('track_order') }}
                    </a>
                </li>
                @if ($web_config['digital_product_setting'] && count($web_config['publishing_houses']) > 0)
                    <li>
                        <a href="{{ route('products', ['product_type' => 'digital', 'page' => 1]) }}">
                            {{ translate('Publication_House') }}
                        </a>
                    </li>
                @endif

                @if($web_config['business_mode'] == 'multi')
                    <li>
                        <a href="{{route('vendors')}}">{{translate('vendors')}}</a>
                    </li>

                    @if ($web_config['seller_registration'])
                        <li class="d-sm-none">
                            <a href="{{route('vendor.auth.registration.index')}}">{{translate('vendor_reg').'.'}}</a>
                        </li>
                    @endif
                @endif

            </ul>
        </div>

        <div class="d-flex align-items-center gap-2 justify-content-between py-4 mt-3">
            <span class="text-dark">{{ translate('theme_mode') }}</span>
            <div class="theme-bar">
                <button class="light_button active">
                    <img loading="lazy" class="svg" src="{{theme_asset('assets/img/icons/light.svg')}}"
                         alt="{{ translate('light_Mode') }}">
                </button>
                <button class="dark_button">
                    <img loading="lazy" class="svg" src="{{theme_asset('assets/img/icons/dark.svg')}}" alt="{{ translate('dark_Mode') }}">
                </button>
            </div>
        </div>

        @if(auth('customer')->check())
            <div class="d-flex justify-content-center mb-2 pb-3 mt-auto px-4">
                <a href="{{route('customer.auth.logout')}}" class="btn btn-base w-100">{{ translate('logout') }}</a>
            </div>
        @else
            <div class="d-flex justify-content-center mb-2 pb-3 mt-auto px-4">
                <a href="javascript:" class="btn btn-base w-100 customer_login_register_modal">
                    {{ translate('login') }} / {{ translate('register') }}
                </a>
            </div>
        @endif
    </div>
</div>
<style>
    #mobile_app_bar {
        display: block !important;
    }
    .section-title {
        font-size: 14px !important;
    }
    .how-to-card .how-to-icon{
        color: #67473b;
    }
    .header-section {
        background: #f3ebe3 !important;
        --title: #000 !important;
    }
    :root {
        --base: linear-gradient(70deg, rgba(234,220,210,1) 0%, rgba(179,148,129,1) 100%);
    }
    .blog-top-nav li.active a {
        color: #67473b;
        background-color: var(--base);
    }
    
    /* تنسيق شريط الشحن المجاني */
    .free-shipping-bar {
        background: linear-gradient(90deg, #28a745 0%, #20c997 50%, #17a2b8 100%) !important;
        font-size: 14px;
        font-weight: 600;
        text-align: center;
        position: relative;
        overflow: hidden;
        animation: fadeInDown 0.5s ease-in-out;
        border-bottom: 2px solid rgba(255,255,255,0.1);
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .free-shipping-bar::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        animation: shine 4s infinite;
    }
    
    .price-highlight {
        background: rgba(255,255,255,0.2);
        padding: 2px 8px;
        border-radius: 12px;
        font-weight: 700;
        color: #fff !important;
        text-shadow: 0 1px 2px rgba(0,0,0,0.3);
    }
    
    /* شريط التقدم التفاعلي */
    .shipping-progress {
        position: absolute;
        right: 20px;
        top: 50%;
        transform: translateY(-50%);
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 12px;
    }
    
    .progress-bar-wrapper {
        width: 100px;
        height: 4px;
        background: rgba(255,255,255,0.3);
        border-radius: 2px;
        overflow: hidden;
    }
    
    .progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #ffd700, #ffed4e);
        border-radius: 2px;
        transition: width 0.3s ease;
        width: 0%;
    }
    
    @keyframes shine {
        0% { left: -100%; }
        100% { left: 100%; }
    }
    
    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .free-shipping-bar i {
        font-size: 16px;
        animation: bounce 3s infinite;
        filter: drop-shadow(0 1px 2px rgba(0,0,0,0.3));
    }
    
    @keyframes bounce {
        0%, 20%, 50%, 80%, 100% {
            transform: translateY(0);
        }
        40% {
            transform: translateY(-3px);
        }
        60% {
            transform: translateY(-2px);
        }
    }
    
    /* تحسين المظهر على الأجهزة المحمولة */
    @media (max-width: 768px) {
        .free-shipping-bar {
            font-size: 12px;
            padding: 8px 0 !important;
        }
        
        .free-shipping-bar i {
            font-size: 14px;
        }
        
        .price-highlight {
            padding: 1px 6px;
            font-size: 12px;
        }
        
        .shipping-progress {
            display: none !important;
        }
    }
    
    @media (max-width: 576px) {
        .free-shipping-bar {
            font-size: 11px;
            padding: 6px 0 !important;
        }
        
        .price-highlight {
            padding: 1px 4px;
            font-size: 11px;
        }
    }
    
    /* تأثير hover */
    .free-shipping-bar:hover {
        background: linear-gradient(90deg, #20c997 0%, #17a2b8 50%, #28a745 100%) !important;
        transition: all 0.5s ease;
        transform: scale(1.01);
    }
    
    .free-shipping-bar:hover .price-highlight {
        background: rgba(255,255,255,0.4);
        transform: scale(1.05);
        transition: all 0.3s ease;
    }
    
    /* تأثير للايقونات */
    .free-shipping-bar:hover i {
        animation: pulse 1s infinite;
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
</style>

<script>
// JavaScript تفاعلي لشريط الشحن المجاني
document.addEventListener('DOMContentLoaded', function() {
    const freeShippingThreshold = 399;
    
    // دالة لحساب التقدم نحو الشحن المجاني
    function updateShippingProgress() {
        // يمكن ربطها بسلة التسوق الفعلية
        const cartTotal = getCartTotal(); // يجب تطبيق هذه الدالة حسب النظام
        
        if (cartTotal > 0) {
            const remaining = Math.max(0, freeShippingThreshold - cartTotal);
            const progress = Math.min(100, (cartTotal / freeShippingThreshold) * 100);
            
            const progressElement = document.getElementById('shipping-progress');
            const remainingElement = document.getElementById('remaining-amount');
            const progressFill = document.getElementById('progress-fill');
            
            if (cartTotal >= freeShippingThreshold) {
                // تم الوصول للشحن المجاني
                if (progressElement) {
                    progressElement.innerHTML = '<span class="text-success fw-bold">🎉 تهانينا! حصلت على الشحن المجاني</span>';
                    progressElement.classList.remove('d-none');
                }
            } else if (cartTotal > 0) {
                // عرض التقدم
                if (remainingElement) remainingElement.textContent = remaining;
                if (progressFill) progressFill.style.width = progress + '%';
                if (progressElement) progressElement.classList.remove('d-none');
            }
        }
    }
    
    // دالة للحصول على إجمالي السلة (يجب تخصيصها حسب النظام)
    function getCartTotal() {
        // هذه مثال - يجب ربطها بالنظام الفعلي
        const cartElement = document.querySelector('.cart-total, #cart-total, [data-cart-total]');
        if (cartElement) {
            const totalText = cartElement.textContent || cartElement.innerText;
            const total = parseFloat(totalText.replace(/[^\d.]/g, ''));
            return isNaN(total) ? 0 : total;
        }
        return 0;
    }
    
    // تشغيل التحديث عند تحميل الصفحة
    updateShippingProgress();
    
    // تحديث عند تغيير السلة (يمكن ربطه بأحداث AJAX)
    document.addEventListener('cartUpdated', updateShippingProgress);
    
    // إضافة تأثير تفاعلي للشريط
    const shippingBar = document.querySelector('.free-shipping-bar');
    if (shippingBar) {
        shippingBar.addEventListener('click', function() {
            // إمكانية التوجه لصفحة المنتجات أو السلة
            // window.location.href = '/products';
        });
        
        // تأثير التمرير
        shippingBar.style.cursor = 'pointer';
        shippingBar.title = 'اطلب الآن واحصل على الشحن المجاني!';
    }
});
</script>
