<section class="category-230-section section-gap pb-0">
    <div class="container">
        <div class="section-title mb-4 pb-lg-1 text-capitalize">
            <div class="d-flex flex-wrap justify-content-between row-gap-2 column-gap-4 align-items-center">
                <h2 class="title mb-0">منتجات العناية</h2>
                <div class="d-flex align-items-center column-gap-4 justify-content-end ms-auto">
                    <div class="d-flex align-items-center column-gap-2 column-gap-sm-4">
                        <div class="owl-prev category-230-prev">
                            <i class="bi bi-chevron-left"></i>
                        </div>
                        <div class="owl-next category-230-next">
                            <i class="bi bi-chevron-right"></i>
                        </div>
                    </div>
                    <a href="{{route('products',['category_id'=>230,'data_from'=>'category','page'=>1])}}" class="see-all">{{ translate('see_all') }}</a>
                </div>
            </div>
        </div>
        <div class="overflow-hidden">
            <div class="category-230-slider-wrapper" id="latest">
                <div class="recommended-slider owl-theme owl-carousel">
                    @if(isset($category230Products) && count($category230Products) > 0)
                        @foreach($category230Products as $product)
                            @if($product)
                                @include('theme-views.partials._product-medium-card',['product'=>$product])
                            @endif
                        @endforeach
                    @else
                        <div class="col-12 text-center">
                            <p>{{ translate('no_products_found') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>

@push('script')
<script>
$(document).ready(function() {
    // Initialize the category 230 slider
    if ($('.category-230-slider').length > 0) {
        $('.category-230-slider').owlCarousel({
            loop: true,
            margin: 15,
            nav: false,
            dots: false,
            autoplay: true,
            autoplayTimeout: 3000,
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
        $('.category-230-next').click(function() {
            $('.category-230-slider').trigger('next.owl.carousel');
        });
        
        $('.category-230-prev').click(function() {
            $('.category-230-slider').trigger('prev.owl.carousel');
        });
    }
});
</script>
@endpush
