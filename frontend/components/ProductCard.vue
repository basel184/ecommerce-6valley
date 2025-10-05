<script setup lang="ts">
import { computed, ref, watchEffect } from 'vue'
import { useBrands } from '../composables/useBrands'
const props = defineProps<{ product: any; qty?: number; busy?: boolean }>()
const emit = defineEmits<{
  (e: 'wishlist', product: any): void;
  (e: 'add', product: any): void;
  (e: 'update', payload: { product: any; qty: number }): void;
  (e: 'remove', product: any): void;
}>()

const cfg = useRuntimeConfig() as any
const assetBase = (cfg?.public?.apiBase || 'http://127.0.0.1:8000/api').replace(/\/api(?:\/v\d+)?$/, '')
const fixPath = (s: string) => {
  let p = s.trim().replace(/\\/g, '/')
  
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
// Normalize various backend image shapes: string path/URL, JSON string, object with path/url, or arrays
const normalize = (s: any): string => {
  if (!s) return ''
  // If array, take first
  if (Array.isArray(s)) return normalize(s[0])
  let v: any = s
  if (typeof s === 'string') {
    const trimmed = s.trim()
    // Attempt to parse JSON arrays/objects like images: "[...]"
    if ((trimmed.startsWith('[') || trimmed.startsWith('{'))) {
      try {
        const parsed = JSON.parse(trimmed)
        return normalize(parsed)
      } catch {
        // not JSON; continue as plain string
      }
    }
    v = trimmed
  } else if (typeof s === 'object') {
    // common keys: path/url/image, or nested objects with .path
    v = (s as any).path || (s as any).url || (s as any).image || ''
  }
  v = (typeof v === 'string' ? v : '').trim()
  if (!v) return ''
  if (/^(https?:|data:|blob:)/i.test(v)) return v
  const fixedPath = fixPath(v)
  const result = `${assetBase}/${fixedPath}`
  return result
}

const link = computed(() => {
  const p: any = props.product || {}
  const slug = p?.slug || p?.product?.slug
  return slug ? `/product/${encodeURIComponent(String(slug))}` : ''
})
const imgSrc = computed(() => {
  const p: any = props.product || {}
  const raw =
    // Prefer full URLs if provided (can be object with path or array)
    p?.thumbnail_full_url ||
    p?.image_full_url ||
    p?.photo_full_url ||
    p?.images_full_url ||
    // Fall back to simple fields
    p?.thumbnail ||
    p?.image ||
    p?.photo ||
    p?.images ||
    p?.gallery_images ||
    // Nested under product
    p?.product?.thumbnail_full_url ||
    p?.product?.image_full_url ||
    p?.product?.photo_full_url ||
    p?.product?.images_full_url ||
    p?.product?.thumbnail ||
    p?.product?.image ||
    p?.product?.photo ||
    p?.product?.images ||
    p?.product?.gallery_images ||
    ''
  const result = normalize(raw)
  return result
})
const title = computed(() => {
  const p: any = props.product || {}
  return p?.name || p?.product_name || p?.product?.name || p?.product?.product_name || 'Product'
})
const { ensure: ensureBrands, nameOf: brandNameOf, ensureBrand } = useBrands() as any
await ensureBrands()
// Ensure specific brand is loaded if only brand_id provided
watchEffect(async () => {
  const p: any = props.product || {}
  if (p?.brand_id) {
    try { await ensureBrand(p.brand_id) } catch {}
  }
})
const brandName = computed(() => {
  const p: any = props.product || {}
  return p?.brand_name || p?.brand?.name || p?.product?.brand_name || p?.product?.brand?.name || brandNameOf(p?.brand_id)
})
// Pricing & discount
const basePrice = computed<number>(() => {
  const p: any = props.product || {}
  const v = p?.unit_price ?? p?.price ?? p?.product?.unit_price ?? p?.product?.price
  const n = Number(v)
  return isFinite(n) && n > 0 ? n : 0
})
const discountValue = computed<number>(() => {
  const p: any = props.product || {}
  const v = p?.discount ?? p?.product?.discount ?? 0
  const n = Number(v)
  return isFinite(n) && n > 0 ? n : 0
})
const discountType = computed<string>(() => {
  const p: any = props.product || {}
  return p?.discount_type || p?.product?.discount_type || 'flat'
})
const oldPrice = computed<number>(() => basePrice.value)
const finalPrice = computed<number>(() => {
  const bp = basePrice.value
  const dv = discountValue.value
  if (!bp || !dv) return bp
  const isPercent = String(discountType.value).toLowerCase().startsWith('per')
  const diff = isPercent ? (bp * dv) / 100 : dv
  return Math.max(0, bp - diff)
})
const hasDiscount = computed<boolean>(() => finalPrice.value > 0 && finalPrice.value < oldPrice.value)
const discountPercent = computed<number>(() => {
  const bp = oldPrice.value
  const dv = discountValue.value
  if (!bp || !dv) return 0
  const isPercent = String(discountType.value).toLowerCase().startsWith('per')
  return Math.max(0, Math.round(isPercent ? dv : (dv / bp) * 100))
})
const formatPrice = (n: number) => {
  if (!isFinite(n)) return ''
  try { return n.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 }) }
  catch { return String(n) }
}
// Rating (0..5)
const rating = computed<number>(() => {
  const p: any = props.product || {}
  const r = p?.reviews_avg_rating ?? p?.avg_rating ?? p?.rating?.[0]?.average ?? 0
  const n = Number(r)
  return isFinite(n) && n >= 0 ? Math.min(5, Math.max(0, n)) : 0
})
const reviewsCount = computed<number>(() => {
  const p: any = props.product || {}
  const c = p?.reviews_count ?? p?.rating?.[0]?.count ?? 0
  const n = Number(c)
  return isFinite(n) && n >= 0 ? Math.round(n) : 0
})
const onErr = (e: any) => {
  e.target.src = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="400" height="400"><rect width="100%" height="100%" fill="%23f3f4f6"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="%239ca3af" font-size="16">No image</text></svg>'
}

// Wishlist + Add to cart
const initialWished = computed(() => {
  const p: any = props.product || {}
  return !!(p?.is_wishlisted || p?.in_wishlist || (Array.isArray(p?.wish_list) && p.wish_list.length > 0))
})
const wished = ref<boolean>(initialWished.value)
const wishlistLoading = ref(false)

const toggleWish = async (e: Event) => {
  e.preventDefault()
  e.stopPropagation()
  
  // Check if user is logged in
  const auth = useAuth()
  if (!auth?.user?.value) {
    // Show login modal or redirect to login
    emit('wishlist', props.product)
    return
  }
  
  if (wishlistLoading.value) return
  
  wishlistLoading.value = true
  
  try {
    const { $post, $del } = useApi()
    const productId = props.product?.id || props.product?.product_id
    
    if (!productId) {
      throw new Error('Product ID not found')
    }
    
    if (wished.value) {
      // Remove from wishlist
      await $del('v1/customer/wish-list/remove', {
        params: {
          product_id: productId
        }
      })
      wished.value = false
      
      // Show success message
      console.log('تم إزالة المنتج من المفضلة')
    } else {
      // Add to wishlist
      await $post('v1/customer/wish-list/add', {
        product_id: productId
      })
      wished.value = true
      
      // Show success message
      console.log('تم إضافة المنتج إلى المفضلة')
    }
  } catch (error: any) {
    console.error('Wishlist error:', error)
    // Revert the state on error
    wished.value = !wished.value
    
    // Show error message
    console.error('حدث خطأ أثناء تحديث المفضلة:', error?.data?.message || error.message)
  } finally {
    wishlistLoading.value = false
  }
}
const currencySymbol = computed(() => {
  const p: any = props.product || {}
  return p?.currency_symbol || '﷼'
})
// Cart controls with quantity
const qty = ref<number>(props.qty ?? 0)
watchEffect(() => {
  if (typeof props.qty === 'number' && props.qty !== qty.value) {
    qty.value = props.qty
  }
})
const isBusy = computed(() => !!props.busy)
const handleAdd = (e: Event) => {
  e.preventDefault(); e.stopPropagation()
  if (isBusy.value) return
  if (qty.value <= 0) {
    qty.value = 1
    emit('add', props.product)
  } else {
    qty.value = qty.value + 1
    emit('update', { product: props.product, qty: qty.value })
  }
}
const inc = (e: Event) => {
  e.preventDefault(); e.stopPropagation();
  if (isBusy.value) return
  qty.value = qty.value + 1
  emit('update', { product: props.product, qty: qty.value })
}
const dec = (e: Event) => {
  e.preventDefault(); e.stopPropagation();
  if (isBusy.value) return
  if (qty.value > 1) {
    qty.value = qty.value - 1
    emit('update', { product: props.product, qty: qty.value })
  } else if (qty.value === 1) {
    qty.value = 0
    emit('remove', props.product)
  }
}
const clearQty = (e: Event) => { if (isBusy.value) return; e.preventDefault(); e.stopPropagation(); qty.value = 0; emit('remove', props.product) }

// Promotional chip
const promoChip = computed<{ text: string; tone: 'green' | 'pink' | 'blue' } | null>(() => {
  const p: any = props.product || {}
  if (p?.flash_deal_status || p?.flash_deal) return { text: 'عرض اليوم الوطني', tone: 'green' }
  if (p?.bogo || p?.offer_type === 'buy_one_get_one') return { text: '1+1 مجاناً', tone: 'pink' }
  if ((p?.order_details_count ?? 0) > 500) return { text: 'الأفضل مبيعًا', tone: 'blue' }
  if (p?.is_bestseller) return { text: 'الأفضل مبيعًا', tone: 'blue' }
  return null
})
const promoText = computed(() => promoChip.value?.text || '')
const promoTone = computed(() => promoChip.value?.tone || '')
// Stock
const inStock = computed<boolean>(() => {
  const p: any = props.product || {}
  const q = p?.current_stock ?? p?.stock ?? p?.quantity ?? p?.product?.current_stock ?? p?.product?.stock ?? 0
  return (Number(q) > 0) || p?.in_stock === true
})
</script>

<template>
  <NuxtLink :to="(link as any) || '#'" class="card" dir="rtl" :aria-disabled="!(link as any)" @click="!(link as any) && $event.preventDefault()">
    <div class="thumb">
      <!-- wishlist at top-left -->
      <button 
        class="fab wish" 
        :class="{ on: wished, loading: wishlistLoading }" 
        @click="toggleWish" 
        :disabled="wishlistLoading"
        aria-label="Wishlist"
      >
        <svg v-if="!wishlistLoading" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 122.88 109.57" style="enable-background:new 0 0 122.88 109.57" xml:space="preserve"><g><path d="M65.46,19.57c-0.68,0.72-1.36,1.45-2.2,2.32l-2.31,2.41l-2.4-2.33c-0.71-0.69-1.43-1.4-2.13-2.09 c-7.42-7.3-13.01-12.8-24.52-12.95c-0.45-0.01-0.93,0-1.43,0.02c-6.44,0.23-12.38,2.6-16.72,6.65c-4.28,4-7.01,9.67-7.1,16.57 c-0.01,0.43,0,0.88,0.02,1.37c0.69,19.27,19.13,36.08,34.42,50.01c2.95,2.69,5.78,5.27,8.49,7.88l11.26,10.85l14.15-14.04 c2.28-2.26,4.86-4.73,7.62-7.37c4.69-4.5,9.91-9.49,14.77-14.52c3.49-3.61,6.8-7.24,9.61-10.73c2.76-3.42,5.02-6.67,6.47-9.57 c2.38-4.76,3.13-9.52,2.62-13.97c-0.5-4.39-2.23-8.49-4.82-11.99c-2.63-3.55-6.13-6.49-10.14-8.5C96.5,7.29,91.21,6.2,85.8,6.82 C76.47,7.9,71.5,13.17,65.46,19.57L65.46,19.57z M60.77,14.85C67.67,7.54,73.4,1.55,85.04,0.22c6.72-0.77,13.3,0.57,19.03,3.45 c4.95,2.48,9.27,6.1,12.51,10.47c3.27,4.42,5.46,9.61,6.1,15.19c0.65,5.66-0.29,11.69-3.3,17.69c-1.7,3.39-4.22,7.03-7.23,10.76 c-2.95,3.66-6.39,7.44-10,11.17C97.2,74.08,91.94,79.12,87.2,83.66c-2.77,2.65-5.36,5.13-7.54,7.29L63.2,107.28l-2.31,2.29 l-2.34-2.25l-13.6-13.1c-2.49-2.39-5.37-5.02-8.36-7.75C20.38,71.68,0.81,53.85,0.02,31.77C0,31.23,0,30.67,0,30.09 c0.12-8.86,3.66-16.18,9.21-21.36c5.5-5.13,12.97-8.13,21.01-8.42c0.55-0.02,1.13-0.03,1.74-0.02C46,0.48,52.42,6.63,60.77,14.85 L60.77,14.85z"/></g></svg>
        <div v-else class="wishlist-spinner"></div>
      </button>
      <img :src="(imgSrc as any)" :alt="(title as any)" @error="onErr" />
      
      <!-- promo below image -->
      <div v-if="promoText" class="chip" :class="promoTone">{{ promoText }}</div>
      
      <!-- add/qty area bottom-left; lock if out of stock -->
      <div class="cart-ctrl" :class="{ oos: !inStock, busy: (isBusy as any) }">
        <template v-if="inStock">
          <template v-if="qty > 0">
            <button class="ctrl-btn" v-if="qty > 1" @click="dec" :disabled="(isBusy as any)" aria-label="Minus">
              <svg width="18" height="18" viewBox="0 0 24 24"><path fill="currentColor" d="M19 13H5v-2h14v2Z"/></svg>
            </button>
            <button class="ctrl-btn" v-else @click="dec" :disabled="(isBusy as any)" aria-label="Remove">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bag-x" viewBox="0 0 16 16">
              <path fill-rule="evenodd" d="M6.146 8.146a.5.5 0 0 1 .708 0L8 9.293l1.146-1.147a.5.5 0 1 1 .708.708L8.707 10l1.147 1.146a.5.5 0 0 1-.708.708L8 10.707l-1.146 1.147a.5.5 0 0 1-.708-.708L7.293 10 6.146 8.854a.5.5 0 0 1 0-.708"/>
              <path d="M8 1a2.5 2.5 0 0 1 2.5 2.5V4h-5v-.5A2.5 2.5 0 0 1 8 1m3.5 3v-.5a3.5 3.5 0 1 0-7 0V4H1v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V4zM2 5h12v9a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1z"/>
            </svg>            </button>
            <span class="qty">{{ qty }}</span>
            <button class="ctrl-btn" @click="inc" :disabled="(isBusy as any)" aria-label="Plus">
              <svg width="18" height="18" viewBox="0 0 24 24"><path fill="currentColor" d="M11 11V6h2v5h5v2h-5v5h-2v-5H6v-2z"/></svg>
            </button>
          </template>
          <template v-else>
            <button class="ctrl-btn" @click="handleAdd" :disabled="(isBusy as any)" aria-label="Add to cart">
              <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" class="bi bi-bag" viewBox="0 0 16 16">
                <path d="M8 1a2.5 2.5 0 0 1 2.5 2.5V4h-5v-.5A2.5 2.5 0 0 1 8 1m3.5 3v-.5a3.5 3.5 0 1 0-7 0V4H1v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V4zM2 5h12v9a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1z"/>
              </svg>
            </button>
          </template>
        </template>
        <template v-else>
          <span class="lock" title="غير متوفر">
            <svg width="18" height="18" viewBox="0 0 24 24"><path fill="currentColor" d="M12 17a2 2 0 0 0 2-2v-2a2 2 0 1 0-4 0v2a2 2 0 0 0 2 2m6-6h-1V9a5 5 0 0 0-10 0v2H6a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2m-3 0H9V9a3 3 0 0 1 6 0z"/></svg>
          </span>
        </template>
        <span v-if="(isBusy as any)" class="mini-spin" aria-hidden="true"></span>
      </div>
    </div>
    
    <div class="info">
      <!-- Brand and Title -->
      <div class="brand" v-if="brandName">{{ brandName }}</div>
      <div class="meta">
        <div class="stars" aria-label="rating">
          <span class="star" :class="{ on: true }">★</span>
          <b class="rating-num">{{ (rating as any)?.toFixed ? (rating as any).toFixed(1) : rating }}</b>
          <span class="count">({{ reviewsCount }})</span>
        </div>
      </div>
      <div class="title">{{ title }}</div>
      
      <!-- Pricing -->
      <div class="pricing-section">
        <div class="price-row">
          <span class="price final">{{ formatPrice((finalPrice as any)) }} {{ currencySymbol }}</span>
          <div v-if="hasDiscount" class="badge">-{{ discountPercent }}%</div>
        </div>
        <div v-if="hasDiscount" class="old-price-row">
          <span class="old">{{ formatPrice((oldPrice as any)) }} {{ currencySymbol }}</span>
          <span class="save-amount">حفظ {{ formatPrice((oldPrice as any) - (finalPrice as any)) }} {{ currencySymbol }}</span>
        </div>
      </div>
    </div>
  </NuxtLink>
</template>

<style scoped>
.card { 
  display: block; 
  border: 1px solid #e5e7eb; 
  border-radius: 12px; 
  overflow: hidden; 
  background: #fff; 
  transition: transform 0.2s ease, box-shadow 0.2s ease; 
  direction: rtl;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}
.card:hover { 
  transform: translateY(-2px); 
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15); 
}

.thumb { 
  aspect-ratio: 1 / 1; 
  background: #f9fafb; 
  display: flex; 
  align-items: center; 
  justify-content: center; 
  overflow: hidden; 
  position: relative;
  padding: 20px;
}
img { 
  width: 100%; 
  height: 100%; 
  object-fit: contain; 
  max-width: 200px;
  max-height: 200px;
}

.badge { 
  background: #10b981; 
  color: #fff; 
  font-weight: 700; 
  font-size: 11px; 
  padding: 4px 8px; 
  border-radius: 6px;
  display: inline-block;
  min-width: 32px;
  text-align: center;
}

.chip { 
  position: absolute; 
  left: 50%;
  transform: translateX(-50%);
  bottom: 10px; 
  text-align: center; 
  padding: 6px 12px; 
  font-size: 12px; 
  font-weight: 700; 
  border-radius: 20px; 
  color: #fff;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
.chip.green { background: #10b981 }
.chip.pink { background: #ec4899 }
.chip.blue { background: #3b82f6 }

.fab { 
  position: absolute; 
  width: 32px; 
  height: 32px; 
  border-radius: 50%; 
  display: flex; 
  align-items: center; 
  justify-content: center; 
  background: rgba(255, 255, 255, 0.9); 
  color: #374151; 
  border: 1px solid #e5e7eb; 
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); 
  cursor: pointer; 
  transition: all 0.2s ease;
  backdrop-filter: blur(4px);
}
.fab:hover { 
  transform: scale(1.1); 
  background: #fff;
}
.fab.wish { 
  left: 12px; 
  top: 12px; 
  transition: all 0.3s ease;
  z-index: 10;
}
.fab.wish:hover {
  transform: scale(1.1);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
.fab.wish.on { 
  color: #ef4444; 
  border-color: #fecaca; 
  background: #fff0f0;
  animation: wishlist-pulse 0.6s ease-out;
}
.fab.wish.on:hover {
  background: #fecaca;
  transform: scale(1.15);
}
.fab.wish.loading {
  pointer-events: none;
  opacity: 0.7;
  transform: scale(0.95);
}
.wishlist-spinner {
  width: 20px;
  height: 20px;
  border: 2px solid #f3f4f6;
  border-top: 2px solid #ef4444;
  border-radius: 50%;
  animation: wishlist-spin 1s linear infinite;
}
@keyframes wishlist-spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
@keyframes wishlist-pulse {
  0% { transform: scale(1); }
  50% { transform: scale(1.2); }
  100% { transform: scale(1); }
}

.cart-ctrl { 
  position: absolute; 
  left: 12px; 
  bottom: 12px; 
  display: inline-flex; 
  align-items: center; 
  gap: 8px; 
  background: rgba(255, 255, 255, 0.95); 
  border: 1px solid #ffffff; 
  border-radius: 50%; 
  padding: 0; 
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  backdrop-filter: blur(4px);
}
.cart-ctrl.oos { opacity: 0.75 }
.cart-ctrl.busy { opacity: 0.85 }

.ctrl-btn { 
  width: 35px; 
  height: 30px; 
  border-radius: 50%; 
  display: inline-flex; 
  align-items: center; 
  justify-content: center; 
  border: 1px solid #ffffff; 
  background: #fff; 
  cursor: pointer;
  transition: all 0.2s ease;
}
.ctrl-btn:hover {
  background: #f3f4f6;
}
.ctrl-btn[disabled] { 
  opacity: 0.5; 
  cursor: not-allowed; 
}

.qty { 
  min-width: 20px; 
  text-align: center; 
  font-weight: 700; 
  font-size: 14px;
}

.lock { 
  display: inline-flex; 
  align-items: center; 
  justify-content: center; 
  color: #6b7280; 
}

.mini-spin { 
  width: 14px; 
  height: 14px; 
  border-radius: 50%; 
  border: 2px solid #e5e7eb; 
  border-top-color: #9ca3af; 
  animation: spin 0.8s linear infinite; 
}

@keyframes spin { 
  to { transform: rotate(360deg) } 
}

.info { 
  padding: 16px; 
}

.meta { 
  display: inline-block; 
  width : 50%;
  margin-bottom: 8px; 
}

.stars { 
  color: #f59e0b; 
  font-size: 14px; 
  display: flex; 
  align-items: center; 
  justify-content: flex-end;
  gap: 4px; 
}

.star {
  font-size: 16px;
}

.rating-num { 
  color: #111827; 
  font-weight: 700; 
  font-size: 14px; 
}

.count { 
  color: #6b7280; 
  font-size: 12px; 
}

.brand { 
  color: #6b7280; 
  font-weight: 700; 
  font-size: 14px; 
  margin-bottom: 4px; 
  display: inline-block;
  width : 50%;
}

.title { 
  font-weight: 600; 
  color: #111827; 
  margin-bottom: 12px; 
  line-height: 1.4; 
  display: -webkit-box; 
  -webkit-line-clamp: 2; 
  line-clamp: 2; 
  -webkit-box-orient: vertical; 
  overflow: hidden; 
  text-align: right;
  font-size: 14px;
}

.pricing-section {
  margin-bottom: 12px;
}

.price-row { 
  display: flex; 
  align-items: center; 
  gap: 8px; 
  justify-content: space-between;
  margin-bottom: 4px;
}

.price.final { 
  color: #ef4444; 
  font-weight: 800; 
  font-size: 18px;
}

.old-price-row {
  display: flex;
  align-items: center;
  gap: 8px;
  justify-content: space-between;
}

.old { 
  color: #9ca3af; 
  text-decoration: line-through; 
  font-size: 14px;
}

.save-amount {
  color: #10b981;
  font-size: 12px;
  font-weight: 600;
}

.add-to-cart-btn {
  width: 100%;
  background: #fff;
  color: #374151;
  border: 1px solid #d1d5db;
  padding: 10px 16px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  text-align: center;
}

.add-to-cart-btn:hover:not(:disabled) {
  background: #f9fafb;
  border-color: #9ca3af;
}

.add-to-cart-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
