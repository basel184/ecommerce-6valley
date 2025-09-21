@extends('theme-views.layouts.app')

@section('title', translate('my_order_details_reviews').' | '.$web_config['company_name'].' '.translate('ecommerce'))

@section('content')
    <section class="user-profile-section section-gap pt-0">
        <div class="container">
            @include('theme-views.partials._profile-aside')
            <div class="card bg-section border-0">
                <div class="card-body p-lg-4">
                    @include('theme-views.partials._order-details-head',['order'=>$order])
                    <div class="mt-4 card border-0 bg-body">

                        @php($review_count = 0)
                        @foreach ($order->details as $order_details)
                            @isset($order_details->reviewData)
                                @php($review_count++)
                                <div class="card-body mb-xl-5">
                                    <div class="media gap-3">
                                        <div class="align-items-center aspect-1 d-flex h-100 overflow-hidden rounded w-100px position-relative">
                                            <img class="d-block img-fit w-100" src="{{ getStorageImages(path:$order_details?->product?->thumbnail_full_url, type: 'product') }}" alt="">

                                            <div class="position-absolute top-start-position-5px">
                                                @if($order_details?->product?->discount > 0)
                                                    <span class="badge badge-soft-base">
                                                        @if ($order_details?->product?->discount_type == 'percent')
                                                            {{ round($order_details?->product?->discount)}}%
                                                        @elseif($order_details?->product?->discount_type =='flat')
                                                            {{ webCurrencyConverter(amount: $order_details?->product?->discount) }}
                                                        @endif
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="media-body d-flex gap-1 flex-column">
                                            <h6 class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                                <a href="{{ $order_details?->product?->slug ? route('product', $order_details?->product?->slug) : 'javascript:' }}">
                                                    {{ $order_details?->product?->name ? Str::limit($order_details?->product?->name, 40) : translate('Product_not_found') }}
                                                </a>
                                                <div class="d-inline-block">
                                                    <button class="btn-star" type="button" data-bs-toggle="modal"
                                                            data-bs-target="#reviewModal{{$order_details->id}}">
                                                        <i class="bi bi-star"></i>
                                                        <span>{{ translate('update_review') }}</span>
                                                    </button>
                                                </div>
                                            </h6>

                                            @if($order_details->variant)
                                                <small>
                                                    {{ translate('variant')}} : {{$order_details->variant}}
                                                </small>
                                            @endif

                                        </div>
                                    </div>

                                    @include('theme-views.layouts.partials.modal._review', ['id' => $order_details->id, 'order_details' => $order_details])

                                    <div class="comments-information mt-4">
                                        <ul id="product-review-list">
                                            <li class="d-block">
                                                <div>
                                                    <div class="mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                                                        <h4 class="text-capitalize">
                                                            <i class="bi bi-star-fill text-star"></i>
                                                            {{ translate('my_review') }}
                                                        </h4>
                                                        <small>
                                                            {{ date('M d , Y h:i A',strtotime($order_details->reviewData?->updated_at))}}
                                                        </small>
                                                    </div>
                                                    <div class="content-area w-100">
                                                        <p class="mb-3">
                                                            {{ $order_details->reviewData?->comment ?? ''}}
                                                        </p>

                                                        @if (count($order_details?->reviewData?->attachment_full_url) > 0)
                                                            <div class="products-comments-img d-flex flex-wrap gap-2 custom-image-popup-init">
                                                                @foreach ($order_details->reviewData->attachment_full_url as $key => $photo)
                                                                    <a href="{{ getStorageImages(path: $photo, type: 'product') }}"
                                                                       class="custom-image-popup">
                                                                        <img loading="lazy"
                                                                             src="{{ getStorageImages(path: $photo, type: 'product') }}"
                                                                             alt="">
                                                                    </a>
                                                                @endforeach
                                                            </div>
                                                        @endif

                                                    </div>
                                                </div>

                                                @if($order_details->reviewData && $order_details->reviewData->reply)
                                                    <div class="ps-md-4 mt-3 me-1">
                                                        <div class="review-reply rounded bg-E9F3FF80 p-3 ms-md-4 before-border-left">
                                                            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                                                                <div class="d-flex align-items-center gap-2">
                                                                    <img src="{{dynamicAsset('public/assets/front-end/img/seller-reply-icon.png')}}"
                                                                         alt="">
                                                                    <h6 class="font-bold text-normal">
                                                                        {{ translate('Reply_by_Seller') }}
                                                                    </h6>
                                                                </div>
                                                                <span class="opacity-50">
                                                                    {{ isset($order_details->reviewData->reply->created_at) ? $order_details->reviewData->reply->created_at->format('M-d-Y') : '' }}
                                                                </span>
                                                            </div>
                                                            <p class="text-sm">
                                                                {!! $order_details->reviewData->reply->reply_text !!}
                                                            </p>
                                                        </div>
                                                    </div>
                                                @endif
                                            </li>

                                        </ul>
                                    </div>
                                </div>
                            @endisset
                        @endforeach
                        @if ($review_count == 0)
                            <div class="text-center pt-5 text-capitalize">
                                <img class="mb-3" src="{{dynamicAsset(path: 'public/assets/front-end/img/icons/empty-review.svg')}}"
                                     alt="">
                                <p class="opacity-60 mt-3 text-capitalize">{{translate('no_review_found')}}!</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

    </section>
    <span id="order-details-review-message"
          data-drop-here="{{ translate('drop_here') }}"
          data-input-type="{{ translate('input_png_or_jpg') }}"
          data-on-big-size-file= "{{ translate('file_size_too_big') }}"
          data-placeholder="{{ theme_asset('assets/img/image-place-holder-4_1.png') }}"

    ></span>
@endsection

@push('script')
    <script src="{{ theme_asset('assets/js/spartan-multi-image-picker.js') }}"></script>
    <script src="{{ theme_asset('assets/js/_reviews.js') }}"></script>
@endpush
