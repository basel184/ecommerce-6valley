@extends('theme-views.layouts.app')

@section('title',translate('flash_deal_products').' | '.$web_config['company_name'].' '.translate('ecommerce'))

@section('content')
    <main class="main-content d-flex flex-column gap-3 py-3">
        @php($deal_banner = getStorageImages(path: $deal['banner_full_url'],type: 'banner',source: theme_asset('assets/img/flash-deals.png')))
        <div class="container">
            <div class="flash-deals-wrapper mw-100 style-2 __bg-img" data-img="{{$deal_banner}}">
                <ul class="countdown"
                    data-countdown="{{ $web_config['flash_deals'] ? $web_config['flash_deals']['end_date'] : '' }}">
                    <li>
                        <h6 class="days">00</h6>
                        <span class="text days_text">{{translate('days')}}</span>
                    </li>
                    <li>
                        <h6 class="hours">00</h6>
                        <span class="text hours_text">{{translate('hours')}}</span>
                    </li>
                    <li>
                        <h6 class="minutes">00</h6>
                        <span class="text minutes_text">{{translate('minutes')}}</span>
                    </li>
                    <li>
                        <h6 class="seconds">00</h6>
                        <span class="text seconds_text">{{translate('seconds')}}</span>
                    </li>
                </ul>
            </div>
        </div>

        <section class="all-products-section pt-20px scroll_to_form_top">
            <form action="{{ url()->current() }}" method="POST" id="fashion_products_list_form">
                @csrf

                <input hidden name="offer_type" value="{{ request()->is('flash-deals*') ? 'flash-deals' : request('offer_type') }}">
                <input hidden name="flash_deals_id" value="{{ $web_config['flash_deals']['id'] }}">

                <div class="container">
                    <div class="section-title mb-4">
                        <div
                            class="d-flex flex-column flex-sm-row justify-content-between row-gap-3 column-gap-2 align-items-sm-center search-page-title">
                            <ul class="breadcrumb">
                                <li>
                                    <a href="{{ route('home') }}">{{ translate('home') }}</a>
                                </li>
                                <li>
                                    <a href="javascript:" class="text-capitalize text-base product_view_title"
                                       data-allproduct="{{ translate('flash_deals') }}">
                                        {{ translate('flash_deals') }}
                                    </a>
                                </li>
                            </ul>
                            <div
                                class="d-flex flex-wrap-reverse justify-content-between justify-content-sm-end align-items-center column-gap-3 row-gap-2 text-capitalize min-w-lg-190">
                                <div class="flex-grow-1">
                                    <div class="position-relative select2-prev-icon d-none d-lg-block">
                                        <i class="bi bi-sort-up"></i>
                                        <select
                                            class="select2-init form-control size-40px filter_select_input filter_by_product_list_web ps-32px"
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
                                        <button type="button" class="btn btn-base filter-toggle d-lg-none">
                                            <i class="bi bi-funnel"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <main class="main-wrapper">

                        <aside class="sidebar">
                            @include('theme-views.product.partials._products-list-aside',[
                                'categories' => $productCategories,
                                'activeBrands' => $activeBrands,
                                'colors' => $allProductsColorList,
                                'publishingHouses' => $web_config['publishing_houses'],
                                'digitalProductAuthors' => $web_config['digital_product_authors']
                            ])
                        </aside>

                        <article class="article">
                            <div id="selected_filter_area">
                                @include('theme-views.product._selected_filter_tags',[
                                    'tags_category' => $tag_category,
                                    'publishingHouse' => $tagPublishingHouse,
                                    'productAuthors' => $tagProductAuthors,
                                    'tags_brands' => $tag_brand,
                                    'rating' => null
                                ])
                            </div>
                            <input type="hidden" name="per_page_product" value="{{ $singlePageProductCount }}">
                            <div id="ajax_products_section">
                                @include('theme-views.product._ajax-products', [
                                    'products' => $products,'page'=> 1,
                                    'paginate_count' => $paginate_count,
                                    'singlePageProductCount' => $singlePageProductCount,
                                ])
                            </div>
                        </article>
                    </main>
                </div>
            </form>
        </section>
    </main>
@endsection
