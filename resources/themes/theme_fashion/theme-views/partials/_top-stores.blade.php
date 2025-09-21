<section class="top-fashion-house section-gap pb-0">
    <div class="container">
        <div class="section-title-3 mb-0">
            <div class="mb-32px text-capitalize">
                <div class="d-flex flex-wrap justify-content-between justify-content-lg-center row-gap-2 column-gap-4 align-items-center">
                    <h2 class="title mb-0">{{ translate('top_Fashion_House') }}</h2>
                    <div class="cevron-wrapper d-flex align-items-center column-gap-4 justify-content-end ms-auto ms-md-0">
                        <div class="owl-prev fashion-prev">
                            <i class="bi bi-chevron-left"></i>
                        </div>
                        <div class="owl-next fashion-next">
                            <i class="bi bi-chevron-right"></i>
                        </div>
                        <a href="{{ route('vendors', ['filter'=>'top-vendors']) }}" class="see-all">{{ translate('see_all') }}</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="overflow-hidden">
            <div class="fashion-house-slider-wrapper">
                <div class="fashion-house-slider owl-theme owl-carousel">
                    @foreach($topVendorsList as $vendorData)
                        <div class="fashion-card cursor-pointer thisIsALinkElement"
                             data-linkpath="{{route('shopView',['id'=> $vendorData['id']])}}">
                            <div class="fashion-card-top">
                                <a href="javascript:" data-linkpath="{{route('shopView',['id'=>$vendorData['id']])}}"
                                   class="thumb thisIsALinkElement">
                                    <div class="position-relative">
                                        <div>
                                            <img loading="lazy" alt="{{ translate('shop') }}" title="{{ $vendorData->name }}"
                                                 src="{{ getStorageImages(path: $vendorData->image_full_url, type: 'shop') }}">
                                        </div>
                                        @if($vendorData->temporary_close)
                                            <span
                                                class="temporary-closed position-absolute text-center h6 rounded px-2">
                                                <span>{{translate('Temporary_OFF')}}</span>
                                            </span>
                                        @elseif(($vendorData->vacation_status && ($currentDate >= $vendorData->vacation_start_date) && ($currentDate <= $vendorData->vacation_end_date)))
                                            <span
                                                class="temporary-closed position-absolute text-center h6 rounded px-2">
                                                <span>{{translate('closed_now')}}</span>
                                            </span>
                                        @endif
                                    </div>
                                </a>
                                <img loading="lazy" class="cover" alt="{{ translate('banner') }}"
                                     src="{{ getStorageImages(path: $vendorData->banner_full_url, type: 'shop-banner') }}">

                            </div>
                            <div class="fashion-card-bottom">
                                <span class="btn">
                                    <i class="bi bi-star-fill text-star"></i> {{ round($vendorData->average_rating ,1) }} {{ translate('rating') }}
                                </span>
                                <span class="btn">
                                    {{ $vendorData->products_count > 99 ? '99+' : $vendorData->products_count }} {{ translate('products') }}
                                </span>
                                <a href="{{route('shopView',['id'=>$vendorData['id']])}}"
                                   class="btn __btn-outline">
                                    {{ translate('visit') }}
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
