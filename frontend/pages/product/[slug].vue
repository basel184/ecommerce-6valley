<script setup lang="ts">
import { computed, ref, watch, onMounted, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'
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

// Wishlist data
const isInWishlist = ref(false)
const wishlistLoading = ref(false)

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
const assetBase = (cfg?.public?.apiBase || 'http://127.0.0.1:8000/api').replace(/\/api(?:\/v\d+)?$/, '')
const fixPath = (s: string) => {
  let p = (s || '').trim().replace(/\\/g, '/')
  
  // Remove common prefixes
  p = p.replace(/^public\//, '')
  p = p.replace(/^app\/public\//, '')
  p = p.replace(/^storage\/app\/public\//, '')
  
  // Clean up slashes
  p = p.replace(/\/+/g, '/').replace(/^\//, '')
  
  // Ensure it starts with storage/
  if (!p.startsWith('storage/')) {
    p = 'storage/' + p
  }
  
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
const auth = useAuth()
const { t } = useI18n()
const { $get, $post, $del } = useApi()

// Login form
const loginForm = ref({ email: '', password: '' })
const loginLoading = ref(false)
const loginError = ref('')


// Review form
const showReviewModal = ref(false)
const reviewForm = ref({
  rating: 5,
  comment: '',
  images: []
})
const reviewLoading = ref(false)
const reviewError = ref('')

// Handle write review button click
const handleWriteReview = () => {
  if (auth?.user?.value) {
    // User is logged in, show review form
    showReviewModal.value = true
    reviewForm.value = {
      rating: 5,
      comment: '',
      images: []
    }
  } else {
    // User is not logged in, show login modal
    showLoginModal.value = true
  }
}

// Submit review
const submitReview = async () => {
  if (!reviewForm.value.comment.trim()) {
    reviewError.value = 'يرجى كتابة تعليق'
    return
  }
  
  reviewLoading.value = true
  reviewError.value = ''
  
  try {
    const productId = product.value?.id || product.value?.product_id || product.value?.product?.id
    if (!productId) {
      reviewError.value = 'خطأ في معرف المنتج'
      return
    }
    
    const response = await $post('v1/products/reviews/submit-guest', {
      product_id: productId,
      rating: reviewForm.value.rating,
      comment: reviewForm.value.comment,
      images: reviewForm.value.images
    })
    
    if (response) {
      showReviewModal.value = false
      // Reload reviews
      await loadReviews()
      // Show success message
      console.log('تم إرسال التقييم بنجاح')
    }
  } catch (error: any) {
    console.error('Review submission error:', error)
    if (error?.data?.errors && Array.isArray(error.data.errors)) {
      const errorMessages = error.data.errors.map((err: any) => err.message).join(', ')
      reviewError.value = errorMessages
    } else {
      reviewError.value = error?.data?.message || 'خطأ في إرسال التقييم'
    }
  } finally {
    reviewLoading.value = false
  }
}

// Close review modal
const closeReviewModal = () => {
  showReviewModal.value = false
  reviewError.value = ''
  reviewForm.value = {
    rating: 5,
    comment: '',
    images: []
  }
}

// Reply functions
const showReplyModal = ref(false)
const selectedReviewId = ref<string | null>(null)
const replyText = ref('')
const replyLoading = ref(false)
const replyError = ref('')

const openReplyModal = (reviewId: string) => {
  selectedReviewId.value = reviewId
  replyText.value = ''
  replyError.value = ''
  showReplyModal.value = true
}

const closeReplyModal = () => {
  showReplyModal.value = false
  selectedReviewId.value = null
  replyText.value = ''
  replyError.value = ''
}

const submitReply = async () => {
  if (!replyText.value.trim()) {
    replyError.value = 'يرجى كتابة رد'
    return
  }
  
  replyLoading.value = true
  replyError.value = ''
  
  try {
    const response = await $post('v1/products/review/reply', {
      review_id: selectedReviewId.value,
      reply_text: replyText.value
    })
    
    if (response) {
      closeReplyModal()
      // Reload reviews
      await loadReviews()
    }
  } catch (error: any) {
    console.error('Reply submission error:', error)
    if (error?.data?.errors && Array.isArray(error.data.errors)) {
      const errorMessages = error.data.errors.map((err: any) => err.message).join(', ')
      replyError.value = errorMessages
    } else {
      replyError.value = error?.data?.message || 'خطأ في إرسال الرد'
    }
  } finally {
    replyLoading.value = false
  }
}

// Like functions
const likeReview = async (reviewId: string) => {
  try {
    const response = await $post('v1/products/review/like', {
      review_id: reviewId
    })
    
    if (response) {
      // Reload reviews to update likes count
      await loadReviews()
    }
  } catch (error: any) {
    console.error('Like error:', error)
  }
}

const unlikeReview = async (reviewId: string) => {
  try {
    const response = await $fetch(`/api/v1/products/review/like/${reviewId}`, {
      method: 'DELETE',
      credentials: 'include',
      headers: { lang: 'sa' }
    })
    
    if (response) {
      // Reload reviews to update likes count
      await loadReviews()
    }
  } catch (error: any) {
    console.error('Unlike error:', error)
  }
}

// Register modal state
const showRegisterModal = ref(false)
const registerForm = ref({
  f_name: '',
  l_name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
  referral_code: ''
})
const registerLoading = ref(false)
const registerError = ref('')

// Handle register
const handleRegister = () => {
  showLoginModal.value = false
  showRegisterModal.value = true
  registerError.value = ''
  registerForm.value = {
    f_name: '',
    l_name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    referral_code: ''
  }
}

// Close register modal
const closeRegisterModal = () => {
  showRegisterModal.value = false
  registerError.value = ''
  registerForm.value = {
    f_name: '',
    l_name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    referral_code: ''
  }
}

// Handle register submission
const handleRegisterSubmit = async () => {
  if (!registerForm.value.f_name || !registerForm.value.l_name || !registerForm.value.email || !registerForm.value.phone || !registerForm.value.password) {
    registerError.value = 'جميع الحقول مطلوبة'
    return
  }

  if (registerForm.value.password !== registerForm.value.password_confirmation) {
    registerError.value = 'كلمة المرور غير متطابقة'
    return
  }

  if (registerForm.value.password.length < 6) {
    registerError.value = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل'
    return
  }

  registerLoading.value = true
  registerError.value = ''

  try {
    const response = await $post('v1/auth/register', {
      f_name: registerForm.value.f_name,
      l_name: registerForm.value.l_name,
      email: registerForm.value.email,
      phone: registerForm.value.phone,
      password: registerForm.value.password,
      referral_code: registerForm.value.referral_code || null
    })

    if (response?.token) {
      // Login successful
      auth.setToken(response.token)
      try {
        const userInfo = await $get('v1/customer/info')
        if (userInfo) {
          auth.setUser(userInfo)
        }
      } catch (e) {
        auth.setUser(response.user || response.data)
      }
      closeRegisterModal()
      handleLoginSuccess()
    } else if (response?.temporary_token) {
      // Need verification
      registerError.value = 'تم إنشاء الحساب بنجاح. يرجى التحقق من بريدك الإلكتروني أو رقم الهاتف'
      setTimeout(() => {
        closeRegisterModal()
      }, 3000)
    }
  } catch (error: any) {
    console.error('Register error:', error)
    if (error?.data?.errors && Array.isArray(error.data.errors)) {
      const errorMessages = error.data.errors.map((err: any) => err.message).join(', ')
      registerError.value = errorMessages
    } else {
      registerError.value = error?.data?.message || 'خطأ في إنشاء الحساب'
    }
  } finally {
    registerLoading.value = false
  }
}

// Handle login success
const handleLoginSuccess = () => {
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
// Login functions
async function handleLogin() {
  if (!loginForm.value.email || !loginForm.value.password) {
    loginError.value = t('login.required_fields') || 'جميع الحقول مطلوبة'
    return
  }
  
  loginLoading.value = true
  loginError.value = ''
  
  try {
    const response = await $post('v1/auth/login', {
      email_or_phone: loginForm.value.email,
      password: loginForm.value.password,
      type: 'email'
    })
    
    // Handle different response formats
    if (response?.access_token) {
      auth.setToken(response.access_token)
      // Try to get user info
      try {
        const userInfo = await $get('v1/customer/info')
        if (userInfo) auth.setUser(userInfo)
      } catch (e) {
        // If user info fails, still set token
        auth.setUser(response.user || response.data)
      }
      showLoginModal.value = false
      loginForm.value = { email: '', password: '' }
    } else if (response?.token) {
      auth.setToken(response.token)
      auth.setUser(response.user || response.data)
      showLoginModal.value = false
      loginForm.value = { email: '', password: '' }
    }
  } catch (error: any) {
    console.error('Login error:', error)
    // Handle validation errors
    if (error?.data?.errors && Array.isArray(error.data.errors)) {
      const errorMessages = error.data.errors.map((err: any) => err.message).join(', ')
      loginError.value = errorMessages
    } else {
      loginError.value = error?.data?.message || t('login.error') || 'خطأ في تسجيل الدخول'
    }
  } finally {
    loginLoading.value = false
  }
}

function openLoginModal() {
  showLoginModal.value = true
  loginError.value = ''
  loginForm.value = { email: '', password: '' }
}

// Wishlist functions
async function checkWishlistStatus() {
  if (!auth?.user?.value || !product.value?.id) return
  
  try {
    const response = await $get('v1/customer/wish-list/')
    if (response && Array.isArray(response)) {
      isInWishlist.value = response.some((item: any) => item.product_id === product.value.id)
    }
  } catch (error) {
    console.error('Error checking wishlist status:', error)
  }
}

async function toggleWishlist() {
  if (!auth?.user?.value) {
    showLoginModal.value = true
    return
  }
  
  if (!product.value?.id) return
  
  wishlistLoading.value = true
  
  try {
    if (isInWishlist.value) {
      // Remove from wishlist
      await $del('v1/customer/wish-list/remove', {
        params: { product_id: product.value.id }
      })
      isInWishlist.value = false
      console.log('تم إزالة المنتج من المفضلة')
    } else {
      // Add to wishlist
      await $post('v1/customer/wish-list/add', {
        product_id: product.value.id
      })
      isInWishlist.value = true
      console.log('تم إضافة المنتج إلى المفضلة')
    }
  } catch (error: any) {
    console.error('Error toggling wishlist:', error)
    if (error?.data?.message) {
      console.error('Error message:', error.data.message)
    }
  } finally {
    wishlistLoading.value = false
  }
}

function closeLoginModal() {
  showLoginModal.value = false
  loginError.value = ''
  loginForm.value = { email: '', password: '' }
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
  // Check wishlist status after product loads
  watch(product, () => {
    if (product.value) {
      checkWishlistStatus()
    }
  }, { immediate: true })
  
  // Check wishlist status when user logs in
  watch(() => auth?.user?.value, () => {
    if (auth?.user?.value && product.value) {
      checkWishlistStatus()
    }
  })
  
  // Add escape key listener for modals
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      if (showLoginModal.value) {
        closeLoginModal()
      } else if (showReviewModal.value) {
        closeReviewModal()
      } else if (showReplyModal.value) {
        closeReplyModal()
      }
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
          <button 
            class="wishlist-btn" 
            :class="{ active: isInWishlist }"
            @click="toggleWishlist"
            :disabled="wishlistLoading"
          >
            <svg v-if="wishlistLoading" width="20" height="20" viewBox="0 0 24 24" class="spinner">
              <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2" stroke-dasharray="31.416" stroke-dashoffset="31.416">
                <animate attributeName="stroke-dasharray" dur="2s" values="0 31.416;15.708 15.708;0 31.416" repeatCount="indefinite"/>
                <animate attributeName="stroke-dashoffset" dur="2s" values="0;-15.708;-31.416" repeatCount="indefinite"/>
              </circle>
            </svg>
            <svg v-else width="20" height="20" viewBox="0 0 24 24" :fill="isInWishlist ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2">
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
              <template v-if="auth?.user?.value">
                <button class="write-review-btn" @click="handleWriteReview">
                  اكتب تقييمك
                </button>
              </template>
              <template v-else>
                <button class="write-review-btn" @click="handleWriteReview" type="button">
                  تسجيل الدخول للتقيم
                </button>
              </template>
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
              
              <!-- Replies Section -->
              <div v-if="review.replies && review.replies.length > 0" class="replies-section">
                <div class="replies-header">
                  <h4>الردود ({{ review.replies_count || 0 }})</h4>
                </div>
                <div class="replies-list">
                  <div v-for="reply in review.replies" :key="reply.id" class="reply-item">
                    <div class="reply-header">
                      <span class="reply-author">{{ maskCustomerName(reply.customer?.f_name || reply.customer?.name || 'مجهول') }}</span>
                      <span class="reply-date">{{ formatDate(reply.created_at) }}</span>
                    </div>
                    <div class="reply-text">{{ reply.reply_text }}</div>
                  </div>
                </div>
              </div>
              
              <div class="review-actions">
                <button class="action-btn" @click="openReplyModal(review.id)">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M21.99 4c0-1.1-.89-2-2-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14l4 4-.01-18z"/>
                  </svg>
                  رد ({{ review.replies_count || 0 }})
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
    <teleport to="body">
      <div v-if="showLoginModal" class="login-overlay" @click.self="closeLoginModal">
        <div class="login-modal" dir="rtl">
          <div class="login-header">
            <h2>{{ t('login') || 'تسجيل الدخول' }}</h2>
            <button class="close-btn" @click="closeLoginModal">
              <svg width="20" height="20" viewBox="0 0 24 24"><path fill="currentColor" d="M19 6.41L17.59 5L12 10.59L6.41 5L5 6.41L10.59 12L5 17.59L6.41 19L12 13.41L17.59 19L19 17.59L13.41 12z"/></svg>
            </button>
          </div>
          
          <form @submit.prevent="handleLogin" class="login-form">
            <div class="form-group">
              <label for="email">{{ t('email') || 'البريد الإلكتروني' }}</label>
              <input 
                id="email"
                v-model="loginForm.email" 
                type="email" 
                :placeholder="t('email') || 'البريد الإلكتروني'"
                required
                :disabled="loginLoading"
              />
            </div>
            
            <div class="form-group">
              <label for="password">{{ t('password') || 'كلمة المرور' }}</label>
              <input 
                id="password"
                v-model="loginForm.password" 
                type="password" 
                :placeholder="t('password') || 'كلمة المرور'"
                required
                :disabled="loginLoading"
              />
            </div>
            
            <div v-if="loginError" class="error-message">
              {{ loginError }}
            </div>
            
            <button type="submit" class="login-btn" :disabled="loginLoading">
              <span v-if="loginLoading">{{ t('loading') || 'جاري التحميل...' }}</span>
              <span v-else>{{ t('login') || 'تسجيل الدخول' }}</span>
            </button>
          </form>
          
          <div class="login-footer">
            <p>ليس لديك حساب؟ <a href="#" @click.prevent="handleRegister">إنشاء حساب جديد</a></p>
          </div>
        </div>
      </div>
    </teleport>

    <!-- Register Modal -->
    <teleport to="body">
      <div v-if="showRegisterModal" class="login-overlay" @click.self="closeRegisterModal">
        <div class="login-modal" dir="rtl">
          <div class="login-header">
            <h2>إنشاء حساب جديد</h2>
            <button class="close-btn" @click="closeRegisterModal">
              <svg width="20" height="20" viewBox="0 0 24 24"><path fill="currentColor" d="M19 6.41L17.59 5L12 10.59L6.41 5L5 6.41L10.59 12L5 17.59L6.41 19L12 13.41L17.59 19L19 17.59L13.41 12z"/></svg>
            </button>
          </div>
          
          <form @submit.prevent="handleRegisterSubmit" class="login-form">
            <div class="form-row">
              <div class="form-group">
                <label for="f_name">الاسم الأول *</label>
                <input 
                  id="f_name" 
                  v-model="registerForm.f_name" 
                  type="text" 
                  placeholder="الاسم الأول" 
                  required 
                  :disabled="registerLoading" 
                />
              </div>
              <div class="form-group">
                <label for="l_name">الاسم الأخير *</label>
                <input 
                  id="l_name" 
                  v-model="registerForm.l_name" 
                  type="text" 
                  placeholder="الاسم الأخير" 
                  required 
                  :disabled="registerLoading" 
                />
              </div>
            </div>

            <div class="form-group">
              <label for="register_email">البريد الإلكتروني *</label>
              <input 
                id="register_email" 
                v-model="registerForm.email" 
                type="email" 
                placeholder="البريد الإلكتروني" 
                required 
                :disabled="registerLoading" 
              />
            </div>

            <div class="form-group">
              <label for="register_phone">رقم الهاتف *</label>
              <input 
                id="register_phone" 
                v-model="registerForm.phone" 
                type="tel" 
                placeholder="رقم الهاتف" 
                required 
                :disabled="registerLoading" 
              />
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="register_password">كلمة المرور *</label>
                <input 
                  id="register_password" 
                  v-model="registerForm.password" 
                  type="password" 
                  placeholder="كلمة المرور" 
                  required 
                  :disabled="registerLoading" 
                />
              </div>
              <div class="form-group">
                <label for="password_confirmation">تأكيد كلمة المرور *</label>
                <input 
                  id="password_confirmation" 
                  v-model="registerForm.password_confirmation" 
                  type="password" 
                  placeholder="تأكيد كلمة المرور" 
                  required 
                  :disabled="registerLoading" 
                />
              </div>
            </div>

            <div class="form-group">
              <label for="referral_code">كود الإحالة (اختياري)</label>
              <input 
                id="referral_code" 
                v-model="registerForm.referral_code" 
                type="text" 
                placeholder="كود الإحالة" 
                :disabled="registerLoading" 
              />
            </div>

            <div v-if="registerError" class="error-message">
              {{ registerError }}
            </div>

            <button type="submit" class="login-btn" :disabled="registerLoading">
              <span v-if="registerLoading">جاري إنشاء الحساب...</span>
              <span v-else>إنشاء حساب</span>
            </button>
          </form>
          
          <div class="login-footer">
            <p>لديك حساب بالفعل؟ <a href="#" @click.prevent="showLoginModal = true; closeRegisterModal()">تسجيل الدخول</a></p>
          </div>
        </div>
      </div>
    </teleport>

    <!-- Review Modal -->
    <teleport to="body">
      <div v-if="showReviewModal" class="review-overlay" @click.self="closeReviewModal">
        <div class="review-modal" dir="rtl">
          <div class="review-header">
            <h2>اكتب تقييمك</h2>
            <button class="close-btn" @click="closeReviewModal">
              <svg width="20" height="20" viewBox="0 0 24 24"><path fill="currentColor" d="M19 6.41L17.59 5L12 10.59L6.41 5L5 6.41L10.59 12L5 17.59L6.41 19L12 13.41L17.59 19L19 17.59L13.41 12z"/></svg>
            </button>
          </div>
          
          <form @submit.prevent="submitReview" class="review-form">
            <div class="form-group">
              <label>تقييمك</label>
              <div class="rating-input">
                <span 
                  v-for="i in 5" 
                  :key="i" 
                  class="star-input" 
                  :class="{ filled: i <= reviewForm.rating }"
                  @click="reviewForm.rating = i"
                >
                  ★
                </span>
              </div>
            </div>
            
            <div class="form-group">
              <label for="comment">تعليقك</label>
              <textarea 
                id="comment"
                v-model="reviewForm.comment" 
                :placeholder="'اكتب تعليقك عن المنتج...'"
                rows="4"
                required
                :disabled="reviewLoading"
              ></textarea>
            </div>
            
            <div v-if="reviewError" class="error-message">
              {{ reviewError }}
            </div>
            
            <button type="submit" class="submit-btn" :disabled="reviewLoading">
              <span v-if="reviewLoading">جاري الإرسال...</span>
              <span v-else>إرسال التقييم</span>
            </button>
          </form>
        </div>
      </div>
    </teleport>

    <!-- Reply Modal -->
    <teleport to="body">
      <div v-if="showReplyModal" class="reply-overlay" @click.self="closeReplyModal">
        <div class="reply-modal" dir="rtl">
          <div class="reply-header">
            <h2>أضف رد</h2>
            <button class="close-btn" @click="closeReplyModal">
              <svg width="20" height="20" viewBox="0 0 24 24"><path fill="currentColor" d="M19 6.41L17.59 5L12 10.59L6.41 5L5 6.41L10.59 12L5 17.59L6.41 19L12 13.41L17.59 19L19 17.59L13.41 12z"/></svg>
            </button>
          </div>
          
          <form @submit.prevent="submitReply" class="reply-form">
            <div class="form-group">
              <label for="reply-text">ردك</label>
              <textarea 
                id="reply-text"
                v-model="replyText" 
                :placeholder="'اكتب ردك...'"
                rows="4"
                required
                :disabled="replyLoading"
              ></textarea>
            </div>
            
            <div v-if="replyError" class="error-message">
              {{ replyError }}
            </div>
            
            <button type="submit" class="submit-btn" :disabled="replyLoading">
              <span v-if="replyLoading">جاري الإرسال...</span>
              <span v-else>إرسال الرد</span>
            </button>
          </form>
        </div>
      </div>
    </teleport>
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
  .wishlist-btn{ 
    background:none; 
    border:none; 
    cursor:pointer; 
    color:#6b7280; 
    transition:all 0.3s ease;
    padding: 8px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
  }
  .wishlist-btn:hover{ 
    color:#ef4444; 
    background: rgba(239, 68, 68, 0.1);
    transform: scale(1.1);
  }
  .wishlist-btn.active {
    color: #ef4444;
    background: rgba(239, 68, 68, 0.1);
  }
  .wishlist-btn:disabled {
    cursor: not-allowed;
    opacity: 0.6;
  }
  .wishlist-btn .spinner {
    animation: spin 1s linear infinite;
  }
  @keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
  }

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

  /* Login Modal */
  .login-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 70;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
  }

  .login-modal {
    background: white;
    border-radius: 12px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    width: 100%;
    max-width: 400px;
    max-height: 90vh;
    overflow-y: auto;
  }

  .login-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 24px 24px 0;
  }

  .login-header h2 {
    font-size: 20px;
    font-weight: 700;
    color: #111827;
    margin: 0;
  }

  .close-btn {
    background: none;
    border: none;
    color: #6b7280;
    cursor: pointer;
    padding: 8px;
    border-radius: 6px;
    transition: background-color 0.2s ease;
  }

  .close-btn:hover {
    background: #f3f4f6;
  }

  .login-form {
    padding: 24px;
  }

.form-group {
  margin-bottom: 20px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

  .form-group label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
  }

  .form-group input {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 16px;
    transition: border-color 0.2s ease;
    box-sizing: border-box;
  }

  .form-group input:focus {
    outline: none;
    border-color: #6b46c1;
    box-shadow: 0 0 0 3px rgba(107, 70, 193, 0.1);
  }

  .login-btn {
    width: 100%;
    background: #6b46c1;
    color: #fff;
    border: none;
    padding: 12px 16px;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s ease;
  }

  .login-btn:hover:not(:disabled) {
    background: #553c9a;
  }

  .login-btn:disabled {
    background: #ccc;
    cursor: not-allowed;
  }

  .error-message {
    background: #fee;
    color: #c53030;
    padding: 10px 12px;
    border-radius: 6px;
    font-size: 14px;
    margin-bottom: 16px;
    border: 1px solid #feb2b2;
  }

  .login-footer {
    padding: 16px 24px 24px;
    text-align: center;
    border-top: 1px solid #e5e7eb;
  }

  .login-footer p {
    margin: 0;
    color: #6b7280;
    font-size: 14px;
  }

  .login-footer a {
    color: #6b46c1;
    text-decoration: none;
    font-weight: 600;
  }

  .login-footer a:hover {
    text-decoration: underline;
  }
  
  .form-row {
    grid-template-columns: 1fr;
    gap: 12px;
  }

  /* Review Modal */
  .review-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 80;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
  }

  .review-modal {
    background: white;
    border-radius: 12px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    width: 100%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
  }

  .review-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 24px 24px 0;
  }

  .review-header h2 {
    font-size: 20px;
    font-weight: 700;
    color: #111827;
    margin: 0;
  }

  .review-form {
    padding: 24px;
  }

  .rating-input {
    display: flex;
    gap: 8px;
    margin-top: 8px;
  }

  .star-input {
    font-size: 32px;
    color: #d1d5db;
    cursor: pointer;
    transition: color 0.2s ease;
    user-select: none;
  }

  .star-input:hover {
    color: #f59e0b;
  }

  .star-input.filled {
    color: #f59e0b;
  }

  .form-group textarea {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 16px;
    transition: border-color 0.2s ease;
    box-sizing: border-box;
    resize: vertical;
    min-height: 100px;
    font-family: inherit;
  }

  .form-group textarea:focus {
    outline: none;
    border-color: #6b46c1;
    box-shadow: 0 0 0 3px rgba(107, 70, 193, 0.1);
  }

  .submit-btn {
    width: 100%;
    background: #6b46c1;
    color: #fff;
    border: none;
    padding: 12px 16px;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s ease;
  }

  .submit-btn:hover:not(:disabled) {
    background: #553c9a;
  }

  .submit-btn:disabled {
    background: #ccc;
    cursor: not-allowed;
  }

  /* Replies Section */
  .replies-section {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid #e5e7eb;
  }

  .replies-header h4 {
    margin: 0 0 12px;
    font-size: 14px;
    font-weight: 600;
    color: #374151;
  }

  .replies-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  .reply-item {
    background: #f9fafb;
    border-radius: 8px;
    padding: 12px;
    border: 1px solid #e5e7eb;
  }

  .reply-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
  }

  .reply-author {
    font-weight: 600;
    color: #111827;
    font-size: 13px;
  }

  .reply-date {
    color: #9ca3af;
    font-size: 12px;
  }

  .reply-text {
    color: #6b7280;
    line-height: 1.5;
    font-size: 14px;
  }

  /* Reply Modal */
  .reply-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 90;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
  }

  .reply-modal {
    background: white;
    border-radius: 12px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    width: 100%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
  }

  .reply-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 24px 24px 0;
  }

  .reply-header h2 {
    font-size: 20px;
    font-weight: 700;
    color: #111827;
    margin: 0;
  }

  .reply-form {
    padding: 24px;
  }

  /* Enhanced Action Buttons */
  .action-btn {
    background: none;
    border: none;
    color: #6b7280;
    cursor: pointer;
    font-size: 12px;
    display: flex;
    align-items: center;
    gap: 4px;
    transition: color 0.2s;
    padding: 8px 12px;
    border-radius: 6px;
    transition: all 0.2s;
  }

  .action-btn:hover {
    color: #111827;
    background: #f3f4f6;
  }

  .action-btn svg {
    width: 16px;
    height: 16px;
  }
</style>
