@extends('theme-views.layouts.app')

@section('title',translate('shop_page').' | '.$web_config['company_name'].' '.translate('ecommerce'))

@push('css_or_js')
    @if($shop['id'] != 0)
        <meta property="og:image" content="{{$shop->image_full_url['path']}}"/>
        <meta property="og:title" content="{{ $shop->name}} "/>
        <meta property="og:url" content="{{route('shopView',[$shop['id']])}}">
    @else
        <meta property="og:image" content="{{$web_config['fav_icon']['path']}}"/>
        <meta property="og:title" content="{{ $shop['name']}} "/>
        <meta property="og:url" content="{{route('shopView',[$shop['id']])}}">
    @endif
    <meta property="og:description" content="{!! $web_config['meta_description'] !!}">
    @if($shop['id'] != 0)
        <meta property="twitter:card" content="{{$shop->image_full_url['path']}}"/>
        <meta property="twitter:title" content="{{route('shopView',[$shop['id']])}}"/>
        <meta property="twitter:url" content="{{route('shopView',[$shop['id']])}}">
    @else
        <meta property="twitter:card"
              content="{{$web_config['fav_icon']['path']}}"/>
        <meta property="twitter:title" content="{{route('shopView',[$shop['id']])}}"/>
        <meta property="twitter:url" content="{{route('shopView',[$shop['id']])}}">
    @endif
    <meta property="twitter:description" content="{!! $web_config['meta_description'] !!}">
@endpush

@section('content')
    @if ($shop['id'] != 0 && auth('customer')->check())
        @include('theme-views.layouts.partials.modal._chat-with-seller',['shop'=>$shop,'user_type'=>'seller'])
    @elseif ($shop['id'] == 0 && auth('customer')->check())
        @include('theme-views.layouts.partials.modal._chat-with-seller',['shop'=>0,'user_type'=>'admin'])
    @endif

    <section class="seller-profile-section p-1">
        <div class="container">
            <div class="seller-profile-wrapper">
                <div class="seller-profile-info">
                    <div class="seller-profile">
                        @if($shop['id'] != 0)
                            <div class="seller-profile-top text-center text-capitalize">
                                <div class="position-relative img-area ">
                                    <div>
                                        <img loading="lazy" alt="{{ translate('shop') }}" src="{{ getStorageImages(path: $shop->image_full_url, type: 'shop') }}">
                                    </div>
                                    @if($seller_temporary_close || $inhouse_temporary_close)
                                        <div class="shop_close_now_overly">
                                            <span class="temporary-closed position-absolute">
                                                <span>{{translate('Temporary_OFF')}}</span>
                                            </span>
                                        </div>
                                    @elseif(($seller_id==0 && $inHouseVacationStatus && $current_date >=
                                    $inhouse_vacation_start_date && $current_date <= $inhouse_vacation_end_date) ||
                                        $seller_id!=0 && $seller_vacation_status && $current_date>= $seller_vacation_start_date
                                        && $current_date <= $seller_vacation_end_date)
                                        <div class="shop_close_now_overly">
                                            <span class="temporary-closed position-absolute">
                                                <span>{{translate('closed_now')}}</span>
                                        </span>
                                        </div>
                                    @endif
                                </div>
                                <div class="seller-profile-content">
                                    <h5 class="name mt-2">{{ $shop->name}}</h5>
                                    <div class="rating">
                                        @for ($i = 1; $i <= 5; $i++)
                                            @if ($i <=$avg_rating)
                                                <i class="bi bi-star-fill"></i>
                                            @elseif ($avg_rating != 0 && $i <= (int)$avg_rating + 1 && $avg_rating>=
                                                ((int)$avg_rating+.30))
                                                <i class="bi bi-star-half"></i>
                                            @else
                                                <i class="bi bi-star"></i>
                                            @endif
                                        @endfor
                                        <span>({{round($avg_rating,1)}})</span>
                                    </div>
                                    <div class="d-flex justify-content-md-center">
                                        <span>{{ $total_order}} {{translate('orders')}}</span> <span>
                                    <span class="mx-1">|</span> </span>
                                        <span>{{ $total_review}} {{translate('reviews')}}</span>
                                    </div>
                                    @php($minimum_order_amount = getWebConfig(name: 'minimum_order_amount_status'))
                                    @php($minimum_order_amount_by_seller = getWebConfig(name: 'minimum_order_amount_by_seller'))
                                    @if ($minimum_order_amount ==1 && $minimum_order_amount_by_seller ==1)
                                        <div class="d-flex justify-content-md-center">
                                            <span>{{ webCurrencyConverter($shop->seller->minimum_order_amount)}} {{translate('minimum_order_amount')}}</span>
                                        </div>
                                    @endif
                                    <div class="d-flex flex-wrap btn-grp">
                                        @if (auth('customer')->id() == '')
                                            <button type="button" class="btn __btn-outline customer_login_register_modal">
                                                {{ translate('message') }}
                                            </button>
                                        @else
                                            <button type="button" class="btn __btn-outline" data-bs-toggle="modal"
                                                    data-bs-target="#contact_sellerModal">
                                                {{ translate('message') }}
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="seller-profile-top text-center text-capitalize">
                                <div class="img-area position-relative mb-2">
                                    <img loading="lazy" alt="{{ translate('logo') }}" class="m-0"
                                         src="{{ getStorageImages(path: $web_config['fav_icon'], type:'shop') }}">

                                    @if($seller_temporary_close || $inhouse_temporary_close)
                                        <span class="temporary-closed position-absolute">
                                            <span>{{translate('Temporary_OFF')}}</span>
                                        </span>
                                    @elseif(($seller_id==0 && $inHouseVacationStatus && $current_date >=
                                    $inhouse_vacation_start_date && $current_date <= $inhouse_vacation_end_date) || $seller_id!=0 &&
                                    $seller_vacation_status && $current_date>= $seller_vacation_start_date && $current_date <=
                                        $seller_vacation_end_date)
                                        <span class="temporary-closed position-absolute">
                                            <span>{{translate('closed_now')}}</span>
                                        </span>
                                    @endif
                                </div>
                                <div class="seller-profile-content">

                                    <h5 class="name">{{ $web_config['company_name'] }}</h5>
                                    <div class="rating">
                                        @for ($i = 1; $i <= 5; $i++)
                                            @if ($i <=$avg_rating)
                                                <i class="bi bi-star-fill"></i>
                                            @elseif ($avg_rating != 0 && $i <= (int)$avg_rating + 1 && $avg_rating>=
                                                ((int)$avg_rating+.30))
                                                <i class="bi bi-star-half"></i>
                                            @else
                                                <i class="bi bi-star"></i>
                                            @endif
                                        @endfor
                                        <span>({{round($avg_rating,1)}})</span>
                                    </div>
                                    <div class="d-flex justify-content-md-center">
                                        <span>{{ $total_order}} {{translate('orders')}}</span>
                                        <span> <span class="mx-1">|</span> </span>
                                        <span>{{ $total_review}} {{translate('reviews')}}</span>
                                    </div>
                                    <div class="mt-2">
                                        @php($minimum_order_amount_status = getWebConfig(name: 'minimum_order_amount_status'))
                                        @php($minimum_order_amount_by_seller = getWebConfig(name: 'minimum_order_amount_by_seller'))
                                        @if ($minimum_order_amount_status ==1 && $minimum_order_amount_by_seller ==1)

                                            @if($shop['id'] == 0)
                                                @php($minimum_order_amount = getWebConfig(name: 'minimum_order_amount'))
                                                <span
                                                    class="text-sm-nowrap">{{ webCurrencyConverter($minimum_order_amount)}} {{translate('minimum_order_amount')}}</span>
                                            @else
                                                <span
                                                    class="text-sm-nowrap">{{ webCurrencyConverter($shop->seller->minimum_order_amount)}} {{translate('minimum_order_amount')}}</span>
                                            @endif
                                        @endif
                                    </div>

                                    <div class="d-flex flex-wrap btn-grp">
                                        @if (auth('customer')->id() == '')
                                            <button type="button" class="btn __btn-outline customer_login_register_modal">
                                                {{ translate('message') }}
                                            </button>
                                        @else
                                            <button type="button" class="btn __btn-outline" data-bs-toggle="modal"
                                                    data-bs-target="#contact_sellerModal">
                                                {{ translate('message') }}
                                            </button>
                                        @endif
                                    </div>

                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="seller-profile-hero">
                    @if($shop['id'] != 0)
                        <img loading="lazy" alt="{{ translate('banner') }}"
                             src="{{ getStorageImages(path: $shop->banner_full_url, type:'shop-banner') }}">
                    @else
                        @php($banner = getWebConfig(name: 'shop_banner'))
                        <img loading="lazy" alt="{{ translate('banner') }}" src="{{ getStorageImages(path: $banner, type:'shop-banner') }}">
                    @endif
                </div>
            </div>
            <div class="mt-10px mb-10px ms-auto seller-profile-count-area">
                <div class="count-area">
                    <div class="item">
                        <h5>{{ round($rattingStatusArray['positive']) }}%</h5>
                        <div class="text-capitalize">{{translate("positive_review")}}</div>
                    </div>
                    <div class="item">
                        <h5>{{$products_for_review}}</h5>
                        <div>{{translate('products')}}</div>
                    </div>
                </div>
                @if ($shop['id'] != 0 && $shop->offer_banner)
                    <img loading="lazy" alt="{{ translate('banner') }}"
                         src="{{ getStorageImages(path: $shop->offer_banner_full_url, type:'shop-banner') }}">
                @elseif ($shop['id'] == 0)
                    @php($offer_banner = getWebConfig(name: 'offer_banner'))
                    <img loading="lazy"
                         alt="{{ translate('banner') }}" src="{{ getStorageImages(path: $offer_banner, type:'shop-banner') }}">
                @else
                    <img loading="lazy" src="" alt="">
                @endif
            </div>
        </div>
    </section>

    @if ($featuredProductsList->count() > 0)
        <section class="featured-product section-gap pb-0">
            <div class="container">
                <div class="section-title mb-4 pb-lg-1">
                    <div class="d-flex flex-wrap justify-content-between row-gap-2 column-gap-4 align-items-center">
                        <h2 class="title mb-0 me-auto text-base text-capitalize line-limit-1 w-0 flex-grow-1">{{ translate('featured_product_from_this_store') }}
                            <sup
                                class="font-regular text-small text-text-2 d-none d-sm-inline-block">({{$featuredProductsList->count()}} {{translate('product')}}
                                )</sup>
                        </h2>
                        <div>
                            <a href="{{route('products', ['data_from' => 'featured','shop_id'=> $shop['id'],'page'=>1])}}"
                               class="see-all">{{ translate('see_all') }}</a>
                        </div>
                    </div>
                </div>
                <div class="overflow-hidden">
                    <div class="--bg-4 p-20px">
                        <div class="similler-product-slider-area">
                            <div class="similler-product-slider-2 owl-theme owl-carousel">
                                @foreach ($featuredProductsList as $product)
                                    @include('theme-views.partials._product-small-card', ['product'=>$product])
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif


    <section class="seller-profile-details-section pt-32px pb-5 scroll_to_form_top">
        <div class="container">
            <ul class="nav nav-tabs nav--tabs-2 justify-content-center" role="tablist">
                <li class="nav-item">
                    <a href="{{ route('shopView',['id' => $seller_id]) }}" class="nav-link {{ request('offer_type') != 'clearance_sale' && request('type') != 'review' ? 'active' : '' }}">{{ translate('all_product') }}</a>
                </li>
                @if($stockClearanceSetup && $stockClearanceProducts > 0)
                    <li class="nav-item">
                        <a href="{{ route('shopView',['id' => $seller_id, 'offer_type' => 'clearance_sale']) }}" class="nav-link {{ request('offer_type') == 'clearance_sale' ? 'active' : '' }}">{{ translate('clearance_sale') }}</a>
                    </li>
                @endif
                <li class="nav-item" role="presentation">
                    <a href="{{ route('shopView',['id' => $seller_id, 'type' => 'review']) }}" class="nav-link {{ request('type') == 'review' ? 'active' : '' }}">{{translate('review')}}
                        <sup>{{ $total_review}}</sup></a>
                </li>
            </ul>
            <div class="tab-content pt-3" id="products">

                <div class="tab-pane fade {{ request('type') == 'review' ? '' : 'show active' }}">
                    <form
                        action="{{ route('ajax-filter-products', request('offer_type') == 'clearance_sale' ? ['offer_type' => 'clearance_sale'] : []) }}" method="POST" id="fashion_products_list_form">
                        @csrf
                        <input type="hidden" name="shop_id" value="{{ $shop['id'] }}">
                        <div
                            class="ms-auto ms-md-0 d-flex flex-wrap justify-content-between align-items-center column-gap-3 row-gap-2 mb-4 text-capitalize">
                            <div></div>
                            <div class="position-relative select2-prev-icon filter_select_input_div d-none d-md-block">
                                <i class="bi bi-sort-up"></i>
                                <select
                                    class="select2-init form-control size-40px filter_select_input filter_by_product_list_web"
                                    name="sort_by"
                                    data-primary_select="{{translate('sort_by')}} : {{translate('default')}}">
                                    <option value="latest" {{ request('sort_by') == 'latest' ? 'selected':'' }}>
                                        {{ translate('sort_by') }} : {{ translate('Default') }}
                                    </option>
                                    <option value="low-high" {{ request('sort_by') == 'low-high' ? 'selected':'' }}>
                                        {{ translate('sort_by') }} : {{ translate('Price') }} ({{ translate('Low_to_High') }})
                                    </option>
                                    <option value="high-low" {{ request('sort_by') == 'high-low' ? 'selected':'' }}>
                                        {{ translate('sort_by') }} : {{ translate('Price') }} ({{ translate('High_to_Low') }})
                                    </option>
                                    <option value="rating-low-high" {{ request('sort_by') == 'rating-low-high' ? 'selected':'' }}>
                                        {{ translate('sort_by') }} : {{ translate('Ratting') }} ({{ translate('Low_to_High') }})
                                    </option>
                                    <option value="rating-high-low" {{ request('sort_by') == 'rating-high-low' ? 'selected':'' }}>
                                        {{ translate('sort_by') }} : {{ translate('Ratting') }} ({{ translate('High_to_Low') }})
                                    </option>
                                    <option value="a-z" {{ request('sort_by') == 'a-z' ? 'selected':'' }}>
                                        {{ translate('sort_by') }} : {{ translate('Alphabetical') }} ({{ 'A '.translate('to').' Z' }})
                                    </option>
                                    <option value="z-a" {{ request('sort_by') == 'z-a' ? 'selected':'' }}>
                                        {{ translate('sort_by') }} : {{ translate('Alphabetical') }} ({{ 'Z '.translate('to').' A' }})
                                    </option>
                                </select>
                            </div>
                            <div class="d-lg-none">
                                <button type="button" class="btn btn-soft-base border filter-toggle d-lg-none">
                                    <i class="bi bi-funnel"></i>
                                </button>
                            </div>
                        </div>
                        <input type="hidden" name="per_page_product" value="{{ $singlePageProductCount }}">

                        <main class="main-wrapper">

                            <aside class="sidebar">
                                @include('theme-views.product.partials._products-list-aside', [
                                    'categories' => $categories,
                                    'activeBrands' => $brands,
                                    'colors' => $allProductsColorList,
                                    'publishingHouses' => $shopPublishingHouses,
                                    'digitalProductAuthors' => $digitalProductAuthors,
                                ])
                            </aside>

                            <article class="article">
                                <div id="selected_filter_area">
                                    @include('theme-views.product._selected_filter_tags',['tags_category'=>null,'tags_brands'=>null,'rating'=>null])
                                </div>
                                <div id="ajax_products_section">
                                    @include('theme-views.product._ajax-products', [
                                        'products' => $products,
                                        'page' => 1,
                                        'paginate_count' => $paginate_count
                                    ])
                                </div>
                            </article>
                        </main>
                    </form>
                </div>

                <div class="tab-pane fade {{ request('type') == 'review' ? 'show active' : '' }}" id="comments">
                    <div class="product-information p-0 shadow-0 border-0">
                        <div class="product-information-inner single-page-height-800px">
                            <div class="details-review row-gap-4">
                                <div class="details-review-item">
                                    <h2 class="title">{{round($avg_rating, 1)}}</h2>
                                    <div class="text-star">
                                        @for ($i = 1; $i <= 5; $i++)
                                            @if ($i <=$avg_rating)
                                                <i class="bi bi-star-fill"></i>
                                            @elseif ($avg_rating != 0 && $i <= (int)$avg_rating + 1 && $avg_rating>=
                                                ((int)$avg_rating+.30))
                                                <i class="bi bi-star-half"></i>
                                            @else
                                                <i class="bi bi-star"></i>
                                            @endif
                                        @endfor
                                    </div>
                                    <span>{{ $total_review}} {{translate('reviews')}}</span>
                                </div>
                                <div class="details-review-item">
                                    <h2 class="title font-regular">{{ round($rattingStatusArray['positive']) }}%</h2>
                                    <span class="text-capitalize">{{ translate('positive_review') }}</span>
                                </div>
                                <div class="details-review-item details-review-info">
                                    <div class="item">
                                        <div class="form-label mb-3 d-flex justify-content-between">
                                            <span>{{ translate('positive') }}</span>
                                            <span>{{ round($rattingStatusArray['positive']) }}%</span>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-fill"
                                                 style="--fill:{{ round($rattingStatusArray['positive']) }}%"></div>
                                        </div>
                                    </div>
                                    <div class="item">
                                        <div class="form-label mb-3 d-flex justify-content-between">
                                            <span>{{ translate('good') }}</span>
                                            <span>{{ round($rattingStatusArray['good']) }}%</span>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-fill"
                                                 style="--fill:{{ round($rattingStatusArray['good']) }}%">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="item">
                                        <div class="form-label mb-3 d-flex justify-content-between">
                                            <span>{{ translate('neutral') }}</span>
                                            <span>{{ round($rattingStatusArray['neutral']) }}%</span>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-fill"
                                                 style="--fill:{{ round($rattingStatusArray['neutral']) }}%"></div>
                                        </div>
                                    </div>
                                    <div class="item">
                                        <div class="form-label mb-3 d-flex justify-content-between">
                                            <span>{{ translate('negative') }}</span>
                                            <span>{{ round($rattingStatusArray['negative']) }}%</span>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-fill"
                                                 style="--fill:{{ round($rattingStatusArray['negative']) }}%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="comments-information mt-32px">
                                <ul id="shop-review-list">
                                    @include('theme-views.layouts.partials._product-reviews',['productReviews'=>$reviews])
                                    @if($total_review == 0)
                                        <div class="d-flex justify-content-center align-items-center w-100">
                                            <div class="d-flex flex-column justify-content-center align-items-center gap-2 py-5 w-100">
                                                <img width="80" class="mb-3" src="{{ theme_asset('assets/img/empty-state/empty-review.svg') }}" alt="">
                                                <h5 class="text-center text-muted">
                                                    {{ translate('No_review_yet') }}!
                                                </h5>
                                            </div>
                                        </div>
                                    @endif
                                </ul>
                            </div>
                            @if($total_review > 4)
                                <a href="javascript:" id="load_review_for_shop"
                                   class="product-information-view-more-custom see-more-details-review view_text"
                                   data-shopid="{{$shop['id']}}"
                                   data-routename="{{route('review-list-shop')}}"
                                   data-afterextend="{{translate('view_less')}}"
                                   data-seemore="{{translate('view_more')}}"
                                   data-onerror="{{translate('no_more_review_remain_to_load')}}">{{translate('view_more')}}</a>
                            @endif
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <span id="shop_follow_url" data-url="{{route('shop-follow')}}"></span>

@endsection
