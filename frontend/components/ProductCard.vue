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
const assetBase = (cfg?.public?.apiBase || '').replace(/\/api(?:\/v\d+)?$/, '')
const fixPath = (s: string) => {
  let p = s.trim().replace(/\\/g, '/').replace(/^public\//, '').replace(/^app\/public\//, 'storage/')
  p = p.replace(/\/+/g, '/').replace(/^\//, '')
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
  return `${assetBase}/${fixPath(v)}`
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
  return normalize(raw)
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
const toggleWish = (e: Event) => {
  e.preventDefault()
  e.stopPropagation()
  wished.value = !wished.value
  emit('wishlist', props.product)
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
  if ((p?.order_details_count ?? 0) > 500) return { text: 'الأكثر شهرة', tone: 'green' }
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
      <button class="fab wish" :class="{ on: wished }" @click="toggleWish" aria-label="Wishlist">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M12 21s-6.716-4.584-9.428-7.296C.86 12.99.5 11.858.5 10.68.5 7.962 2.74 5.75 5.44 5.75c1.34 0 2.64.538 3.56 1.492A5.022 5.022 0 0 1 12 5.75c1.34 0 2.64.538 3.56 1.492a4.99 4.99 0 0 1 1.492 3.438c0 1.178-.36 2.31-2.072 3.024C18.716 16.416 12 21 12 21z"/></svg>
      </button>
      <!-- promo top center -->
      <div v-if="promoText" class="chip" :class="promoTone">{{ promoText }}</div>
      <img :src="(imgSrc as any)" :alt="(title as any)" @error="onErr" />
      
      <!-- add/qty area bottom-left; lock if out of stock -->
      <div class="cart-ctrl" :class="{ oos: !inStock, busy: (isBusy as any) }">
        <template v-if="inStock">
          <template v-if="qty > 0">
            <button class="ctrl-btn" v-if="qty > 1" @click="dec" :disabled="(isBusy as any)" aria-label="Minus">
              <svg width="18" height="18" viewBox="0 0 24 24"><path fill="currentColor" d="M19 13H5v-2h14v2Z"/></svg>
            </button>
            <button class="ctrl-btn" v-else @click="dec" :disabled="(isBusy as any)" aria-label="Remove">
              <svg width="18" height="18" viewBox="0 0 24 24"><path fill="currentColor" d="M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6Zm12-14h-3.5l-1-1h-5l-1 1H4v2h16Z"/></svg>
            </button>
            <span class="qty">{{ qty }}</span>
            <button class="ctrl-btn" @click="inc" :disabled="(isBusy as any)" aria-label="Plus">
              <svg width="18" height="18" viewBox="0 0 24 24"><path fill="currentColor" d="M11 11V6h2v5h5v2h-5v5h-2v-5H6v-2z"/></svg>
            </button>
          </template>
          <template v-else>
            <button class="ctrl-btn" @click="handleAdd" :disabled="(isBusy as any)" aria-label="Add to cart">
              <svg width="18" height="18" viewBox="0 0 24 24"><path fill="currentColor" d="M10 20a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm10 0a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM5.1 5l.4 2h13a1 1 0 0 1 .98 1.2l-1.2 6a2 2 0 0 1-1.97 1.6H8a2 2 0 0 1-1.97-1.6L4.2 4H2V2h2.6a1 1 0 0 1 .98.8L5.1 5zM12 7v3h3v2h-3v3h-2v-3H7V10h3V7h2z"/></svg>
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
      <div class="brand" v-if="brandName">{{ brandName }}</div>
      <div class="title">{{ title }}</div>
      <div class="meta">
        <div class="stars" aria-label="rating">
          <span class="count">({{ reviewsCount }})</span>
          <span class="star" :class="{ on: true }">★</span>
          <b class="rating-num">{{ (rating as any)?.toFixed ? (rating as any).toFixed(1) : rating }}</b>
        </div>
      </div>
      <span v-if="hasDiscount" class="old">{{ formatPrice((oldPrice as any)) }}</span>
      <div class="price-row">
        <div v-if="hasDiscount" class="badge">-{{ discountPercent }}%</div>
        <span class="price final">{{ formatPrice((finalPrice as any)) }} {{ currencySymbol }}</span>
      </div>
    </div>
  </NuxtLink>
</template>

<style scoped>
.card { display:block; border:1px solid #eee; border-radius:14px; overflow:hidden; background:#fff; transition: transform .12s ease, box-shadow .12s ease; direction: rtl }
.card:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(0,0,0,.08) }
.thumb { aspect-ratio: 1 / 1; background:#fafafa; display:flex; align-items:center; justify-content:center; overflow:hidden; position:relative }
img { width:100%; height:100%; object-fit:contain }
.badge { background:#ef4444; color:#fff; font-weight:700; font-size:12px; padding:4px 6px; border-radius:8px }
.chip { position:absolute; inset-inline: 25%; top:10px; text-align:center; padding:4px 10px; font-size:12px; font-weight:700; border-radius:999px; color:#fff }
.chip.green{ background:#2b8a3e }
.chip.pink{ background:#db2777 }
.chip.blue{ background:#2563eb }
.fab { position:absolute; width:34px; height:34px; border-radius:999px; display:flex; align-items:center; justify-content:center; background:#fff; color:#374151; border:1px solid #e5e7eb; box-shadow:0 2px 8px rgba(0,0,0,.05); cursor:pointer; transition: transform .12s ease }
.fab:hover { transform: scale(1.05) }
.fab.wish { left:10px; top:10px }
.fab.wish.on { color:#ef4444; border-color:#fecaca; background:#fff0f0 }
.cart-ctrl { position:absolute; left:10px; bottom:10px; display:inline-flex; align-items:center; gap:10px; background:#fff; border:1px solid #e5e7eb; border-radius:999px; padding:6px 10px; box-shadow:0 2px 8px rgba(0,0,0,.05) }
.cart-ctrl.oos { opacity:.75 }
.cart-ctrl.busy { opacity: .85 }
.ctrl-btn { width:28px; height:28px; border-radius:999px; display:inline-flex; align-items:center; justify-content:center; border:1px solid #e5e7eb; background:#fff; cursor:pointer }
.ctrl-btn[disabled] { opacity:.5; cursor: not-allowed }
.qty { min-width: 18px; text-align:center; font-weight:700 }
.lock { display:inline-flex; align-items:center; justify-content:center; color:#6b7280 }
.mini-spin { width:14px; height:14px; border-radius:50%; border:2px solid #e5e7eb; border-top-color:#9ca3af; animation: spin .8s linear infinite }
@keyframes spin { to { transform: rotate(360deg) } }
.info { padding:10px }
.brand { color:#6b7280; font-weight:700; font-size:14px; margin-bottom:4px; text-align:right }
.title { font-weight:700; color:#111827; margin-bottom:6px; line-height:1.4; display:-webkit-box; -webkit-line-clamp:2; line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; text-align:right }
.meta { display:flex; align-items:center; justify-content:flex-start; gap:6px; margin-bottom:6px }
.stars { color:#f59e0b; font-size:12px; display:flex; align-items:center; gap:6px }
.rating-num { color:#111827; font-weight:700; font-size:12px }
.count { color:#6b7280; font-size:12px }
.price-row { display: flex;align-items: center;gap: 8px;flex-direction: row-reverse;justify-content: flex-end; }
.price.final { color:#e11d48; font-weight:800 }
.old { color:#9ca3af; text-decoration: line-through }
</style>
