<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
const cart = useCart()
onMounted(async () => {
  // Safety timeout: if API hangs, flip loading off and show a friendly error
  const safety = setTimeout(() => {
      try {
      if (cart.loading?.value) {
        cart.error && (cart.error.value = 'تعذر تحميل السلة حالياً. حاول مرة أخرى.')
        cart.loading.value = false
      }
    } catch {}
    }, 6000)
  try {
    await cart.list()
    debugCartItems('بعد cart.list')
  } catch (e) {
    // avoid unhandled rejection from bubbling to Nuxt error boundary
    console.warn('Failed to load cart', e)
  } finally {
    clearTimeout(safety)
    // لا نظهر شاشة التحميل بعد التحميل الأولي
    initialLoading.value = false
  }
})

// Debug: اطبع هيكل عناصر السلة بعد التحميل مباشرة
// نطبع نسخة مجمدة لتفادي الـ Proxy وإظهار الحقول كما هي
function debugCartItems(stage: string) {
  try {
    const list: any[] = (cart as any)?.items?.value ?? []
    const plain = JSON.parse(JSON.stringify(list))
    console.log(`=== CART ITEMS DEBUG (${stage}) ===`)
    console.log('العدد:', Array.isArray(list) ? list.length : 0)
    console.log('العنصر[0]:', plain?.[0])
    console.log('مفاتيح العنصر[0]:', plain?.[0] ? Object.keys(plain[0]) : [])
    if (plain?.[0]?.product) {
      console.log('product داخل العنصر[0]:', plain[0].product)
      console.log('product.keys:', Object.keys(plain[0].product))
      console.log('product.translations:', plain[0].product?.translations)
      console.log('product.thumbnail_full_url:', plain[0].product?.thumbnail_full_url)
    }
    console.log('=== END CART ITEMS DEBUG ===')
  } catch (e) {
    console.log('DEBUG CART ITEMS FAILED:', e)
  }
}

// سجلات مفصلة لعناصر السلة عند توفرها
function logCartDetails() {
  try {
    const list: any[] = (cart as any)?.items?.value ?? []
    if (!Array.isArray(list) || list.length === 0) {
      console.log('[Cart:details] لا توجد عناصر لإظهار تفاصيلها')
      return
    }
    const plain = JSON.parse(JSON.stringify(list))
    console.group('=== CART ITEMS DETAILS ===')
    plain.forEach((it: any, i: number) => {
      const p = it?.product || {}
      console.groupCollapsed(`العنصر #${i} (id=${it?.id} pid=${it?.product_id || p?.id})`)
      console.log('المفاتيح:', Object.keys(it || {}))
      console.table({
        id: it?.id,
        product_id: it?.product_id || p?.id,
        name_item: it?.name,
        name_product: p?.name,
        variant: it?.variant,
        qty: it?.quantity || it?.qty,
        price: it?.price,
        discount: it?.discount,
        tax: it?.tax,
        tax_model: it?.tax_model,
        shipping_cost: it?.shipping_cost,
        shop: it?.shop?.name,
      })
      console.log('product.keys:', Object.keys(p || {}))
      console.log('الصورة المحتملة:', {
        p_thumbnail_full_url: p?.thumbnail_full_url,
        p_image_full_url: p?.image_full_url,
        it_thumbnail_full_url: it?.thumbnail_full_url,
        it_image_full_url: it?.image_full_url,
        p_thumbnail: p?.thumbnail,
        it_thumbnail: it?.thumbnail,
        p_images_full_url: p?.images_full_url,
      })
      console.groupEnd()
    })
    console.groupEnd()
  } catch (e) {
    console.warn('logCartDetails failed', e)
  }
}

// راقب تغيّر عناصر السلة: عند أول توفر عناصر اطبع التفاصيل
watch(() => (cart as any)?.items?.value, (val: any[]) => {
  if (Array.isArray(val) && val.length > 0) logCartDetails()
})

// لوحة Debug اختيارية للإضافة السريعة (افتح الصفحة مع ?debug=1)
const route = useRoute() as any
const showDebugPanel = computed(() => {
  const q = String(route?.query?.debug ?? '')
  return q === '1' || q.toLowerCase() === 'true'
})
const debugProductId = ref<number | null>(null)
const debugQty = ref<number>(1)
async function debugAdd() {
  if (!debugProductId.value) {
    console.warn('debugAdd: product_id مطلوب')
    return
  }
  try {
    await cart.add({ product_id: Number(debugProductId.value), quantity: Number(debugQty.value || 1) })
    await cart.list()
    debugCartItems('بعد debugAdd')
  } catch (e) {
    console.warn('debugAdd failed', e)
  }
}

const actionBusy = ref<Record<string, boolean>>({})
const clearAllBusy = ref(false)
const isBusy = (it: any) => !!actionBusy.value[String(it?.id ?? '')]

async function removeItem(item: any) {
  const key = item?.id || item?.key
  if (!key) return
  const idStr = String(item?.id ?? key)
  actionBusy.value[idStr] = true
  try {
    await cart.remove(Number(key))
  } finally {
    delete actionBusy.value[idStr]
  }
}

async function inc(item: any) {
  const key = item?.id || item?.key
  const qty = Number(item?.quantity || item?.qty || 0) + 1
  if (!key) return
  const idStr = String(item?.id ?? key)
  actionBusy.value[idStr] = true
  try {
    await cart.update({ key: Number(key), quantity: qty })
  } finally {
    delete actionBusy.value[idStr]
  }
}
async function dec(item: any) {
  const key = item?.id || item?.key
  const qty = Number(item?.quantity || item?.qty || 0)
  if (!key) return
  const idStr = String(item?.id ?? key)
  actionBusy.value[idStr] = true
  try {
    if (qty > 1) await cart.update({ key: Number(key), quantity: qty - 1 })
    else await cart.remove(Number(key))
  } finally {
    delete actionBusy.value[idStr]
  }
}

async function onClearAll() {
  clearAllBusy.value = true
  try {
    await cart.clearAll()
  } finally {
    clearAllBusy.value = false
  }
}

// Minimal image helper
const cfg = useRuntimeConfig() as any
const assetBase = (cfg?.public?.apiBase || '').replace(/\/api(?:\/v\d+)?$/, '')
const fixPath = (s: string) => s?.trim()?.replace(/\\/g, '/')?.replace(/^public\//, '')?.replace(/^app\/public\//, 'storage/')?.replace(/\/+/g, '/')?.replace(/^\//, '')
function imgOf(it: any): string {
  const p = it?.product || {}
  const pickUrl = (v: any): string | null => {
    if (!v) return null
    if (typeof v === 'string') return v
    if (typeof v === 'object' && typeof v.path === 'string') return v.path
    return null
  }

  // 1) حاول استخدام روابط كاملة إن وجدت (كسلسلة أو كائن {path})
  const candidatesFull = [
    pickUrl(p?.thumbnail_full_url),
    pickUrl(p?.image_full_url),
    pickUrl(it?.thumbnail_full_url),
    pickUrl(it?.image_full_url),
    Array.isArray(p?.images_full_url) ? pickUrl(p.images_full_url[0]) : null
  ].filter(Boolean) as string[]
  if (candidatesFull.length && candidatesFull[0]) {
    const u = candidatesFull[0].trim()
    if (u) return u
  }

  // 2) أسماء ملفات خام
  const raw = String(
    p?.thumbnail || it?.thumbnail || p?.image || it?.image || ''
  ).trim()
  if (!raw) return ''
  if (/^(https?:|data:|blob:)/i.test(raw)) return raw

  const clean = fixPath(raw)
  const guesses = [
    `storage/product/thumbnail/${clean}`,
    `storage/product/${clean}`,
    `storage/${clean}`,
    clean
  ]
  return `${assetBase}/${guesses[0]}`
}

// Currency + totals helpers
const { locale } = useI18n() as any
const currencyCode = (cfg?.public?.currencyCode || 'SAR') as string
function money(n: any): string {
  const loc = locale?.value === 'ar' ? 'ar-SA' : 'en-US'
  try {
    return new Intl.NumberFormat(loc, { style: 'currency', currency: currencyCode }).format(Number((n as any)?.value ?? n) || 0)
  } catch {
    // Fallback: plain number with symbol
    const sym = (cfg?.public?.currencySymbol || (locale?.value === 'ar' ? 'ر.س' : 'SAR')) as string
    const raw = (n as any)?.value ?? n
    const val = Number(raw != null ? raw : 0)
    return `${val.toFixed(2)} ${sym}`
  }
}

// Use .value when reading refs in script (templates auto-unwrap)
const initialLoading = ref(true)
const items = computed(() => cart.items.value || [])
const itemsTotal = computed(() => items.value.reduce((s: number, it: any) => s + Number(it?.quantity || it?.qty || 0), 0))
// Subtotal BEFORE discount (API gives price & discount per unit)
const subtotal = computed(() => items.value.reduce((s: number, it: any) => s + (Number(it?.price || 0) * Number(it?.quantity || it?.qty || 0)), 0))
// Total discount across all items
const discountTotal = computed(() => items.value.reduce((s: number, it: any) => s + (Number(it?.discount || 0) * Number(it?.quantity || it?.qty || 0)), 0))
// Subtotal AFTER discount
const subtotalAfterDiscount = computed(() => Math.max(0, subtotal.value - discountTotal.value))
const taxIncluded = computed(() => items.value.reduce((s: number, it: any) => s + (it?.tax_model === 'include' ? Number(it?.tax || 0) * Number(it?.quantity || it?.qty || 0) : 0), 0))
const taxExcluded = computed(() => items.value.reduce((s: number, it: any) => s + (it?.tax_model === 'exclude' ? Number(it?.tax || 0) * Number(it?.quantity || it?.qty || 0) : 0), 0))
const shipping = computed(() => items.value.reduce((s: number, it: any) => s + Number(it?.shipping_cost || 0), 0))
// Grand total: add excluded tax & shipping only; included tax is informational
const grandTotal = computed(() => subtotalAfterDiscount.value + taxExcluded.value + shipping.value)
// booleans for template conditions to avoid ref-vs-number TS issues
const hasTaxExcluded = computed(() => taxExcluded.value > 0)
const hasTaxIncluded = computed(() => taxIncluded.value > 0)
const hasShipping = computed(() => shipping.value > 0)
const hasDiscountTotal = computed(() => discountTotal.value > 0)
// Only show loader when we have no items yet
// لا نظهر شاشة التحميل إلا في التحميل الأول فقط
const isLoadingVisible = computed(() => initialLoading.value && ((cart as any)?.loading?.value === true))

// Name helper: prefer Arabic product name when locale is 'ar'
function nameOf(it: any): string {
  const p = it?.product || {}
  const direct = String(p?.name || it?.name || '').trim()
  return direct || 'منتج'
}

// Discount helpers (display only; totals remain based on price field)
function unitNet(it: any): number {
  const price = Number(it?.price || 0)
  const disc = Number(it?.discount || 0)
  const net = price - (disc > 0 ? disc : 0)
  return net > 0 ? net : 0
}
function hasDiscount(it: any): boolean {
  return Number(it?.discount || 0) > 0
}
</script>

<template>
  <div style="max-width:960px;margin:24px auto;padding:16px;" dir="rtl">
    
  <h2 style="margin-bottom:12px">سلة التسوق</h2>
  <template v-if="isLoadingVisible">
    <div>
      جاري التحميل…
      <div style="margin-top:8px">
        <button class="btn" @click="cart.list()">إعادة المحاولة</button>
      </div>
    </div>
  </template>
  <template v-else>
    <div v-if="showDebugPanel" style="padding:8px;border:1px dashed #999;margin-bottom:12px;background:#fafafa">
      <b>لوحة Debug</b>
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:6px">
        <label>Product ID: <input type="number" v-model.number="debugProductId" style="width:120px" /></label>
        <label>Qty: <input type="number" v-model.number="debugQty" min="1" style="width:80px" /></label>
        <button class="btn" @click="debugAdd">إضافة للسلة (Debug)</button>
      </div>
      <small>افتح الصفحة بـ ?debug=1 لإظهار هذه اللوحة فقط أثناء التطوير.</small>
    </div>
    <div v-if="cart.error" style="color:#c00">
      {{ cart.error }}
      <div style="margin-top:8px">
        <button class="btn" @click="cart.list()">إعادة المحاولة</button>
      </div>
    </div>
    <div v-if="!items || items.length === 0">سلتك فارغة. 
      <NuxtLink to="/" class="btn" style="margin-inline-start:8px">اذهب للتسوق</NuxtLink>
    </div>
    <div v-else class="cart-list">
        <div v-for="(it,i) in items" :key="it?.id || i" class="row">
          <div class="thumb"><img :src="imgOf(it)" :alt="nameOf(it)" /></div>
          <div class="name">
            <div>{{ nameOf(it) }}</div>
            <div class="sub">
              <span v-if="it?.shop?.name">{{ it?.shop?.name }}</span>
              <span v-if="it?.variant"> • {{ it?.variant }}</span>
              <span v-if="it?.is_product_available === 0" class="oos">غير متوفر</span>
            </div>
          </div>
          <div class="qty-ctrl">
            <button class="btn" @click="dec(it)" :disabled="isBusy(it)" :aria-label="(it?.quantity||1) > 1 ? 'إنقاص' : 'حذف'">
              <span v-if="(it?.quantity||1) > 1">−</span>
              <span v-else>🗑️</span>
            </button>
            <span class="qty">{{ it?.quantity || it?.qty || 1 }}</span>
            <button class="btn" @click="inc(it)" :disabled="isBusy(it)" aria-label="زيادة">+</button>
            <span v-if="isBusy(it)" class="spinner" aria-hidden="true"></span>
          </div>
          <div class="price">
            <div class="line">
              <template v-if="hasDiscount(it)">
                {{ money(unitNet(it) * (it?.quantity || it?.qty || 1)) }}
                <span class="save">وفّرت {{ money((it?.discount || 0) * (it?.quantity || it?.qty || 1)) }}</span>
              </template>
              <template v-else>
                {{ money((it?.price || 0) * (it?.quantity || it?.qty || 1)) }}
              </template>
            </div>
            <div class="sub">
              سعر الوحدة:
              <template v-if="hasDiscount(it)">
                <span class="old">{{ money(it?.price || 0) }}</span>
                <span class="new">{{ money(unitNet(it)) }}</span>
              </template>
              <template v-else>
                {{ money(it?.price || 0) }}
              </template>
            </div>
            <div v-if="it?.tax_model === 'include'" class="tag include">شامل الضريبة</div>
            <div v-else-if="it?.tax_model === 'exclude'" class="tag exclude">غير شامل الضريبة</div>
          </div>
        </div>
        <div class="totals">
          <div class="trow"><span>عدد العناصر</span><b>{{ itemsTotal }}</b></div>
          <div class="trow"><span>قيمة المنتجات</span><b>{{ money(subtotal) }}</b></div>
          <div class="trow" v-if="hasDiscountTotal"><span>الخصم</span><b>-{{ money(discountTotal) }}</b></div>
          <div class="trow"><span>المجموع الفرعي</span><b>{{ money(subtotalAfterDiscount) }}</b></div>
          <div class="trow" v-if="hasTaxExcluded"><span>الضريبة</span><b>{{ money(taxExcluded) }}</b></div>
          <div class="trow mute" v-if="hasTaxIncluded"><span>الضريبة (شاملة في الأسعار)</span><b>{{ money(taxIncluded) }}</b></div>
          <div class="trow" v-if="hasShipping"><span>الشحن</span><b>{{ money(shipping) }}</b></div>
          <div class="trow total"><span>الإجمالي</span><b>{{ money(grandTotal) }}</b></div>
        </div>
        <div style="margin-top:12px; display:flex; gap:8px; justify-content:flex-end;">
          <button class="btn danger" @click="onClearAll" :disabled="clearAllBusy">حذف الكل</button>
        </div>
      </div>
  </template>
  </div>
</template>

<style scoped>
  .row { display:grid; grid-template-columns: 64px 1fr auto 160px; gap:12px; align-items:center; padding:10px 0; border-bottom:1px solid #eee }
  .thumb { width:64px; height:64px; border:1px solid #eee; border-radius:10px; overflow:hidden; background:#fafafa; display:flex; align-items:center; justify-content:center }
  .thumb img { width:100%; height:100%; object-fit:contain }
  .name { font-weight:700; color:#111827 }
  .name .sub { margin-top:4px; font-weight:400; font-size:12px; color:#6b7280 }
  .name .sub .oos { color:#b91c1c }
  .qty-ctrl { display:flex; align-items:center; gap:8px; justify-content:center }
  .qty { min-width: 24px; text-align:center; font-weight:700 }
  .btn { padding:6px 10px; border:1px solid #e5e7eb; background:#fff; border-radius:8px; cursor:pointer }
  .btn.danger { border-color:#fecaca; background:#fff0f0; color:#b91c1c }
  .price { text-align:left; font-weight:700; color:#111827 }
  /* For RTL, the container is RTL already; keep number alignment readable */
  .price .line { display:flex; align-items:center; gap:8px; flex-wrap:wrap }
  .price .sub { font-weight:500; font-size:12px; color:#6b7280; margin-top:4px }
  .price .sub .old { text-decoration:line-through; color:#9ca3af; margin-inline-end:6px }
  .price .sub .new { font-weight:700; color:#111827 }
  .price .line .save { font-weight:500; font-size:12px; color:#16a34a; margin-inline-start:8px }
  .price .tag { display:inline-block; margin-top:6px; padding:2px 6px; border-radius:6px; font-size:11px; border:1px solid #e5e7eb }
  .price .tag.include { background:#f0fdf4; border-color:#dcfce7; color:#166534 }
  .price .tag.exclude { background:#fff7ed; border-color:#ffedd5; color:#9a3412 }
  .totals { margin-top:8px; display:flex; flex-direction:column; align-items:flex-end; gap:6px; padding-top:12px }
  .totals .trow { display:flex; gap:20px; min-width:300px; justify-content:space-between }
  .totals .trow.mute { color:#6b7280 }
  .totals .trow.total { border-top:1px dashed #e5e7eb; padding-top:8px; font-size:16px }
  .spinner {
    width: 14px; height: 14px; border: 2px solid #e5e7eb; border-top-color: #9ca3af; border-radius: 50%;
    animation: spin 0.8s linear infinite;
  }
  @keyframes spin { to { transform: rotate(360deg) } }
</style>
