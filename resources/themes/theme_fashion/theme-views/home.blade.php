@extends('theme-views.layouts.app')

@section('title', $web_config['company_name'].' '.translate('online_shopping').' | '.$web_config['company_name'].' '.translate('ecommerce'))

@push('css_or_js')
    <meta property="og:image" content="{{$web_config['web_logo']['path']}}"/>
    <meta property="og:title" content="Welcome To {{$web_config['company_name']}} Home"/>
    <meta property="og:url" content="{{ config('app.url') }}">
    <meta name="description" content="{{ $web_config['meta_description'] }}">
    <meta property="og:description" content="{{ $web_config['meta_description'] }}">
    <meta property="twitter:card" content="{{$web_config['web_logo']['path']}}"/>
    <meta property="twitter:title" content="Welcome To {{$web_config['company_name']}} Home"/>
    <meta property="twitter:url" content="{{ config('app.url') }}">
    <meta property="twitter:description" content="{{ $web_config['meta_description'] }}">
@endpush

@section('content')

    @include('theme-views.partials._banner-section')

    <div class="container d-none d-xl-block">
        @include('theme-views.layouts.partials._search-form-partials')
    </div>
    @if ($categories->count() > 0)
        @include('theme-views.partials._categories')
    @endif
    <div class="container mt-4">
        <div class="row g-3">
            <div class="col-lg-12 col-md-12 col-sm-12">
                    <img src="{{ asset('/public/assets/front-end/gif-banners/banner1.gif') }}" 
                         alt="بانر متحرك 1" 
                         class=""
                         style="width: 100%;"
                         loading="lazy">
            </div>
        </div>
    </div>
    
    <div class="container mt-4">
        {{-- Her & Him Perfumes Banner --}}
        <div class="row">
            <div class="col-6">
                    <a href="https://bernsa.com/products?category_id=166&data_from=category&page=1" class="promotion-container">
                        <img loading="lazy" src="/public/assets/front-end/WhatsApp Image 2025-09-07 at 19.10.34_e13fb944.jpg" 
                                alt="عطور نسائية" class="img-fluid rounded">
                    </a>
            </div>
            <div class="col-6" style="text-align: end;">

                    <a href="https://bernsa.com/products?category_id=165&data_from=category&page=1" class="promotion-container">
                        <img loading="lazy" src="/public/assets/front-end/WhatsApp Image 2025-09-07 at 19.10.33_6f8b3494.jpg" 
                             alt="عطور رجالية" class="img-fluid rounded">
                    </a>
            </div>
        </div>
    </div>
    @if ($bannerTypePromoBannerBottom)
        <div class="container mt-4">
                <a href="{{ $bannerTypePromoBannerBottom->url }}" target="_blank" class="d-block promotional-banner">
                    <img loading="lazy" class="w-100 rounded aspect-ratio-8-1" alt="{{ translate('banner') }}"
                         src="{{ getStorageImages(path: $bannerTypePromoBannerBottom['photo_full_url'], type:'banner') }}">
                </a>
        </div>
    @endif
    @if ($flashDeal['flashDeal'] && $flashDeal['flashDealProducts']  && count($flashDeal['flashDealProducts']) > 0)
        @include('theme-views.partials._flash-deals')
    @endif

    @include('theme-views.partials._clearance-sale')
    @include('theme-views.partials._sale-products')
    
    {{-- Promotional Banners Section --}}
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <a href="https://bernsa.com/products?category_id=164&data_from=category&page=1" class="promotion-container">
                    <img loading="lazy" src="https://bernsa.com/sites/default/files/banner/%D8%B9%D8%B7%D9%88%D8%B1-%D8%A7%D9%84%D9%86%D9%8A%D8%B4.png" 
                            alt="عطور النيش" class="img-fluid rounded">
                </a>
            </div>
        </div>
    </div>

    @include('theme-views.partials._niche-perfumes')

    {{-- Promotional Banners Section --}}
    <div class="container mt-4">

        {{-- Best Sellers Banner --}}
        <div class="row">
            <div class="col-12">

                    <a href="{{ route('products', ['data_from'=>'best-selling', 'page'=>1]) }}" class="promotion-container">

                        <img loading="lazy" src="/public/images/الافضل-مبيعا3 (2) (1).png" 
                            alt="الأكثر مبيعاً" class="img-fluid rounded">
                    </a>
            </div>
        </div>
    </div>
    @include('theme-views.partials.__featured-product')
    {{-- Custom GIF Banners Section --}}
    <div class="container mt-4 mb-4">
        <div class="row g-3">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <img src="{{ asset('/public/assets/front-end/gif-banners/banner2.gif') }}" 
                     alt="بانر متحرك 1" 
                     class=""
                     style="width: 100%;"
                     loading="lazy">
            </div>
        </div>
    </div>
    
    @if ($bannerTypePromoBannerLeft && $bannerTypePromoBannerMiddleTop && $bannerTypePromoBannerMiddleBottom && $bannerTypePromoBannerRight)
        @include('theme-views.partials._promo-banner')
    @endif

    @include('theme-views.partials._deal-of-the-day')
    
    @include('theme-views.partials._recommended-product')
    
    @if ($bannerTypePromoBannerLeft)
        <div class="container d-sm-none overflow-hidden pt-4">
            <a href="{{ $bannerTypePromoBannerLeft['url'] }}" target="_blank" class="img3 img-fluid">
                <img loading="lazy" src="{{ getStorageImages(path: $bannerTypePromoBannerLeft['photo_full_url'], type:'banner') }}"
                class="img-fluid" alt="{{ translate('banner') }}">
            </a>
        </div>
    @endif
    
    @if ($bannerTypePromoBannerMiddleTop)
        <div class="container d-sm-none mt-3">
            <a href="{{ $bannerTypePromoBannerMiddleTop['url'] }}" target="_blank" class="img1 promo-1">
                <img loading="lazy" class="img-fluid" alt="{{ translate('banner') }}" src="{{ getStorageImages(path: $bannerTypePromoBannerMiddleTop['photo_full_url'], type: 'banner') }}">
            </a>
        </div>
    @endif
    @if ($bannerTypePromoBannerMiddleBottom)
        <div class="container d-sm-none overflow-hidden pt-4">
            <a href="{{ $bannerTypePromoBannerMiddleBottom['url'] }}" target="_blank" class="img2">
                <img loading="lazy" src="{{ getStorageImages(path: $bannerTypePromoBannerMiddleBottom['photo_full_url'], type:'banner') }}"
                class="img-fluid" alt="{{ translate('banner') }}">
            </a>
        </div>
    @endif
    {{--@include('theme-views.partials._all-products-home')--}}

    {{-- Hair & Body Perfumes Banner Section --}}
    <div class="container mt-4">
         <h2 class="brand-slider-title">{{ translate('our_brands') ?? 'علاماتنا التجارية' }}</h2>
        <div class="row g-3">
            <div class="col-6">
                <div class="promotion-container">
                    <a href="https://bernsa.com/products?brand_id=679&data_from=brand&page=1">
                        <img loading="lazy" 
                            src="https://bernsa.com/sites/default/files/banner/Group%20%D9%A8.jpg" 
                            alt="بانر عطور الشعر" 
                            class="img-fluid rounded">
                    </a>
                </div>
            </div>
            <div class="col-6">
                <div class="promotion-container">
                    <a href="https://bernsa.com/products?brand_id=679&data_from=brand&page=1">
                    <img loading="lazy" 
                         src="https://bernsa.com/sites/default/files/banner/Group%20%D9%A7.jpg" 
                         alt="بانر عطور الجسم" 
                         class="img-fluid rounded">
                    </a>
                </div>
            </div>
        </div>
    </div>
    {{-- Hair & Body Perfumes Banner Section --}}
    <div class="container banners-3">
        <div class="row g-3">
            <div class="col-4">
                <div class="promotion-container">
                    <a href="https://bernsa.com/products?brand_id=668&data_from=brand&page=1">
                        <img loading="lazy" 
                            src="https://bernsa.com/sites/default/files/banner/Group%20.jpg" 
                            alt="بانر عطور الشعر" 
                            class="img-fluid rounded">
                    </a>
                </div>
            </div>
            <div class="col-4">
                <div class="promotion-container">
                    <a href="https://bernsa.com/products?brand_id=675&data_from=brand&page=1">
                        <img loading="lazy" 
                            src="https://bernsa.com/sites/default/files/banner/Group%20%D9%A4.jpg" 
                            alt="بانر عطور الشعر" 
                            class="img-fluid rounded">
                    </a>
                </div>
            </div>
            <div class="col-4">
                <div class="promotion-container">
                    <a href="https://bernsa.com/products?brand_id=666&data_from=brand&page=1">
                    <img loading="lazy" 
                         src="https://bernsa.com/sites/default/files/banner/joseon.jpg" 
                         alt="بانر عطور الجسم" 
                         class="img-fluid rounded">
                    </a>
                </div>
            </div>
        </div>
    </div>
    {{-- Brand Slider Section --}}
    <div class="container brand-slider-container">
        <div class="creams-sliders3">
            <div>
                <a href="https://bernsa.com/products?brand_id=678&data_from=brand&page=1">
                    <img src="https://bernsa.com/sites/default/files/2025-03/Group%20%D9%A6.jpg" alt="مافالا - منتجات العناية">
                </a>
            </div>
            
            <div>
                <a href="https://bernsa.com/products?brand_id=683&data_from=brand&page=1">
                    <img src="https://bernsa.com/sites/default/files/2025-03/Group%20%D9%A9.jpg" alt="كارسيل - منتجات التجميل">
                </a>
            </div>
            
            <div>
                <a href="https://bernsa.com/products?brand_id=740&data_from=brand&page=1">
                    <img src="https://bernsa.com/sites/default/files/2025-03/Group%20%D9%A1%D9%A0.jpg" alt="مايلي أورجانيكس - منتجات طبيعية">
                </a>
            </div>
            
            <div>
                <a href="https://bernsa.com/products?brand_id=691&data_from=brand&page=1">
                    <img src="https://bernsa.com/sites/default/files/2025-03/Group%20%D9%A1%D9%A4.jpg" alt="سيتافيل - منتجات العناية بالبشرة">
                </a>
            </div>
            
            <div>
                <a href="https://bernsa.com/products?brand_id=691&data_from=brand&page=1">
                    <img src="https://bernsa.com/sites/default/files/2025-03/Group%20%D9%A1%D9%A3.jpg" alt="سيتافيل - مجموعة متنوعة">
                </a>
            </div>
            
            <div>
                <a href="https://bernsa.com/products?brand_id=687&data_from=brand&page=1">
                    <img src="https://bernsa.com/sites/default/files/2025-03/Group%20%D9%A1%D9%A2.jpg" alt="آي سي إم - منتجات العناية">
                </a>
            </div>
            
            <div>
                <a href="https://bernsa.com/products?brand_id=694&data_from=brand&page=1">
                    <img src="https://bernsa.com/sites/default/files/2025-03/Group%20%D9%A1%D9%A5.jpg" alt="أكيور - منتجات الجمال">
                </a>
            </div>
            
            <div>
                <a href="https://bernsa.com/products?brand_id=694&data_from=brand&page=1">
                    <img src="https://bernsa.com/sites/default/files/2025-03/Group%20%D9%A1%D9%A6.jpg" alt="أكيور - مجموعة خاصة">
                </a>
            </div>
            
            <div>
                <a href="https://bernsa.com/products?brand_id=697&data_from=brand&page=1">
                    <img src="https://bernsa.com/sites/default/files/2025-03/Group%20%D9%A1%D9%A7.jpg" alt="أنجلوت - منتجات فاخرة">
                </a>
            </div>
        </div>
    </div>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <a href="http://bernsa.com/products?category_id=230&data_from=category&page=1" class="promotion-container">
                    <img loading="lazy" src="/public/images/العناية 1.png" 
                            alt="منتجات العناية" class="img-fluid rounded">
                </a>
            </div>
        </div>
    </div>
    @include('theme-views.partials._category-230-products')

    @include('theme-views.partials._signature-product')

    @if ($web_config['business_mode'] == 'multi' && count($topVendorsList) > 0)
        @include('theme-views.partials._top-stores')
    @endif

    @include('theme-views.partials._most-demanded-product')

    @if ($web_config['business_mode'] == 'multi' && getCustomerFromQuery() && count($recentOrderShopList)>0)
        @include('theme-views.partials._recent-ordered-shops')
    @endif

    @if ($web_config['business_mode'] == 'multi')
        @include('theme-views.partials._other-stores')
    @endif

    @include('theme-views.partials._how-to-section')

@endsection

@if ($bannerTypeMainBanner->count() <= 1)
@push('script')
    <script src="{{ theme_asset('assets/js/home-blade.js') }}"></script>
    <script src="{{ theme_asset('assets/js/gif-banners.js') }}"></script>
    <script src="{{ theme_asset('assets/js/brand-slider.js') }}"></script>
    <script src="{{ theme_asset('assets/js/promotional-banners.js') }}"></script>
    <script src="{{ theme_asset('assets/js/signature-auto-slider.js') }}"></script>
    
    {{-- Direct inline script to guarantee signature slider movement --}}
    <script>
        $(document).ready(function() {
            // Wait for everything to load
            setTimeout(function() {
                console.log('🚀 Direct signature slider initialization...');
                
                var $signatureSlider = $('.signature-products-slider');
                
                if ($signatureSlider.length > 0) {
                    console.log('Found signature slider, items:', $signatureSlider.children().length);
                    
                    // Force destroy any existing carousel
                    $signatureSlider.trigger('destroy.owl.carousel');
                    $signatureSlider.removeClass('owl-loaded owl-drag owl-carousel owl-theme');
                    
                    // Simple initialization
                    $signatureSlider.owlCarousel({
                        items: 3,
                        margin: 20,
                        loop: true,
                        autoplay: true,
                        autoplayTimeout: 2000, // 2 seconds for fast movement
                        autoplayHoverPause: false,
                        smartSpeed: 500,
                        nav: true,
                        dots: true,
                        responsive: {
                            0: { items: 1 },
                            768: { items: 2 },
                            1200: { items: 3 }
                        },
                        onInitialized: function() {
                            console.log('✅ Signature slider is now auto-moving!');
                        }
                    });
                    
                    // Additional backup - force movement every 3 seconds
                    setInterval(function() {
                        $signatureSlider.trigger('next.owl.carousel');
                        console.log('🔄 Forced next slide');
                    }, 3000);
                    
                } else {
                    console.warn('❌ Signature slider element not found');
                }
            }, 2000);
        });
    </script>
@endpush
@endif
<style>
    .banners-3 .promotion-container img{
        height: 389px !important;
        object-fit: cover;
    }
    @media (min-width: 767px) {
        .promotion-container img {
            width: 100% !important;
            height: auto !important;
        }
    }
    .creams-sliders3 img {
        opacity: 1 !important;
    }
    
    /* Hair & Body Perfumes Banner Styles */
    .promotion-container {
        position: relative;
        overflow: hidden;
        border-radius: 8px;
        transition: transform 0.3s ease;
    }
    
    .promotion-container:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    
    .promotion-container img {
        width: 100%;
        height: auto;
        transition: transform 0.3s ease;
    }
    
    .promotion-container:hover img {
        transform: scale(1.05);
    }
    
    @media (max-width: 768px) {
        .banners-3 .promotion-container img {
            height: 100px !important;
        }
        .promotion-container {
            margin-bottom: 15px;
        }
        .creams-sliders3 img {
            height: 100px !important;
        }
    }
</style>