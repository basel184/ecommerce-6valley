@extends('theme-views.layouts.app')

@section('title', $product['name'].' | '.$web_config['company_name'].' '.translate('ecommerce'))

@push('css_or_js')
    @include(VIEW_FILE_NAMES['product_seo_meta_content_partials'], ['metaContentData' => $product?->seoInfo, 'productDetails' => $product])
    <style>
        .bg-light-green {
            background-color: #e8f5e8;
            border: 1px solid #d4edda;
        }
        .bg-light-blue {
            background-color: #e3f2fd;
            border: 1px solid #bbdefb;
        }
        .text-light-blue {
            color: #1976d2;
        }
        .text-warning {
            color: #ff9800 !important;
        }
        .text-primary {
            color: #007bff !important;
        }
        .text-dark {
            color: #343a40 !important;
        }
        .text-muted {
            color: #6c757d !important;
        }
        .bg-light {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
        }
        .bg-white {
            background-color: #ffffff;
        }
        .border {
            border: 1px solid #dee2e6 !important;
        }
        .rounded {
            border-radius: 0.375rem !important;
        }
        .p-3 {
            padding: 1rem !important;
        }
        .mt-20px {
            margin-top: 20px !important;
        }
        .mb-2 {
            margin-bottom: 0.5rem !important;
        }
        .mb-0 {
            margin-bottom: 0 !important;
        }
        .mt-2 {
            margin-top: 0.5rem !important;
        }
        .me-2 {
            margin-right: 0.5rem !important;
        }
        .ms-2 {
            margin-left: 0.5rem !important;
        }
        .gap-3 {
            gap: 1rem !important;
        }
        .d-flex {
            display: flex !important;
        }
        .justify-content-between {
            justify-content: space-between !important;
        }
        .justify-content-center {
            justify-content: center !important;
        }
        .align-items-center {
            align-items: center !important;
        }
        .text-center {
            text-align: center !important;
        }
        .fw-bold {
            font-weight: 700 !important;
        }
        .h6 {
            font-size: 1rem;
            font-weight: 500;
            line-height: 1.2;
        }
        .p {
            margin-bottom: 0.5rem;
        }
    </style>
@endpush

@section('content')
    @php($productDetails = $product)
    <section class="product-single-section pt-20px">
        <div class="container">
            <div class="section-title mb-4">
                <div
                    class="d-flex flex-wrap justify-content-between row-gap-3 column-gap-2 align-items-center search-page-title">
                    <ul class="breadcrumb">
                        <li>
                            <a href="{{route('home')}}">{{ translate('home') }}</a>
                        </li>
                        <li>
                            <a href="{{route('products',['category_id'=> $product->category_id,'data_from'=>'category','page'=>1])}}">
                                {{translate('products')}}
                            </a>
                        </li>
                        <li>
                            <a href="javascript:" class="text-base">{{$product->name}}</a>
                        </li>
                    </ul>
                    <div class="text-capitalize">{{ translate('similar_category_product') }}
                        <span class="text-base cursor-pointer thisIsALinkElement"
                              data-linkpath="{{route('products',['category_id'=> $product->category_id,'data_from'=>'category','page'=>1])}}">
                    {{$relatedProducts}} {{ translate('item') }}</span>
                    </div>
                </div>
            </div>

            @if($product?->preview_file_full_url['path'])
                @include('theme-views.partials._product-preview-modal', ['previewFileInfo' => $previewFileInfo])
            @endif

            @if ( preg_match('/^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=|\?v=)([^#\&\?]*).*/',$product->video_url))
                <div class="modal fade" id="videoModal" tabindex="-1" aria-labelledby="videoModalLabel"
                     aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-body p-0">
                                <iframe class="videoModalIframe" src="{{$product->video_url}}" frameborder="0"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                        allowfullscreen></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="product-cart-option-container">
                <form class="cart add-to-cart-details-form addToCartDynamicForm" action="{{ route('cart.add') }}"
                      data-errormessage="{{translate('please_choose_all_the_options')}}"
                      data-outofstock="{{translate('sorry').', '.translate('out_of_stock')}}.">
                    @csrf
                    <div class="product-single-wrapper">

                        @if($product->images!=null && json_decode($product->images)>0)
                            <div class="product-single-thumb">
                                @if(json_decode($product->colors) && $product->color_image)
                                    <div class="overflow-hidden rounded position-relative">
                                        <div class="product-share-icons">
                                            <a href="javascript:" class="share-icon" title="{{translate('share')}}">
                                                <i class="bi bi-share-fill"></i>
                                            </a>
                                            <ul>
                                                <li>
                                                    <a href="javascript:" class="social_share_function"
                                                       data-url="{{route('product',$product->slug)}}"
                                                       data-social="facebook.com/sharer/sharer.php?u="
                                                    >
                                                        <i class="bi bi-facebook"></i>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="javascript:" class="social_share_function"
                                                       data-url="{{route('product',$product->slug)}}"
                                                       data-social="twitter.com/intent/tweet?text="
                                                    >
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                                             fill="currentColor" class="bi bi-twitter-x" viewBox="0 0 16 16">
                                                            <path
                                                                d="M12.6.75h2.454l-5.36 6.142L16 15.25h-4.937l-3.867-5.07-4.425 5.07H.316l5.733-6.57L0 .75h5.063l3.495 4.633L12.601.75Zm-.86 13.028h1.36L4.323 2.145H2.865l8.875 11.633Z"/>
                                                        </svg>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="javascript:" class="social_share_function"
                                                       data-url="{{route('product',$product->slug)}}"
                                                       data-social="linkedin.com/shareArticle?mini=true&url="
                                                    >
                                                        <i class="bi bi-linkedin"></i>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="javascript:" class="social_share_function"
                                                       data-url="{{route('product',$product->slug)}}"
                                                       data-social="api.whatsapp.com/send?text="
                                                    >
                                                        <i class="bi bi-whatsapp"></i>
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                        <div id="sync1" class="owl-carousel owl-theme product-single-main-slider">
                                            @foreach ($product->color_images_full_url as $key => $photo)
                                                @if (count($product->color_images_full_url) > 1 && $key==1 && preg_match('/^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=|\?v=)([^#\&\?]*).*/',$product->video_url))
                                                    <div class="main-thumb border rounded overflow-hidden">
                                                        <div class="" data-bs-toggle="modal" data-bs-target="#videoModal">
                                                            <a href="javascript:">
                                                                <img loading="lazy"
                                                                     src="https://i.ytimg.com/vi/{{substr($product->video_url, strrpos($product->video_url, '/') + 1) }}/0.jpg"
                                                                     alt="{{ translate('products') }}"
                                                                     class="onerror-placeholder-image"
                                                                     height="380px">
                                                            </a>
                                                            <div class="play--icon">
                                                                <i class="bi bi-play-btn-fill"></i>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                                @if($photo['color'] != null)
                                                    <div class="main-thumb border rounded overflow-hidden">
                                                            <a href="{{ getStorageImages(path: $photo['image_name'], type:'product') }}">
                                                                <img loading="lazy" alt="{{ translate('product') }}"
                                                                     src="{{ getStorageImages(path: $photo['image_name'], type:'product') }}">
                                                            </a>
                                                    </div>
                                                @endif
                                            @endforeach

                                            @foreach ($product->color_images_full_url as $key => $photo)
                                                @if($photo['color'] == null)
                                                    <div class="main-thumb border rounded overflow-hidden">
                                                            <a href="{{ getStorageImages(path: $photo['image_name'], type:'product') }}">
                                                                <img loading="lazy" alt="{{ translate('product') }}"
                                                                     src="{{ getStorageImages(path: $photo['image_name'], type:'product') }}">
                                                            </a>
                                                    </div>
                                                @endif
                                            @endforeach

                                            @if (count($product->color_images_full_url) < 1 && preg_match('/^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=|\?v=)([^#\&\?]*).*/',$product->video_url))
                                                <div class="main-thumb border rounded overflow-hidden">
                                                    <div class="" data-bs-toggle="modal" data-bs-target="#videoModal">
                                                        <a href="javascript:">
                                                            <img loading="lazy"
                                                                 src="https://i.ytimg.com/vi/{{substr($product->video_url, strrpos($product->video_url, '/') + 1) }}/0.jpg"
                                                                 alt="{{ translate('products') }}"
                                                                 class="onerror-placeholder-image"
                                                                 height="380px">
                                                        </a>
                                                        <div class="play--icon">
                                                            <i class="bi bi-play-btn-fill"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>

                                        @if($product?->preview_file_full_url['path'])
                                            <button type="button" class="product-preview-modal-button btn btn-dark font-bold px-3 py-2"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#product-preview-modal">
                                                <i class="bi bi-eye-fill"></i>
                                                <span>{{ translate('Preview') }}</span>
                                            </button>
                                        @endif
                                    </div>
                                @else
                                    <div class="overflow-hidden rounded position-relative">
                                        <div class="product-share-icons">
                                            <a href="javascript:" class="share-icon" title="{{translate('share')}}">
                                                <i class="bi bi-share-fill"></i>
                                            </a>
                                            <ul>
                                                <li>
                                                    <a href="javascript:" class="social_share_function"
                                                       data-url="{{route('product',$product->slug)}}"
                                                       data-social="facebook.com/sharer/sharer.php?u=">
                                                        <i class="bi bi-facebook"></i>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="javascript:" class="social_share_function"
                                                       data-url="{{route('product',$product->slug)}}"
                                                       data-social="twitter.com/intent/tweet?text=">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                                             fill="currentColor" class="bi bi-twitter-x" viewBox="0 0 16 16">
                                                            <path
                                                                d="M12.6.75h2.454l-5.36 6.142L16 15.25h-4.937l-3.867-5.07-4.425 5.07H.316l5.733-6.57L0 .75h5.063l3.495 4.633L12.601.75Zm-.86 13.028h1.36L4.323 2.145H2.865l8.875 11.633Z"/>
                                                        </svg>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="javascript:" class="social_share_function"
                                                       data-url="{{route('product',$product->slug)}}"
                                                       data-social="linkedin.com/shareArticle?mini=true&url=">
                                                        <i class="bi bi-linkedin"></i>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="javascript:" class="social_share_function"
                                                       data-url="{{route('product',$product->slug)}}"
                                                       data-social="api.whatsapp.com/send?text=">
                                                        <i class="bi bi-whatsapp"></i>
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                        <div id="sync1" class="owl-carousel owl-theme product-single-main-slider">
                                            @foreach ($product->images_full_url as $key => $photo)
                                                @if (count($product->images_full_url) > 1 && $key==1 && preg_match('/^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=|\?v=)([^#\&\?]*).*/',$product->video_url))
                                                    <div class="main-thumb border rounded overflow-hidden">
                                                        <div class="" data-bs-toggle="modal" data-bs-target="#videoModal">
                                                            <a href="javascript:">
                                                                <img loading="lazy"
                                                                     src="https://i.ytimg.com/vi/{{substr($product->video_url, strrpos($product->video_url, '/') + 1) }}/0.jpg"
                                                                     alt="{{ translate('products') }}"
                                                                     class="onerror-placeholder-image">
                                                            </a>
                                                            <div class="play--icon">
                                                                <i class="bi bi-play-btn-fill"></i>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                                <div class="main-thumb border rounded overflow-hidden">
                                                        <a href="{{ getStorageImages(path: $photo, type:'product') }}">
                                                            <img loading="lazy" alt="{{ translate('product') }}"
                                                                 src="{{ getStorageImages(path: $photo, type:'product') }}">
                                                        </a>
                                                </div>
                                            @endforeach
                                            @if (count($product->images_full_url) < 1 && preg_match('/^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=|\?v=)([^#\&\?]*).*/',$product->video_url))
                                                <div class="main-thumb border rounded overflow-hidden">
                                                    <div class="" data-bs-toggle="modal" data-bs-target="#videoModal">
                                                        <a href="javascript:">
                                                            <img loading="lazy"
                                                                 src="https://i.ytimg.com/vi/{{substr($product->video_url, strrpos($product->video_url, '/') + 1) }}/0.jpg"
                                                                 alt="{{ translate('products') }}"
                                                                 class="onerror-placeholder-image">
                                                        </a>
                                                        <div class="play--icon">
                                                            <i class="bi bi-play-btn-fill"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>

                                        @if($product?->preview_file_full_url['path'])
                                            <button type="button" class="product-preview-modal-button btn btn-dark font-bold px-3 py-2"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#product-preview-modal">
                                                <i class="bi bi-eye-fill"></i>
                                                <span>{{ translate('Preview') }}</span>
                                            </button>
                                        @endif
                                    </div>
                                @endif

                                <div class="overflow-hidden">
                                    <div id="sync2" class="owl-carousel owl-theme product-single-thumbnails">
                                        @if(count($product->images_full_url)>0)
                                            @if(json_decode($product->colors) && count($product->color_images_full_url)>0)
                                                @foreach ($product->color_images_full_url as $key => $photo)
                                                    @if ($key == 1)
                                                        @if ( preg_match('/^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=|\?v=)([^#\&\?]*).*/',$product->video_url))
                                                            <div class="thumb youtube_video">
                                                                <img loading="lazy"
                                                                     src="https://i.ytimg.com/vi/{{substr($product->video_url, strrpos($product->video_url, '/') + 1) }}/0.jpg"
                                                                     class="w-100px onerror-placeholder-image" alt="{{ translate('products') }}">
                                                                <div class="play--icon">
                                                                    <i class="bi bi-play-btn-fill"></i>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    @endif
                                                    @if($photo['color'] != null)
                                                        <div class="thumb color_variants_preview-box-{{$photo['color']}}">
                                                            <img loading="lazy" alt="{{ translate('product') }}"
                                                                 src="{{ getStorageImages(path: $photo['image_name'], type:'product') }}">
                                                        </div>
                                                    @endif
                                                @endforeach

                                                @foreach ($product->color_images_full_url as $key => $photo)
                                                    @if($photo['color'] == null)
                                                        <img loading="lazy" alt="{{ translate('product') }}"
                                                             src="{{ getStorageImages(path: $photo['image_name'], type:'product') }}">
                                                    @endif
                                                @endforeach
                                            @else
                                                @php($product_images = $product->images_full_url)
                                                @foreach ($product_images as $key => $photo)
                                                    @if (count($product_images) > 1 && $key==1 && preg_match('/^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=|\?v=)([^#\&\?]*).*/',$product->video_url))
                                                        <div class="thumb youtube_video">
                                                            <img loading="lazy"
                                                                 src="https://i.ytimg.com/vi/{{substr($product->video_url, strrpos($product->video_url, '/') + 1) }}/0.jpg"
                                                                 class="w-100px onerror-placeholder-image" alt="{{ translate('products') }}">
                                                            <div class="play--icon">
                                                                <i class="bi bi-play-btn-fill"></i>
                                                            </div>
                                                        </div>
                                                    @endif
                                                    <div class="thumb ">
                                                        <img loading="lazy" src="{{ getStorageImages(path: $photo, type: 'product') }}"
                                                             alt="{{ translate('product') }}">
                                                    </div>

                                                @endforeach
                                                @if (count($product_images) <= 1 && preg_match('/^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=|\?v=)([^#\&\?]*).*/',$product->video_url))
                                                    <div class="thumb youtube_video">
                                                        <img loading="lazy"
                                                             src="https://i.ytimg.com/vi/{{substr($product->video_url, strrpos($product->video_url, '/') + 1) }}/0.jpg"
                                                             class="w-100px onerror-placeholder-image" alt="{{ translate('products') }}">
                                                        <div class="play--icon">
                                                            <i class="bi bi-play-btn-fill"></i>
                                                        </div>
                                                    </div>
                                                @endif
                                            @endif
                                        @endif

                                    </div>
                                </div>

                            </div>
                        @endif

                        <div class="product-single-content">
                            <h2 class="title fw-bold">{{$product->name}}</h2>

                            @if($product->brand_id && $product->brand)
                                <div class="product-brand mb-3">
                                    <a href="{{route('products',['brand_id'=> $product->brand_id,'data_from'=>'brand','page'=>1])}}" 
                                       style="font-size: 16px;" class="text-base fw-semibold text-decoration-none">
                                        {{$product->brand->name}}
                                    </a>
                                    <img src="https://www.goldenscent.com/assets/100-original.svg" alt="">
                                </div>
                            @endif
                            
                            <input type="hidden" name="id" value="{{ $product->id }}">
                            <div class="d-flex flex-wrap align-items-center column-gap-4">
                                @if (count($product->reviews) > 0)
                                    <div class=" review position-relative">
                                        <i class="bi bi-star-fill"></i>
                                        <span>{{round($overallRating[0], 1)}} <small>({{ count($product->reviews) }} {{translate('review')}})</small></span>

                                        <div class="review-details-popup z-3">
                                            <div class="mb-4px">{{ translate('rating') }}</div>
                                            <div class="review-items d-flex flex-column row-gap-1">
                                                <div class="d-flex column-gap-2 align-items-center">
                                                    <div class="stars">
                                                        <i class="bi bi-star-fill"></i>
                                                        <i class="bi bi-star-fill"></i>
                                                        <i class="bi bi-star-fill"></i>
                                                        <i class="bi bi-star-fill"></i>
                                                        <i class="bi bi-star-fill"></i>
                                                    </div>
                                                    <span class="progress">
                                                <div class="progress-fill"
                                                     style="--fill:{{($rating[0] != 0?number_format($rating[0]*100 / array_sum($rating)):0)}}%"></div>
                                            </span>
                                                    <span>({{$rating[0]}})</span>
                                                </div>
                                                <div class="d-flex column-gap-2 align-items-center">
                                                    <div class="stars">
                                                        <i class="bi bi-star-fill"></i>
                                                        <i class="bi bi-star-fill"></i>
                                                        <i class="bi bi-star-fill"></i>
                                                        <i class="bi bi-star-fill"></i>
                                                        <i class="bi bi-star-fill"></i>
                                                    </div>
                                                    <span class="progress">
                                                <div class="progress-fill"
                                                     style="--fill:{{($rating[1] != 0?number_format($rating[1]*100 / array_sum($rating)):0)}}%"></div>
                                            </span>
                                                    <span>({{$rating[1]}})</span>
                                                </div>
                                                <div class="d-flex column-gap-2 align-items-center">
                                                    <div class="stars">
                                                        <i class="bi bi-star-fill"></i>
                                                        <i class="bi bi-star-fill"></i>
                                                        <i class="bi bi-star-fill"></i>
                                                        <i class="bi bi-star-fill"></i>
                                                        <i class="bi bi-star-fill"></i>
                                                    </div>
                                                    <span class="progress">
                                                <div class="progress-fill"
                                                     style="--fill:{{($rating[2] != 0?number_format($rating[2]*100 / array_sum($rating)):0)}}%"></div>
                                            </span>
                                                    <span>({{$rating[2]}})</span>
                                                </div>
                                                <div class="d-flex column-gap-2 align-items-center">
                                                    <div class="stars">
                                                        <i class="bi bi-star-fill"></i>
                                                        <i class="bi bi-star-fill"></i>
                                                        <i class="bi bi-star-fill"></i>
                                                        <i class="bi bi-star-fill"></i>
                                                        <i class="bi bi-star-fill"></i>
                                                    </div>
                                                    <span class="progress">
                                                <div class="progress-fill"
                                                     style="--fill:{{($rating[3] != 0?number_format($rating[3]*100 / array_sum($rating)):0)}}%"></div>
                                            </span>
                                                    <span>({{$rating[3]}})</span>
                                                </div>
                                                <div class="d-flex column-gap-2 align-items-center">
                                                    <div class="stars">
                                                        <i class="bi bi-star-fill"></i>
                                                        <i class="bi bi-star-fill"></i>
                                                        <i class="bi bi-star-fill"></i>
                                                        <i class="bi bi-star-fill"></i>
                                                        <i class="bi bi-star-fill"></i>
                                                    </div>
                                                    <span class="progress">
                                                <div class="progress-fill"
                                                     style="--fill:{{($rating[4] != 0?number_format($rating[4]*100 / array_sum($rating)):0)}}%"></div>
                                            </span>
                                                    <span>({{$rating[4]}})</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class=" review position-relative">
                                        <i class="bi bi-star-fill"></i>
                                        <span>{{round($overallRating[0], 1)}}</span>
                                    </div>
                                @endif

                            </div>

                            <div class="price">
                                <div class="d-flex align-items-center gap-2">
                                    @if(getProductPriceByType(product: $product, type: 'discount', result: 'value') > 0)
                                        <del class="text-muted fs-20">
                                            <span class="product-details-original-price-amount">
                                                {!! webCurrencyConverterWithImage(amount: $product->unit_price) !!}
                                            </span>
                                        </del>
                                    @endif
                                    <h3 class="d-flex align-items-center gap-2">
                                        <span class="product-details-chosen-price-amount">
                                            {!! webCurrencyConverterWithImage(amount: $product->unit_price - getProductDiscount(product: $product, price: $product->unit_price)) !!}
                                        </span>
                                    </h3>

                                    <span class="discounted-badge-element" {!! getProductPriceByType(product: $product, type: 'discount', result: 'value') <= 0 ? 'style="display: none;"' : '' !!}>
                                    @if($product->discount > 0)
                                            @if ($product->discount_type === "percent")
                                                <span class="badge bg-base discounted_badge">
                                                {{translate('save')}} {!! webCurrencyConverterWithImage($product->discount) !!}%
                                            </span>
                                            @else
                                                <span class="badge bg-base discounted_badge">
                                                {{translate('save')}} {!! webCurrencyConverterWithImage(amount: $product->discount) !!}
                                            </span>
                                            @endif
                                        @endif
                                </span>
                                </div>
                            </div>


                            @if (count(json_decode($product->colors)) > 0)
                                <div>
                                    <label class="form-label">{{translate('color')}}</label>
                                    <div class="check-color-group justify-content-start align-items-center">
                                        @foreach (json_decode($product->colors) as $key => $color)
                                            <label>
                                                <input type="radio" name="color"
                                                       value="{{ $color }}" {{ $key == 0 ? 'checked' : '' }}>
                                                <span style="--base:{{ $color }}" class="focus_preview_image_by_color"
                                                      data-colorid="preview-box-{{ str_replace('#','',$color) }}"
                                                      id="color_variants_preview-box-{{ str_replace('#','',$color) }}">
                                                <i class="bi bi-check"></i>
                                            </span>
                                            </label>
                                        @endforeach
                                        <span class="product-details-sticky-color-name"></span>
                                    </div>
                                </div>
                            @endif

                            @php($extensionIndex=0)
                            @if($product['product_type'] == 'digital' && $product['digital_product_file_types'] && count($product['digital_product_file_types']) > 0 && $product['digital_product_extensions'])
                                @foreach($product['digital_product_extensions'] as $extensionKey => $extensionGroup)
                                    <div class="mt-20px">
                                        <label class="form-label">
                                            {{ translate($extensionKey) }}
                                        </label>
                                        @if(count($extensionGroup) > 0)
                                            <div class="d-flex flex-wrap gap-2 user-select-none">
                                                @foreach($extensionGroup as $index => $extension)
                                                    <label class="form-check-size user-select-none">
                                                        <input type="radio" hidden
                                                               id="extension_{{ str_replace(' ', '-', $extension) }}"
                                                               name="variant_key"
                                                               value="{{ $extensionKey.'-'.preg_replace('/\s+/', '-', $extension) }}"
                                                            {{ $extensionIndex == 0 ? 'checked' : ''}}>
                                                        <span class="form-check-label rounded-10 border-2">
                                            {{ $extension }}
                                        </span>
                                                    </label>
                                                    @php($extensionIndex++)
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            @endif

                            @foreach (json_decode($product->choice_options) as $key => $choice)
                                <div class="mt-20px">
                                    <label class="form-label">{{translate($choice->title)}}</label>
                                    <div class="d-flex flex-wrap gap-2 user-select-none">
                                        @foreach ($choice->options as $key => $option)
                                            <label class="form-check-size">
                                                <input type="radio" name="{{ $choice->name }}" value="{{ $option }}"
                                                    {{ $key == 0 ? 'checked' : '' }} >
                                                <span class="form-check-label">{{$option}}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach

                            <div class="d-flex align-items-center row-gap-2 column-gap-4 mt-20px">
                                <span>{{ translate('quantity') }} :</span>
                                <div class="inc-inputs">
                                    <input type="number" name="quantity" value="{{ $product->minimum_order_qty ?? 1 }}"
                                           class="form-control product_quantity__qty product-details-cart-qty"
                                           min="{{ $product->minimum_order_qty ?? 1 }}"
                                           max="{{$product['product_type'] == 'physical' ? $product->current_stock : 100}}">
                                </div>
                            </div>
                            <input type="hidden" class="product-generated-variation-code" name="product_variation_code" data-product-id="{{ $product['id'] }}">
                            <input type="hidden" value="" class="product-exist-in-cart-list form-control w-50" name="key">
                            @php($guestCheckout = getWebConfig(name: 'guest_checkout'))
                            <div class="btn-grp product-add-and-buy-section-parent">
                                <div class="product-add-and-buy-section d--flex flex-wrap gap-2" {!! $firstVariationQuantity <= 0 ? 'style="display: none;"' : '' !!}>
                                    @if(($product->added_by == 'seller' && ($sellerTemporaryClose || (isset($product->seller->shop) && $product->seller->shop->vacation_status && $currentDate >= $sellerVacationStartDate && $currentDate <= $sellerVacationEndDate))) ||
                                    (   $product->added_by == 'admin' && ($inHouseTemporaryClose || ($inHouseVacationStatus && $currentDate >= $inHouseVacationStartDate && $currentDate <= $inHouseVacationEndDate))))
                                        <button type="button" class="btn btn-base text-capitalize font-medium" disabled>
                                            @include('theme-views.partials.icons._cart-icon')
                                            {{translate('add_to_cart')}}</button>
                                        <button type="button"
                                                class="product-buy-now-button btn btn-base __btn-outline-warning secondary-color fs-16 text-capitalize"
                                                disabled>
                                            @include('theme-views.partials.icons._buy-now')
                                            {{translate('buy_now')}}
                                        </button>
                                    @else
                                        <a href="javascript:"
                                           class="btn btn-base text-capitalize font-medium product-add-to-cart-button"
                                           type="button"
                                           data-form=".add-to-cart-details-form"
                                           data-update="{{ translate('update_cart') }}"
                                           data-add="{{ translate('add_to_cart') }}"
                                        >
                                            @include('theme-views.partials.icons._cart-icon')
                                            <span class="text">{{ translate('add_to_cart') }}</span>
                                        </a>
                                    @endif
                                </div>

                                @if(($product['product_type'] == 'physical'))
                                    <div class="product-restock-request-section collapse" {!! $firstVariationQuantity <= 0 ? 'style="display: block;"' : '' !!}>
                                        <button type="button"
                                                class="btn btn-md __btn-outline-base text-capitalize product-restock-request-button"
                                                data-auth="{{ auth('customer')->check() }}"
                                                data-form=".addToCartDynamicForm"
                                                data-default="{{ translate('Request_Restock') }}"
                                                data-requested="{{ translate('Request_Sent') }}"
                                        >
                                            {{ translate('Request_Restock')}}
                                        </button>
                                    </div>
                                @endif

                                <a href="javascript:"
                                   class="btn btn-base btn-sm __btn-outline addWishlist_function_view_page border bg-transparent"
                                   data-id="{{$product['id']}}">
                                    <i class="wishlist_{{$product['id']}} bi {{($wishlistStatus == 1?'bi-heart-fill text-danger':'bi-heart text-base')}} font--lg"></i>
                                    <span class="product_wishlist_count_status">{{ \App\Utils\format_biginteger($countWishlist) }}</span>
                                </a>

                            </div>

                            @if(($product->added_by == 'seller' && ($sellerTemporaryClose || (isset($product->seller->shop) && $product->seller->shop->vacation_status && $currentDate >= $sellerVacationStartDate && $currentDate <= $sellerVacationEndDate))) ||
                            ($product->added_by == 'admin' && ($inHouseTemporaryClose || ($inHouseVacationStatus && $currentDate >= $inHouseVacationStartDate && $currentDate <= $inHouseVacationEndDate))))
                                <div class="alert alert-danger mt-3" role="alert">
                                    {{translate('this_shop_is_temporary_closed_or_on_vacation')}}
                                    .
                                    {{translate('you_cannot_add_product_to_cart_from_this_shop_for_now')}}
                                </div>
                            @endif
                                                        <!-- Payment Options Section -->
                                                        <div class="mt-20px">
                                <div class="bg-light p-3 rounded text-center">
                                    <h6 class="text-dark mb-2">أو قسمها على 4 دفعات شهرية بقيمة {{ number_format($product->unit_price / 4, 2) }} ر.س</h6>
                                    
                                    <div class="d-flex justify-content-center gap-3 mt-2 align-items-center">
                                        <p class="text-muted mb-0">بدون فوائد ورسوم خفية!</p>

                                        <img src="/public/images/tabby_logo.svg" alt="">
                                        <div class="border rounded p-3 bg-white">
                                            <img src="/public/images/tamara_ar.svg" alt="">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Product Authenticity Section -->
                            <div class="mt-20px">
                                <div class="p-3 rounded d-flex justify-content-between align-items-center" style="border: 1px solid #007aff;flex-direction: row-reverse;">
                                    <div class="d-flex align-items-center">
                                        <img style="width: 10px; height: 10px;" src="/public/images/left-arrow-svgrepo-com.svg" alt="">
                                    </div>
                                    <div class="d-flex align-items-center">
                                    <img src="/public/images/100-original.svg" alt="">
                                        <span class="ms-2" style="color: #007aff;">منتج أصلي</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </form>
                
            </div>

            @if (count($product->reviews) >0)
                <div class="details-review row-gap-4 mt-32px">
                    <div class="details-review-item">
                        <h2 class="title">{{$overallRating[0]}}</h2>
                        <div class="text-star">
                            @for ($i = 1; $i <= 5; $i++)
                                @if ($i <= (int)$overallRating[0])
                                    <i class="bi bi-star-fill"></i>
                                @elseif ($overallRating[0] != 0 && $i <= (int)$overallRating[0] + 1.1 && $overallRating[0] > ((int)$overallRating[0]))
                                    <i class="bi bi-star-half"></i>
                                @else
                                    <i class="bi bi-star"></i>
                                @endif
                            @endfor
                        </div>
                        <span>{{ count($product->reviews) }} {{translate('reviews')}}</span>
                    </div>
                    <div class="details-review-item">
                        <h2 class="title font-regular">{{ round($rattingStatus['positive']) }}%</h2>
                        <span class="text-capitalize">{{translate('positive_review')}}</span>
                    </div>
                    <div class="details-review-item details-review-info">
                        <div class="item">
                            <div class="form-label mb-3 d-flex justify-content-between">
                                <span>{{ translate('positive') }}</span>
                                <span>{{ round($rattingStatus['positive']) }}%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-fill"
                                     style="--fill:{{ round($rattingStatus['positive']) }}%"></div>
                            </div>
                        </div>
                        <div class="item">
                            <div class="form-label mb-3 d-flex justify-content-between">
                                <span>{{ translate('good') }}</span>
                                <span>{{ round($rattingStatus['good']) }}%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-fill" style="--fill:{{ round($rattingStatus['good']) }}%"></div>
                            </div>
                        </div>
                        <div class="item">
                            <div class="form-label mb-3 d-flex justify-content-between">
                                <span>{{translate('neutral')}}</span>
                                <span>{{ round($rattingStatus['neutral']) }}%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-fill"
                                     style="--fill:{{ round($rattingStatus['neutral']) }}%"></div>
                            </div>
                        </div>
                        <div class="item">
                            <div class="form-label mb-3 d-flex justify-content-between">
                                <span>{{translate('negative')}}</span>
                                <span>{{ round($rattingStatus['negative']) }}%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-fill"
                                     style="--fill:{{ round($rattingStatus['negative']) }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if ($product->details != null || count($product->reviews) != 0)
                <div class="row g-2 mt-4">
                    <div class="col-xl-8 product-information-det col-lg-7">
                        <div class="product-information active">
                            <div class="product-information-inner">
                                <ul class="nav nav-tabs nav--tabs-2 justify-content-center">

                                    <li class="nav-item nav-item-ative">
                                        <h2 href="#general-info" class="nav-link cursor-pointer active"
                                           data-bs-toggle="tab">{{ translate('general_info') }}</h2>
                                    </li>

                                    <li class="nav-item">
                                        <h2 href="#description" class="nav-link cursor-pointer"
                                           data-bs-toggle="tab">{{ translate('description') }}</h2>
                                    </li>

                                    <li class="nav-item">
                                        <h2 href="#comments" class="nav-link cursor-pointer"
                                           data-bs-toggle="tab">{{ translate('comment') }}
                                            <sup>{{ count($product->reviews) }}</sup></h2>
                                    </li>
                                </ul>
                                <div class="tab-content">
                                    @if ($product->details != null)
                                        <div class="tab-pane fade show active" id="general-info">
                                            <div class="general-information">
                                                <div class="rich-editor-html-content">
                                                    {!! $product->details !!}
                                                </div>
                                            </div>
                                            <a href="javascript:" class="product-information-view-more"
                                               data-view-more="{{translate('view_more')}}"
                                               data-view-less="{{translate('view_less')}}">
                                                {{translate('view_more')}}
                                            </a>
                                        </div>
                                    @else
                                        <div class="tab-pane fade show active" id="general-info">
                                            <div class="general-information">
                                                {{ translate('No_data_found') }}
                                            </div>
                                        </div>
                                    @endif

                                    <div class="tab-pane fade" id="description">
                                        <div class="description-information">
                                            @if ($product->meta_description && trim($product->meta_description) != '')
                                                <p>
                                                    {!! ($product->meta_description) !!}
                                                </p>
                                                <a href="javascript:" class="product-information-view-more"
                                                    data-view-more="{{translate('view_more')}}"
                                                    data-view-less="{{translate('view_less')}}">
                                                        {{translate('view_more')}}
                                                </a>
                                            @else
                                                <p>{{ translate('No_data_found') }}</p>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="tab-pane fade" id="comments">
                                        @if(count($product->reviews) > 0)
                                            <div class="comments-information">
                                                <ul id="product-review-list">
                                                    @include('theme-views.layouts.partials._product-reviews',['productReviews'=>$productReviews])
                                                </ul>
                                            </div>
                                            @if(count($product->reviews) > 2)
                                                <a href="javascript:" id="load_review_function"
                                                   class="product-information-view-more-custom see-more-details-review view_text"
                                                   data-productid="{{$product->id}}"
                                                   data-routename="{{route('review-list-product')}}"
                                                   data-afterextend="{{translate('see_less')}}"
                                                   data-seemore="{{translate('see_more')}}"
                                                   data-onerror="{{translate('no_more_review_remain_to_load')}}">{{translate('see_more')}}</a>
                                            @endif
                                        @else
                                            <div class="text-center w-100">
                                                <div class="text-center pt-5 mb-5">
                                                    <img loading="lazy"
                                                         src="{{ theme_asset('assets/img/icons/review.svg') }}"
                                                         alt="{{ translate('review') }}">
                                                    <h5 class="my-3 pt-2 text-muted">{{translate('not_reviewed_yet')}}
                                                        !</h5>
                                                    <p class="text-center text-muted">{{ translate('sorry_no_review_found_to_show_you') }}</p>
                                                </div>
                                            </div>
                                        @endif
                                        @if(auth('customer')->check())
                                            <div class="mt-3">
                                                <form action="{{ route('review.store') }}" method="post" enctype="multipart/form-data" class="review-card shadow-sm rounded-3 p-3 p-md-4 border position-relative">
                                                    @csrf
                                                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <i class="bi bi-chat-dots text-base fs-4"></i>
                                                            <h6 class="mb-0 fw-bold">{{ translate('review') }}</h6>
                                                        </div>
                                                    </div>
                                                    <div class="row g-3 align-items-center">
                                                        <div class="col-md-5">
                                                            <label class="form-label mb-1">{{ translate('rating') }}</label>
                                                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                                                <div class="star-rating d-inline-flex align-items-center gap-1" data-target="#review-rating-input-{{ $product->id }}" aria-label="{{ translate('rating') }}">
                                                                    @for($i=1;$i<=5;$i++)
                                                                        <i class="bi bi-star star-icon" role="button" tabindex="0" data-value="{{ $i }}" title="{{ $i }}/5" aria-label="{{ $i }} {{ translate('stars') }}"></i>
                                                                    @endfor
                                                                </div>
                                                                <span class="rating-indicator badge bg-light text-dark border fw-semibold" data-for="#review-rating-input-{{ $product->id }}">5/5</span>
                                                            </div>
                                                            <input type="hidden" id="review-rating-input-{{ $product->id }}" name="rating" value="5">
                                                        </div>
                                                        <div class="col-md-7 col-12">
                                                            <label class="form-label mb-1">{{ translate('comment') }}</label>
                                                            <div class="position-relative">
                                                                <textarea name="comment" class="form-control review-textarea" rows="3" maxlength="500" placeholder="{{ translate('leave_a_comment') }}"></textarea>
                                                                <small class="char-counter text-muted position-absolute end-0 bottom-0 me-2 mb-1">0/500</small>
                                                            </div>
                                                        </div>
                                                        <div class="col-12 d-flex justify-content-start">
                                                            <button type="submit" class="btn btn-base px-4 submit-review-btn">
                                                                <span class="btn-text">{{ translate('submit') }}</span>
                                                                <span class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <style>
                                                        .review-card { background: #fff; }
                                                        .review-card .star-rating .star-icon { font-size: 1.6rem; color: #d6d6d6; cursor: pointer; transition: transform .12s ease, color .12s ease; }
                                                        .review-card .star-rating .star-icon.active,
                                                        .review-card .star-rating .star-icon.hover { color: #ffb800; }
                                                        .review-card .star-rating .star-icon:focus { outline: 2px solid rgba(255,184,0,.3); border-radius: 2px; }
                                                        .review-card .review-textarea { resize: vertical; min-height: 90px; }
                                                        .review-card .char-counter { font-size: .75rem; }
                                                        .product-information:not(.active) .product-information-inner {
                                                            max-height: 50rem !important;
                                                        }
                                                    </style>
                                                    <script>
                                                        (function() {
                                                            const form = document.currentScript.closest('form');
                                                            const wrap = form.querySelector('.star-rating');
                                                            if (!wrap) return;
                                                            const input = document.querySelector(wrap.getAttribute('data-target'));
                                                            const stars = Array.from(wrap.querySelectorAll('.star-icon'));
                                                            const indicator = form.querySelector('.rating-indicator');
                                                            const area = form.querySelector('.review-textarea');
                                                            const counter = form.querySelector('.char-counter');
                                                            const setActive = (val) => {
                                                                stars.forEach(s => {
                                                                    const v = parseInt(s.dataset.value);
                                                                    if (v <= val) { s.classList.add('active'); s.classList.remove('bi-star'); s.classList.add('bi-star-fill'); }
                                                                    else { s.classList.remove('active'); s.classList.remove('bi-star-fill'); s.classList.add('bi-star'); }
                                                                });
                                                                if (indicator) indicator.textContent = val + '/5';
                                                            };
                                                            const defaultVal = parseInt(input.value || '5');
                                                            setActive(defaultVal);
                                                            stars.forEach(s => {
                                                                s.addEventListener('click', () => {
                                                                    const val = parseInt(s.dataset.value);
                                                                    input.value = val;
                                                                    setActive(val);
                                                                });
                                                                s.addEventListener('keydown', (e) => {
                                                                    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); s.click(); }
                                                                });
                                                                s.addEventListener('mouseenter', () => {
                                                                    const val = parseInt(s.dataset.value);
                                                                    stars.forEach(si => si.classList.toggle('hover', parseInt(si.dataset.value) <= val));
                                                                });
                                                                s.addEventListener('mouseleave', () => stars.forEach(si => si.classList.remove('hover')));
                                                            });
                                                            if (area && counter) {
                                                                const updateCounter = () => {
                                                                    counter.textContent = area.value.length + '/500';
                                                                };
                                                                updateCounter();
                                                                area.addEventListener('input', () => {
                                                                    updateCounter();
                                                                    area.style.height = 'auto';
                                                                    area.style.height = Math.min(area.scrollHeight, 240) + 'px';
                                                                });
                                                            }
                                                            const submitBtn = form.querySelector('.submit-review-btn');
                                                            form.addEventListener('submit', () => {
                                                                if (!submitBtn) return;
                                                                submitBtn.disabled = true;
                                                                submitBtn.querySelector('.spinner-border')?.classList.remove('d-none');
                                                            }, { once: true });
                                                        })();
                                                    </script>
                                                </form>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @if($productsThisStoreTopRated->count() > 0)
                        <div class="col-xl-4 col-lg-5">
                            <div
                                class="border top-rated-product-from-store-wrapper p-3 p-md-18 d-flex flex-column justify-content-center border-light-base shadow-light-base">
                                <div class="section-title mb-4 pb-lg-1">
                                    <div
                                        class="d-flex justify-content-between row-gap-2 column-gap-4 align-items-center">
                                        <h2 class="mb-0 me-auto text-capitalize fs-20 fw-bold">{{ translate('top_rated_product_from_this_store') }}</h2>
                                    </div>
                                </div>
                                <div class="overflow-hidden">
                                    <div class="side-column-slider">
                                        <div class="owl-theme owl-carousel slider">
                                            @foreach ($productsThisStoreTopRated as $relatedProduct)
                                                @include('theme-views.partials._similar-product-large-card', ['product'=>$relatedProduct])
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @else
                @if($productsThisStoreTopRated->count() > 0)
                    <div class="mt-3">
                        <div
                            class="border h-100 p-3 p-md-18 d-flex flex-column justify-content-center border-light-base shadow-light-base">
                            <div class="section-title mb-4 pb-lg-1">
                                <div class="d-flex justify-content-between row-gap-2 column-gap-4 align-items-center">
                                    <h4 class="mb-0 me-auto text-capitalize">{{ translate('top_rated_product_from_this_store') }}</h4>
                                    <div
                                        class="d-flex align-items-center column-gap-4 justify-content-end ms-auto ms-md-0">
                                        <div class="owl-prev top-rated-product-from-store-prev"><i
                                                class="bi bi-chevron-left"></i>
                                        </div>
                                        <div class="owl-next top-rated-product-from-store-next"><i
                                                class="bi bi-chevron-right"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="overflow-hidden">
                                <div class="side-column-slider">
                                    <div class="owl-theme owl-carousel top-rated-product-from-store-slider">
                                        @foreach ($productsThisStoreTopRated as $relatedProduct)
                                            @include('theme-views.partials._similar-product-large-card', ['product'=>$relatedProduct])
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endif

            @if($web_config['business_mode'] == 'multi')
                <div class="mt-4">
                    <div class="similler-product-slider-wrapper">
                        <div class="row g-0">
                            <div class="col-md-5 col-lg-4 col-xl-3">
                                <div class="p-3 ps-xl-4">
                                    @if($product->added_by == 'seller')
                                        @if(isset($product->seller->shop))
                                            <div class="others-store-card bg-white p-0">
                                                <div class="p-3 pt-4">
                                                    <div class="name-area">
                                                        <div class="position-relative ">
                                                            <div>
                                                                <img loading="lazy" class="rounded-full other-store-logo"
                                                                     src="{{ getStorageImages(path: $product->seller->shop->image_full_url, type:'shop') }}"
                                                                     alt="{{ translate('others_store') }}">
                                                            </div>
                                                            @if($product->seller->shop->temporary_close)
                                                                <span class="temporary-closed position-absolute text-center h6 rounded-full">
                                                                    <span>{{translate('Temporary_OFF')}}</span>
                                                                </span>
                                                            @elseif(($product->seller->shop->vacation_status && ($currentDate >= $product->seller->shop->vacation_start_date) && ($currentDate <= $product->seller->shop->vacation_end_date)))
                                                                <span class="temporary-closed position-absolute text-center h6 rounded-full">
                                                                    <span>{{translate('closed_now')}}</span>
                                                                </span>
                                                            @endif
                                                        </div>
                                                        <div class="info">
                                                            <h2 class="name h6 letter-spacing-05em">{{$product->seller->shop->name}}</h2>
                                                            <span
                                                                class="offer-badge">{{round($positiveReview)}}% {{translate('positive_review')}}</span>
                                                        </div>
                                                    </div>
                                                    <div class="info-area mb-2">
                                                        <div class="info-item">
                                                            <h6>{{$totalReviews}}</h6>
                                                            <span>{{ translate('reviews') }}</span>
                                                        </div>
                                                        <div class="info-item">
                                                            <h6>{{$productsCount}}</h6>
                                                            <span>{{ translate('products') }}</span>
                                                        </div>
                                                        <div class="info-item">
                                                            <h6>{{number_format($avgRating, 2)}}</h6>
                                                            <i class="bi bi-star-fill"></i>
                                                            <span>{{ translate('rating') }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="btn-grp d-flex jusitfy-content-center bg-E2F0FF gap-2 p-3">
                                                    <a href="{{ route('shopView',[$product->seller->id]) }}"
                                                       class="btn bg-white __btn-outline">
                                                        <i class="bi bi-shop"></i> {{ translate('visit_shop') }}
                                                    </a>
                                                    @if (auth('customer')->id() == '')
                                                        <a href="javascript:"
                                                           class="btn bg-white __btn-outline customer_login_register_modal">
                                                            <i class="bi bi-chat-dots"></i> {{ translate('chat') }}
                                                        </a>
                                                    @else
                                                        <a href="javascript:" class="btn bg-white __btn-outline"
                                                           data-bs-toggle="modal"
                                                           data-bs-target="#contact_sellerModal">
                                                            <i class="bi bi-chat-dots"></i> {{ translate('chat') }}
                                                        </a>
                                                    @endif
                                                </div>
                                                @if (auth('customer')->id() != '')
                                                    @include('theme-views.layouts.partials.modal._chat-with-seller',['shop'=>$product->seller->shop, 'user_type'=>'seller'])
                                                @endif
                                            </div>
                                        @endif
                                    @else
                                        <div class="others-store-card bg-white p-0">
                                            <div class="p-3 pt-4">
                                                <div class="name-area">
                                                    <img loading="lazy" alt="{{ translate('logo') }}"
                                                         src="{{ getStorageImages(path: $web_config['fav_icon'], type:'shop') }}">
                                                    <div class="info">
                                                        <h6 class="name">{{$web_config['company_name']}}</h6>
                                                        <span class="offer-badge">
                                                        {{ round($positiveReview) }}% {{translate('positive_review')}}
                                                    </span>
                                                    </div>
                                                </div>
                                                <div class="info-area mb-2">
                                                    <div class="info-item">
                                                        <h6>{{$totalReviews}}</h6>
                                                        <span>{{ translate('reviews') }}</span>
                                                    </div>
                                                    <div class="info-item">
                                                        <h6>{{$productsCount}}</h6>
                                                        <span>{{ translate('products') }}</span>
                                                    </div>
                                                    <div class="info-item">
                                                        <h6>{{number_format($avgRating, 2)}}</h6>
                                                        <i class="bi bi-star-fill"></i>
                                                        <span>{{ translate('rating') }}</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="btn-grp d-flex jusitfy-content-center bg-E2F0FF gap-2 p-3">
                                                <a href="{{ route('shopView',[0]) }}" class="btn bg-white __btn-outline">
                                                    <i class="bi bi-shop"></i> {{ translate('visit_shop') }}
                                                </a>
                                                @if (auth('customer')->id() == '')
                                                    <a href="javascript:"
                                                       class="btn bg-white __btn-outline customer_login_register_modal">
                                                        <i class="bi bi-chat-dots"></i> {{ translate('chat') }}
                                                    </a>
                                                @else
                                                    <a href="javascript:" class="btn bg-white __btn-outline"
                                                       data-bs-toggle="modal"
                                                       data-bs-target="#contact_sellerModal">
                                                        <i class="bi bi-chat-dots"></i> {{ translate('chat') }}
                                                    </a>
                                                @endif
                                            </div>
                                            @if (auth('customer')->id() != '')
                                                @include('theme-views.layouts.partials.modal._chat-with-seller',['shop'=>0, 'user_type'=>'admin'])
                                            @endif

                                        </div>
                                    @endif

                                </div>
                            </div>


                            <div class="col-md-7 col-lg-8 col-xl-9">
                                <div class="py-3 ps-3">
                                    <div class="section-title mb-4 pb-lg-1 pe-3">
                                        <div
                                            class="d-flex flex-wrap justify-content-between row-gap-2 column-gap-4 align-items-center text-capitalzie">
                                            <h2 class="mb-0 me-auto font-bold w-0 flex-grow-1">{{ translate('similar_product_from_this_store') }}
                                                <small
                                                    class="font-regular text-text-2">({{count($moreProductFromSeller)}} {{ translate('product') }}
                                                    )</small>
                                            </h2>
                                            @if($product->added_by=='seller')
                                                @if(isset($product->seller->shop))
                                                    <a href="{{ route('shopView',[$product->seller->id]) }}"
                                                       class="see-all">{{ translate('see_all') }}</a>
                                                @endif
                                            @else
                                                <a href="{{ route('shopView',[0]) }}"
                                                   class="see-all">{{ translate('see_all') }}</a>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="overflow-hidden">
                                        @if ($moreProductFromSeller->count() > 0)
                                            <div class="similler-product-slider-area">
                                                <div class="similler-product-slider owl-theme owl-carousel">
                                                    @foreach($moreProductFromSeller as $product)
                                                        @include('theme-views.partials._product-small-card', ['product'=>$product])
                                                    @endforeach
                                                </div>
                                            </div>
                                        @else
                                            <div class="d-flex">
                                                <div class="card">
                                                    <div class="card-body">
                                                        <h6>{{translate('similar_product_not_available')}}</h6>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </section>

    @if(count($productsLatest) > 0 && count($productsTopRated) > 0)
    <section class="recommended-product-section section-gap pb-0">
        <div class="container">
            <div class="section-title mb-4 pb-lg-1">
                <div
                    class="d-flex flex-column flex-md-row justify-content-md-between row-gap-2 column-gap-4 align-items-md-center single_section_dual_tabs text-capitalize">
                    <h2 class="title mb-0 me-auto text-capitalize">{{ translate('you_may_also_like') }}</h2>
                    <div class="d-flex column-gap-4 align-items-center justify-content-between">
                        <ul class="nav nav-tabs nav--tabs single_section_dual_btn text-capitalize">
                            <li data-targetbtn="0" role="tab">
                               <h2 class="lh-1">
                                    <a href="#latest" class="active"
                                    data-bs-toggle="tab">{{ translate('latest_product') }}</a>
                               </h2>
                            </li>
                            <li data-targetbtn="1" role="tab">
                                <h2 class="lh-1">
                                    <a href="#top-rated-product" data-bs-toggle="tab">{{ translate('top_rated') }}</a>
                                </h2>
                            </li>
                        </ul>
                        <div
                            class="d-flex align-items-center column-gap-3 column-gap-md-4 justify-content-end ms-auto ms-md-0">
                            <div class="owl-prev recommended-prev">
                                <i class="bi bi-chevron-left"></i>
                            </div>
                            <div class="owl-next recommended-nex">
                                <i class="bi bi-chevron-right"></i>
                            </div>
                            <div class="single_section_dual_target">
                                <a href="{{route('products',['data_from'=>'latest','page'=>1])}}"
                                   class="see-all text-nowrap">{{ translate('see_all') }}</a>
                                <a href="{{route('products',['data_from'=>'top-rated','page'=>1])}}"
                                   class="see-all text-nowrap d-none">{{ translate('see_all') }}</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="overflow-hidden">
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="latest">
                        <div class="recommended-slider-wrapper">
                            <div class="recommended-slider owl-theme owl-carousel">
                                @foreach ($productsLatest as $singleProduct)
                                    @include('theme-views.partials._product-medium-card', ['product' => $singleProduct, 'hideQuickView' => true])
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade show" id="top-rated-product">
                        <div class="recommended-slider-wrapper">
                            <div class="recommended-slider owl-theme owl-carousel">
                                @foreach ($productsTopRated as $singleProduct)
                                    @include('theme-views.partials._product-medium-card', ['product'=>$singleProduct, 'hideQuickView' => true])
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif

    @if($web_config['business_mode'] == 'multi')
        @include('theme-views.partials._other-stores')
    @endif

    @include('theme-views.partials._how-to-section')

@endsection

@push('script')
    <script src="{{ theme_asset('assets/js/product-details.js') }}"></script>
    
    <!-- Add to Cart Fix Script -->
    <script>
        "use strict";
        // Cache busting - Updated: 2025-01-16-5
        console.log('Product Details Fix loaded - Version 2025-01-16-5');
        console.log('Timestamp:', new Date().toISOString());
        console.log('Cache busting ID:', Math.random().toString(36).substr(2, 9));

        // Add to Cart Function
        function addToCart(formElement, redirectToCheckout = "false", url = null) {
            console.log('addToCart function called', formElement, redirectToCheckout, url);
            if (checkFromValidityForVariantPrice(formElement)) {
                $.ajaxSetup({
                    headers: {
                        "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content"),
                    },
                });
                let redirectCheckout = redirectToCheckout?.toString();

                let existCartItem = $('.product-exist-in-cart-list[name="key"]').val();
                let formActionUrl = formElement.attr("action");
                if (existCartItem !== "" && redirectCheckout === "false") {
                    formActionUrl = $("#update_quantity_url").data("url");
                }

                $.post({
                    url: formActionUrl,
                    data: formElement.serializeArray()
                        .concat({
                            name: "buy_now",
                            value: redirectCheckout === "true" ? 1 : 0,
                        }),
                    beforeSend: function () {
                    },
                    success: function (response) {
                        if (response.status === 2) {
                            hideProductDetailsStickySection();
                            $("#buyNowModal-body").html(
                                response.shippingMethodHtmlView
                            );
                            $("#quickViewModal").modal("hide");
                            $("#buyNowModal").modal("show");
                        }

                        if (response.status == 1) {
                            updateNavCart();

                            let actionAddToCartBtn = formElement.find(".product-add-to-cart-button");
                            actionAddToCartBtn.children("span").html(actionAddToCartBtn.data("update"));

                            if (response?.product_variant_type === 'single_variant') {
                                $('.add-to-cart-details-form').find(".product-add-to-cart-button").children('.text').html(actionAddToCartBtn.data("update"));
                                $('.add-to-cart-sticky-form').find(".product-add-to-cart-button").children('.text').html(actionAddToCartBtn.data("update"));
                            }

                            toastr.success(response.message, {
                                CloseButton: true,
                                ProgressBar: true,
                                timeOut: 2000, // duration
                            });
                            if (
                                redirectCheckout === "true" &&
                                response.redirect_to_url
                            ) {
                                setTimeout(function () {
                                    location.href = response.redirect_to_url;
                                }, 100);
                            } else if (redirectCheckout === "true") {
                                setTimeout(function () {
                                    location.href = url;
                                }, 100);
                            }

                            $("#quickViewModal").modal("hide");
                        } else if (response.status == 0) {
                            toastr.warning(response.message, {
                                CloseButton: true,
                                ProgressBar: true,
                                timeOut: 2000, // duration
                            });
                        }
                    },
                    complete: function () {
                    },
                });
            } else if (formElement.find("input[name=quantity]") == 0) {
                toastr.warning(formElement.data("outofstock"), {
                    CloseButton: true,
                    ProgressBar: true,
                    timeOut: 2000, // duration
                });
            } else {
                toastr.info(formElement.data("errormessage"), {
                    CloseButton: true,
                    ProgressBar: true,
                    timeOut: 2000, // duration
                });
            }
        }

        // Hide Product Details Sticky Section Function
        function hideProductDetailsStickySection() {
            $('.product-details-sticky-section').hide();
        }

        // Make functions globally available
        window.addToCart = addToCart;
        window.hideProductDetailsStickySection = hideProductDetailsStickySection;

        console.log('addToCart function defined successfully');
        console.log('addToCart function available:', typeof window.addToCart);

        // Global function availability check
        window.checkAddToCartAvailability = function() {
            console.log('Checking addToCart availability...');
            console.log('window.addToCart:', typeof window.addToCart);
            console.log('addToCart:', typeof addToCart);
            return typeof window.addToCart === 'function' || typeof addToCart === 'function';
        };

        // Event Handlers
        $(document).ready(function() {
            console.log('Setting up product details event handlers...');
            
            // Product Add to Cart Event Handler
            $(".product-add-to-cart-button").on("click", function () {
                console.log('Product add to cart button clicked');
                let parentElement = $(this).closest('.product-cart-option-container');
                let productCartForm = parentElement.find('.addToCartDynamicForm');
                
                if (typeof window.addToCart === 'function') {
                    window.addToCart(productCartForm.length ? productCartForm : $(".add-to-cart-details-form"));
                } else if (typeof addToCart === 'function') {
                    addToCart(productCartForm.length ? productCartForm : $(".add-to-cart-details-form"));
                } else {
                    console.error('CRITICAL: addToCart function not available anywhere!');
                    console.error('Available functions:', Object.keys(window).filter(k => k.includes('addToCart')));
                }
            });

            // Product Buy Now Button Event Handler
            $(".product-buy-now-button").on("click", function () {
                console.log('Buy now button clicked');
                let url = $(this).data("route");
                let redirectStatus = $(this).data("auth").toString();
                let parentElement = $(this).closest('.product-cart-option-container');
                let productCartForm = parentElement.find('.addToCartDynamicForm');
                
                if (typeof window.addToCart === 'function') {
                    window.addToCart(productCartForm, redirectStatus, url);
                } else if (typeof addToCart === 'function') {
                    addToCart(productCartForm, redirectStatus, url);
                } else {
                    console.error('CRITICAL: addToCart function not available anywhere!');
                    console.error('Available functions:', Object.keys(window).filter(k => k.includes('addToCart')));
                }
                
                if (redirectStatus === "false") {
                    $("#quickViewModal").modal("hide");
                    if (typeof customerLoginRegisterModalCall === 'function') {
                        customerLoginRegisterModalCall();
                    }
                    toastr.warning($('.login-warning').data('login-warning-message'));
                }
            });

            // Mobile Add to Cart Event Handler
            $(".add_to_cart_mobile").on("click", function () {
                console.log('Mobile add to cart button clicked');
                let productID = $(this).data('id');
                let parentElement = $(this).closest('.product-cart-option-container');
                let productCartForm = parentElement.find('.addToCartDynamicForm');
                
                if (typeof window.addToCart === 'function') {
                    window.addToCart(productCartForm.length ? productCartForm : $(".add-to-cart-details-form"));
                } else if (typeof addToCart === 'function') {
                    addToCart(productCartForm.length ? productCartForm : $(".add-to-cart-details-form"));
                } else {
                    console.error('CRITICAL: addToCart function not available anywhere!');
                    console.error('Available functions:', Object.keys(window).filter(k => k.includes('addToCart')));
                }
            });

            // Restock Request Button Event Handler
            $(".product-restock-request-button").on("click", function () {
                console.log('Restock request button clicked');
                let auth = $(this).data('auth');
                let form = $(this).data('form');
                let defaultText = $(this).data('default');
                let requestedText = $(this).data('requested');
                
                if (auth === true || auth === 'true' || auth === 1) {
                    // User is logged in, proceed with restock request
                    if (typeof window.addToCart === 'function') {
                        window.addToCart($(form));
                    } else if (typeof addToCart === 'function') {
                        addToCart($(form));
                    }
                } else {
                    // User not logged in, show login modal
                    if (typeof customerLoginRegisterModalCall === 'function') {
                        customerLoginRegisterModalCall();
                    }
                }
            });

            console.log('All product details event handlers set up successfully');
        });
    </script>
@endpush
<style>
    @media screen and (max-width: 767px) {
        .product-information-det {
            width: 100%;
        }
        
    }
</style>