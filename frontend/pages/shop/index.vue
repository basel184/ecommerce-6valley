<script setup lang="ts">
// Shop page with filters + infinite scroll
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useCatalog } from '../../composables/useCatalog'
const { filter } = useProducts()
const { categories, brands } = useCatalog()

// Filter state
const route = useRoute()
const router = useRouter()
const q = ref<string>('')
const sort_by = ref<string>('latest')
const product_type = ref<string>('') // physical|digital|''
const price_min = ref<number | null>(null)
const price_max = ref<number | null>(null)
const category = ref<number[]>([]) // store selected ids (any level)
const brand = ref<number[]>([])

// Options data
const cats = ref<any[]>([])
const brandsResp = ref<any>({ total_size: 0, brands: [] })

// Fetch filter sources
onMounted(async () => {
  try { cats.value = await categories() } catch (e) { console.warn('categories failed', e) }
  try { brandsResp.value = await brands({ limit: 200, offset: 1 }) } catch (e) { console.warn('brands failed', e) }
})

// Results with pagination via infinite scroll
const limit = ref(24)
const offset = ref(1) // page-style offset used by backend
const total = ref(0)
const items = ref<any[]>([])
const loading = ref(false)
const done = computed(() => items.value.length >= total.value && total.value > 0)

// Build filter body for API
const buildBody = () => {
  const body: any = {
    search: q.value ? btoa(unescape(encodeURIComponent(q.value))) : '',
    limit: limit.value,
    offset: offset.value,
  }
  if (category.value.length) body.category = JSON.stringify(category.value)
  if (brand.value.length) body.brand = JSON.stringify(brand.value)
  if (sort_by.value) body.sort_by = sort_by.value
  if (product_type.value) body.product_type = product_type.value
  if (price_min.value != null) body.price_min = price_min.value
  if (price_max.value != null) body.price_max = price_max.value
  return body
}

// Load a page
const loadPage = async () => {
  if (loading.value || done.value) return
  loading.value = true
  try {
    const body = buildBody()
    const res: any = await filter(body)
    const list = Array.isArray(res?.products) ? res.products : []
    // On first page, reset items
    if (offset.value === 1) items.value = []
    items.value = items.value.concat(list)
    total.value = Number(res?.total_size || list.length)
    offset.value = Number(res?.offset || offset.value)
    // Next page
    offset.value = offset.value + 1
  } catch (e) {
    console.error('[shop] filter failed', e)
  } finally {
    loading.value = false
  }
}

// Reset and fetch from first page
const resetAndFetch = async () => {
  offset.value = 1
  total.value = 0
  items.value = []
  await loadPage()
}

// Fetch on any filter change, debounced
const filterKey = computed(() => JSON.stringify({
  q: q.value,
  sort: sort_by.value,
  type: product_type.value,
  min: price_min.value,
  max: price_max.value,
  category: [...category.value].sort(),
  brand: [...brand.value].sort(),
}))
let keyTimer: any
watch(filterKey, () => {
  clearTimeout(keyTimer)
  keyTimer = setTimeout(() => {
    resetAndFetch()
  }, 300)
})

// Infinite scroll sentinel
const sentinel = ref<HTMLElement | null>(null)
let io: IntersectionObserver | null = null
onMounted(() => {
  io = new IntersectionObserver((entries) => {
    for (const e of entries) {
      if (e.isIntersecting) loadPage()
    }
  }, { rootMargin: '200px' })
  if (sentinel.value) io.observe(sentinel.value)
})
onBeforeUnmount(() => { if (io) { io.disconnect(); io = null } })

// Apply route query (e.g., from header category click)
onMounted(() => {
  const catParam = route.query.category
  if (Array.isArray(catParam)) {
    category.value = catParam.map(v => Number(v)).filter(n => !isNaN(n))
  } else if (typeof catParam === 'string' && catParam) {
    const n = Number(catParam)
    if (!isNaN(n)) category.value = [n]
  }
  loadPage()
})

// React to route query changes
watch(() => route.query, (qobj) => {
  const catParam = qobj?.category as any
  let nextCats: number[] = []
  if (Array.isArray(catParam)) {
    nextCats = catParam.map(v => Number(v)).filter(n => !isNaN(n))
  } else if (typeof catParam === 'string' && catParam) {
    const n = Number(catParam)
    if (!isNaN(n)) nextCats = [n]
  }
  // Only update if different
  if (JSON.stringify(nextCats) !== JSON.stringify(category.value)) {
    category.value = nextCats
    resetAndFetch()
  }
}, { deep: true })

// Helpers for rendering categories (flat checklist from tree)
type FlatCat = { id: number | string; name: string; depth: number }
const flatCategories = computed<FlatCat[]>(() => {
  const res: FlatCat[] = []
  const walk = (arr: any[], depth = 0) => {
    for (const c of arr || []) {
      res.push({ id: c.id, name: c.name, depth })
      if (Array.isArray(c.childes) && c.childes.length) walk(c.childes, depth + 1)
    }
  }
  walk(cats.value)
  return res
})

const priceRangeText = computed(() => {
  const parts = [] as string[]
  if (price_min.value != null) parts.push(String(price_min.value))
  if (price_max.value != null) parts.push(String(price_max.value))
  return parts.length ? parts.join(' - ') : ''
})
</script>

<template>
  <div class="shop" dir="rtl">
    <aside class="sidebar">
      <div class="box">
        <input v-model="q" type="search" placeholder="ابحث عن منتج" class="search" />
      </div>

      <div class="box">
        <div class="box-title">السعر</div>
        <div class="row">
          <input class="num" type="number" v-model.number="price_min" placeholder="حد أدنى" />
          <span>-</span>
          <input class="num" type="number" v-model.number="price_max" placeholder="حد أقصى" />
        </div>
        <div class="subtle" v-if="priceRangeText">النطاق: {{ priceRangeText }}</div>
      </div>

      <div class="box">
        <div class="box-title">الأقسام</div>
        <div class="list">
          <label v-for="c in flatCategories" :key="c.id" class="chk" :style="{ paddingInlineStart: (8 + c.depth*12) + 'px' }">
            <input type="checkbox" :value="c.id" v-model="category" /> {{ c.name }}
          </label>
        </div>
      </div>

      <div class="box">
        <div class="box-title">العلامات التجارية</div>
        <div class="list">
          <label v-for="b in (brandsResp?.brands || [])" :key="b.id" class="chk">
            <input type="checkbox" :value="b.id" v-model="brand" /> {{ b.name }}
          </label>
        </div>
      </div>
    </aside>

    <main class="content">
      <div class="toolbar">
        <div class="result">النتائج: {{ items.length }} / {{ total }}</div>
        <div class="spacer" />
        <select v-model="sort_by" class="select small">
          <option value="latest">الأحدث</option>
          <option value="low-high">السعر: الأقل للأعلى</option>
          <option value="high-low">السعر: الأعلى للأقل</option>
          <option value="a-z">أ-ي</option>
          <option value="z-a">ي-أ</option>
        </select>
      </div>

      <div class="grid">
        <ProductCard v-for="p in items" :key="p.id || p.slug" :product="p" />
      </div>

      <div ref="sentinel" class="sentinel">
        <span v-if="loading && !done">يتم التحميل…</span>
        <span v-else-if="done">انتهت النتائج</span>
      </div>
    </main>
  </div>
</template>

<style scoped>
.shop { display:grid; grid-template-columns: 280px 1fr; gap:16px; padding:16px }
@media (max-width: 900px){ .shop { grid-template-columns: 1fr } .sidebar{ order:2 } .content{ order:1 } }
.sidebar { display:flex; flex-direction:column; gap:12px }
.box { border:1px solid #eee; border-radius:12px; background:#fff; padding:12px }
.box-title { font-weight:800; margin-bottom:8px; color:#111827 }
.search { width:100%; border:1px solid #e5e7eb; border-radius:8px; padding:10px 12px }
.select { width:100%; border:1px solid #e5e7eb; border-radius:8px; padding:8px 12px; background:#fff }
.select.small { width:auto }
.chk { display:flex; align-items:center; gap:8px; padding:6px 0; color:#111827 }
.row { display:flex; align-items:center; gap:8px }
.num { width:100%; border:1px solid #e5e7eb; border-radius:8px; padding:8px 10px }
.subtle { color:#6b7280; font-size:12px; margin-top:6px }
.list { max-height: 280px; overflow: auto; border:1px dashed #eee; border-radius:8px; padding:8px }
.content { display:flex; flex-direction:column; gap:12px }
.toolbar { display:flex; align-items:center; gap:12px; padding:8px 0 }
.spacer { flex:1 }
.grid { display:grid; grid-template-columns: repeat(5, 1fr); gap:12px }
@media (max-width: 1200px){ .grid { grid-template-columns: repeat(4, 1fr) } }
@media (max-width: 900px){ .grid { grid-template-columns: repeat(3, 1fr) } }
@media (max-width: 640px){ .grid { grid-template-columns: repeat(2, 1fr) } }
.sentinel { text-align:center; padding:16px; color:#6b7280 }
</style>
