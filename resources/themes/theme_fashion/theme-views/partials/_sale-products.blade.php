<section class="sale-products-section section-gap pb-0">
    <div class="container">
        <div class="section-title mb-4 pb-lg-1 text-capitalize">
            <div class="d-flex flex-wrap justify-content-between row-gap-2 column-gap-4 align-items-center single_section_dual_tabs">
                <h2 class="title mb-0">عروض العيد الوطني </h2>
                <div class="d-flex align-items-center column-gap-4 justify-content-end ms-auto ms-md-0 order-0 order-sm-2">
                    <div class="d-flex align-items-center column-gap-2 column-gap-sm-4">
                        <div class="owl-prev sale-products-prev">
                            <i class="bi bi-chevron-left"></i>
                        </div>
                        <div class="owl-next sale-products-next">
                            <i class="bi bi-chevron-right"></i>
                        </div>
                    </div>
                    <div class="single_section_dual_target">
                        <a href="/products?offer_type=discounted&page=1" class="see-all">{{ translate('see_all') ?? 'عرض الكل' }}</a>
                        <a href="{{route('products',['data_from'=>'latest','page'=>1])}}" class="see-all d-none">{{ translate('see_all') ?? 'عرض الكل' }}</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="overflow-hidden">
            <div class="tab-content">
                <div class="tab-pane fade show active" id="discounted-products">
                    <div class="sale-products-slider-wrapper">
                        <div class="recommended-slider owl-theme owl-carousel">
                            @if(isset($discountedProducts) && count($discountedProducts) > 0)
                                @foreach($discountedProducts as $product)
                                    @if($product)
                                        @include('theme-views.partials._product-medium-card',['product'=>$product])
                                    @endif
                                @endforeach
                            @else
                                <div class="col-12 text-center">
                                    <p>{{ translate('no_discounted_products_found') ?? 'لا توجد منتجات مخفضة' }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade show" id="flash-deals">
                    <div class="sale-products-slider-wrapper">
                        <div class="sale-products-slider owl-theme owl-carousel">
                            @if(isset($flashDeal['flashDealProducts']) && count($flashDeal['flashDealProducts']) > 0)
                                @foreach($flashDeal['flashDealProducts'] as $product)
                                    @if($product)
                                        @include('theme-views.partials._product-medium-card',['product'=>$product])
                                    @endif
                                @endforeach
                            @else
                                <div class="col-12 text-center">
                                    <p>{{ translate('no_flash_deals_found') ?? 'لا توجد عروض فلاش' }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@push('script')
<script>
$(document).ready(function() {
    // Initialize the sale products slider
    if ($('.sale-products-slider').length > 0) {
        $('.sale-products-slider').owlCarousel({
            loop: true,
            margin: 15,
            nav: false,
            dots: false,
            autoplay: true,
            autoplayTimeout: 3500,
            responsive: {
                0: {
                    items: 2
                },
                600: {
                    items: 3
                },
                1000: {
                    items: 4
                },
                1200: {
                    items: 5
                }
            }
        });

        // Custom navigation
        $('.sale-products-next').click(function() {
            $('.sale-products-slider').trigger('next.owl.carousel');
        });
        
        $('.sale-products-prev').click(function() {
            $('.sale-products-slider').trigger('prev.owl.carousel');
        });
    }

    // Tab switching functionality
    $('.single_section_dual_btn li a').on('click', function(e) {
        e.preventDefault();
        
        var targetTab = $(this).attr('href');
        var tabIndex = $(this).closest('li').data('targetbtn');
        
        // Update active tab
        $('.single_section_dual_btn li a').removeClass('active');
        $(this).addClass('active');
        
        // Show/hide tab content
        $('.tab-pane').removeClass('active show');
        $(targetTab).addClass('active show');
        
        // Update see all link
        $('.single_section_dual_target a').addClass('d-none');
        $('.single_section_dual_target a').eq(tabIndex).removeClass('d-none');
        
        // Reinitialize slider for the active tab
        setTimeout(function() {
            $(targetTab + ' .sale-products-slider').trigger('refresh.owl.carousel');
        }, 100);
    });
});
</script>
@endpush
