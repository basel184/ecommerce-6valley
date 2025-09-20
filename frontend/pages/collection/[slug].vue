<script setup lang="ts">
import { ref, computed, watch } from 'vue'
const route = useRoute()
const slug = route.params.slug as string
const { $get } = useApi()

const { data: section, pending, error } = await useAsyncData(`section-${slug}` as const, () => $get(`v1/home-sections/${slug}`))
// Fallback holder if show endpoint 404s
const localSection = ref<any | null>(null)
const sectionValue = computed(() => localSection.value ?? (section as any)?.value)

const limit = ref(24)
const offset = ref(0)
const loading = ref(false)
const products = ref<any[]>([])
const total = ref(0)
// Debounce handle for search
let qTimer: any = null

// Filters state
const q = ref('')
const selectedBrands = ref<number[]>([])
const selectedCategories = ref<number[]>([])
const minPrice = ref<number | null>(null)
const maxPrice = ref<number | null>(null)
const sort = ref<'latest' | 'price_asc' | 'price_desc' | 'name_asc' | 'name_desc'>('latest')

// Facets
const facets = ref<{ brands: any[]; categories: any[]; price: { min: number; max: number } } | null>(null)
const loadFacets = async () => {
  try {
    const res = await $get(`v1/home-sections/${slug}/facets`)
    facets.value = res
    if (res?.price) {
      if (minPrice.value == null) minPrice.value = Number(res.price.min || 0)
      if (maxPrice.value == null) maxPrice.value = Number(res.price.max || 0)
    }
  } catch {}
}

const buildParams = () => {
  const params: Record<string, any> = { limit: limit.value, offset: offset.value }
  if (q.value) params.q = q.value
  if (selectedBrands.value.length) params.brand_ids = selectedBrands.value.join(',')
  if (selectedCategories.value.length) params.category_id = selectedCategories.value.join(',')
  if (minPrice.value != null) params.min_price = minPrice.value
  if (maxPrice.value != null) params.max_price = maxPrice.value
  if (sort.value) params.sort = sort.value
  const usp = new URLSearchParams(params as any)
  return usp.toString()
}

const load = async (reset = false) => {
  if (loading.value) return
  loading.value = true
  try {
    if (reset) { offset.value = 0; products.value = [] }
    const res = await $get(`v1/home-sections/${slug}/products?${buildParams()}`)
    total.value = res?.total_size || 0
    const list = Array.isArray(res?.products) ? res.products : []
    products.value = reset ? list : products.value.concat(list)
    offset.value += limit.value
  } finally {
    loading.value = false
  }
}

// Load products only when the section type is products
watch(
  () => sectionValue.value?.type,
  (t) => {
    if (t === 'products') {
      loadFacets();
      load(true)
    }
  },
  { immediate: true }
)

// If primary fetch failed, try to find the section from the list as a graceful fallback
watch(
  () => error?.value,
  async (err) => {
    if (err && !localSection.value) {
      try {
        const list = await $get('v1/home-sections')
        if (Array.isArray(list)) {
          const found = list.find((s: any) => String(s?.slug) === String(slug))
          if (found) {
            localSection.value = found
          }
        }
      } catch {}
    }
  },
  { immediate: true }
)

const hasMore = computed(() => products.value.length < total.value)
// When any filter changes, reload products
watch([selectedBrands, selectedCategories, minPrice, maxPrice, sort], () => {
  if (sectionValue.value?.type === 'products') load(true)
})

// Debounced search
watch(q, () => {
  if (qTimer) clearTimeout(qTimer)
  qTimer = setTimeout(() => {
    if (sectionValue.value?.type === 'products') load(true)
  }, 400)
})
</script>

<template>
  <main class="collection" dir="rtl">
    <div class="container">
      <header class="header">
        <h1>{{ sectionValue?.title || 'المجموعة' }}</h1>
      </header>

      <!-- Loading state -->
      <section v-if="pending" class="section card">
        <p>جارٍ التحميل...</p>
      </section>

      <!-- Two-column layout: sidebar + products -->
      <section v-else-if="sectionValue?.type === 'products'" class="layout">
        <aside class="sidebar card">
          <div class="filters-grid">
          <div class="filter-group">
            <label>البحث</label>
            <input type="search" v-model.trim="q" placeholder="ما الذي تبحث عنه؟" />
          </div>
          <div class="filter-group">
            <label>العلامات التجارية</label>
            <div class="chips">
              <label v-for="b in (facets?.brands||[])" :key="b.id" class="chip">
                <input type="checkbox" :value="b.id" v-model="selectedBrands"> <span>{{ b.name }}</span>
              </label>
            </div>
          </div>
          <div class="filter-group">
            <label>الأقسام</label>
            <div class="chips">
              <label v-for="c in (facets?.categories||[])" :key="c.id" class="chip">
                <input type="checkbox" :value="c.id" v-model="selectedCategories"> <span>{{ c.name }}</span>
              </label>
            </div>
          </div>
          <div class="filter-group">
            <label>السعر</label>
            <div class="row">
              <input type="number" v-model.number="minPrice" :min="facets?.price?.min ?? 0" :max="facets?.price?.max ?? undefined" placeholder="الأدنى"/>
              <span>–</span>
              <input type="number" v-model.number="maxPrice" :min="facets?.price?.min ?? 0" :max="facets?.price?.max ?? undefined" placeholder="الأعلى"/>
            </div>
          </div>
          <div class="filter-group">
            <label>الترتيب</label>
            <select v-model="sort">
              <option value="latest">الأحدث</option>
              <option value="price_asc">السعر: من الأقل للأعلى</option>
              <option value="price_desc">السعر: من الأعلى للأقل</option>
              <option value="name_asc">الاسم: تصاعدي</option>
              <option value="name_desc">الاسم: تنازلي</option>
            </select>
          </div>
        </div>
    </aside>
    <section class="content">
      <div class="section card">
        <template v-if="loading">
          <div class="skeleton-grid">
            <div v-for="i in 8" :key="i" class="skeleton-card">
              <div class="sk-img"></div>
              <div class="sk-line w-80"></div>
              <div class="sk-line w-60"></div>
            </div>
          </div>
        </template>
        <template v-else>
          <ProductGrid :products="products" />
          <div class="load-more" v-if="hasMore">
            <button class="btn" :disabled="loading" @click="load()">تحميل المزيد</button>
          </div>
        </template>
      </div>
    </section>
  </section>

      <!-- Banners type -->
      <section v-else-if="sectionValue?.type === 'banners'" class="section card">
        <div v-if="sectionValue?.banner_layout === 'slider'">
          <BannerCarousel :banners="sectionValue?.banners || []" />
        </div>
        <div v-else>
          <PromoBannerRow :banners="sectionValue?.banners || []" :columns="sectionValue?.banner_layout === 'grid_1' ? 1 : (sectionValue?.banner_layout === 'grid_2' ? 2 : 3)" />
        </div>
      </section>

      <!-- Not found / error -->
      <section v-else class="section card">
        <p>القسم غير متاح</p>
      </section>
    </div>
  </main>
  
</template>

<style scoped>
.container { max-width: 1200px; margin: 0 auto; padding: 16px; display:flex; gap:16px; flex-direction: column }
.header h1 { font-size: 20px; font-weight: 700 }
.section.card { background:#fff; border:1px solid #eee; border-radius: 12px; padding: 14px }
.layout { display:grid; grid-template-columns: 280px 1fr; gap:16px; align-items:start }
.sidebar.card { position: sticky; top: 12px; height: fit-content }
.filters.card { background:#fff; border:1px solid #eee; border-radius:12px; padding:14px }
.filters-grid { display:grid; grid-template-columns: repeat(1, minmax(0,1fr)); gap:12px }
.filter-group { display:flex; flex-direction:column; gap:6px }
.filter-group label { font-weight:600; font-size:14px }
.chips { display:flex; flex-wrap:wrap; gap:8px }
.chip { display:inline-flex; align-items:center; gap:6px; background:#f8f8f8; border:1px solid #eee; border-radius:999px; padding:6px 10px }
.row { display:flex; align-items:center; gap:8px }
.load-more { display:flex; justify-content:center; padding: 12px 0 }
.btn { background:#111827; color:#fff; padding:10px 16px; border-radius:8px; border:none; cursor:pointer }
.btn[disabled] { opacity:.5; cursor: default }
@media (max-width: 640px) {
  .layout { grid-template-columns: 1fr }
  .filters-grid { grid-template-columns: 1fr }
}

/* Skeleton styles */
.skeleton-grid { display:grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap:12px }
.skeleton-card { border:1px solid #eee; border-radius:12px; padding:10px; background:#fff }
.sk-img { height: 160px; background: linear-gradient(90deg, #f5f5f5 25%, #ececec 37%, #f5f5f5 63%); background-size: 400% 100%; animation: sk 1.4s ease infinite; border-radius:10px }
.sk-line { height:12px; margin-top:10px; background: linear-gradient(90deg, #f5f5f5 25%, #ececec 37%, #f5f5f5 63%); background-size: 400% 100%; animation: sk 1.4s ease infinite; border-radius:6px }
.sk-line.w-80 { width:80% }
.sk-line.w-60 { width:60% }
@keyframes sk { 0% { background-position: 100% 50% } 100% { background-position: 0 50% } }
</style>