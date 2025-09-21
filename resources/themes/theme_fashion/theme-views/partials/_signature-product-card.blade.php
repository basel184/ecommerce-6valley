<div class="product-card product-cart-option-container">
  <div class="product-card-inner">
      <div class="img">
          <a href="{{route('product',$product->slug)}}" class="d-block h-100">
              <img loading="lazy" class="w-100" alt="{{ translate('product') }}"
                   src="{{ getStorageImages(path: $product->thumbnail_full_url, type: 'product') }}">
          </a>
          @if (isset($product->created_at) && $product->created_at->diffInMonths(\Carbon\Carbon::now()) < 1)
              <span class="badge badge-title z-2">{{translate('new')}}</span>
          @endif
          <div class="hover-content d-flex justify-content-center">
              <div
                      class="d-flex flex-wrap justify-content-between align-items-center column-gap-3">
                  <a href="{{route('product',$product->slug)}}"
                     class="d-inline-flex">
                      <i class="bi bi-eye"></i>
                  </a>
                  @php($wishlist = count($product->wishList)>0 ? 1 : 0)
                  <a href="javascript:"
                     class="d-inline-flex wish-icon addWishlist_function_view_page"
                     data-id="{{$product->id}}">
                      <i class="wishlist_{{$product->id}} bi {{($wishlist == 1?'bi-heart-fill text-danger':'bi-heart')}}"></i>
                  </a>
                  <div class="rating">
                      <i class="bi bi-star-fill text-star"></i>
                      <span>{{round($product->reviews->avg('rating'),1) ?? 0}}</span>
                  </div>
              </div>
          </div>
      </div>
      <div class="cont">
          <h6 class="title">
              <a href="{{route('product',$product->slug)}}"
                 title="{{ $product['name'] }}">{{ Str::limit($product['name'], 18) }}</a>
          </h6>
          <div class="d-flex align-items-center justify-content-between column-gap-2">
              <h4 class="price flex-wrap">
                  <span>{!! getProductPriceByType(product: $product, type: 'discounted_unit_price', result: 'string_with_image') !!}</span>
                  @if(getProductPriceByType(product: $product, type: 'discount', result: 'value') > 0)
                      <del>{!! webCurrencyConverter($product->unit_price) !!}</del>
                  @endif
              </h4>
              @if (json_decode($product->variation) != null)
                  @php($variations = json_decode($product->variation, true))
                  @php($first_variation = $variations ? reset($variations) : null)
                  <span class="btn add-to-cart-btn">
                      <a href="javascript:" 
                         class="store_vacation_check_function w-100 h-100 d-flex align-items-center justify-content-center"
                         data-id="{{ $product['id'] }}"
                         data-added_by="{{ $product['added_by'] }}"
                         data-user_id="{{ $product['user_id'] }}"
                         data-action_url="{{ route('ajax-shop-vacation-check') }}"
                         onclick="addToCartWithVariation(this, {{ $product['id'] }}, '{{ $first_variation['color'] ?? '' }}', {{ json_encode($first_variation) }})">
                          <i class="bi bi-plus"></i>
                      </a>
                  </span>
              @else
                  <span class="btn add-to-cart-btn">
                      <a href="javascript:" 
                         class="store_vacation_check_function w-100 h-100 d-flex align-items-center justify-content-center"
                         data-id="{{ $product['id'] }}"
                         data-added_by="{{ $product['added_by'] }}"
                         data-user_id="{{ $product['user_id'] }}"
                         data-action_url="{{ route('ajax-shop-vacation-check') }}"
                         onclick="addToCartSimple(this, {{ $product['id'] }})">
                          <i class="bi bi-plus"></i>
                      </a>
                  </span>
              @endif
          </div>
          @php($overallRating = getOverallRating($product->reviews))
      </div>
  </div>
</div>

<script>
function addToCartSimple(element, productId) {
    // منع إعادة تحميل الصفحة
    event.preventDefault();
    
    const formData = new FormData();
    formData.append('_token', '{{ csrf_token() }}');
    formData.append('id', productId);
    formData.append('quantity', 1);
    
    fetch('{{ route("cart.add") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status == 1) {
            toastr.success(data.message || 'تم إضافة المنتج للسلة بنجاح');
            // تحديث عداد السلة
            updateCartCount();
        } else {
            toastr.error(data.message || 'حدث خطأ أثناء إضافة المنتج');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        toastr.error('حدث خطأ غير متوقع');
    });
}

function addToCartWithVariation(element, productId, color, variation) {
    // منع إعادة تحميل الصفحة
    event.preventDefault();
    
    const formData = new FormData();
    formData.append('_token', '{{ csrf_token() }}');
    formData.append('id', productId);
    formData.append('quantity', 1);
    
    if (color && color.trim() !== '') {
        formData.append('color', color);
    }
    
    if (variation && variation.attribute_id && Array.isArray(variation.attribute_id)) {
        variation.attribute_id.forEach((attr_id, index) => {
            if (variation.variant && variation.variant[index]) {
                formData.append(`attribute_id_${attr_id}`, variation.variant[index]);
            }
        });
    }
    
    fetch('{{ route("cart.add") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status == 1) {
            toastr.success(data.message || 'تم إضافة المنتج للسلة بنجاح');
            // تحديث عداد السلة
            updateCartCount();
        } else {
            toastr.error(data.message || 'حدث خطأ أثناء إضافة المنتج');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        toastr.error('حدث خطأ غير متوقع');
    });
}

function updateCartCount() {
    // تحديث عداد السلة في الصفحة
    fetch('{{ route("cart.variant_price") }}', {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.text())
    .then(html => {
        // تحديث محتوى السلة
        const cartDropdown = document.querySelector('.dropdown-menu.__header-cart-menu');
        if (cartDropdown) {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newCartContent = doc.querySelector('.dropdown-menu.__header-cart-menu');
            if (newCartContent) {
                cartDropdown.innerHTML = newCartContent.innerHTML;
            }
        }
        
        // تحديث عداد السلة
        const cartCount = document.querySelector('.btn-status');
        if (cartCount) {
            fetch('{{ route("cart.nav-count") }}')
                .then(response => response.text())
                .then(count => {
                    cartCount.textContent = count;
                });
        }
    });
}
</script>
