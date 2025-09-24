<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
// Nuxt i18n composable to build locale-switching links
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore
import { useSwitchLocalePath } from '#i18n'

const router = useRouter()
const { $get, $post } = useApi()
const auth = useAuth()
const { t, locale: i18nLocale } = useI18n()
const switchLocalePath = useSwitchLocalePath()

const uiDir = computed(() => ((i18nLocale as any).value === 'ar' ? 'rtl' : 'ltr'))
const isAr = computed(() => (i18nLocale as any).value === 'ar')
const currentLangCode = computed(() => (isAr.value ? 'AR' : 'EN'))

// Config
const { data: cfg } = await useAsyncData<any>('cfg-header', async () => {
  try {
    return await $get('v1/config')
  } catch (e) {
    return { name: '6valley' }
  }
})

const brandName = computed(() => {
  const v = cfg?.value as any
  return v?.company_name || v?.value?.name || v?.name || '6valley'
})

// Categories (fallback if API not ready)
type Cat = { id: number | string; name: string; children?: Cat[] }
const fallbackCats: Cat[] = [
  { id: 1, name: 'مكياج', children: [
    { id: '1-1', name: 'الوجه' },
    { id: '1-2', name: 'العيون' },
    { id: '1-3', name: 'الشفاه' },
  ]},
  { id: 2, name: 'العناية', children: [
    { id: '2-1', name: 'البشرة' },
    { id: '2-2', name: 'الشعر' },
  ]},
  { id: 3, name: 'العطور' },
]

const { data: catRes } = await useAsyncData<any>('header-cats', async () => {
  try {
    // Try a few common shapes
    const r = await $get('v1/categories')
    return r
  } catch (e) {
    return null
  }
})

function normalizeCat(item: any): Cat {
  const childrenSrc = Array.isArray(item?.children)
    ? item.children
    : Array.isArray(item?.childes)
      ? item.childes
      : []
  return {
    id: item?.id ?? item?.category_id ?? String(Math.random()),
    name: item?.name || item?.translation?.name || item?.title || '',
    children: childrenSrc.map((sc: any) => normalizeCat(sc)),
  }
}

const categories = computed<Cat[]>(() => {
  const raw = catRes?.value
  if (!raw) return fallbackCats
  if (Array.isArray(raw)) return raw.map(normalizeCat)
  if (Array.isArray(raw?.data)) return raw.data.map(normalizeCat)
  if (Array.isArray(raw?.categories)) return raw.categories.map(normalizeCat)
  return fallbackCats
})

// UI State
const showCats = ref(false)
const searchOpen = ref(false)
const q = ref('')
const loadingSearch = ref(false)
const suggestions = ref<string[]>([])
const products = ref<any[]>([])
const langOpen = ref(false)
const loginModalOpen = ref(false)
// Keep a lightweight dictionary for suggestions/typo-fix
const recentNames = ref<string[]>([])

// Login form state
const loginForm = ref({
  email: '',
  password: ''
})
const loginLoading = ref(false)
const loginError = ref('')

// Build a dictionary from categories + popular chips + recent product names
const dictionary = computed<string[]>(() => {
  const set = new Set<string>()
  try {
    categories.value.forEach(c => {
      if (c?.name) set.add(String(c.name))
      ;(c.children || []).forEach(sc => { if (sc?.name) set.add(String(sc.name)) })
    })
  } catch {}
  popularChips.forEach(w => set.add(w))
  recentNames.value.forEach(w => set.add(w))
  return Array.from(set).filter(Boolean)
})

// Arabic-aware normalization to improve fuzzy matching
function normalizeArabic(input: string): string {
  return (input || '')
    .toLowerCase()
    .replace(/[\u064B-\u0652]/g, '') // remove diacritics
    .replace(/\u0640/g, '') // tatweel
    .replace(/[إأآا]/g, 'ا')
    .replace(/ة/g, 'ه')
    .replace(/ى/g, 'ي')
    .trim()
}

// Simple Levenshtein distance for typo tolerance
function lev(a: string, b: string): number {
  a = normalizeArabic(a)
  b = normalizeArabic(b)
  const m = a.length, n = b.length
  if (m === 0) return n
  if (n === 0) return m
  const dp = Array.from({ length: m + 1 }, () => new Array<number>(n + 1).fill(0))
  for (let i = 0; i <= m; i++) dp[i][0] = i
  for (let j = 0; j <= n; j++) dp[0][j] = j
  for (let i = 1; i <= m; i++) {
    for (let j = 1; j <= n; j++) {
      const cost = a[i - 1] === b[j - 1] ? 0 : 1
      dp[i][j] = Math.min(
        dp[i - 1][j] + 1,      // deletion
        dp[i][j - 1] + 1,      // insertion
        dp[i - 1][j - 1] + cost // substitution
      )
    }
  }
  return dp[m][n]
}

function getCorrections(term: string, max = 5): string[] {
  const q = normalizeArabic(term)
  if (!q) return []
  const pool = dictionary.value
  // Prefer contains matches first
  const containsMatches = pool.filter(w => normalizeArabic(w).includes(q))
  if (containsMatches.length) {
    // Sort by shortest and then lexicographically
    return containsMatches.sort((a,b)=>a.length - b.length || a.localeCompare(b)).slice(0, max)
  }
  // Fallback to Levenshtein distance
  const scored = pool.map(w => ({ w, d: lev(q, w) }))
  scored.sort((a,b)=> a.d - b.d || a.w.length - b.w.length)
  // Dynamic threshold: half the length (rounded up), min 1, max 4
  const threshold = Math.max(1, Math.min(4, Math.ceil(q.length / 2)))
  return scored.filter(s => s.d <= threshold).slice(0, max).map(s => s.w)
}

const popularChips = [
  'كريم اساس','كونسيلر','ريفلون','ماسكارا','برونزر','رموش','غسول','مرطب','سيروم','فيتامينات البشرة','العناية بالشعر'
]
const brandLogos = [
  'https://dummyimage.com/140x80/fff/333&text=TOPFACE',
  'https://dummyimage.com/140x80/fff/333&text=MAYBELLINE',
  'https://dummyimage.com/140x80/fff/333&text=NARS',
  'https://dummyimage.com/140x80/fff/333&text=ESSENCE',
]

async function fetchSearch(term: string) {
  if (!term || term.trim().length < 2) {
    suggestions.value = []
    products.value = []
    return
  }
  loadingSearch.value = true
  try {
    // Try common search endpoint shape; swallow errors gracefully
    const r: any = await $get(`v1/products`, { params: { search: term, name: term, limit: 7 } })
    const rawList = Array.isArray(r?.products)
      ? r.products
      : Array.isArray(r?.data)
        ? r.data
        : Array.isArray(r?.products?.data)
          ? r.products.data
          : (Array.isArray(r) ? r : [])
    // Normalize product fields used in UI
    const list = rawList.map((p: any) => ({
      ...p,
      name: p?.name || p?.product_name || p?.title || '',
      slug: p?.slug || p?.id,
      image_full_url: p?.image_full_url || p?.thumbnail || p?.image || p?.image_url,
      price: p?.price || p?.unit_price || p?.current_price || p?.selling_price,
    }))
    // Collect names for future suggestions
    recentNames.value = [
      ...new Set([
        ...recentNames.value,
        ...list.map((p: any) => String(p?.name || p?.product_name || '')).filter(Boolean)
      ])
    ]
    // Build suggestions: corrections + original term
    const corrections = getCorrections(term, 7)
    suggestions.value = Array.from(new Set([term, ...corrections]))
    products.value = list.slice(0, 7)
  } catch (e) {
    const corrections = getCorrections(term, 7)
    suggestions.value = Array.from(new Set([term, ...corrections]))
    products.value = []
  } finally {
    loadingSearch.value = false
  }
}

let timer: any
watch(q, (val) => {
  clearTimeout(timer)
  timer = setTimeout(() => fetchSearch(val), 250)
})

function goSearch(term?: string) {
  const query = (term ?? q.value).trim()
  if (!query) return
  searchOpen.value = false
  // If no current hits, try a best correction
  const [best] = getCorrections(query, 1)
  const finalQ = (products.value.length === 0 && best && best !== query) ? best : query
  router.push({ path: '/search', query: { q: finalQ } })
}

function goCategory(cat: Cat) {
  // Navigate to Shop with category filter applied
  router.push({ path: '/shop', query: { category: String(cat.id) } })
  showCats.value = false
}

function changeLocale(loc: 'ar' | 'en') {
  const path = switchLocalePath(loc)
  router.push(path)
  langOpen.value = false
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
      loginModalOpen.value = false
      loginForm.value = { email: '', password: '' }
    } else if (response?.token) {
      auth.setToken(response.token)
      auth.setUser(response.user || response.data)
      loginModalOpen.value = false
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
  loginModalOpen.value = true
  loginError.value = ''
  loginForm.value = { email: '', password: '' }
}

function closeLoginModal() {
  loginModalOpen.value = false
  loginError.value = ''
  loginForm.value = { email: '', password: '' }
}
</script>

<template>
  <header class="app-header" :dir="uiDir">
    <div class="container">
      <!-- Left group: logo + categories -->
      <div class="left">
        <NuxtLink to="/" class="brand">
          <span class="logo-circle">N</span>
          <span class="brand-text">{{ brandName }}</span>
        </NuxtLink>

        <div class="cats" @mouseenter="showCats = true" @mouseleave="showCats = false">
          <button class="cats-btn" type="button">
            <svg width="18" height="18" viewBox="0 0 24 24"><path fill="currentColor" d="M3 6h18v2H3V6m0 5h18v2H3v-2m0 5h18v2H3v-2Z"/></svg>
            <span>{{ t('categories') }}</span>
          </button>

          <div v-show="showCats" class="mega" @mouseenter="showCats = true" @mouseleave="showCats = false">
            <div class="mega-col" v-for="c in categories" :key="c.id">
              <div class="mega-title" @click="goCategory(c)">{{ c.name }}</div>
              <div class="mega-item" v-for="sc in c.children || []" :key="sc.id" @click="goCategory(sc)">
                {{ sc.name }}
              </div>
            </div>
            <div class="mega-side">
              <img src="https://dummyimage.com/220x160/f5f5f5/888&text=Promo" alt="promo" />
              <div class="brands">
                <img v-for="(b,i) in brandLogos" :key="i" :src="b" alt="brand" />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Middle: search -->
    <div class="middle" @click="searchOpen = true">
        <div class="search-box">
          <svg width="18" height="18" viewBox="0 0 24 24"><path fill="currentColor" d="M9.5 3A6.5 6.5 0 0 1 16 9.5c0 1.6-.58 3.07-1.54 4.2l.2.2h.84l5 5l-1.5 1.5l-5-5v-.84l-.2-.2A6.516 6.516 0 0 1 9.5 16A6.5 6.5 0 0 1 3 9.5A6.5 6.5 0 0 1 9.5 3m0 2A4.5 4.5 0 0 0 5 9.5A4.5 4.5 0 0 0 9.5 14A4.5 4.5 0 0 0 14 9.5A4.5 4.5 0 0 0 9.5 5Z"/></svg>
      <input type="text" :placeholder="t('search.placeholder')" readonly />
        </div>
      </div>

      <!-- Right: quick actions -->
      <nav class="right">
        <div class="action hide-sm">
          <svg width="18" height="18" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2l4 4h-3v6h-2V6H8l4-4Z"/></svg>
          <span>{{ t('deliver.to') }}</span>
        </div>

        <NuxtLink class="action hide-sm" to="/brands">{{ t('brands') }}</NuxtLink>

        <div class="lang" @mouseenter="langOpen = true" @mouseleave="langOpen = false">
          <button class="lang-btn" type="button">
            <svg width="18" height="18" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2a10 10 0 1 0 10 10A10.011 10.011 0 0 0 12 2Zm7.93 9h-3.09a15.63 15.63 0 0 0-1.19-6.05A8.009 8.009 0 0 1 19.93 11ZM12 4.07A13.6 13.6 0 0 1 13.86 11H10.14A13.6 13.6 0 0 1 12 4.07ZM4.07 13H7.2a15.63 15.63 0 0 0 1.19 6.05A8.009 8.009 0 0 1 4.07 13Zm0-2A8.009 8.009 0 0 1 8.39 4.95 15.63 15.63 0 0 0 7.2 11ZM12 19.93A13.6 13.6 0 0 1 10.14 13h3.72A13.6 13.6 0 0 1 12 19.93ZM14.61 19.05A15.63 15.63 0 0 0 15.8 13h3.23a8.009 8.009 0 0 1-4.42 6.05Z"/></svg>
            <span>{{ currentLangCode }}</span>
            <svg width="16" height="16" viewBox="0 0 24 24"><path fill="currentColor" d="M7 10l5 5l5-5z"/></svg>
          </button>

          <div v-if="langOpen" class="lang-menu" :dir="uiDir">
            <div class="lang-title">{{ t('languages') || 'اللغات' }}</div>
            <div class="lang-grid">
              <button class="lang-card" type="button" @click="changeLocale('ar')">
                <div class="row">
                  <div class="label">العربية</div>
                  <div class="meta">
                    <span class="badge">السعودية</span>
                    <span class="flag" aria-hidden>🇸🇦</span>
                    <span class="radio" :class="{ checked: isAr }" aria-hidden></span>
                  </div>
                </div>
              </button>
              <button class="lang-card" type="button" @click="changeLocale('en')">
                <div class="row">
                  <div class="label">English</div>
                  <div class="meta">
                    <span class="flag" aria-hidden>🇬🇧</span>
                    <span class="radio" :class="{ checked: !isAr }" aria-hidden></span>
                  </div>
                </div>
              </button>
            </div>
          </div>
        </div>

        <NuxtLink class="icon-btn" to="/cart" aria-label="Cart">
          <svg width="22" height="22" viewBox="0 0 24 24"><path fill="currentColor" d="M7 18a2 2 0 1 0 0 4a2 2 0 0 0 0-4m10 0a2 2 0 1 0 .001 4.001A2 2 0 0 0 17 18M7.16 14h9.53c.75 0 1.4-.42 1.73-1.05L22 7H6.21L5.27 5H2v2h2l3.6 7.59l-1.35 2.44A2 2 0 0 0 8 20h12v-2H8l1.1-2h8.59l-1.45 2H7.16Z"/></svg>
        </NuxtLink>

        <div class="profile">
          <template v-if="auth?.user?.value">
            <NuxtLink class="icon-btn" to="/account" aria-label="Account">
              <svg width="22" height="22" viewBox="0 0 24 24"><path fill="currentColor" d="M12 19.2c-2.5 0-7.5 1.25-7.5 3.75V25h15v-2.05c0-2.5-5-3.75-7.5-3.75M12 2a5 5 0 0 0-5 5a5 5 0 0 0 10 0a5 5 0 0 0-5-5Z"/></svg>
            </NuxtLink>
            <button class="text-btn" @click="auth.setToken(null); auth.setUser(null)">{{ t('logout') }}</button>
          </template>
          <template v-else>
            <button class="text-btn" @click="openLoginModal">{{ t('login') }}</button>
          </template>
        </div>
      </nav>
    </div>

    <!-- Search Overlay -->
    <teleport to="body">
      <div v-if="searchOpen" class="search-overlay" @click.self="searchOpen = false">
        <div class="so-header">
          <div class="so-search">
            <svg width="18" height="18" viewBox="0 0 24 24"><path fill="currentColor" d="M9.5 3A6.5 6.5 0 0 1 16 9.5c0 1.6-.58 3.07-1.54 4.2l.2.2h.84l5 5l-1.5 1.5l-5-5v-.84l-.2-.2A6.516 6.516 0 0 1 9.5 16A6.5 6.5 0 0 1 3 9.5A6.5 6.5 0 0 1 9.5 3m0 2A4.5 4.5 0 0 0 5 9.5A4.5 4.5 0 0 0 9.5 14A4.5 4.5 0 0 0 14 9.5A4.5 4.5 0 0 0 9.5 5Z"/></svg>
            <input v-model="q" type="text" :placeholder="t('search.placeholder')" @keyup.enter="goSearch()" autofocus />
          </div>
          <button class="text-btn" @click="searchOpen = false">إغلاق</button>
        </div>

        <div class="so-body">
          <div class="chips">
            <div class="chip" v-for="(c,i) in popularChips" :key="i" @click="goSearch(c)">{{ c }}</div>
          </div>

          <div class="brands-grid">
            <img v-for="(b,i) in brandLogos" :key="i" :src="b" alt="brand" />
          </div>

          <div class="suggest" v-if="q && (suggestions.length || products.length)">
            <div class="s-col">
              <div class="s-title">Suggestions</div>
              <div class="s-item" v-for="(s,i) in suggestions" :key="i" @click="goSearch(s)">
                <svg width="16" height="16" viewBox="0 0 24 24"><path fill="currentColor" d="M9.5 3A6.5 6.5 0 0 1 16 9.5c0 1.6-.58 3.07-1.54 4.2l.2.2h.84l5 5l-1.5 1.5l-5-5v-.84l-.2-.2A6.516 6.516 0 0 1 9.5 16A6.5 6.5 0 0 1 3 9.5A6.5 6.5 0 0 1 9.5 3Z"/></svg>
                <span v-html="s"></span>
              </div>
            </div>
            <div class="s-col">
              <div class="s-title">Products</div>
              <NuxtLink class="p-item" v-for="(p,i) in products" :key="i" :to="{ path:'/product/'+(p?.slug || p?.id), query: { from: 'search' } }" @click="searchOpen=false">
                <img :src="p?.thumbnail_full_url?.path|| p?.thumbnail || 'https://dummyimage.com/56x56/f0f0f0/aaa'" alt="p" />
                <div class="p-info">
                  <div class="p-name">{{ p?.name || p?.product_name || 'منتج' }}</div>
                  <div class="p-price" v-if="p?.price">
                    <b>{{ p.price }}</b>
                  </div>
                </div>
              </NuxtLink>
            </div>
          </div>
        </div>
      </div>
    </teleport>

    <!-- Login Modal -->
    <teleport to="body">
      <div v-if="loginModalOpen" class="login-overlay" @click.self="closeLoginModal">
        <div class="login-modal" :dir="uiDir">
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
            <p>{{ t('no_account') || 'ليس لديك حساب؟' }} 
              <NuxtLink to="/auth/register" @click="closeLoginModal">
                {{ t('register') || 'إنشاء حساب' }}
              </NuxtLink>
            </p>
          </div>
        </div>
      </div>
    </teleport>

    <!-- Bottom navigation (mobile) -->
    <nav class="bottom-nav">
      <NuxtLink to="/" class="bn-item">
        <svg width="22" height="22" viewBox="0 0 24 24"><path fill="currentColor" d="M10 20v-6h4v6h5v-8h3L12 3L2 12h3v8z"/></svg>
  <span>{{ t('home') }}</span>
      </NuxtLink>
      <NuxtLink to="/categories" class="bn-item">
        <svg width="22" height="22" viewBox="0 0 24 24"><path fill="currentColor" d="M3 6h18v2H3V6m0 5h18v2H3v-2m0 5h18v2H3v-2Z"/></svg>
  <span>{{ t('categories') }}</span>
      </NuxtLink>
      <NuxtLink to="/brands" class="bn-item">
        <svg width="22" height="22" viewBox="0 0 24 24"><path fill="currentColor" d="M7 5h10v2H7V5m0 6h10v2H7v-2m0 6h10v2H7v-2Z"/></svg>
  <span>{{ t('brands') }}</span>
      </NuxtLink>
      <NuxtLink to="/cart" class="bn-item">
        <svg width="22" height="22" viewBox="0 0 24 24"><path fill="currentColor" d="M7 18a2 2 0 1 0 0 4a2 2 0 0 0 0-4m10 0a2 2 0 1 0 .001 4.001A2 2 0 0 0 17 18M7.16 14h9.53c.75 0 1.4-.42 1.73-1.05L22 7H6.21L5.27 5H2v2h2l3.6 7.59l-1.35 2.44A2 2 0 0 0 8 20h12v-2H8l1.1-2h8.59l-1.45 2H7.16Z"/></svg>
  <span>{{ t('bag') }}</span>
      </NuxtLink>
      <NuxtLink :to="auth?.user?.value ? '/account' : '/auth/login'" class="bn-item">
        <svg width="22" height="22" viewBox="0 0 24 24"><path fill="currentColor" d="M12 19.2c-2.5 0-7.5 1.25-7.5 3.75V25h15v-2.05c0-2.5-5-3.75-7.5-3.75M12 2a5 5 0 0 0-5 5a5 5 0 0 0 10 0a5 5 0 0 0-5-5Z"/></svg>
  <span>{{ t('account') }}</span>
      </NuxtLink>
    </nav>
  </header>
</template>

<style scoped>
.app-header { border-bottom: 1px solid #eee; background: #fff; position: sticky; top: 0; z-index: 40; }
.container { max-width: 1200px; margin: 0 auto; padding: 12px 16px; display: grid; grid-template-columns: 1fr 2fr 1fr; gap: 16px; align-items: center; }
.left { display: flex; align-items: center; gap: 12px; }
.brand { display: inline-flex; align-items: center; gap: 8px; color: inherit; text-decoration: none; }
.logo-circle { width: 32px; height: 32px; border-radius: 50%; background: #000; color: #fff; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; }
.brand-text { font-weight: 700; }

.cats { position: relative; }
.cats-btn { display: inline-flex; align-items: center; gap: 8px; padding: 8px 10px; border: 1px solid #eee; border-radius: 10px; background: #fafafa; cursor: pointer; }
.cats-btn:hover { background: #f3f3f3; }

.mega { position: absolute; top: calc(100% + 0); right: 0; background: #fff; border: 1px solid #eee; box-shadow: 0 8px 24px rgba(0,0,0,.08); border-radius: 14px; padding: 16px; display: grid; grid-template-columns: repeat(4, minmax(160px, 1fr)) 240px; gap: 16px; width: min(1000px, 90vw); }
.mega-col { padding: 8px 0; }
.mega-title { font-weight: 700; margin-bottom: 10px; cursor: pointer; }
.mega-item { padding: 6px 0; color: #333; cursor: pointer; }
.mega-item:hover { color: #6b46c1; }
.mega-side { display: grid; gap: 12px; align-content: start; }
.mega-side img { width: 100%; border-radius: 12px; border: 1px solid #eee; }
.brands { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
.brands img { width: 100%; height: 60px; object-fit: contain; background: #fff; border: 1px solid #eee; border-radius: 12px; }

.middle .search-box { display: flex; align-items: center; gap: 8px; border: 1px solid #eee; border-radius: 999px; padding: 10px 14px; background: #fafafa; cursor: text; }
.middle input { border: 0; outline: 0; background: transparent; width: 100%; }

.right { display: flex; align-items: center; justify-content: flex-end; gap: 12px; }
.action { display: inline-flex; align-items: center; gap: 8px; color: #555; }
.icon-btn { display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 50%; border: 1px solid #eee; }
.text-btn { background: transparent; border: 0; color: #6b46c1; cursor: pointer; }
.profile { display: inline-flex; align-items: center; gap: 10px; }

/* Language dropdown */
.lang { position: relative; }
.lang-btn { display: inline-flex; align-items: center; gap: 6px; border: 1px solid #eee; border-radius: 10px; padding: 8px 10px; background: #fff; cursor: pointer; }
.lang-menu { position: absolute; top: calc(100% + 0); inset-inline-end: 0; background: #fff; border: 1px solid #eee; border-radius: 12px; box-shadow: 0 10px 28px rgba(0,0,0,.12); width: 360px; padding: 12px; z-index: 50; }
.lang-title { text-align: center; font-weight: 700; color: #777; margin-bottom: 10px; }
.lang-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.lang-card { background: #fff; border: 1px solid #eee; border-radius: 12px; padding: 12px; cursor: pointer; text-align: start; }
.lang-card:hover { border-color: #d9d9d9; background: #fafafa; }
.lang-card .row { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
.lang-card .label { font-weight: 600; }
.lang-card .meta { display: inline-flex; align-items: center; gap: 8px; color: #555; }
.lang-card .badge { background: #eef7ee; color: #2b8a3e; border: 1px solid #d1ecd1; border-radius: 6px; padding: 2px 6px; font-size: 12px; }
.lang-card .flag { font-size: 18px; }
.lang-card .radio { width: 18px; height: 18px; border: 2px solid #bbb; border-radius: 50%; display: inline-block; position: relative; }
.lang-card .radio.checked { border-color: #6b46c1; }
.lang-card .radio.checked::after { content: ''; position: absolute; inset: 3px; background: #6b46c1; border-radius: 50%; }

.search-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.35); z-index: 60; display: grid; place-items: start center; padding-top: 6vh; }
.search-overlay .so-header { width: min(980px, 92vw); background: #fff; border-radius: 16px; padding: 12px; display: flex; align-items: center; justify-content: space-between; gap: 12px; box-shadow: 0 6px 18px rgba(0,0,0,.12); }
.so-search { flex: 1; display: flex; align-items: center; gap: 8px; border: 1px solid #eee; border-radius: 12px; padding: 10px 12px; }
.so-search input { border: 0; outline: 0; width: 100%; }

.so-body { width: min(980px, 92vw); background: #fff; border-radius: 16px; margin-top: 10px; padding: 16px; box-shadow: 0 6px 18px rgba(0,0,0,.12); }
.chips { display: flex; flex-wrap: wrap; gap: 10px; }
.chip { padding: 8px 12px; border: 1px solid #e5e5e5; border-radius: 999px; cursor: pointer; background: #fff; }
.chip:hover { background: #faf7ff; border-color: #e0d7ff; }
.brands-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 12px; margin: 16px 0; }
.brands-grid img { width: 100%; height: 76px; object-fit: contain; border: 1px solid #eee; border-radius: 12px; background: #fff; }

.suggest { display: grid; grid-template-columns: 1fr 2fr; gap: 16px; margin-top: 10px; }
.s-title { font-weight: 700; margin-bottom: 8px; }
.s-item { display: flex; align-items: center; gap: 8px; padding: 8px 6px; border-radius: 8px; cursor: pointer; }
.s-item:hover { background: #fafafa; }
.p-item { display: grid; grid-template-columns: 56px 1fr; gap: 10px; align-items: center; padding: 8px; border-radius: 10px; color: inherit; text-decoration: none; }
.p-item:hover { background: #fafafa; }
.p-item img { width: 56px; height: 56px; object-fit: cover; border-radius: 10px; border: 1px solid #eee; }
.p-name { font-size: 14px; line-height: 1.3; }
.p-price { color: #e91e63; }

.bottom-nav { position: fixed; bottom: 0; inset-inline: 0; background: #fff; border-top: 1px solid #eee; display: none; justify-content: space-around; padding: 6px 0; z-index: 45; }
.bn-item { display: inline-flex; flex-direction: column; align-items: center; gap: 2px; color: #444; text-decoration: none; font-size: 12px; }

/* Login Modal Styles */
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
  background: #fff;
  border-radius: 16px;
  width: 100%;
  max-width: 400px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
  animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: translateY(-20px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.login-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px 16px;
  border-bottom: 1px solid #eee;
}

.login-header h2 {
  margin: 0;
  font-size: 20px;
  font-weight: 700;
  color: #333;
}

.close-btn {
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px;
  border-radius: 6px;
  color: #666;
  display: flex;
  align-items: center;
  justify-content: center;
}

.close-btn:hover {
  background: #f5f5f5;
  color: #333;
}

.login-form {
  padding: 24px;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 6px;
  font-weight: 600;
  color: #333;
  font-size: 14px;
}

.form-group input {
  width: 100%;
  padding: 12px 16px;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 14px;
  transition: border-color 0.2s ease;
  box-sizing: border-box;
}

.form-group input:focus {
  outline: none;
  border-color: #6b46c1;
  box-shadow: 0 0 0 3px rgba(107, 70, 193, 0.1);
}

.form-group input:disabled {
  background: #f5f5f5;
  color: #999;
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

.login-footer {
  padding: 16px 24px 24px;
  text-align: center;
  border-top: 1px solid #eee;
}

.login-footer p {
  margin: 0;
  color: #666;
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

@media (max-width: 768px) {
  .container { grid-template-columns: 1fr; gap: 8px; }
  .left { justify-content: space-between; }
  .middle { grid-row: 2; }
  .right .hide-sm { display: none; }
  .mega { display: none !important; }
  .bottom-nav { display: flex; }
  
  .login-modal {
    margin: 10px;
    max-width: none;
  }
  
  .login-header,
  .login-form,
  .login-footer {
    padding-left: 20px;
    padding-right: 20px;
  }
}
</style>
