@extends('theme-views.layouts.app')

@section('title',translate('products').' | '.$web_config['company_name'].' '.translate('ecommerce'))

@push('css_or_js')
    <meta property="og:image" content="{{$web_config['web_logo']['path']}}"/>
    <meta property="og:title" content="Products of {{$web_config['company_name']}} "/>
    <meta property="og:url" content="{{config('app.url')}}">
    <meta property="og:description" content="{!! $web_config['meta_description'] !!}">
    <meta property="twitter:card" content="{{$web_config['web_logo']['path']}}"/>
    <meta property="twitter:title" content="Products of {{$web_config['company_name']}}"/>
    <meta property="twitter:url" content="{{config('app.url')}}">
    <meta property="twitter:description" content="{!! $web_config['meta_description'] !!}">
@endpush

@section('content')

    <div class="container pt-30px mobile-search-form">
        @include('theme-views.layouts.partials._search-form-partials')
    </div>

    <section class="all-products-section pt-20px scroll_to_form_top">
        <form action="{{ route('ajax-filter-products', ['offer_type' => $data['offer_type']]) }}" method="POST" id="fashion_products_list_form">
            @csrf
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
                                   data-allproduct="{{translate('all_products')}}">
                                    {{translate(str_replace(['-', '_', '/'],' ',request('data_from')))}} {{translate('products')}} {{ request('brand_name') ? ' / '.str_replace('_',' ',request('brand_name')) : ''}} {{ request('name') ? '('.request('name').')' : ''}}
                                </a>
                            </li>
                        </ul>
                        <div
                            class="d-flex flex-wrap-reverse justify-content-between justify-content-sm-end align-items-center column-gap-3 row-gap-2 text-capitalize min-w-lg-190">
                            <div class="flex-grow-1">
                                <div class="position-relative select2-prev-icon d-none d-lg-block">
                                    <i class="bi bi-sort-up"></i>
                                    <select name="sort_by"
                                        class="select2-init form-control size-40px filter_select_input filter_by_product_list_web ps-32px"
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
                            'categories' => $categories,
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
                                'rating'=>null
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

    @include('theme-views.partials._how-to-section')

@endsection
<style>
    @media screen and (max-width: 767px) {
        .mobile-search-form {
            display: none !important;
        }
        
    }
</style>