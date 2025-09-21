<section class="others-section section-gap pb-0">
    <div class="container">
        <div class="section-title mb-0">
            <div class="mb-32px">
                <div
                    class="d-flex flex-wrap justify-content-between row-gap-2 column-gap-4 align-items-center text-capitalize">
                    <h2 class="title mb-0 me-auto font-medium test-capitalize">{{ translate("other_stores") }}</h2>
                    <ul class=" nav nav-tabs nav--tabs order-1">
                        <li>
                            <a href="#top-rated" class="active"
                               data-bs-toggle="tab">{{ translate('top_rated_vendor') }}</a>
                        </li>
                        <li>
                            <a href="#new-seller" data-bs-toggle="tab">{{ translate('new_vendor') }}</a>
                        </li>
                    </ul>
                    <div
                        class="d-flex align-items-center column-gap-3 column-gap-md-4 justify-content-end ms-auto ms-md-0 order-0 order-sm-2">
                        <div class="owl-prev store-prev">
                            <i class="bi bi-chevron-left"></i>
                        </div>
                        <div class="owl-next store-next">
                            <i class="bi bi-chevron-right"></i>
                        </div>
                        <a href="{{route('vendors')}}" class="see-all">{{ translate('see_all') }}</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="overflow-hidden">
            <div class="tab-content">
                <div class="tab-pane fade show active" id="top-rated">
                    <div class="others-store-slider-wrapper">
                        <div class="others-store-slider owl-theme owl-carousel">

                            @foreach ($topRatedShops as $shopItem)
                                <div class="others-store-card text-capitalize">
                                    <div class="name-area">
                                        <div class="position-relative ">
                                            <div>
                                                <img loading="lazy" class="rounded-full other-store-logo"
                                                     src="{{ getStorageImages(path: $shopItem->image_full_url, type:'shop') }}"
                                                     alt="{{ translate('others_store') }}">
                                            </div>
                                            @if($shopItem->temporary_close)
                                                <span class="temporary-closed position-absolute text-center h6 rounded-full">
                                                    <span>{{translate('Temporary_OFF')}}</span>
                                                </span>
                                            @elseif(($shopItem->vacation_status && ($currentDate >= $shopItem->vacation_start_date) && ($currentDate <= $shopItem->vacation_end_date)))
                                                <span class="temporary-closed position-absolute text-center h6 rounded-full">
                                                    <span>{{translate('closed_now')}}</span>
                                                </span>
                                            @endif
                                        </div>
                                        <div class="info">
                                            <h2 class="lh-1 h6">
                                                <a title="{{ $shopItem?->name ?? $shopItem?->shop?->name ?? '' }}"
                                                    class="name cursor-pointer h6 d-block"
                                                    href="{{route('shopView',['id' => $shopItem['id']]) }}">
                                                    {{ \Illuminate\Support\Str::limit($shopItem->name ?? $shopItem?->shop?->name, 18) }}
                                                </a>
                                            </h2>
                                            <span class="offer-badge">
                                                {{ round(($shopItem->average_rating * 100) / 5) }}% {{ translate('positive_review') }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="info-area">
                                        <div class="info-item">
                                            <h6>{{ $shopItem->review_count > 99 ? '99+' : $shopItem->review_count }}</h6>
                                            <span>{{ translate('reviews') }}</span>
                                        </div>
                                        <div class="info-item">
                                            <h6>{{ $shopItem->products_count > 99 ? '99+' : $shopItem->products_count }}</h6>
                                            <span>{{ translate('products') }}</span>
                                        </div>
                                        <div class="info-item">
                                            <h6>{{ number_format(($shopItem->average_rating ?? 0), 1) }}</h6>
                                            <i class="bi bi-star-fill"></i>
                                            <span>{{ translate('rating') }}</span>
                                        </div>
                                    </div>
                                    <a href="{{route('shopView',['id'=> $shopItem['id']])}}" class="btn __btn-outline">
                                        {{ translate('visit_shop') }}
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="new-seller">
                    <div class="others-store-slider-wrapper">
                        <div class="others-store-slider owl-theme owl-carousel">
                            @foreach ($newSellers as $shopItem)
                                <div class="others-store-card text-capitalize new-store">
                                    <img loading="lazy" class="new-store-banner-bg" alt="{{ translate('banner') }}"
                                         src="{{ getStorageImages(path: $shopItem?->banner_full_url, type:'shop-banner') }}">
                                    <div class="name-area mt-n-17">
                                        <div class="position-relative">
                                            <div>
                                                <img loading="lazy" class="rounded-full other-store-logo"
                                                     src="{{ getStorageImages(path: $shopItem?->image_full_url, type:'shop') }}"
                                                     alt="{{ translate('others_store') }}">
                                            </div>
                                            @if($shopItem?->temporary_close)
                                                <span class="temporary-closed position-absolute text-center h6 rounded-full">
                                                    <span>{{translate('Temporary_OFF')}}</span>
                                                </span>
                                            @elseif(($shopItem->vacation_status && ($currentDate >= $shopItem->vacation_start_date) && ($currentDate <= $shopItem->vacation_end_date)))
                                                <span class="temporary-closed position-absolute text-center h6 rounded-full">
                                                    <span>{{translate('closed_now')}}</span>
                                                </span>
                                            @endif
                                        </div>
                                        <div class="info">
                                            <a title="{{ $shopItem?->name ?? translate('vendor_not_available') }}"
                                               class="name cursor-pointer m-0 h6 d-block"
                                               href="{{ route('shopView', ['id'=> $shopItem['id']]) }}">
                                                {{ $shopItem ? Str::limit($shopItem->name, 18) : translate('vendor_not_available') }}
                                            </a>
                                        </div>
                                    </div>
                                    <div class="info-area w-100 align-items-center justify-content-between mb-0">
                                        <div class="info-area mb-0">
                                            <div class="info-item">
                                                <h6>{{ $shopItem->products_count > 99 ? '99+' : $shopItem->products_count }}</h6>
                                                <span>{{ translate('products') }}</span>
                                            </div>
                                        </div>
                                        <a href="{{route('shopView',['id'=>$shopItem['id']])}}" class="btn __btn-outline">
                                            {{ translate('visit_shop') }}
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
