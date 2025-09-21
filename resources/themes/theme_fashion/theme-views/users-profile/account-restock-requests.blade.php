@extends('theme-views.layouts.app')

@section('title', translate('Restock_Requests').' | '.$web_config['company_name'].' '.translate('ecommerce'))

@section('content')
    <section class="user-profile-section section-gap pt-0">
        <div class="container">
            @include('theme-views.partials._profile-aside')
            <div class="cart-title-area">
                @if($restockProducts->total() > 0)
                    <h6 class="title text-capitalize">{{translate('all_Restock_Request_product_list')}} (<span class="wishlist_count_status">{{$restockProducts->total()}}</span>)</h6>
                    <a href="javascript:" class="text-danger call-route-alert"
                        data-route="{{ route('user-restock-request-delete') }}"
                        data-message="{{translate('want_to_remove_all_restock_request_data')}}?">
                        {{translate('remove_all')}}
                    </a>
                @endif
            </div>
            @if($restockProducts->total() > 0)
                <div class="table-responsive d-none d-md-block">
                    <table class="table __table vertical-middle">
                        <thead class="word-nobreak">
                            <tr>
                                <th>
                                    <label class="form-check m-0">
                                        <span class="form-check-label">{{translate('product_name')}}</span>
                                    </label>
                                </th>
                                <th>
                                    {{translate('variation')}}
                                </th>
                                <th>
                                    {{translate('discount')}}
                                </th>
                                <th>
                                    {{translate('review')}}
                                </th>
                                <th>
                                    {{translate('unit_price')}}
                                </th>
                                <th class="text-center">
                                    {{translate('action')}}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($restockProducts as $key => $restockProduct)
                            <tr>
                                <td>
                                    <div class="cart-product align-items-center">
                                        <label class="form-check">
                                            <img loading="lazy" alt="Product"
                                                src="{{ getStorageImages(path: $restockProduct?->product->thumbnail_full_url, type: 'backend-product') }}">
                                        </label>
                                        <div class="cont">
                                            <a href="{{ $restockProduct?->product?->slug ? route('product', $restockProduct?->product?->slug) : 'javascript' }}" class="name text-title align-items-center webkit-line-clamp max-w-280px">{{ $restockProduct?->product?->name}} </a>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($restockProduct['variant'])
                                        <div>
                                            <span class="text-muted">{{$restockProduct['variant']  }}</span>
                                        </div>
                                    @else
                                        <span class="badge badge-soft-secondary font-regular text-muted" >{{translate('no_variation')}}</span>
                                    @endif
                                </td>

                                <td class="text-capitalize">
                                    @if(getProductPriceByType(product: $restockProduct?->product, type: 'discount', result: 'value') > 0)
                                        <span class="badge badge-soft-base">
                                            -{{ getProductPriceByType(product: $restockProduct?->product, type: 'discount', result: 'string') }}
                                        </span>
                                    @else
                                        <span class="badge badge-soft-secondary font-regular text-muted" >{{translate('no_discount')}}</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $overallRating = $restockProduct?->product?->reviews ? getOverallRating($restockProduct?->product?->reviews) : 0;
                                    @endphp
                                    <div class="d-inline-flex align-items-center text-gold mb-2">
                                        @if(isset($overallRating[0]) && $overallRating[0] != 0)
                                            <i class="bi bi-star-fill"></i>
                                            <div class="text-dark ms-1">
                                                <span class="font-bold">{{ $overallRating[0] }}</span>
                                                <span class="text-muted">({{count($restockProduct?->product?->reviews)}}&nbsp;{{ translate('review') }})</span>
                                            </div>
                                        @else
                                            <span class="badge badge-soft-secondary font-regular text-muted" >{{translate('No_Review_Yet')}}</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $productPrices = $restockProduct?->product?->unit_price;
                                        $restockProductsList = json_decode($restockProduct?->product?->variation, true);
                                        if(!empty($restockProductsList) && count($restockProductsList) > 0) {
                                            foreach ($restockProductsList as $item) {
                                                if ($item['type'] === $restockProduct->variant) {
                                                    $productPrices = $item['price'];
                                                }
                                            }
                                        }
                                    @endphp
                                    <div class="text-base">
                                        @if(getProductPriceByType(product: $restockProduct?->product, type: 'discount', result: 'value') > 0)
                                            <span class="font-bold">{{ getProductPriceByType(product: $restockProduct?->product, type: 'discounted_unit_price', result: 'string', price: $productPrices) }}</span>
                                            <span class="text-muted fs-14 ms-2"><del>{{ webCurrencyConverter(amount: $productPrices) }}</del></span>
                                        @else
                                            <span class="font-bold">{{ webCurrencyConverter(amount: $productPrices) }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-center">
                                        <a href="{{ route('user-restock-request-delete', ['id' => $restockProduct['id']]) }}" class="btn btn-outline-danger btn-action">
                                            <i class="bi bi-trash-fill"></i>
                                        </a>
                                    </div>
                                </td>

                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center pt-5 w-100">
                    <div class="text-center mb-5">
                        <img loading="lazy" src="{{ theme_asset('assets/img/icons/wishlist.svg') }}" alt="{{ translate('wishlist') }}">
                        <h5 class="my-3 text-muted">{{translate('no_Saved_Products_Found')}}!</h5>
                        <p class="text-center text-muted">{{ translate('you_have_not_add_any_products_in_Wishlist ') }}</p>
                    </div>
                </div>
            @endif
            @foreach($restockProducts as $key => $restockProduct)
            <div class="d-flex d-md-none gap-3 flex-column">
                <div class="border-bottom d-flex align-items-center justify-content-between py-2 gap-2">
                    <div class="cart-product align-items-center align-items-md-start">
                        <label class="form-check">
                            <img loading="lazy" alt="Shop" src="{{ getStorageImages(path: $restockProduct?->product->thumbnail_full_url, type: 'backend-product') }}">
                        </label>
                        <div class="cont">
                            <div class="name text-title line-limit-2">
                                <a href="{{ $restockProduct?->product?->slug ? route('product', $restockProduct?->product?->slug) : 'javascript' }}" class="name text-title">
                                    {{ $restockProduct?->product?->name }}
                                </a>
                            </div>

                            @php($overallRating = $restockProduct?->product?->reviews ? getOverallRating($restockProduct?->product?->reviews) : 0)
                            @if(isset($overallRating[0]) && $overallRating[0] != 0)
                                <div class="d-inline-flex align-items-center text-gold mb-2">
                                    <i class="bi bi-star-fill"></i>
                                    <div class="text-dark ms-1">
                                        <span class="font-bold">{{ $overallRating[0] }}</span>
                                        <span class="text-muted">({{count($restockProduct?->product?->reviews)}}&nbsp;{{ translate('review') }})</span>
                                    </div>
                                </div>
                            @else
                                <div class="d-none d-md-inline-flex align-items-center text-gold mb-2">
                                    <span class="badge badge-soft-secondary font-regular text-muted">{{translate('No_Review_Yet')}}</span>
                                </div>
                            @endif

                            <div class="text-basa mb-1">
                                <span>{{translate('total_price')}}
                                    <strong>{{webCurrencyConverter($restockProduct?->product?->unit_price)}}</strong>
                                </span>
                            </div>

                            @if($restockProduct['variant'])
                                <div class="text-basa mb-1">
                                    <span>{{translate('Variation :')}}
                                        <span class="text-muted">{{$restockProduct['variant']  }}</span>
                                    </span>
                                </div>
                            @else
                                <div class="text-basa mb-1 d-none d-md-block">
                                    <span>{{translate('Variation :')}}
                                        <span class="badge badge-soft-secondary font-regular text-muted" >{{translate('no_variation')}}</span>
                                    </span>
                                </div>
                            @endif

                            <div class="text-basa mb-1">
                                @if($restockProduct?->product?->discount > 0)
                                    <span>
                                        {{ translate('discount') }}
                                        <span class="badge badge-soft-base">
                                                        @if ($restockProduct?->product?->discount_type == 'percent')
                                                -{{round($restockProduct?->product?->discount,(!empty(getWebConfig(name: 'decimal_point_settings')) ? getWebConfig(name: 'decimal_point_settings'): 0))}}%
                                            @elseif($restockProduct?->product?->discount_type =='flat')
                                                -{{ webCurrencyConverter(amount: $restockProduct?->product?->discount) }}
                                            @endif
                                        </span>
                                    </span>
                                @else
                                    <span class="badge badge-soft-secondary font-regular text-muted d-none d-md-block">
                                        {{translate('no_discount')}}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-column gap-3 align-items-center">
                        <a href="{{ route('user-restock-request-delete', ['id' => $restockProduct['id']]) }}" class="btn btn-outline-danger">
                            <i class="bi bi-trash-fill"></i>
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
            @if (count($restockProducts) > 0)
                <div class="my-4" id="paginator-ajax">
                    {!! $restockProducts->links() !!}
                </div>
            @endif
        </div>
    </section>
@endsection
