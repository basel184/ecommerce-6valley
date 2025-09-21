@extends('theme-views.layouts.app')

@section('title', (request('filter') && request('filter') == 'top-vendors' ? translate('top_Stores') : translate('all_Stores')).' | '.$web_config['company_name'].' '.translate('ecommerce'))

@section('content')

    <section class="breadcrumb-section pt-20px">
        <div class="container">
            <div class="section-title mb-4">
                <div
                    class="d-flex flex-wrap justify-content-between row-gap-3 column-gap-2 align-items-center search-page-title">
                    <ul class="breadcrumb">
                        <li>
                            <a href="{{ route('home') }}">{{ translate('home') }}</a>
                        </li>
                        <li>
                            <a href="javascript:" class="text-capitalize text-base">
                                {{ (request('filter') && request('filter') == 'top-vendors' ? translate('top_Stores') : translate('all_Stores')) }}
                            </a>
                        </li>
                    </ul>
                    <div class="ms-auto ms-md-0">
                        <div class="position-relative select2-prev-icon filter_select_input_div select2-max-width-100">
                            <i class="bi bi-sort-up"></i>
                            <select class="select2-init form-control size-40px text-capitalize goToPageBasedSelectValue"
                                    name="order_by" id="filter_select_input">
                                <option value="{{ route('vendors') }}"
                                    {{ !(request()->has('order_by')) ? 'selected' : '' }}>
                                    {{translate('sort_by')}} : {{ translate('Default') }}
                                </option>
                                <option
                                    value="{{ route('vendors', ['filter'=>request('filter'), 'order_by'=>'asc']) }}" {{ isset($order_by) ? ($order_by =='asc'?'selected':'') : ''}}>
                                    {{translate('sort_by')}} : {{ translate('a_to_z_order') }}
                                </option>
                                <option
                                    value="{{ route('vendors', ['filter'=>request('filter'), 'order_by'=>'desc']) }}" {{ isset($order_by) ? ($order_by=='desc'?'selected':''):''}}>
                                    {{translate('Sort_by')}} : {{ translate('z_to_a_order') }}
                                </option>
                                <option
                                    value="{{ route('vendors', ['filter'=>request('filter'), 'order_by'=>'rating-high-to-low']) }}" {{ isset($order_by) ? ($order_by=='rating-high-to-low'?'selected':''):''}}>
                                    {{translate('Sort_by')}} : {{ translate('rating_High_to_Low') }}
                                </option>
                                <option
                                    value="{{ route('vendors', ['filter'=>request('filter'), 'order_by'=>'rating-low-to-high']) }}" {{ isset($order_by) ? ($order_by=='rating-low-to-high'?'selected':''):''}}>
                                    {{translate('Sort_by')}} : {{ translate('rating_Low_to_High') }}
                                </option>
                                <option
                                    value="{{ route('vendors', ['filter'=>request('filter'), 'order_by'=>'highest-products']) }}" {{ isset($order_by) ? ($order_by=='highest-products'?'selected':''):''}}>
                                    {{translate('Sort_by')}} : {{ translate('highest_Products') }}
                                </option>
                                <option
                                    value="{{ route('vendors', ['filter'=>request('filter'), 'order_by'=>'lowest-products']) }}" {{ isset($order_by) ? ($order_by=='lowest-products'?'selected':''):''}}>
                                    {{translate('Sort_by')}} : {{ translate('lowest_Products') }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="search-form-section py-24px">
        <div class="container">
            <form action="{{ route('vendors') }}" method="GET">
                @if(request('filter'))
                    <input type="hidden" name="filter" value="{{ request('filter') }}">
                @endif
                <div class="search-form-2 search-form-mobile">
                <span class="icon d-flex">
                    <i class="bi bi-search"></i>
                </span>
                    <input type="text" name="shop_name" value="{{ request('shop_name') }}"
                           class="form-control text-title" placeholder="{{ translate('search_store') }}"
                           autocomplete="off" required>
                    <button type="submit" class="clear border-0 text-title">
                        @if (request('shop_name') != null)
                            <a href="{{route('vendors')}}" class="text-danger">{{translate('clear')}}</a>
                        @else
                            <span>{{translate('search')}}</span>
                        @endif
                    </button>
                </div>
            </form>
        </div>
    </section>

    <section class="others-section mt-4 pb-0">

        <div class="container">
            <div class="row g-3 g-md-3 g-xl-4">
                @foreach ($vendorsList as $vendor)
                    @php($current_date = date('Y-m-d'))
                    @php($start_date = date('Y-m-d', strtotime($vendor['vacation_start_date'])))
                    @php($end_date = date('Y-m-d', strtotime($vendor['vacation_end_date'])))

                    <div class="col-lg-3 col-md-4 col-sm-6">
                        <div class="others-store-card text-capitalize">
                            <div class="name-area">
                                <div class="position-relative rounded-circle overflow-hidden">
                                    <div>
                                        <img loading="lazy" src="{{ getStorageImages(path: $vendor->image_full_url, type: 'shop') }}"
                                             data-linkpath="{{route('shopView', ['id' => $vendor['id']])}}"
                                             alt="{{ $vendor->name }}"
                                             class="cursor-pointer rounded-full other-store-logo thisIsALinkElement">
                                    </div>
                                    @if($vendor->temporary_close)
                                        <span class="temporary-closed position-absolute text-center h6 rounded-full">
                                            <span>{{translate('Temporary_OFF')}}</span>
                                        </span>
                                    @elseif(($vendor->vacation_status && ($current_date >= $vendor->vacation_start_date) && ($current_date <= $vendor->vacation_end_date)))
                                        <span class="temporary-closed position-absolute text-center h6 rounded-full">
                                            <span>{{translate('closed_now')}}</span>
                                        </span>
                                    @endif
                                </div>
                                <div class="info">
                                    <h6 class="name cursor-pointer thisIsALinkElement"
                                        data-linkpath="{{route('shopView',['id'=>$vendor['seller_id']])}}">
                                        {{Str::limit($vendor->name, 14)}}</h6>
                                    <span
                                        class="offer-badge">{{ round($vendor['positive_review']) }}% {{ translate('positive_review') }}</span>
                                </div>
                            </div>
                            <div class="info-area">
                                <div class="info-item">
                                    <h6>{{ $vendor['review_count']}}</h6>
                                    <span>{{ translate('reviews') }}</span>
                                </div>
                                <div class="info-item">
                                    <h6>{{ $vendor['products_count'] < 1000 ? $vendor['products_count'] : number_format($vendor['products_count']/1000 , 1).'K'}}</h6>
                                    <span>{{ translate('products') }}</span>
                                </div>
                                <div class="info-item">
                                    <h6>
                                        {{ round($vendor->average_rating,1) }}
                                    </h6>
                                    <i class="bi bi-star-fill"></i>
                                    <span>{{ translate('rating') }}</span>
                                </div>
                            </div>
                            <a href="{{route('shopView',['id'=>$vendor['seller_id']])}}" class="btn __btn-outline">
                                {{ translate('visit_shop') }}
                            </a>
                        </div>
                    </div>
                @endforeach

                @if($vendorsList->count()==0)
                    <div class="col-12 py-3">
                        <div class="text-center w-100">
                            <div class="text-center mb-5">
                                <img loading="lazy" src="{{ theme_asset('assets/img/icons/seller.svg') }}" alt="{{ translate('vendor') }}">
                                <h5 class="my-3 pt-2 text-muted">{{translate('vendor_not_available')}}!</h5>
                                <p class="text-center text-muted">{{ translate('sorry_no_data_found_related_to_your_search') }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="d-flex justify-content-end w-100 overflow-auto" id="paginator-ajax">
                    {{ $vendorsList->links() }}
                </div>
            </div>
        </div>

    </section>

    @include('theme-views.partials._how-to-section')

@endsection
