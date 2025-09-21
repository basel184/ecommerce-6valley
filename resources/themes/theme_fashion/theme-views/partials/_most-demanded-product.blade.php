@if ($mostDemandedProducts !=null)
<section class="most-demanded-product-section section-gap pb-0">
    <div class="overflow-hidden">
        <div class="section-title-2 text-capitalize">
            <span class="shapetitle">{{ translate('most_demanded') }}</span>
            <h2 class="title text-capitalize">{{ translate('most_demanded_product') }}</h2>
        </div>
    </div>
    <div class="container">
        <div class="most-demanded-product-wrapper __bg-img cursor-pointer thisIsALinkElement" data-linkpath="{{ route('product', $mostDemandedProducts->product['slug']) }}">
            <img loading="lazy" src="{{ getStorageImages(path: $mostDemandedProducts->banner_full_url, type:'banner') }}"
                alt="{{ translate('banner') }}" class="inner-bg">
            <div class="most-demanded-product-content">
                <h2 class="subtitle">
                    <span class="d-block text-capitalize">{{ translate('most_demanded') }}</span>
                    <span class="d-block text-capitalize">{{ translate('product_of_this_year') }}</span>
                </h2>
                <div class="counter-wrapper">
                    <div class="count-item">
                        <div class="count-item-inner">
                            <h3 class="count">{{ $mostDemandedProducts->product->reviews_count }}</h3>
                            <span class="subtext">{{ translate('review') }}</span>
                        </div>
                    </div>
                    <div class="count-item">
                        <div class="count-item-inner">
                            <h3 class="count text-base">{{ $mostDemandedProducts->product->order_details_count }}</h3>
                            <span class="subtext text-base">{{ translate('order') }}</span>
                        </div>
                    </div>
                    <div class="count-item">
                        <div class="count-item-inner">
                            <h3 class="count">{{ $mostDemandedProducts->product->order_delivered_count }}</h3>
                            <span class="subtext">{{ translate('delivery') }}</span>
                        </div>
                    </div>
                    <div class="count-item">
                        <div class="count-item-inner">
                            <h3 class="count">{{ $mostDemandedProducts->product->wish_list_count }}</h3>
                            <span class="subtext">{{ translate('wishes') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endif
