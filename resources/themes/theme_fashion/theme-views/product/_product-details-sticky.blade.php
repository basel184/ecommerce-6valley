<div class="bg-white product-details-sticky product-details-sticky-section pt-4 pt-md-3 pb-3 {{ $productDetails->variation && count(json_decode($productDetails->variation)) > 0 ? 'multi-variation-product' : '' }}">
    <div class="btn-circle bg-primary text-white product-details-sticky-collapse-btn d-md-none transition cursor-pointer shadow-sm position-absolute translate-middle-custom top-0 left-50 justify-content-center align-items-center {{ $productDetails->variation && count(json_decode($productDetails->variation)) > 0 ? 'd-flex' : 'd-none' }}" style="--size: 34px">
        <i class="bi bi-chevron-up"></i>
    </div>

    <div class="container product-cart-option-container">
        <form class="add-to-cart-sticky-form addToCartDynamicForm" action="{{ route('cart.add') }}"
                      data-errormessage="{{translate('please_choose_all_the_options')}}"
                      data-outofstock="{{translate('sorry').', '.translate('out_of_stock')}}.">
            @csrf
            <input type="hidden" name="id" value="{{ $productDetails->id }}">
            <input type="hidden" name="position" value="bottom">

            <div class="product-details-sticky-top">
                <div class="border-bottom d-flex flex-column gap-3 mb-3 pb-3">

                    @if (count(json_decode($productDetails->colors)) > 0)
                    <div>
                        <label class="form-label">
                            {{ translate('color') }}
                            <span class="px-2 opacity-75 product-details-sticky-color-name"></span>
                        </label>
                        <div class="check-color-group justify-content-start align-items-center flex-nowrap overflow-x-auto overflow-y-hidden scrollbar-none">
                            @foreach (json_decode($productDetails->colors) as $key => $color)
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
                        </div>
                    </div>
                    @endif

                    @php($extensionIndex=0)
                    @if($productDetails['product_type'] == 'digital' && $productDetails['digital_product_file_types'] && count($productDetails['digital_product_file_types']) > 0 && $productDetails['digital_product_extensions'])
                        @foreach($productDetails['digital_product_extensions'] as $extensionKey => $extensionGroup)
                        <div>
                            <label class="form-label">
                                {{ translate($extensionKey) }}
                            </label>
                            @if(count($extensionGroup) > 0)
                                <div class="d-flex flex-wrap gap-2">
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

                    @foreach (json_decode($productDetails->choice_options) as $key => $choice)
                    <div>
                        <label class="form-label">
                            {{ translate($choice->title) }}
                        </label>
                        <div class="d-flex flex-wrap gap-2 flex-nowrap overflow-x-auto overflow-y-hidden scrollbar-none">
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

                </div>
            </div>

            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 product-details-sticky-bottom">
            <div class="media gap-3">
                <img width="48" class="rounded d-none d-sm-block object-cover aspect-1"
                     src="{{ getStorageImages(path: $productDetails->thumbnail_full_url, type: 'product') }}"
                     alt="">
                <div class="media-body">
                    <h6 class="mb-0 fs-14 line--limit-1">{{ $productDetails->name }}</h6>
                    <div>
                        <input type="hidden" class="product-generated-variation-code" name="product_variation_code" data-product-id="{{ $productDetails['id'] }}">
                        <input type="hidden" value="" class="product-exist-in-cart-list form-control w-50" name="key">
                    </div>
                    <div class="d-flex flex-wrap align-items-center mb-2 pro">
                        <span class="fs-12 text-muted line--limit-1 text-capitalize product-generated-variation-text">
                        </span>
                        <div class="d-none d-sm-flex flex-wrap align-items-center">
                            <span class="__inline-25 mx-2"></span>
                            <h6 class="text-primary fs-14 flex-wrap d-flex gap-2">
                                {!! getPriceRangeWithDiscount(product: $productDetails) !!}
                            </h6>
                            <span class="py-1 px-2 font-bold fs-13 mx-2 bg-primary rounded text-absolute-white fw-bold discounted-badge-element d-none">
                                    <span class="direction-ltr d-block discounted_badge">
                                    </span>
                                </span>
                        </div>
                    </div>
                </div>

                <div class="d-sm-none d-flex flex-column flex-sm-row">
                    {!! getPriceRangeWithDiscount(product: $productDetails) !!}
                </div>
            </div>

            <div class="d-flex align-items-center gap-2 gap-sm-3 gap-xl-4">
                <div class="inc-inputs">
                    <input type="number" name="quantity" value="{{ $productDetails->minimum_order_qty ?? 1 }}"
                           class="form-control product_quantity__qty product_qty"
                           min="{{ $productDetails->minimum_order_qty ?? 1 }}"
                           max="{{$productDetails['product_type'] == 'physical' ? $productDetails->current_stock : 100}}"
                    >
                </div>

                <div class="font-weight-normal text-accent align-items-end gap-2 d-none d-lg-flex">
                    <span class="text-primary fs-5 fw-bold product-details-chosen-price-amount user-select-none"></span>
                </div>

                @if(($productDetails->added_by == 'seller' && ($sellerTemporaryClose || (isset($productDetails->seller->shop) && $productDetails->seller->shop->vacation_status && $currentDate >= $sellerVacationStartDate && $currentDate <= $sellerVacationEndDate))) ||
                            ($productDetails->added_by == 'admin' && ($inHouseTemporaryClose || ($inHouseVacationStatus && $currentDate >= $inHouseVacationStartDate && $currentDate <= $inHouseVacationEndDate))))
                    <div class="alert alert-danger m-0" role="alert">
                        {{translate('you_cannot_add_product_to_cart_from_this_shop_for_now')}}
                    </div>
                @else
                    <div class="btn-grp">
                        <div class="product-add-and-buy-section d--flex gap-2" {!! $firstVariationQuantity <= 0 ? 'style="display: none;"' : '' !!}>
                            @if(($productDetails->added_by == 'seller' && ($sellerTemporaryClose || (isset($productDetails->seller->shop) && $productDetails->seller->shop->vacation_status && $currentDate >= $sellerVacationStartDate && $currentDate <= $sellerVacationEndDate))) ||
                            (   $productDetails->added_by == 'admin' && ($inHouseTemporaryClose || ($inHouseVacationStatus && $currentDate >= $inHouseVacationStartDate && $currentDate <= $inHouseVacationEndDate))))
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
                                   data-add="{{ translate('add_to_cart') }}">
                                    @include('theme-views.partials.icons._cart-icon')
                                    <span class="text">{{ translate('add_to_cart') }}</span>
                                </a>

                                <a href="javascript:"
                                   class="btn btn-base btn-md __btn-outline-warning secondary-color text-capitalize product-buy-now-button"
                                   data-form=".add-to-cart-sticky-form"
                                   data-auth="{{( getWebConfig(name: 'guest_checkout') == 1 || Auth::guard('customer')->check() ? 'true':'false')}}"
                                   data-route="{{ route('shop-cart') }}"
                                >
                                    @include('theme-views.partials.icons._buy-now')
                                    {{ translate('buy_now') }}</a>
                            @endif
                        </div>

                        @if(($productDetails['product_type'] == 'physical'))
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

                    </div>
                @endif

            </div>
        </div>
        </form>
    </div>
</div>
