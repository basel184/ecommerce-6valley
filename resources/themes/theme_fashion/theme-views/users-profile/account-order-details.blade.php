@extends('theme-views.layouts.app')

@section('title', translate('my_order_details').' | '.$web_config['company_name'].' '.translate('ecommerce'))

@section('content')
    <section class="user-profile-section section-gap pt-0">
        <div class="container">
            @include('theme-views.partials._profile-aside')
            <div class="card bg-section border-0 bg-body">
                <div class="card-body p-lg-4">
                    @include('theme-views.partials._order-details-head')
                    <div class="mt-4 card border-0">
                        <div class="card-body">
                            <div class="table-responsive">
                                @php($digital_product = false)
                                @foreach ($order->details as $key=>$detail)
                                    @if(isset($detail->product->digital_product_type))
                                        @php($digital_product = $detail->product->product_type == 'digital' ? true : false)
                                        @if($digital_product == true)
                                            @break
                                        @else
                                            @continue
                                        @endif
                                    @endif
                                @endforeach
                                <table class="table align-middle __table ">
                                    <thead class="table-light">
                                    <tr>
                                        <th class="border-0 text-capitalize">{{translate('product_details')}}</th>
                                        <th class="border-0 text-center">{{translate('qty')}}</th>
                                        <th class="border-0 text-end text-capitalize">{{translate('price')}}</th>
                                        <th class="border-0 text-end">{{translate('discount')}}</th>
                                        <th class="border-0 text-end" {{ ($order->order_type == 'default_type' && $order->order_status=='delivered') ? 'colspan="2"':'' }}>{{translate('total')}}</th>
                                        @if($order->order_type == "POS" || $order->order_type == 'default_type' && ($order->order_status=='delivered' || ($order->payment_status == 'paid' && $digital_product)))
                                            <th class="border-0 text-center">{{translate('Action')}}</th>
                                        @elseif($order->order_type != 'default_type' && $order->order_status=='delivered')
                                            <th class="border-0 text-center"></th>
                                        @endif
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($order->details as $key=>$detail)
                                        @php($product=json_decode($detail->product_details,true))
                                        @if($product)
                                            <tr>
                                                <td>
                                                    <div class="cart-product align-items-center">
                                                        <label class="form-check">
                                                            <img loading="lazy" alt="{{ translate('product') }}"
                                                                 src="{{ getStorageImages(path: $detail?->productAllStatus?->thumbnail_full_url, type: 'product') }}">
                                                        </label>
                                                        <div class="cont">
                                                            <a href="{{route('product',[$product['slug']])}}"
                                                               class="name text-title">{{isset($product['name']) ? Str::limit($product['name'],40) : ''}}</a>
                                                            <div class="d-flex column-gap-1">
                                                                <span>{{ translate('unit_price')}}</span> <span>:</span>
                                                                <strong class="price-red">{!! webCurrencyConverterWithImage($detail->price) !!} </strong>
                                                            </div>

                                                            @if($detail->variant)
                                                                <span>{{translate('variant')}}: </span>
                                                                <strong>{{$detail->variant}} </strong>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-center">{{$detail->qty}}</td>
                                                <td class="text-end price-red">
                                                    {!! webCurrencyConverterWithImage($detail->price * $detail->qty) !!}
                                                </td>
                                                <td class="text-end price-red">
                                                    {!! webCurrencyConverterWithImage($detail->discount) !!}</td>
                                                <td class="text-end price-red">{!! webCurrencyConverterWithImage(($detail->qty*$detail->price)-$detail->discount) !!}</td>
                                                @php($order_details_date = $detail->created_at)
                                                @php($length = $order_details_date->diffInDays($current_date))

                                                <td>
                                                    <div class="d-flex justify-content-center gap-2">
                                                        @if($detail?->product && $order->payment_status == 'paid' && $detail?->product?->digital_product_type == 'ready_product')
                                                            <a href="javascript:"
                                                               data-link="{{ route('digital-product-download', $detail->id) }}"
                                                               class="btn btn-base rounded-pill mb-1 digital_product_download_link"
                                                               data-toggle="tooltip" data-placement="bottom"
                                                               title="{{translate('download')}}">
                                                                <i class="bi bi-download"></i>
                                                            </a>
                                                        @elseif($detail?->product && $order->payment_status == 'paid' && $detail?->product?->digital_product_type == 'ready_after_sell')
                                                            @if($detail->digital_file_after_sell)
                                                                <a href="javascript:"
                                                                   data-link="{{ route('digital-product-download', $detail->id) }}"
                                                                   class="btn btn-base rounded-pill mb-1 digital_product_download_link"
                                                                   data-toggle="tooltip" data-placement="bottom"
                                                                   title="{{translate('download')}}">
                                                                    <i class="bi bi-download"></i>
                                                                </a>
                                                            @else
                                                                <span class="btn btn-base rounded-pill mb-1"
                                                                      data-bs-toggle="tooltip"
                                                                      data-bs-placement="bottom"
                                                                      title="Product not uploaded yet">
                                                                    <i class="bi bi-download"></i>
                                                                </span>
                                                            @endif
                                                        @endif

                                                        @if($order->order_type == 'default_type')
                                                            @if($order->order_status=='delivered')
                                                                <button class="btn btn-base rounded-pill text-nowrap"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#reviewModal{{$detail->id}}">
                                                                    @if (isset($detail->reviewData))
                                                                        {{ translate('Update_Review') }}
                                                                    @else
                                                                        {{ translate('review') }}
                                                                    @endif
                                                                </button>
                                                                @include('theme-views.layouts.partials.modal._review',['id'=>$detail->id,'order_details'=>$detail,])
                                                                @if($detail->refund_request !=0)
                                                                    <a class="btn __btn-outline btn-outline-base rounded-pill text-nowrap"
                                                                       href="{{route('refund-details',[$detail->id])}}">{{translate('refund_details')}}</a>
                                                                @endif
                                                                @if($refund_day_limit > 0 && $length <= $refund_day_limit && $detail->refund_request == 0)
                                                                    <button
                                                                        class="btn __btn-outline btn-outline-base rounded-pill"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#refundModal{{$detail->id}}">{{translate('refund')}}</button>
                                                                    @include('theme-views.layouts.partials.modal._refund',['id'=>$detail->id,'order_details'=>$detail,'order'=>$order,'product'=>$product])
                                                                @endif
                                                            @endif
                                                        @else
                                                            <label
                                                                class="btn badge-soft-base rounded-pill ">{{translate('pos_order')}}</label>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>

                            @php($orderTotalPriceSummary = \App\Utils\OrderManager::getOrderTotalPriceSummary(order: $order))
                            <div class="row justify-content-end mt-2">
                                <div class="col-xl-5 col-lg-6 col-md-8 col-sm-10">
                                    <div class="d-flex flex-column gap-3 text-dark mx-2">
                                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                            <div>{{translate('Total_Item')}}</div>
                                            <div class="price-red">
                                                {{ $orderTotalPriceSummary['totalItemQuantity'] }}
                                            </div>
                                        </div>

                                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                            <div>{{translate('item_price')}}</div>
                                            <div class="price-red">
                                                {!! webCurrencyConverterWithImage(amount:  $orderTotalPriceSummary['itemPrice']) !!}
                                            </div>
                                        </div>

                                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                            <div>{{translate('item_Discount')}}</div>
                                            <div class="price-red">
                                                {!! webCurrencyConverterWithImage(amount:  $orderTotalPriceSummary['itemDiscount']) !!}
                                            </div>
                                        </div>

                                        @if($order->order_type != 'default_type')
                                            <div
                                                class="d-flex flex-wrap justify-content-between align-`item`s-center gap-2">
                                                <div class="text-capitalize">{{translate('extra_discount')}}</div>
                                                <div class="price-red">
                                                    {!! webCurrencyConverterWithImage(amount:  $orderTotalPriceSummary['extraDiscount']) !!}
                                                </div>
                                            </div>
                                        @endif

                                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                            <div>{{translate('subtotal')}}</div>
                                            <div class="price-red">
                                                {!! webCurrencyConverterWithImage(amount:  $orderTotalPriceSummary['subTotal']) !!}
                                            </div>
                                        </div>

                                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                            <div class="text-capitalize">{{translate('coupon_discount')}}</div>
                                            <div class="price-red">
                                               {!! webCurrencyConverterWithImage(amount:  $orderTotalPriceSummary['couponDiscount']) !!}
                                            </div>
                                        </div>

                                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                            <div>{{translate('tax_fee')}}</div>
                                            <div class="price-red">
                                                {!! webCurrencyConverterWithImage(amount:  $orderTotalPriceSummary['taxTotal']) !!}
                                            </div>
                                        </div>

                                        @if($order->order_type == 'default_type' && $order?->is_shipping_free == 0)
                                            <div
                                                class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                                <div>{{translate('shipping_fee')}}</div>
                                                <div class="price-red">
                                                    {!! webCurrencyConverterWithImage(amount:  $orderTotalPriceSummary['shippingTotal']) !!}
                                                </div>
                                            </div>
                                        @endif

                                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                            <h4>{{translate('total')}}</h4>
                                            <h2 class="text-base price-red">
                                                {!! webCurrencyConverterWithImage(amount:  $orderTotalPriceSummary['totalAmount']) !!}
                                            </h2>
                                        </div>
                                        @if ($order->order_type == 'POS' || $order->order_type == 'pos')
                                            <hr class="m-0">
                                            <div
                                                class="d-flex flex-wrap justify-content-between align-`item`s-center gap-2">
                                                <div class="text-capitalize fw-bold">{{translate('paid_amount')}}</div>
                                                <div class="fw-bold price-red">
                                                    {!! webCurrencyConverterWithImage(amount:  $orderTotalPriceSummary['paidAmount']) !!}
                                                </div>
                                            </div>
                                            <div
                                                class="d-flex flex-wrap justify-content-between align-`item`s-center gap-2">
                                                <div class="text-capitalize fw-bold">{{translate('change_amount')}}</div>
                                                <div class="fw-bold price-red">
                                                    {!! webCurrencyConverterWithImage(amount:  $orderTotalPriceSummary['changeAmount']) !!}
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <span id="account-order-details-review-message"
          data-drop-here="{{ translate('drop_here') }}"
          data-input-type="{{ translate('input_png_or_jpg') }}"
          data-on-big-size-file= "{{ translate('file_size_too_big') }}"
          data-placeholder="{{ theme_asset('assets/img/image-place-holder-4_1.png') }}"

    ></span>
@endsection

@push('script')
    <script src="{{ theme_asset('assets/js/spartan-multi-image-picker.js') }}"></script>
    <script src="{{ theme_asset('assets/js/account-order-details.js') }}"></script>
@endpush
