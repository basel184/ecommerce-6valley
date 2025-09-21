@php
    $niche_perfumes = \App\Models\Product::active()
        ->whereHas('category', function($query) {
            $query->where('id', 774);
        })
        ->with(['reviews', 'rating'])
        ->withCount('reviews')
        ->orderBy('unit_price', 'asc')
        ->take(12)
        ->get();
@endphp

@if($niche_perfumes->count() > 0)
    <section class="niche-perfumes-section pt-4">
        <div class="container">
            <div class="section-title mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <h2 class="text-capitalize">{{ translate('مجموعات عطور') ?? 'مجموعات عطور' }}</h2>
                    <a href="{{ route('products', ['data_from' => 'category', 'category_id' => 774]) }}" 
                       class="text-base custom-text-link">
                        {{ translate('view_all') }}
                    </a>
                </div>
            </div>

            <div class="niche-perfumes-slider owl-carousel owl-theme">
                @foreach($niche_perfumes as $product)
                    <div class="item">
                        @include('theme-views.partials._product-medium-card', [
                            'product' => $product,
                            'decimal_point_settings' => getWebConfig(name: 'decimal_point_settings')
                        ])
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

@push('script')
    <script>
        $(document).ready(function() {
            $('.niche-perfumes-slider').owlCarousel({
                items: 6,
                margin: 26,
                loop: true,
                autoplay: true,
                autoplayTimeout: 3000,
                autoplayHoverPause: true,
                smartSpeed: 600,
                nav: false,
                dots: false,
                rtl: $('html').attr('dir') === 'rtl',
                responsive: {
                    0: {
                        items: 3,
                        margin: 10
                    },
                    480: {
                        items: 3,
                        margin: 15
                    },
                    768: {
                        items: 4,
                        margin: 16
                    },
                    992: {
                        items: 5,
                        margin: 20
                    },
                    1200: {
                        items: 6,
                        margin: 26
                    }
                }
            });
        });
    </script>
@endpush

<style>
    .niche-perfumes-section {
        border-radius: 15px;
        padding: 20px 0;
        margin: 20px 0;
    }
    
    .niche-perfumes-section .section-title h2 {
        color: #2c3e50;
        font-weight: 700;
        margin-bottom: 0;
    }
    
    .niche-perfumes-slider .item {
        transition: transform 0.3s ease;
    }
    
    .niche-perfumes-slider .item:hover {
        transform: translateY(-5px);
    }
    
    @media (max-width: 768px) {
        .niche-perfumes-section {
            padding: 15px 0;
            margin: 15px 0;
        }
    }
</style>
