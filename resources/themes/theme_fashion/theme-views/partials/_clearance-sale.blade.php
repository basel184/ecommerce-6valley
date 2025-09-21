@if(count($clearanceSaleProducts) > 0)
    <section class="section-gap pb-0">
  <div class="container">
    <div class="product_blank_banner rounded-4 overflow-hidden">
      <div class="row g-0">
        <div class="col-xl-3">
          <div class="align-items-center bg-clearance-sale d-flex flex-column gap-2 gap-md-3 h-100 justify-content-center px-3 py-4 py-md-5">
            <h2 class="text-uppercase fs-28 text-absolute-white">{{ translate('Clearance_Sale') }}</h2>
            <h3 class="fs-34-on-large text-absolute-white fw-lighter lh-sm">
              {{ translate('Save_More') }}
            </h3>
            <div class="">
              <a href="{{route('products',['offer_type'=>'clearance_sale','page'=>1])}}" class="btn bg-absolute-white min-width-120 text-primary">{{ translate('view_all') }}</a>
            </div>
          </div>
        </div>
        <div class="col-xl-9">
          <div class="p-4">
            <div class="overflow-hidden">
              <div class="d-flex align-items-center column-gap-4 justify-content-end mb-3">
                  <div class="owl-prev flash-prev">
                      <i class="bi bi-chevron-left"></i>
                  </div>
                  <div class="owl-next flash-next">
                      <i class="bi bi-chevron-right"></i>
                  </div>
              </div>

              <div class="recommended-slider-wrapper">
                  <div class="clearance-sell-slider owl-theme owl-carousel">
                      @foreach($clearanceSaleProducts as $key => $product)
                          <div class="signature-product2">
                             @include('theme-views.partials._signature-product-card', ['product'=> $product])
                          </div>
                      @endforeach
                  </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endif
