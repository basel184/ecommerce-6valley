<section class="promo-section d-none d-sm-block ">
    <div class="container">
        <div class="promo-wrapper">
            @if ($bannerTypePromoBannerLeft)
                <a href="{{ $bannerTypePromoBannerLeft['url'] }}" target="_blank" class="img1 overflow-hidden promo-1">
                    <img loading="lazy" src="{{ getStorageImages(path: $bannerTypePromoBannerLeft['photo_full_url'], type:'banner') }}"
                    alt="{{ translate('promo') }}" class="w-100">
                </a>
            @else
                <a href="javascript:" class="img2 overflow-hidden opacity-0">
                    <img loading="lazy" src="" alt="{{ translate('promo') }}">
                </a>
            @endif

            @if($bannerTypePromoBannerMiddleTop || $bannerTypePromoBannerMiddleBottom)
                <div class="promo-2">
                    @if ($bannerTypePromoBannerMiddleTop)
                        <a href="{{ $bannerTypePromoBannerMiddleTop['url'] }}" target="_blank" class="img2 overflow-hidden">
                            <img loading="lazy" alt="{{ translate('promo') }}"
                                 src="{{ getStorageImages(path: $bannerTypePromoBannerMiddleTop['photo_full_url'], type:'banner') }}">
                        </a>
                    @else
                        <a href="javascript:" class="img2 overflow-hidden opacity-0">
                            <img loading="lazy" src="" alt="{{ translate('promo') }}">
                        </a>
                    @endif

                    @if ($bannerTypePromoBannerMiddleBottom)
                        <a href="{{ $bannerTypePromoBannerMiddleBottom['url'] }}" target="_blank" class="img3 overflow-hidden">
                            <img loading="lazy" alt="{{ translate('promo') }}"
                                 src="{{ getStorageImages(path: $bannerTypePromoBannerMiddleBottom['photo_full_url'], type:'banner') }}">
                        </a>
                        @else
                        <a href="javascript:" class="img3 overflow-hidden opacity-0">
                            <img loading="lazy" src="" alt="{{ translate('promo') }}">
                        </a>
                    @endif
                </div>
            @endif

            @if ($bannerTypePromoBannerRight)
                <a href="{{ $bannerTypePromoBannerRight['url'] }}" target="_blank" class="img1 overflow-hidden promo-3 {{ $bannerTypePromoBannerLeft || $bannerTypePromoBannerMiddleTop || $bannerTypePromoBannerMiddleBottom != null ? '' :'w-100'}}">
                    <img loading="lazy" alt="{{ translate('promo') }}"
                         src="{{ getStorageImages(path: $bannerTypePromoBannerRight['photo_full_url'], type:'banner') }}">
                </a>
            @endif

        </div>
    </div>
</section>
