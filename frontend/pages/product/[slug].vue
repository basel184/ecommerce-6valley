<script setup lang="ts">
import { computed, ref, watch, onMounted, nextTick } from 'vue'
// Import Swiper Vue.js components
import { Swiper, SwiperSlide } from 'swiper/vue'
// Import Swiper styles
import 'swiper/css'
import 'swiper/css/free-mode'
import 'swiper/css/navigation'
import 'swiper/css/thumbs'
// Import required modules
import { FreeMode, Navigation, Thumbs } from 'swiper/modules'

// Register Swiper components globally for this component
const SwiperComponent = Swiper
const SwiperSlideComponent = SwiperSlide

const route = useRoute()
const { details: getDetails, related: getRelated } = useProducts() as any
const cart = useCart()

// Reviews data
const reviews = ref<any[]>([])
const reviewsLoading = ref(false)
const reviewsError = ref('')
const totalReviewsCount = ref(0)
const averageRating = ref(0)

const slug = computed(() => String(route.params.slug || ''))
const loading = ref(true)
const error = ref<string>('')
const product = ref<any>(null)
const recLoading = ref(true)
const recommended = ref<any[]>([])

// Helpers to normalize media paths similar to ProductCard
const cfg = useRuntimeConfig() as any
const assetBase = (cfg?.public?.apiBase || '').replace(/\/api(?:\/v\d+)?$/, '')
const fixPath = (s: string) => {
  let p = (s || '').trim().replace(/\\/g, '/').replace(/^public\//, '').replace(/^app\/public\//, 'storage/')
  p = p.replace(/\/+/g, '/').replace(/^\//, '')
  return p
}
const normalize = (s: any): string => {
  if (!s) return ''
  if (Array.isArray(s)) return normalize(s[0])
  let v: any = s
  if (typeof s === 'string') {
    const trimmed = s.trim()
    if ((trimmed.startsWith('[') || trimmed.startsWith('{'))) {
      try { const parsed = JSON.parse(trimmed); return normalize(parsed) } catch {}
    }
    v = trimmed
  } else if (typeof s === 'object') {
    // Handle different object formats
    v = (s as any).path || (s as any).url || (s as any).image || (s as any).key || ''
  }
  v = (typeof v === 'string' ? v : '').trim()
  if (!v) return ''
  if (/^(https?:|data:|blob:)/i.test(v)) return v
  return `${assetBase}/${fixPath(v)}`
}

const images = computed<string[]>(() => {
  const p: any = product.value || {}
  const raw = p?.images_full_url || p?.images || p?.gallery_images || p?.product?.images_full_url || p?.product?.images || p?.product?.gallery_images || []
  const arr = Array.isArray(raw) ? raw : (typeof raw === 'string' && raw.trim().startsWith('[') ? (JSON.parse(raw) as any[]) : (raw ? [raw] : []))
  const norm = arr.map((x: any) => normalize(x)).filter(Boolean)
  // Handle thumbnail specifically
  const thumbnailData = p?.thumbnail_full_url || p?.image_full_url || p?.photo_full_url || p?.thumbnail || p?.image || p?.photo || p?.product?.thumbnail_full_url || p?.product?.image_full_url || p?.product?.thumbnail || p?.product?.image
  const thumb = normalize(thumbnailData)
  return [...new Set([thumb, ...norm].filter(Boolean))]
})
const mainIndex = ref(0)
const mainImage = computed(() => images.value[mainIndex.value] || '')
const onImgErr = (e: Event) => {
  const el = e.target as HTMLImageElement | null
  if (!el) return
  el.src = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="400" height="400"><rect width="100%" height="100%" fill="%23f3f4f6"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="%239ca3af" font-size="16">No image</text></svg>'
}

// Swiper configuration
const thumbsSwiper = ref(null)
const setThumbsSwiper = (swiper: any) => {
  if (swiper && swiper.el) {
    thumbsSwiper.value = swiper
  }
}
const modules = [FreeMode, Navigation, Thumbs]

// Ensure Swiper is only initialized when images are available and product is loaded
const shouldShowSwiper = computed(() => {
  return !loading.value && !error.value && product.value && images.value.length > 0
})

// Add a small delay to ensure DOM is fully rendered
const swiperReady = ref(false)
watch(shouldShowSwiper, async (newVal) => {
  if (newVal) {
    await nextTick()
    setTimeout(() => {
      swiperReady.value = true
    }, 100)
  } else {
    swiperReady.value = false
  }
}, { immediate: true })

// Core fields
const title = computed(() => {
  const p: any = product.value || {}
  return p?.name || p?.product?.name || 'المنتج'
})
const brandName = computed(() => {
  const p: any = product.value || {}
  return p?.brand_name || p?.brand?.name || p?.product?.brand_name || p?.product?.brand?.name || ''
})
const basePrice = computed<number>(() => {
  const p: any = product.value || {}
  const v = p?.unit_price ?? p?.price ?? p?.product?.unit_price ?? p?.product?.price
  const n = Number(v)
  return isFinite(n) && n > 0 ? n : 0
})
const discountValue = computed<number>(() => {
  const p: any = product.value || {}
  const v = p?.discount ?? p?.product?.discount ?? 0
  const n = Number(v)
  return isFinite(n) && n > 0 ? n : 0
})
const discountType = computed<string>(() => {
  const p: any = product.value || {}
  return p?.discount_type || p?.product?.discount_type || 'flat'
})
const finalPrice = computed<number>(() => {
  const bp = basePrice.value, dv = discountValue.value
  if (!bp || !dv) return bp
  const isPercent = String(discountType.value).toLowerCase().startsWith('per')
  const diff = isPercent ? (bp * dv) / 100 : dv
  return Math.max(0, bp - diff)
})
const hasDiscount = computed(() => finalPrice.value > 0 && finalPrice.value < basePrice.value)
const discountPercent = computed(() => {
  const bp = basePrice.value, dv = discountValue.value
  if (!bp || !dv) return 0
  const isPercent = String(discountType.value).toLowerCase().startsWith('per')
  return Math.max(0, Math.round(isPercent ? dv : (dv / bp) * 100))
})
const rating = computed<number>(() => {
  // Use API data if available, otherwise fallback to product data
  if (averageRating.value > 0) return averageRating.value
  
  const p: any = product.value || {}
  const r = p?.reviews_avg_rating ?? p?.avg_rating ?? p?.rating?.[0]?.average ?? 0
  const n = Number(r)
  return isFinite(n) ? Math.min(5, Math.max(0, n)) : 0
})
const reviewsCount = computed<number>(() => {
  // Use API data if available, otherwise fallback to product data
  if (totalReviewsCount.value > 0) return totalReviewsCount.value
  
  const p: any = product.value || {}
  const c = p?.reviews_count ?? p?.rating?.[0]?.count ?? 0
  const n = Number(c)
  return isFinite(n) ? Math.max(0, Math.round(n)) : 0
})
const inStock = computed<boolean>(() => {
  const p: any = product.value || {}
  const q = p?.current_stock ?? p?.stock ?? p?.quantity ?? p?.product?.current_stock ?? p?.product?.stock ?? 0
  return (Number(q) > 0) || p?.in_stock === true
})
const currencySymbol = computed(() => (product.value?.currency_symbol || '﷼'))
const description = computed(() => {
  const p: any = product.value || {}
  return p?.details ?? p?.description ?? p?.product?.details ?? p?.product?.description ?? ''
})

const metaDescription = computed(() => {
  const p: any = product.value || {}
  const result = p?.meta_description ?? p?.short_description ?? p?.product?.meta_description ?? p?.product?.short_description ?? ''
  // Clean up the text by removing \r\n and extra whitespace
  const cleaned = result.replace(/\r\n/g, ' ').replace(/\s+/g, ' ').trim()
  return cleaned
})

const specifications = computed(() => {
  const p: any = product.value || {}
  return p?.specifications ?? p?.product?.specifications ?? {}
})

const howToUse = computed(() => {
  const p: any = product.value || {}
  return p?.how_to_use ?? p?.usage_instructions ?? p?.product?.how_to_use ?? p?.product?.usage_instructions ?? ''
})

// Helper function to format date
const formatDate = (dateString: string) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('ar-SA', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit'
  }).replace(/\//g, '/')
}

// Helper function to mask customer name
const maskCustomerName = (name: string) => {
  if (!name) return '*****'
  if (name.length <= 2) return '*****' + name
  return '*****' + name.slice(-2)
}

// Active tab
const activeTab = ref('description')

// Auth and review modal
const showLoginModal = ref(false)
const isLoggedIn = ref(false) // This should be connected to your auth system

// Handle write review button click
const handleWriteReview = () => {
  if (isLoggedIn.value) {
    // User is logged in, show review form or redirect to review page
    console.log('User is logged in, show review form')
    // TODO: Implement review form modal or redirect
  } else {
    // User is not logged in, show login modal
    showLoginModal.value = true
  }
}

// Close login modal
const closeLoginModal = () => {
  showLoginModal.value = false
}

// Handle login success
const handleLoginSuccess = () => {
  isLoggedIn.value = true
  showLoginModal.value = false
  // Reload reviews after login
  loadReviews()
}

  // Cart handlers
const qty = ref(1)
const busy = ref(false)
const addToCart = async () => {
  if (busy.value || !product.value) return
  const id = product.value?.id || product.value?.product_id || product.value?.product?.id
  if (!id) return
  try {
    busy.value = true
    await cart.add({ product_id: Number(id), quantity: qty.value })
  } finally {
    busy.value = false
  }
}

// Load reviews
const loadReviews = async () => {
  if (!product.value) return
  
  reviewsLoading.value = true
  reviewsError.value = ''
  
  try {
    const productId = product.value?.id || product.value?.product_id || product.value?.product?.id
    if (!productId) return
    
    const cfg = useRuntimeConfig() as any
    const apiBase = cfg?.public?.apiBase || ''
    
    // Load reviews
    const reviewsResponse = await $fetch(`${apiBase}/v1/products/reviews/${productId}`)
    reviews.value = Array.isArray(reviewsResponse) ? reviewsResponse : []
    totalReviewsCount.value = reviews.value.length
    
    // Load rating separately
    try {
      const ratingResponse = await $fetch(`${apiBase}/v1/products/rating/${productId}`)
      averageRating.value = Number(ratingResponse) || 0
    } catch (ratingError) {
      console.warn('Could not load rating:', ratingError)
      averageRating.value = 0
    }
    
  } catch (e: any) {
    console.error('Error loading reviews:', e)
    // If it's a 404, it means no reviews exist yet
    if (e?.status === 404) {
      reviews.value = []
      totalReviewsCount.value = 0
      averageRating.value = 0
      reviewsError.value = ''
    } else {
      reviewsError.value = e?.data?.message || 'تعذر تحميل التقييمات'
    }
  } finally {
    reviewsLoading.value = false
  }
}

// Loaders
const load = async () => {
  loading.value = true; error.value = ''
  try {
    const res = await getDetails(slug.value)
    product.value = res
    // Wait for DOM to be ready before initializing Swiper
    await nextTick()
    // Load reviews after product is loaded
    await loadReviews()
  } catch (e: any) {
    error.value = e?.data?.message || 'تعذر تحميل تفاصيل المنتج'
  } finally {
    loading.value = false
  }
  // Load recommended
  try {
    recLoading.value = true
    const id = product.value?.id || product.value?.product_id || product.value?.product?.id
    if (id) {
      const list = await getRelated(id)
      recommended.value = Array.isArray(list) ? list : []
    } else {
      recommended.value = []
    }
  } catch {
    recommended.value = []
  } finally {
    recLoading.value = false
  }
}

onMounted(() => {
  load()
  // Add escape key listener for modal
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && showLoginModal.value) {
      closeLoginModal()
    }
  })
})
watch(slug, () => { mainIndex.value = 0; load() })
</script>

<template>
  <div class="wrap" dir="rtl">
    <div class="crumbs">
      <NuxtLink to="/">الرئيسية</NuxtLink>
      <span>/</span>
      <NuxtLink to="/shop">المتجر</NuxtLink>
      <span>/</span>
      <b>{{ title }}</b>
    </div>

    <div v-if="loading" class="loader">جارٍ التحميل…</div>
    <div v-else-if="error" class="error">{{ error }}</div>
    <div v-else class="product">
      <!-- Gallery -->
      <div class="gallery">
        <!-- Main Swiper Gallery -->
        <div v-if="!shouldShowSwiper" class="no-images">
          <div class="no-images-content">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="currentColor">
              <path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/>
            </svg>
            <p>لا توجد صور متاحة</p>
          </div>
        </div>
        <SwiperComponent
          v-if="swiperReady"
          :key="`main-swiper-${images.length}`"
          :style="{
            '--swiper-navigation-color': '#fff',
            '--swiper-pagination-color': '#fff',
          }"
          :space-between="10"
          :navigation="images.length > 1"
          :thumbs="{ swiper: thumbsSwiper }"
          :modules="modules"
          class="mySwiper2"
        >
          <SwiperSlideComponent v-for="(img, i) in images" :key="`main-${i}-${img}`">
            <img :src="img" :alt="title" @error="onImgErr" />
          </SwiperSlideComponent>
        </SwiperComponent>
        
        <!-- Thumbnails Swiper Gallery -->
        <SwiperComponent
          v-if="swiperReady && images.length > 1"
          :key="`thumbs-swiper-${images.length}`"
          @swiper="setThumbsSwiper"
          :space-between="10"
          :slides-per-view="4"
          :free-mode="true"
          :watch-slides-progress="true"
          :modules="modules"
          class="mySwiper"
        >
          <SwiperSlideComponent v-for="(img, i) in images" :key="`thumb-${i}-${img}`">
            <img :src="img" :alt="'img-'+i" />
          </SwiperSlideComponent>
        </SwiperComponent>
      </div>

      <!-- Info -->
      <div class="info">
        <!-- Brand & Wishlist -->
        <div class="brand-section">
          <div class="brand-info">
            <span v-if="brandName" class="brand">{{ brandName }}</span>
            <span class="original-badge">100% أصلي</span>
          </div>
          <button class="wishlist-btn">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
            </svg>
          </button>
        </div>

        <h1 class="title">{{ title }}</h1>
        
        <!-- Rating -->
        <div class="rating-section">
          <div class="stars">
            <span v-for="i in 5" :key="i" class="star" :class="{ filled: i <= Math.round(rating) }">★</span>
          </div>
          <span class="rating-text">({{ reviewsCount }}) التقييمات</span>
        </div>

        <!-- Price -->
        <div class="price-section">
          <div class="price-main">{{ finalPrice.toLocaleString() }} {{ currencySymbol }}</div>
          <div v-if="hasDiscount" class="price-old">{{ basePrice.toLocaleString() }} {{ currencySymbol }}</div>
          <div v-if="hasDiscount" class="discount-badge">-{{ discountPercent }}%</div>
        </div>

        <!-- Promotions -->
        <div v-if="hasDiscount" class="promotion-banner">
          <span class="promo-text">1+1 مجانا</span>
        </div>

        <!-- Product Disclaimer -->
        <div class="disclaimer-box">
          <p>هذا المنتج لا يرد ولا يستبدل</p>
          <p>يجب إضافة حبة العرض في السلة للحصول على العرض</p>
          <p>يتم تطبيق الخصم على المنتج الاقل سعراً</p>
        </div>

        <!-- Payment Options -->
        <div class="payment-options">
          <div class="payment-option">
            <div class="payment-logo">Tabby</div>
            <div class="payment-text">قسم فاتورتك على 4 دفعات بدون فوائد</div>
            <div class="payment-amount">{{ Math.round(finalPrice / 4) }} {{ currencySymbol }}</div>
          </div>
          <div class="payment-option">
            <div class="payment-logo">تمارا</div>
            <div class="payment-text">قسم فاتورتك على 4 دفعات بدون فوائد</div>
            <div class="payment-amount">{{ Math.round(finalPrice / 4) }} {{ currencySymbol }}</div>
          </div>
        </div>

        <!-- Add to Cart -->
        <div class="add-to-cart-section">
          <div class="qty-selector">
            <button @click="qty = Math.max(1, qty - 1)" :disabled="qty <= 1" class="qty-btn">−</button>
            <input type="number" v-model.number="qty" min="1" class="qty-input" />
            <button @click="qty = qty + 1" class="qty-btn">+</button>
          </div>
          <button class="add-to-cart-btn" :disabled="!inStock || busy" @click="addToCart">
            {{ busy ? '...إضافة' : 'أضف للسلة' }}
          </button>
        </div>

        <!-- Stock Status -->
        <div class="stock-status" :class="{ in: inStock, out: !inStock }">
          {{ inStock ? 'متوفر في المخزون' : 'غير متوفر حالياً' }}
        </div>
      </div>
    </div>

    <!-- Product Details Tabs -->
    <div v-if="product" class="product-details">
      <div class="tabs">
        <button 
          class="tab" 
          :class="{ active: activeTab === 'description' }" 
          @click="activeTab = 'description'"
        >
          الوصف
        </button>
        <button 
          class="tab" 
          :class="{ active: activeTab === 'specifications' }" 
          @click="activeTab = 'specifications'"
        >
          مواصفات
        </button>
        <button 
          class="tab" 
          :class="{ active: activeTab === 'reviews' }" 
          @click="activeTab = 'reviews'"
        >
          ({{ reviewsCount }}) التقييمات
        </button>
      </div>

      <div class="tab-content">
        <!-- Description Tab -->
        <div v-if="activeTab === 'description'" class="tab-panel">
          <div class="product-description">
            <h3>{{ title }}</h3>
            <p v-if="metaDescription" class="benefit-text">{{ metaDescription }}</p>
          </div>
        </div>

        <!-- Specifications Tab -->
        <div v-if="activeTab === 'specifications'" class="tab-panel">
          <div class="specifications">
            <div v-if="description" class="description-text" v-html="description"></div>
            <div v-if="Object.keys(metaDescription).length === 0" class="no-content">
              لا توجد مواصفات متاحة
            </div>
          </div>
        </div>

        <!-- Reviews Tab -->
        <div v-if="activeTab === 'reviews'" class="tab-panel">
          <!-- Reviews Header -->
          <div class="reviews-header">
            <div class="reviews-summary">
              <div class="rating-display">
                <div class="rating-number">{{ rating.toFixed(1) }}</div>
                <div class="rating-stars">
                  <span v-for="i in 5" :key="i" class="star" :class="{ filled: i <= Math.round(rating) }">★</span>
                </div>
                <div class="rating-text">بناء على {{ reviewsCount }} تقييم</div>
              </div>
            </div>
            <div class="reviews-actions">
              <button class="write-review-btn" @click="handleWriteReview">
                {{ isLoggedIn ? 'اكتب تقييمك' : 'تسجيل الدخول للتقيم' }}
              </button>
              <select class="sort-select">
                <option value="newest">الأحدث أولاً</option>
                <option value="oldest">الأقدم أولاً</option>
                <option value="highest">الأعلى تقييماً</option>
                <option value="lowest">الأقل تقييماً</option>
              </select>
            </div>
          </div>

          <!-- Reviews List -->
          <div v-if="reviewsLoading" class="reviews-loading">جارٍ تحميل التقييمات...</div>
          <div v-else-if="reviewsError" class="reviews-error">{{ reviewsError }}</div>
          <div v-else-if="reviews.length === 0" class="no-reviews">
            <div class="no-reviews-content">
              <svg width="64" height="64" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
              </svg>
              <h3>لا توجد تقييمات متاحة</h3>
              <p>كن أول من يقيم هذا المنتج</p>
            </div>
          </div>
          <div v-else class="reviews">
            <div v-for="review in reviews" :key="review.id" class="review-item">
              <div class="review-header">
                <div class="review-stars">
                  <span v-for="i in 5" :key="i" class="star" :class="{ filled: i <= review.rating }">★</span>
                </div>
                <div class="review-author">{{ maskCustomerName(review.customer?.f_name || review.customer?.name || 'مجهول') }}</div>
                <div class="review-date">{{ formatDate(review.created_at) }}</div>
              </div>
              <div class="review-text">{{ review.comment || 'لا يوجد تعليق' }}</div>
              <div class="review-actions">
                <button class="action-btn">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M21.99 4c0-1.1-.89-2-2-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14l4 4-.01-18z"/>
                  </svg>
                  {{ review.reply?.length || 0 }}
                </button>
                <button class="action-btn">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M7.5 21H2V9h5.5v12zm7.25-18h-5.5v18h5.5V3zM22 9h-5.5v12H22V9z"/>
                  </svg>
                  {{ review.likes || 0 }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recommended -->
    <div class="rec">
      <h2>منتجات موصى بها</h2>
      <div v-if="recLoading" class="loader">جارٍ التحميل…</div>
      <div v-else class="grid">
        <ProductCard v-for="p in recommended" :key="p.id || p.slug" :product="p" />
      </div>
    </div>

    <!-- Login Modal -->
    <div v-if="showLoginModal" class="modal-overlay" @click="closeLoginModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>تسجيل الدخول</h3>
          <button class="close-btn" @click="closeLoginModal">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
              <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
            </svg>
          </button>
        </div>
        <div class="modal-body">
          <p>يجب تسجيل الدخول لتتمكن من كتابة تقييم</p>
          <div class="login-options">
            <button class="login-btn primary" @click="handleLoginSuccess">
              تسجيل الدخول
            </button>
            <button class="login-btn secondary" @click="closeLoginModal">
              إلغاء
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
  .wrap{ padding:20px; max-width:1200px; margin:0 auto }
  .crumbs{ display:flex; align-items:center; gap:8px; color:#6b7280; margin-bottom:20px; font-size:14px }
  .crumbs a{ color:#6b7280; text-decoration:none }
  .crumbs a:hover{ color:#2563eb }
  .loader,.error{ padding:20px; background:#fafafa; border:1px solid #eee; border-radius:12px; text-align:center }

  /* Product Layout */
  .product{ display:flex; margin-bottom:40px }
  .product .gallery {width: 50%;}
  @media (max-width: 900px){ .product{ grid-template-columns: 1fr; gap:20px } }

  /* Gallery */
  .gallery{ margin-bottom:20px }
  .no-images{ 
    background:#fafafa; 
    border:1px solid #eee; 
    border-radius:16px; 
    min-height:400px;
    display:flex;
    align-items:center;
    justify-content:center;
  }
  .no-images-content{ 
    text-align:center; 
    color:#9ca3af;
  }
  .no-images-content svg{ 
    margin-bottom:12px; 
    opacity:0.5;
  }
  .no-images-content p{ 
    margin:0; 
    font-size:16px;
  }
  .mySwiper2{ 
    background:#fafafa; 
    border:1px solid #eee; 
    border-radius:16px; 
    overflow:hidden; 
    margin-bottom:12px;
    min-height:400px;
  }
  .mySwiper2 img{ 
    width:100%; 
    height:100%; 
    object-fit:contain; 
    max-height:400px;
    display:block;
  }
  .mySwiper{ 
    height:80px; 
    box-sizing:border-box; 
    padding:10px 0;
  }
  .mySwiper img{ 
    width:100%; 
    height:100%; 
    object-fit:cover; 
    border-radius:8px;
    border:2px solid #eee;
    transition:border-color 0.2s;
  }
  .mySwiper .swiper-slide-thumb-active img{ 
    border-color:#2563eb; 
    box-shadow:0 0 0 2px rgba(37, 99, 235, 0.1);
  }
  .mySwiper .swiper-slide{ 
    width:60px; 
    height:60px; 
    cursor:pointer;
  }

  /* Responsive Swiper Gallery */
  @media (max-width: 768px) {
    .mySwiper2{ 
      min-height:300px;
    }
    .mySwiper2 img{ 
      max-height:300px;
    }
    .mySwiper{ 
      height:70px;
    }
    .mySwiper .swiper-slide{ 
      width:50px; 
      height:50px; 
    }
  }

  /* Product Info */
  .info{ padding:0 20px }
  .brand-section{ display:flex; justify-content:space-between; align-items:center; margin-bottom:12px }
  .brand-info{ display:flex; align-items:center; gap:12px }
  .brand{ font-size:18px; font-weight:600; color:#6b7280 }
  .original-badge{ background:#e5f7e5; color:#16a34a; padding:4px 8px; border-radius:4px; font-size:12px; font-weight:600 }
  .wishlist-btn{ background:none; border:none; cursor:pointer; color:#6b7280; transition:color 0.2s }
  .wishlist-btn:hover{ color:#ef4444 }

  .title{ font-size:24px; font-weight:700; color:#111827; margin:0 0 16px; line-height:1.3 }

  .rating-section{ display:flex; align-items:center; gap:8px; margin-bottom:16px }
  .stars{ display:flex; gap:2px }
  .star{ color:#d1d5db; font-size:16px }
  .star.filled{ color:#f59e0b }
  .rating-text{ color:#6b7280; font-size:14px }

  .price-section{ display:flex; align-items:center; gap:12px; margin-bottom:16px }
  .price-main{ font-size:28px; font-weight:700; color:#111827 }
  .price-old{ font-size:18px; color:#9ca3af; text-decoration:line-through }
  .discount-badge{ background:#ef4444; color:#fff; padding:4px 8px; border-radius:6px; font-size:14px; font-weight:600 }

  .promotion-banner{ background:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:8px 12px; margin-bottom:16px; text-align:center }
  .promo-text{ color:#dc2626; font-weight:600; font-size:14px }

  .disclaimer-box{ background:#fef7f7; border:1px solid #fed7d7; border-radius:8px; padding:12px; margin-bottom:20px }
  .disclaimer-box p{ margin:0 0 4px; font-size:12px; color:#7f1d1d }
  .disclaimer-box p:last-child{ margin-bottom:0 }

  .payment-options{ display:flex; gap:12px; margin-bottom:20px }
  .payment-option{ flex:1; border:1px solid #e5e7eb; border-radius:8px; padding:12px; background:#fff; cursor:pointer; transition:all 0.2s }
  .payment-option:hover{ border-color:#2563eb; box-shadow:0 0 0 2px rgba(37, 99, 235, 0.1) }
  .payment-logo{ font-weight:600; color:#111827; margin-bottom:4px }
  .payment-text{ font-size:12px; color:#6b7280; margin-bottom:4px }
  .payment-amount{ font-weight:600; color:#111827 }

  .add-to-cart-section{ display:flex; gap:12px; align-items:center; margin-bottom:16px }
  .qty-selector{ display:flex; align-items:center; border:1px solid #e5e7eb; border-radius:8px; background:#fff }
  .qty-btn{ width:36px; height:36px; border:none; background:#f9fafb; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:18px; font-weight:600; color:#6b7280 }
  .qty-btn:hover{ background:#f3f4f6 }
  .qty-btn:disabled{ opacity:0.5; cursor:not-allowed }
  .qty-input{ width:60px; height:36px; border:none; text-align:center; font-size:16px; font-weight:600; outline:none }
  .add-to-cart-btn{ flex:1; background:#111827; color:#fff; border:none; border-radius:8px; padding:12px 20px; font-size:16px; font-weight:600; cursor:pointer; transition:background 0.2s }
  .add-to-cart-btn:hover:not(:disabled){ background:#374151 }
  .add-to-cart-btn:disabled{ background:#9ca3af; cursor:not-allowed }

  .stock-status{ font-weight:600; font-size:14px }
  .stock-status.in{ color:#16a34a }
  .stock-status.out{ color:#ef4444 }

  /* Product Details Tabs */
  .product-details{ margin-top:40px; border-top:1px solid #e5e7eb; padding-top:20px }
  .tabs{ display:flex; border-bottom:1px solid #e5e7eb; margin-bottom:20px }
  .tab{ background:none; border:none; padding:12px 20px; cursor:pointer; font-size:14px; font-weight:500; color:#6b7280; border-bottom:2px solid transparent; transition:all 0.2s }
  .tab:hover{ color:#111827 }
  .tab.active{ color:#111827; border-bottom-color:#111827 }
  .tab-content{ min-height:200px }
  .tab-panel{ padding:20px 0 }

  .product-description h3{ font-size:20px; font-weight:700; color:#111827; margin:0 0 12px }
  .benefit-text{ font-size:16px; font-weight:600; color:#111827; margin:0 0 16px }
  .description-text{ color:#6b7280; line-height:1.6; margin-bottom:16px }
  .how-to-use{ color:#6b7280; line-height:1.6 }
  .no-content{ color:#9ca3af; font-style:italic; text-align:center; padding:40px 0 }

  .spec-row{ display:flex; justify-content:space-between; padding:12px 0; border-bottom:1px solid #f3f4f6 }
  .spec-row:last-child{ border-bottom:none }
  .spec-row:nth-child(even){ background:#f9fafb }
  .spec-label{ font-weight:600; color:#111827 }
  .spec-value{ color:#6b7280 }

   /* Reviews Header */
   .reviews-header{ 
     display:flex; 
     justify-content:space-between; 
     align-items:center; 
     margin-bottom:24px; 
     padding:20px; 
     background:#f9fafb; 
     border-radius:12px;
   }
   .reviews-summary{ 
     display:flex; 
     align-items:center; 
     gap:20px;
   }
   .rating-display{ 
     text-align:center;
   }
   .rating-number{ 
     font-size:48px; 
     font-weight:700; 
     color:#111827; 
     line-height:1;
   }
   .rating-stars{ 
     display:flex; 
     gap:2px; 
     justify-content:center; 
     margin:8px 0;
   }
   .rating-stars .star{ 
     color:#d1d5db; 
     font-size:20px;
   }
   .rating-stars .star.filled{ 
     color:#f59e0b;
   }
   .rating-text{ 
     color:#6b7280; 
     font-size:14px;
   }
   .reviews-actions{ 
     display:flex; 
     gap:12px; 
     align-items:center;
   }
   .write-review-btn{ 
     background:#8b5cf6; 
     color:#fff; 
     border:none; 
     border-radius:8px; 
     padding:10px 20px; 
     font-weight:600; 
     cursor:pointer; 
     transition:background 0.2s;
   }
   .write-review-btn:hover{ 
     background:#7c3aed;
   }
   .sort-select{ 
     border:1px solid #e5e7eb; 
     border-radius:8px; 
     padding:10px 12px; 
     background:#fff; 
     color:#6b7280; 
     font-size:14px;
   }

   /* Reviews List */
   .reviews-loading, .reviews-error, .no-reviews{ 
     text-align:center; 
     padding:40px 20px; 
     color:#6b7280;
   }
   .reviews-error{ 
     color:#ef4444;
   }
   .no-reviews-content{ 
     display:flex; 
     flex-direction:column; 
     align-items:center; 
     gap:16px;
   }
   .no-reviews-content svg{ 
     color:#d1d5db; 
     opacity:0.5;
   }
   .no-reviews-content h3{ 
     margin:0; 
     color:#6b7280; 
     font-size:18px;
   }
   .no-reviews-content p{ 
     margin:0; 
     color:#9ca3af; 
     font-size:14px;
   }
   .reviews{ 
     margin-top:20px;
   }
   .review-item{ 
     border:1px solid #e5e7eb; 
     border-radius:12px; 
     padding:20px; 
     margin-bottom:16px; 
     background:#fff; 
     transition:box-shadow 0.2s;
   }
   .review-item:hover{ 
     box-shadow:0 4px 12px rgba(0,0,0,0.1);
   }
   .review-header{ 
     display:flex; 
     align-items:center; 
     gap:12px; 
     margin-bottom:12px;
   }
   .review-stars{ 
     display:flex; 
     gap:2px;
   }
   .review-stars .star{ 
     color:#d1d5db; 
     font-size:16px;
   }
   .review-stars .star.filled{ 
     color:#f59e0b;
   }
   .review-author{ 
     font-weight:600; 
     color:#111827; 
     font-size:14px;
   }
   .review-date{ 
     color:#9ca3af; 
     font-size:12px; 
     margin-left:auto;
   }
   .review-text{ 
     color:#6b7280; 
     line-height:1.6; 
     margin-bottom:12px; 
     font-size:14px;
   }
   .review-actions{ 
     display:flex; 
     gap:20px;
   }
   .action-btn{ 
     background:none; 
     border:none; 
     color:#6b7280; 
     cursor:pointer; 
     font-size:12px; 
     display:flex; 
     align-items:center; 
     gap:4px; 
     transition:color 0.2s;
   }
   .action-btn:hover{ 
     color:#111827;
   }

   /* Responsive Reviews */
   @media (max-width: 768px) {
     .reviews-header{ 
       flex-direction:column; 
       gap:16px; 
       text-align:center;
     }
     .reviews-actions{ 
       flex-direction:column; 
       width:100%;
     }
     .write-review-btn{ 
       width:100%;
     }
     .sort-select{ 
       width:100%;
     }
     .rating-number{ 
       font-size:36px;
     }
     .review-header{ 
       flex-wrap:wrap; 
       gap:8px;
     }
     .review-date{ 
       margin-left:0; 
       width:100%;
     }
   }

  /* Login Modal */
  .modal-overlay{ 
    position:fixed; 
    top:0; 
    left:0; 
    right:0; 
    bottom:0; 
    background:rgba(0,0,0,0.5); 
    display:flex; 
    align-items:center; 
    justify-content:center; 
    z-index:1000; 
    padding:20px;
  }
  .modal-content{ 
    background:#fff; 
    border-radius:16px; 
    max-width:400px; 
    width:100%; 
    box-shadow:0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
  }
  .modal-header{ 
    display:flex; 
    justify-content:space-between; 
    align-items:center; 
    padding:20px 24px; 
    border-bottom:1px solid #e5e7eb;
  }
  .modal-header h3{ 
    margin:0; 
    font-size:18px; 
    font-weight:600; 
    color:#111827;
  }
  .close-btn{ 
    background:none; 
    border:none; 
    color:#6b7280; 
    cursor:pointer; 
    padding:4px; 
    border-radius:4px; 
    transition:background 0.2s;
  }
  .close-btn:hover{ 
    background:#f3f4f6;
  }
  .modal-body{ 
    padding:24px;
  }
  .modal-body p{ 
    margin:0 0 20px; 
    color:#6b7280; 
    text-align:center; 
    line-height:1.5;
  }
  .login-options{ 
    display:flex; 
    gap:12px;
  }
  .login-btn{ 
    flex:1; 
    padding:12px 20px; 
    border-radius:8px; 
    font-weight:600; 
    cursor:pointer; 
    transition:all 0.2s; 
    border:none;
  }
  .login-btn.primary{ 
    background:#8b5cf6; 
    color:#fff;
  }
  .login-btn.primary:hover{ 
    background:#7c3aed;
  }
  .login-btn.secondary{ 
    background:#f3f4f6; 
    color:#6b7280;
  }
  .login-btn.secondary:hover{ 
    background:#e5e7eb;
  }

  /* Responsive Modal */
  @media (max-width: 480px) {
    .modal-content{ 
      margin:10px; 
      max-width:none;
    }
    .login-options{ 
      flex-direction:column;
    }
    .modal-header{ 
      padding:16px 20px;
    }
    .modal-body{ 
      padding:20px;
    }
  }

  /* Recommended */
  .rec{ margin-top:40px; padding-top:20px; border-top:1px solid #e5e7eb }
  .rec h2{ font-size:20px; font-weight:700; color:#111827; margin-bottom:16px }
  .grid{ display:grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap:16px }
</style>
