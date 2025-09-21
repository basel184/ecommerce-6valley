@extends('theme-views.layouts.app')

@section('title', translate('track_order_result').' | '.$web_config['company_name'].' '.translate('ecommerce'))

@section('content')

    <section class="breadcrumb-section pt-20px">
        <div class="container">
            <div class="section-title mb-4">
                <div class="d-flex flex-wrap justify-content-between row-gap-3 column-gap-2 align-items-center search-page-title">
                    <ul class="breadcrumb">
                        <li>
                            <a href="{{route('home')}}">{{translate('home')}}</a>
                        </li>
                    </ul>
                    <div class="ms-auto ms-md-0">
                        @if(auth('customer')->check())
                            <a href="{{route('account-orders')}}"
                               class="text-base custom-text-link">{{ translate('check_My_All_Orders') }}</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="search-track-section pt-4 section-gap">
        <div class="container">
            <h3 class="mb-3 mb-lg-4">{{translate('download_your_product')}}</h3>
            <form action="{{ route('digital-product-download-pos.index') }}" method="get" class="mb-3">
                <div class="track-order-wrapper">
                    <div class="track-order-input col--5">
                        <input type="text" class="form-control" name="order_id" value="{{ request('order_id') }}"
                               placeholder="{{translate('order_ID')}}">
                    </div>
                    <div class="track-order-input col--5">
                        <input type="email" class="form-control" name="email" value="{{ request('email') }}"
                               placeholder="{{translate('email')}}">
                    </div>
                    <div class="track-order-input col--2">
                        <button type="submit" class="form-control btn btn-base">
                            {{ isset($order) ? translate('verified') : translate('verify') }}
                        </button>
                    </div>
                </div>
            </form>

            @if(isset($orderDetails))
                @if($isDigitalProductExist != 0)
                    @if($isDigitalProductReadyCount == 0)
                        <div class="border rounded px-3 py-3 fs-15 text-base font-weight-medium custom-light-primary-color mb-3 d-flex align-items-center gap-3">
                            <img src="{{ theme_asset('assets/img/icons/info-light.svg') }}" alt="" class="px-2">
                            <span>
                                        {{ translate('your_digital_product_is_ready.') }}
                                {{ translate('once_the_seller_has_uploaded_the_product__you_will_be_able_to_download_here_by_using_your_order_info.') }}
                                {{ translate('if_you_face_any_issue_during_download_please_until_wait_or_contact_admin_via') }}

                                @if(auth('customer')->check())
                                    <a class="text-base fw-bold text-underline" href="{{route('account-tickets')}}">
                                                {{ translate('support_ticket')}}
                                            </a>
                                @else
                                    <a class="text-base fw-bold text-underline" href="{{route('customer.auth.login')}}">
                                                {{ translate('support_ticket')}}
                                            </a>
                                @endif
                                    </span>
                        </div>
                    @else
                        <div class="d-flex flex-column gap-2 p-4 rounded border">
                            @foreach($orderDetails as $index => $orderDetail)
                                <div class="d-flex justify-content-between align-items-center gap-2">
                                    <div class="d-flex justify-content-between align-items-center gap-2">
                                        <img width="50" src="{{ getStorageImages(path: $orderDetail->product->thumbnail_full_url, type: 'product') }}" alt="" class="border rounded">
                                        <a class="fs-13 font-semi-bold" href="{{ route('product', $orderDetail->product->slug) }}">
                                            {{ $orderDetail->product->name }}
                                        </a>
                                    </div>
                                    <div>
                                        @php($productDetails = json_decode($orderDetail->product_details, true))

                                        @if($productDetails['digital_product_type'] == 'ready_product')
                                                <?php
                                                $checkFilePath = storageLink('product/digital-product', $productDetails['digital_file_ready'], ($productDetails['storage_path'] ?? 'public'));
                                                $filePath = $checkFilePath['path'];
                                                $fileExist = $checkFilePath['status'] == 200;
                                                $fileName = $productDetails['digital_file_ready'];
                                                ?>
                                            @if ($fileExist)
                                                <span class="btn p-0 shadow-none border-0 getDownloadFileUsingFileUrl" data-bs-toggle="tooltip" title="{{ translate('download') }}" data-file-path="{{ $filePath }}">
                                                    <img src="{{ theme_asset(path: 'assets/img/icons/download-green.svg') }}" alt="">
                                                </span>
                                            @else
                                                <a class="btn p-0 shadow-none border-0" data-bs-toggle="tooltip" title="{{ translate('File_not_found') }}" href="javascript:" download>
                                                    <img src="{{ theme_asset(path: 'assets/img/icons/download-green.svg') }}" alt="">
                                                </a>
                                            @endif
                                        @elseif($productDetails['digital_product_type'] == 'ready_after_sell')
                                            @if($orderDetail['digital_file_after_sell'])
                                                    <?php
                                                    $checkFilePath = $orderDetail->digital_file_after_sell_full_url;
                                                    $filePath = $checkFilePath['path'];
                                                    $fileName = $orderDetail['digital_file_after_sell'];
                                                    $fileExist = $checkFilePath['status'] == 200;
                                                    ?>
                                                <span class="btn p-0 shadow-none border-0 getDownloadFileUsingFileUrl" data-bs-toggle="tooltip" title="{{ translate('download') }}" data-file-path="{{ $filePath }}">
                                                    <img src="{{ theme_asset(path: 'assets/img/icons/download-green.svg') }}" alt="">
                                                </span>
                                            @else
                                                <a class="btn p-0 shadow-none border-0" data-bs-toggle="tooltip" title="{{ translate('product_not_uploaded_yet') }}" disabled>
                                                    <img src="{{ theme_asset(path: 'assets/img/icons/download-green.svg') }}" alt="">
                                                </a>
                                            @endif
                                        @endif

                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @else
                    <div class="rounded px-3 py-3 fs-15 text-base font-weight-medium custom-light-primary-color mb-3 d-flex align-items-center gap-3">
                        <img src="{{ theme_asset('public/assets/front-end/img/icons/info-light.svg') }}" alt="" class="px-2">
                        <span>
                            {{ translate('you_have_no_digital_products_in_your_order') }}
                        </span>
                    </div>
                @endif

            @endif


        </div>
    </section>

@endsection

@push('script')
    <script src="{{ theme_asset('assets/js/tracking-page.js') }}"></script>
@endpush

