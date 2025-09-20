<script setup lang="ts">
const { $get } = useApi()
const { data: cfg } = await useAsyncData('cfg', () => $get('v1/config'))
const { data: banners } = await useAsyncData('banners', () => $get('v1/banners'))
// Admin-defined Home Sections (Collections)
const { data: homeSections } = await useAsyncData('home-sections', () => $get('v1/home-sections'))
const { data: categories } = await useAsyncData('home-categories', () => $get('v1/products/home-categories'))
const { data: latest } = await useAsyncData('latest', () => $get('v1/products/latest'))
const { data: featured } = await useAsyncData('featured', () => $get('v1/products/featured'))
const { data: topRated } = await useAsyncData('top-rated', () => $get('v1/products/top-rated'))
const { data: bestSellings } = await useAsyncData('best-sellings', () => $get('v1/products/best-sellings'))
const { data: newArrival } = await useAsyncData('new-arrival', () => $get('v1/products/new-arrival'))
const { data: discounted } = await useAsyncData('discounted', () => $get('v1/products/discounted-product'))
const { data: justForYou } = await useAsyncData('just-for-you', () => $get('v1/products/just-for-you'))

// Flash deal meta and products
const { data: flashMeta } = await useAsyncData('flash', () => $get('v1/flash-deals'))
const { data: flashProducts } = await useAsyncData('flash-products', async () => {
  const fm: any = (flashMeta as any)?.value || {}
  const id = fm?.id || fm?.flash_deal?.id
  return id ? await $get(`v1/flash-deals/products/${id}`) : []
})

// Featured deal products
const { data: featuredDeal } = await useAsyncData('featured-deal', () => $get('v1/deals/featured'))

// Deal of the day
const { data: dealOfTheDayRaw } = await useAsyncData('deal-of-the-day', () => $get('v1/dealsoftheday/deal-of-the-day'))
const dealOfTheDay = computed(() => (dealOfTheDayRaw?.value && typeof dealOfTheDayRaw.value === 'object') ? dealOfTheDayRaw.value : null)

// Clearance sale
const { data: clearance } = await useAsyncData('clearance-sale', () => $get('v1/products/clearance-sale'))

// Brands
const { data: brands } = await useAsyncData('brands', () => $get('v1/brands'))

// Normalize helpers to handle {data: []} or [] or other keyed arrays
const toList = (v: any, key?: string) => {
  const val = v?.value ?? v
  if (!val) return []
  if (Array.isArray(val)) return val
  // Most common wrappers
  if (key && Array.isArray(val[key])) return val[key]
  if (Array.isArray(val.data)) return val.data
  if (Array.isArray(val.items)) return val.items
  if (Array.isArray(val.products)) return val.products
  if (Array.isArray(val.list)) return val.list
  // Try detecting first array value in object
  if (val && typeof val === 'object') {
    for (const k of Object.keys(val)) {
      if (Array.isArray((val as any)[k])) return (val as any)[k]
    }
  }
  return []
}

const bannerItems = computed<any[]>(() => toList(banners))
const categoryItems = computed<any[]>(() => toList(categories))
const latestItems = computed<any[]>(() => toList(latest))
const featuredItems = computed<any[]>(() => toList(featured))
const topRatedItems = computed<any[]>(() => toList(topRated))
const bestSellingItems = computed<any[]>(() => toList(bestSellings))
const newArrivalItems = computed<any[]>(() => toList(newArrival))
const discountedItems = computed<any[]>(() => toList(discounted))
const justForYouItems = computed<any[]>(() => toList(justForYou))
const featuredDealItems = computed<any[]>(() => toList(featuredDeal))
const clearanceItems = computed<any[]>(() => toList(clearance))
const brandItems = computed<any[]>(() => toList(brands))
const flashItems = computed<any[]>(() => toList(flashProducts))
const cfgData = computed<any>(() => (cfg as any)?.value || (cfg as any) || {})
const sectionItems = computed<any[]>(() => toList(homeSections))
const hasSectionItems = computed(() => Array.isArray((sectionItems as any).value) && (sectionItems as any).value.length > 0)

// Booleans to avoid TS complaining about .length on refs in templates
const hasBannerItems = computed(() => Array.isArray((bannerItems as any).value) && (bannerItems as any).value.length > 0)
const hasCategoryItems = computed(() => Array.isArray((categoryItems as any).value) && (categoryItems as any).value.length > 0)
const hasLatestItems = computed(() => Array.isArray((latestItems as any).value) && (latestItems as any).value.length > 0)
const hasFeaturedItems = computed(() => Array.isArray((featuredItems as any).value) && (featuredItems as any).value.length > 0)
const hasTopRatedItems = computed(() => Array.isArray((topRatedItems as any).value) && (topRatedItems as any).value.length > 0)
const hasBestSellingItems = computed(() => Array.isArray((bestSellingItems as any).value) && (bestSellingItems as any).value.length > 0)
const hasNewArrivalItems = computed(() => Array.isArray((newArrivalItems as any).value) && (newArrivalItems as any).value.length > 0)
const hasDiscountedItems = computed(() => Array.isArray((discountedItems as any).value) && (discountedItems as any).value.length > 0)
const hasJustForYouItems = computed(() => Array.isArray((justForYouItems as any).value) && (justForYouItems as any).value.length > 0)
const hasFeaturedDealItems = computed(() => Array.isArray((featuredDealItems as any).value) && (featuredDealItems as any).value.length > 0)
const hasClearanceItems = computed(() => Array.isArray((clearanceItems as any).value) && (clearanceItems as any).value.length > 0)
const hasBrandItems = computed(() => Array.isArray((brandItems as any).value) && (brandItems as any).value.length > 0)
const hasFlashItems = computed(() => Array.isArray((flashItems as any).value) && (flashItems as any).value.length > 0)

// Hero splitting (main slider + side promos)
// Rule: if there are 3+ banners, reserve the last 2 for side promos; otherwise put all in main slider.
const heroMainBanners = computed<any[]>(() => {
  const arr = (bannerItems as any).value || []
  if (!Array.isArray(arr)) return []
  if (arr.length >= 3) return arr.slice(0, arr.length - 2)
  return arr
})
const heroSideBanners = computed<any[]>(() => {
  const arr = (bannerItems as any).value || []
  if (!Array.isArray(arr)) return []
  if (arr.length >= 3) return arr.slice(-2)
  return []
})
const hasHeroSideBanners = computed(() => Array.isArray((heroSideBanners as any).value) && (heroSideBanners as any).value.length > 0)

// Minimal image helpers for side banners
const cfg2 = useRuntimeConfig() as any
const assetBase = (cfg2?.public?.apiBase || '').replace(/\/api(?:\/v\d+)?$/, '')
// Prefer Laravel web host for server-rendered pages (collections). Fallbacks handle dev (3000->8000)
const webBase = computed(() => {
  let base = assetBase || ''
  if (!base && typeof window !== 'undefined') {
    base = window.location.origin
  }
  // If we're on Nuxt dev server (3000), assume Laravel is 8000
  if (/^https?:\/\/localhost:3000$/i.test(base) || /^https?:\/\/127\.0\.0\.1:3000$/i.test(base)) {
    base = base.replace(':3000', ':8000')
  }
  return base
})
const fixPath = (s: string) => {
  let p = s.trim().replace(/\\/g, '/').replace(/^public\//, '').replace(/^app\/public\//, 'storage/')
  p = p.replace(/\/+/g, '/').replace(/^\//, '')
  return p
}
const toSrc = (u: any): string => {
  if (!u) return ''
  if (Array.isArray(u)) return toSrc(u[0])
  let s: any = u
  if (typeof u === 'string') {
    const t = u.trim()
    if (t.startsWith('[') || t.startsWith('{')) {
      try { return toSrc(JSON.parse(t)) } catch {}
    }
    s = t
  } else if (typeof u === 'object') {
    s = (u as any).path || (u as any).url || (u as any).image || ''
  }
  s = (typeof s === 'string' ? s : '').trim()
  if (!s) return ''
  if (/^(https?:|data:|blob:)/i.test(s)) return s
  return `${assetBase}/${fixPath(s)}`
}
const onImgErr = (e: any) => {
  e.target.src = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="600" height="300"><rect width="100%" height="100%" fill="%23f3f4f6"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="%239ca3af" font-size="20">No image</text></svg>'
}
</script>

<template>
  <main class="home" dir="rtl">
    <div class="container">
      <header class="home-header">
        <h1 class="brand">{{ (cfgData as any)?.company_name || '6valley' }}</h1>
      </header>

      <!-- Hero: main carousel + side promos -->
      <section v-if="hasBannerItems" class="hero">
        <div class="hero-grid">
          <div class="hero-side" v-if="hasHeroSideBanners">
            <div v-for="(b, i) in (heroSideBanners as any)" :key="(b as any).id || i" class="hero-side-card">
              <a v-if="(b as any).url && (b as any).url !== '#'" :href="(b as any).url" target="_blank">
                <img :src="toSrc((b as any).photo_full_url || (b as any).image_full_url || (b as any).image || (b as any).photo)" :alt="(b as any).title || 'Promo'" @error="onImgErr" />
                <div class="overlay" v-if="(b as any).title">{{ (b as any).title }}</div>
              </a>
              <template v-else>
                <img :src="toSrc((b as any).photo_full_url || (b as any).image_full_url || (b as any).image || (b as any).photo)" :alt="(b as any).title || 'Promo'" @error="onImgErr" />
                <div class="overlay" v-if="(b as any).title">{{ (b as any).title }}</div>
              </template>
            </div>
          </div>
          <div class="hero-main">
            <BannerCarousel :banners="heroMainBanners" />
          </div>
        </div>
      </section>

      <!-- Categories scroller -->
      <section v-if="hasCategoryItems" class="section card">
        <div class="section-header">
          <h2>تسوق حسب الفئة</h2>
        </div>
        <CategoryPills :categories="categoryItems" />
      </section>

      <!-- Featured rows -->
  <section v-if="hasNewArrivalItems" class="section card">
        <div class="section-header">
          <h2>وصل حديثًا</h2>
        </div>
        <ProductGrid :products="newArrivalItems" />
      </section>

  <section v-if="hasBestSellingItems" class="section card">
        <div class="section-header">
          <h2>الأكثر مبيعًا</h2>
        </div>
        <ProductGrid :products="bestSellingItems" />
      </section>

  <section v-if="hasDiscountedItems" class="section card">
        <div class="section-header">
          <h2>العروض</h2>
          <p class="sub">مختارات مخفضة</p>
        </div>
        <ProductGrid :products="discountedItems" />
      </section>

      <!-- Promo banners row -->
      <section v-if="hasBannerItems" class="section">
        <div class="section-header compact"><h3>أقوى عروض اليوم الوطني</h3></div>
        <PromoBannerRow :banners="bannerItems" />
      </section>

      <!-- Flash / Featured / Top rated in compact cards -->
  <section v-if="hasFlashItems" class="section card">
        <div class="section-header">
          <h2>عروض فلاش</h2>
        </div>
        <ProductGrid :products="flashItems" />
      </section>

  <section v-if="hasFeaturedItems" class="section card">
        <div class="section-header">
          <h2>مختاراتنا</h2>
        </div>
        <ProductGrid :products="featuredItems" />
      </section>

  <section v-if="hasTopRatedItems" class="section card">
        <div class="section-header">
          <h2>الأعلى تقييمًا</h2>
        </div>
        <ProductGrid :products="topRatedItems" />
      </section>

  <section v-if="hasFeaturedDealItems" class="section card">
        <div class="section-header">
          <h2>صفقة مميزة</h2>
        </div>
        <ProductGrid :products="featuredDealItems" />
      </section>

  <section v-if="hasLatestItems" class="section card">
        <div class="section-header">
          <h2>أحدث المنتجات</h2>
        </div>
        <ProductGrid :products="latestItems" />
      </section>

      <!-- Deal of the day spotlight -->
  <section v-if="dealOfTheDay" class="section">
        <DealOfTheDayCard :product="dealOfTheDay" />
      </section>

      <!-- Just for you -->
      <section v-if="hasJustForYouItems" class="section card">
        <div class="section-header">
              <h2>مختارات لك</h2>
        </div>
        <ProductGrid :products="justForYouItems" />
      </section>

      <!-- Brands strip -->
  <section v-if="hasBrandItems" class="section card">
        <div class="section-header">
          <h2>الماركات</h2>
        </div>
        <BrandCarousel :brands="brandItems" />
      </section>

      <!-- Dynamic Home Sections from Admin -->
      <section v-if="hasSectionItems" class="section">
        <div v-for="(s, idx) in (sectionItems as any)" :key="s?.id || idx" class="section card">
          <div class="section-header">
            <h2>{{ s?.title }}</h2>
            <NuxtLink
              v-if="s?.type === 'products' && (s?.feed_type === 'category' ? s?.feed_category_id : s?.slug)"
              :to="s?.feed_type === 'category' ? `/shop?category=${s?.feed_category_id}` : `/collection/${s?.slug}`"
              class="view-all"
            >عرض الكل</NuxtLink>
          </div>
          <template v-if="s?.type === 'products' && Array.isArray(s?.products) && s.products.length">
            <ProductGrid :products="s.products" />
          </template>
          <template v-else-if="s?.type === 'categories' && Array.isArray(s?.categories) && s.categories.length">
            <CategoryPills :categories="s.categories" />
          </template>
          <template v-else-if="s?.type === 'brands' && Array.isArray(s?.brands) && s.brands.length">
            <BrandCarousel :brands="s.brands" />
          </template>
          <template v-else-if="s?.type === 'banners' && Array.isArray(s?.banners) && s.banners.length">
            <div v-if="s?.banner_layout === 'slider'">
              <BannerCarousel :banners="s.banners" />
            </div>
            <div v-else>
              <PromoBannerRow :banners="s.banners" :columns="s?.banner_layout === 'grid_1' ? 1 : (s?.banner_layout === 'grid_2' ? 2 : (s?.banner_layout === 'grid_3' ? 3 : undefined))" />
            </div>
          </template>
        </div>
      </section>
    </div>
  </main>
</template>

<style scoped>
.home { background: #f6f6f6; }
.container { max-width: 1400px; margin: 0 auto; padding: 16px; display: flex; flex-direction: column; gap: 20px; }
.home-header { display:flex; align-items:center; justify-content:space-between; }
.brand { font-size: 22px; font-weight: 700; color: #111827; }

.hero { border-radius: 16px; }
.hero-grid { display:grid; grid-template-columns: 1fr; gap:12px; align-items: start; }
.hero-main { overflow:hidden; border-radius:16px; }
.hero-side { display:grid; grid-template-columns: 1fr; gap:12px; align-content: start }
.hero-side-card { position:relative; border-radius:14px; overflow:hidden; border:1px solid #eee; background:#fff; }
.hero-side-card img { width:100%; height: 160px; object-fit:cover; display:block; }
.hero-side-card .overlay { position:absolute; left:10px; bottom:10px; background:rgba(0,0,0,.45); color:#fff; padding:6px 10px; border-radius:8px; font-size:12px }

.section { display: flex; flex-direction: column; gap: 12px; }
.section.card { background: #fff; border: 1px solid #eee; border-radius: 14px; padding: 14px; }
.section-header { display:flex; align-items:baseline; gap:8px; padding: 0 4px; justify-content: space-between; }
.section-header h2 { font-size: 18px; font-weight: 700; color:#111827; }
.section-header .sub { color:#6b7280; font-size: 12px; }
.section-header .view-all { font-size:12px; color:#2563eb; text-decoration:none }

@media (min-width: 900px) {
  .container { gap: 28px; padding: 20px; }
  .brand { font-size: 26px; }
  .section-header h2 { font-size: 20px; }
  .hero-side { grid-template-columns: 1fr; }
  .hero-side-card img { height: 180px; }
}
</style>
