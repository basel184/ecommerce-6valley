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

// Get first 5 main categories for navigation
const mainCategories = computed<Cat[]>(() => {
  const cats = categories.value
  // If we have categories from API, take first 5
  if (cats.length > 0) {
    return cats.slice(0, 5)
  }
  // Fallback to predefined categories
  return fallbackCats.slice(0, 5)
})

// UI State
const showCats = ref(false)
const searchOpen = ref(false)
const q = ref('')
const loadingSearch = ref(false)
const suggestions = ref<string[]>([])
const products = ref<any[]>([])
const showLang = ref(false)
const loginModalOpen = ref(false)
const hoveredCategory = ref<any>(null)
const hoveredMegaCategory = ref<any>(null)
const keepMegaMenuOpen = ref(false)
// Keep a lightweight dictionary for suggestions/typo-fix
const recentNames = ref<string[]>([])

// Login form state
const loginForm = ref({
  email: '',
  password: ''
})
const loginLoading = ref(false)
const loginError = ref('')

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
  // Navigate to category page or shop with category filter
  if (cat.id) {
  router.push({ path: '/shop', query: { category: String(cat.id) } })
  }
  showCats.value = false
}

function handleMegaMenuLeave() {
  // Add a small delay to allow moving to subcategories
  setTimeout(() => {
    if (!keepMegaMenuOpen.value) {
      showCats.value = false
      hoveredMegaCategory.value = null
    }
  }, 150)
}

// Reset keepMegaMenuOpen when showCats changes
watch(showCats, (newValue) => {
  if (!newValue) {
    keepMegaMenuOpen.value = false
    hoveredMegaCategory.value = null
  }
})

function changeLocale(loc: 'ar' | 'en') {
  const path = switchLocalePath(loc)
  router.push(path)
  showLang.value = false
}

function switchTo(loc: 'ar' | 'en') {
  changeLocale(loc)
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

// Register functions
function openRegisterModal() {
  showRegisterModal.value = true
  loginModalOpen.value = false
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

function closeRegisterModal() {
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

async function handleRegisterSubmit() {
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
</script>

<template>
  <header class="app-header" :dir="uiDir">
    <!-- Main Header -->
    <div class="main-header">
    <div class="container">
        <!-- Logo Section -->
        <div class="logo-section">
        <NuxtLink to="/" class="brand">
            <div class="logo-container">
          <span class="logo-circle">N</span>
              <div class="brand-text">
                <span class="brand-arabic">نايس ون</span>
                <span class="brand-english">NICE ONE</span>
              </div>
            </div>
          </NuxtLink>
      </div>

        <!-- Search Section -->
        <div class="search-section">
          <div class="search-container" @click="searchOpen = true">
        <div class="search-box">
              <svg width="20" height="20" viewBox="0 0 24 24" class="search-icon">
                <path fill="currentColor" d="M9.5 3A6.5 6.5 0 0 1 16 9.5c0 1.6-.58 3.07-1.54 4.2l.2.2h.84l5 5l-1.5 1.5l-5-5v-.84l-.2-.2A6.516 6.516 0 0 1 9.5 16A6.5 6.5 0 0 1 3 9.5A6.5 6.5 0 0 1 9.5 3m0 2A4.5 4.5 0 0 0 5 9.5A4.5 4.5 0 0 0 9.5 14A4.5 4.5 0 0 0 14 9.5A4.5 4.5 0 0 0 9.5 5Z"/>
              </svg>
              <input type="text" :placeholder="t('search.placeholder')" readonly class="search-input" />
        </div>
      </div>
        </div>

        <!-- Actions Section -->
        <div class="actions-section">
          <!-- Language Selector -->
          <div class="lang" @click="showLang = !showLang">
            <button class="lang-btn">
              <svg width="16" height="16" viewBox="0 0 24 24">
                <path fill="currentColor" d="M12.87 15.07l-2.54-2.51.03-.03c1.74-1.94 2.98-4.17 3.71-6.53H17V4h-7V2H8v2H1v1.99h11.17C11.5 7.92 10.44 9.75 9 11.35 8.07 10.32 7.3 9.19 6.69 8h-2c.73 1.63 1.73 3.17 2.98 4.56l-5.09 5.02L4 19l5-5 3.11 3.11.76-2.04zM18.5 10h-2L12 22h2l1.12-3h4.75L21 22h2l-4.5-12zm-2.62 7l1.62-4.33L19.12 17h-3.24z"/>
              </svg>
            <span>{{ currentLangCode }}</span>
              <svg width="12" height="12" viewBox="0 0 24 24" class="chevron">
                <path fill="currentColor" d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/>
              </svg>
          </button>

            <div v-show="showLang" class="lang-menu">
              <div class="lang-title">{{ t('select_language') || 'اختر اللغة' }}</div>
            <div class="lang-grid">
                <div class="lang-card" :class="{ checked: isAr }" @click="switchTo('ar')">
                <div class="row">
                  <div class="meta">
                      <span class="flag">🇸🇦</span>
                      <span class="label">العربية</span>
                  </div>
                    <div class="radio" :class="{ checked: isAr }"></div>
                </div>
                </div>
                <div class="lang-card" :class="{ checked: !isAr }" @click="switchTo('en')">
                <div class="row">
                  <div class="meta">
                      <span class="flag">🇺🇸</span>
                      <span class="label">English</span>
                  </div>
                    <div class="radio" :class="{ checked: !isAr }"></div>
                </div>
                </div>
            </div>
          </div>
        </div>

          <!-- User Account -->
        <div class="profile">
          <template v-if="auth?.user?.value">
              <NuxtLink class="profile-btn" to="/account">
                <svg width="20" height="20" viewBox="0 0 24 24">
                  <path fill="currentColor" d="M12 19.2c-2.5 0-7.5 1.25-7.5 3.75V25h15v-2.05c0-2.5-5-3.75-7.5-3.75M12 2a5 5 0 0 0-5 5a5 5 0 0 0 10 0a5 5 0 0 0-5-5Z"/>
                </svg>
                <span>{{ t('my_account') || 'حسابي' }}</span>
            </NuxtLink>
              <button class="logout-btn" @click="auth.setToken(null); auth.setUser(null)">
                {{ t('logout') }}
              </button>
          </template>
          <template v-else>
              <button class="login-btn" @click="openLoginModal">
                <svg width="20" height="20" viewBox="0 0 24 24">
                  <path fill="currentColor" d="M12 19.2c-2.5 0-7.5 1.25-7.5 3.75V25h15v-2.05c0-2.5-5-3.75-7.5-3.75M12 2a5 5 0 0 0-5 5a5 5 0 0 0 10 0a5 5 0 0 0-5-5Z"/>
                </svg>
                <span>{{ t('login') || 'تسجيل دخول' }}</span>
              </button>
          </template>
        </div>

          <!-- Notifications -->
          <button class="action-btn notification-btn">
            <svg width="20" height="20" viewBox="0 0 24 24">
              <path fill="currentColor" d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/>
            </svg>
            <span class="notification-badge">3</span>
          </button>

          <!-- Cart -->
          <NuxtLink to="/cart" class="action-btn cart-btn">
            <svg width="20" height="20" viewBox="0 0 24 24">
              <path fill="currentColor" d="M7 18a2 2 0 1 0 0 4a2 2 0 0 0 0-4m10 0a2 2 0 1 0 .001 4.001A2 2 0 0 0 17 18M7.16 14h9.53c.75 0 1.4-.42 1.73-1.05L22 7H6.21L5.27 5H2v2h2l3.6 7.59l-1.35 2.44A2 2 0 0 0 8 20h12v-2H8l1.1-2h8.59l-1.45 2H7.16Z"/>
            </svg>
            <span class="cart-badge">2</span>
          </NuxtLink>
        </div>
      </div>
    </div>

    <!-- Navigation Bar -->
    <nav class="nav-bar">
      <div class="container">
        <div class="nav-left">
          <div class="categories-dropdown" @mouseenter="showCats = true" @mouseleave="showCats = false">
            <button class="categories-btn">
              <svg width="18" height="18" viewBox="0 0 24 24">
                <path fill="currentColor" d="M3 6h18v2H3V6m0 5h18v2H3v-2m0 5h18v2H3v-2Z"/>
              </svg>
              <span>{{ t('all_categories') || 'جميع الأقسام' }}</span>
              <svg width="12" height="12" viewBox="0 0 24 24" class="chevron">
                <path fill="currentColor" d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/>
              </svg>
            </button>

            <div v-show="showCats" class="mega-menu" @mouseenter="showCats = true" @mouseleave="handleMegaMenuLeave">
              <div class="mega-content">
                <!-- Main Categories Column -->
                <div class="mega-main-categories">
                  <div 
                    v-for="c in categories" 
                    :key="c.id" 
                    class="mega-category-item"
                    @mouseenter="hoveredMegaCategory = c; keepMegaMenuOpen = true"
                    @mouseleave="keepMegaMenuOpen = false"
                  >
                    <div class="mega-title" @click="goCategory(c)">
                      {{ c.name }}
                      <svg v-if="c.children && c.children.length > 0" width="12" height="12" viewBox="0 0 24 24" class="mega-arrow">
                        <path fill="currentColor" d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/>
                      </svg>
                    </div>
                  </div>
                </div>
                
                <!-- Subcategories Area -->
                <div class="mega-subcategories-area" @mouseenter="keepMegaMenuOpen = true" @mouseleave="keepMegaMenuOpen = false">
                  <div v-if="hoveredMegaCategory && hoveredMegaCategory.children && hoveredMegaCategory.children.length > 0" class="mega-subcategories">
                    <div class="mega-subcategories-content">
                      <div 
                        v-for="sc in hoveredMegaCategory.children" 
                        :key="sc.id" 
                        class="mega-subcategory-item"
                        @click="goCategory(sc)"
                      >
                        {{ sc.name }}
                      </div>
                    </div>
                  </div>
                  <div v-else class="mega-placeholder">
                    <img src="https://dummyimage.com/220x160/f5f5f5/888&text=Promo" alt="promo" />
                    <div class="brands">
                      <img v-for="(b,i) in brandLogos" :key="i" :src="b" alt="brand" />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="nav-center">
          <div class="nav-links">
            <div 
              v-for="category in mainCategories" 
              :key="category.id" 
              class="nav-item"
              @mouseenter="hoveredCategory = category"
              @mouseleave="hoveredCategory = null"
            >
              <NuxtLink 
                :to="`/category/${category.id}`" 
                class="nav-link"
                @click="goCategory(category)"
              >
                {{ category.name }}
                <svg v-if="category.children && category.children.length > 0" width="12" height="12" viewBox="0 0 24 24" class="nav-arrow">
                  <path fill="currentColor" d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/>
                </svg>
              </NuxtLink>
              
              <!-- Subcategories Dropdown -->
              <div 
                v-if="hoveredCategory && hoveredCategory.id === category.id && category.children && category.children.length > 0" 
                class="subcategories-dropdown"
                @mouseenter="hoveredCategory = category"
                @mouseleave="hoveredCategory = null"
              >
                <div class="subcategories-content">
                  <NuxtLink 
                    v-for="subcategory in category.children" 
                    :key="subcategory.id"
                    :to="`/category/${subcategory.id}`" 
                    class="subcategory-link"
                    @click="goCategory(subcategory)"
                  >
                    {{ subcategory.name }}
                  </NuxtLink>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </nav>


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
              <a href="#" @click.prevent="openRegisterModal">
                {{ t('register') || 'إنشاء حساب' }}
              </a>
            </p>
          </div>
        </div>
      </div>
    </teleport>

    <!-- Register Modal -->
    <teleport to="body">
      <div v-if="showRegisterModal" class="login-overlay" @click.self="closeRegisterModal">
        <div class="login-modal" :dir="uiDir">
          <div class="login-header">
            <h2>{{ t('register') || 'إنشاء حساب جديد' }}</h2>
            <button class="close-btn" @click="closeRegisterModal">
              <svg width="20" height="20" viewBox="0 0 24 24"><path fill="currentColor" d="M19 6.41L17.59 5L12 10.59L6.41 5L5 6.41L10.59 12L5 17.59L6.41 19L12 13.41L17.59 19L19 17.59L13.41 12z"/></svg>
            </button>
          </div>
          
          <form @submit.prevent="handleRegisterSubmit" class="login-form">
            <div class="form-row">
              <div class="form-group">
                <label for="f_name">{{ t('first_name') || 'الاسم الأول' }} *</label>
                <input 
                  id="f_name" 
                  v-model="registerForm.f_name" 
                  type="text" 
                  :placeholder="t('first_name') || 'الاسم الأول'" 
                  required 
                  :disabled="registerLoading" 
                />
              </div>
              <div class="form-group">
                <label for="l_name">{{ t('last_name') || 'الاسم الأخير' }} *</label>
                <input 
                  id="l_name" 
                  v-model="registerForm.l_name" 
                  type="text" 
                  :placeholder="t('last_name') || 'الاسم الأخير'" 
                  required 
                  :disabled="registerLoading" 
                />
              </div>
            </div>

            <div class="form-group">
              <label for="register_email">{{ t('email') || 'البريد الإلكتروني' }} *</label>
              <input 
                id="register_email" 
                v-model="registerForm.email" 
                type="email" 
                :placeholder="t('email') || 'البريد الإلكتروني'" 
                required 
                :disabled="registerLoading" 
              />
            </div>

            <div class="form-group">
              <label for="register_phone">{{ t('phone') || 'رقم الهاتف' }} *</label>
              <input 
                id="register_phone" 
                v-model="registerForm.phone" 
                type="tel" 
                :placeholder="t('phone') || 'رقم الهاتف'" 
                required 
                :disabled="registerLoading" 
              />
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="register_password">{{ t('password') || 'كلمة المرور' }} *</label>
                <input 
                  id="register_password" 
                  v-model="registerForm.password" 
                  type="password" 
                  :placeholder="t('password') || 'كلمة المرور'" 
                  required 
                  :disabled="registerLoading" 
                />
              </div>
              <div class="form-group">
                <label for="password_confirmation">{{ t('confirm_password') || 'تأكيد كلمة المرور' }} *</label>
                <input 
                  id="password_confirmation" 
                  v-model="registerForm.password_confirmation" 
                  type="password" 
                  :placeholder="t('confirm_password') || 'تأكيد كلمة المرور'" 
                  required 
                  :disabled="registerLoading" 
                />
              </div>
            </div>

            <div class="form-group">
              <label for="referral_code">{{ t('referral_code') || 'كود الإحالة' }} ({{ t('optional') || 'اختياري' }})</label>
              <input 
                id="referral_code" 
                v-model="registerForm.referral_code" 
                type="text" 
                :placeholder="t('referral_code') || 'كود الإحالة'" 
                :disabled="registerLoading" 
              />
            </div>

            <div v-if="registerError" class="error-message">
              {{ registerError }}
            </div>

            <button type="submit" class="login-btn" :disabled="registerLoading">
              <span v-if="registerLoading">{{ t('creating_account') || 'جاري إنشاء الحساب...' }}</span>
              <span v-else>{{ t('register') || 'إنشاء حساب' }}</span>
            </button>
          </form>
          
          <div class="login-footer">
            <p>{{ t('have_account') || 'لديك حساب بالفعل؟' }} 
              <a href="#" @click.prevent="openLoginModal(); closeRegisterModal()">
                {{ t('login') || 'تسجيل الدخول' }}
              </a>
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
/* Main Header Container */
.app-header { 
  background: #fff; 
  position: sticky; 
  top: 0; 
  z-index: 50;
  box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
  border-bottom: 1px solid #f0f0f0;
}

.container { 
  max-width: 1200px; 
  margin: 0 auto; 
  padding: 0 20px; 
}

/* Top Bar */
.top-bar {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 8px 0;
  font-size: 14px;
}

.top-bar .container {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.tagline {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 500;
}

.tagline-icon {
  color: #ffd700;
}

.delivery-info {
  display: flex;
  align-items: center;
  gap: 6px;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 6px;
  transition: background-color 0.2s ease;
}

.delivery-info:hover {
  background: rgba(255, 255, 255, 0.1);
}

.location-icon {
  color: #ffd700;
}

.chevron {
  transition: transform 0.2s ease;
}

.delivery-info:hover .chevron {
  transform: rotate(180deg);
}

/* Main Header */
.main-header {
  padding: 16px 0;
  border-bottom: 1px solid #f0f0f0;
}

.main-header .container {
  display: grid;
  grid-template-columns: 1fr 2fr 1fr;
  gap: 24px;
  align-items: center;
}

/* Logo Section */
.logo-section {
  display: flex;
  align-items: center;
}

.brand {
  color: inherit;
  text-decoration: none;
  transition: transform 0.2s ease;
}

.brand:hover {
  transform: scale(1.02);
}

.logo-container {
  display: flex;
  align-items: center;
  gap: 12px;
}

.logo-circle {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 20px;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.brand-text {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.brand-arabic {
  font-weight: 700;
  font-size: 18px;
  color: #333;
  line-height: 1.2;
}

.brand-english {
  font-weight: 600;
  font-size: 14px;
  color: #666;
  letter-spacing: 1px;
}

/* Search Section */
.search-section {
  display: flex;
  justify-content: center;
}

.search-container {
  width: 100%;
  max-width: 500px;
}

.search-box {
  display: flex;
  align-items: center;
  gap: 12px;
  border: 2px solid #f0f0f0;
  border-radius: 50px;
  padding: 14px 20px;
  background: #fafafa;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.search-box:hover {
  border-color: #667eea;
  background: #fff;
  box-shadow: 0 4px 16px rgba(102, 126, 234, 0.1);
}

.search-icon {
  color: #999;
  transition: color 0.2s ease;
}

.search-box:hover .search-icon {
  color: #667eea;
}

.search-input {
  border: 0;
  outline: 0;
  background: transparent;
  width: 100%;
  font-size: 16px;
  color: #333;
}

.search-input::placeholder {
  color: #999;
}

/* Actions Section */
.actions-section {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 16px;
}

/* Language Selector */
.lang {
  position: relative;
}

.lang-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  border: 1px solid #e0e0e0;
  border-radius: 12px;
  padding: 10px 14px;
  background: #fff;
  cursor: pointer;
  transition: all 0.2s ease;
  font-weight: 500;
  color: #333;
}

.lang-btn:hover {
  border-color: #667eea;
  background: #f8f9ff;
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.1);
}

.lang-menu {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  background: #fff;
  border: 1px solid #e0e0e0;
  border-radius: 16px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
  width: 280px;
  padding: 16px;
  z-index: 60;
  animation: slideDown 0.2s ease-out;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.lang-title {
  text-align: center;
  font-weight: 700;
  color: #333;
  margin-bottom: 12px;
  font-size: 16px;
}

.lang-grid {
  display: grid;
  gap: 8px;
}

.lang-card {
  background: #fff;
  border: 2px solid #f0f0f0;
  border-radius: 12px;
  padding: 12px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.lang-card:hover {
  border-color: #e0e0e0;
  background: #f8f9ff;
}

.lang-card.checked {
  border-color: #667eea;
  background: #f8f9ff;
}

.lang-card .row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.lang-card .meta {
  display: flex;
  align-items: center;
  gap: 10px;
}

.lang-card .label {
  font-weight: 600;
  color: #333;
}

.lang-card .flag {
  font-size: 20px;
}

.lang-card .radio {
  width: 20px;
  height: 20px;
  border: 2px solid #ddd;
  border-radius: 50%;
  position: relative;
  transition: all 0.2s ease;
}

.lang-card .radio.checked {
  border-color: #667eea;
}

.lang-card .radio.checked::after {
  content: '';
  position: absolute;
  inset: 4px;
  background: #667eea;
  border-radius: 50%;
}

/* Profile Section */
.profile {
  display: flex;
  align-items: center;
  gap: 12px;
}

.profile-btn, .login-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  border: 1px solid #e0e0e0;
  border-radius: 12px;
  background: #fff;
  color: #333;
  text-decoration: none;
  cursor: pointer;
  transition: all 0.2s ease;
  font-weight: 500;
}

.profile-btn:hover, .login-btn:hover {
  border-color: #667eea;
  background: #f8f9ff;
  color: #667eea;
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.1);
}

.logout-btn {
  background: transparent;
  border: 1px solid #e0e0e0;
  color: #666;
  padding: 8px 12px;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 14px;
}

.logout-btn:hover {
  background: #fee;
  border-color: #fcc;
  color: #c53030;
}

/* Action Buttons */
.action-btn {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  border: 1px solid #e0e0e0;
  border-radius: 12px;
  background: #fff;
  color: #333;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
}

.action-btn:hover {
  border-color: #667eea;
  background: #f8f9ff;
  color: #667eea;
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.1);
}

.notification-badge, .cart-badge {
  position: absolute;
  top: -6px;
  right: -6px;
  background: #ff4757;
  color: white;
  border-radius: 50%;
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 600;
  border: 2px solid #fff;
}

/* Navigation Bar */
.nav-bar {
  background: #f8f9fa;
  padding: 16px 0;
  border-bottom: 1px solid #e0e0e0;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.nav-bar .container {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.nav-left {
  display: flex;
  align-items: center;
}

.categories-dropdown {
  position: relative;
}

.categories-btn {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 20px;
  border: 1px solid #e0e0e0;
  border-radius: 12px;
  background: #fff;
  cursor: pointer;
  transition: all 0.2s ease;
  font-weight: 600;
  color: #333;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.categories-btn:hover {
  border-color: #667eea;
  background: #f8f9ff;
  color: #667eea;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
}

.nav-center {
  flex: 1;
  display: flex;
  justify-content: center;
  align-items: center;
}

.nav-links {
  display: flex;
  gap: 20px;
  align-items: center;
  flex-wrap: wrap;
  justify-content: center;
  min-height: 40px;
}

.nav-link {
  color: #333;
  text-decoration: none;
  font-weight: 600;
  padding: 10px 18px;
  border-radius: 8px;
  transition: all 0.3s ease;
  position: relative;
  white-space: nowrap;
  font-size: 15px;
  display: inline-block;
  text-align: center;
  min-width: 80px;
}

.nav-link:hover {
  color: #667eea;
  background: rgba(102, 126, 234, 0.1);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
}

.nav-link::after {
  content: '';
  position: absolute;
  bottom: -3px;
  left: 50%;
  transform: translateX(-50%);
  width: 0;
  height: 3px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  transition: width 0.3s ease;
  border-radius: 2px;
}

.nav-link:hover::after {
  width: 90%;
}

.nav-link:active {
  transform: translateY(-1px);
}

/* Navigation Item with Dropdown */
.nav-item {
  position: relative;
  display: inline-block;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 6px;
}

.nav-arrow {
  transition: transform 0.2s ease;
  opacity: 0.7;
}

.nav-item:hover .nav-arrow {
  transform: rotate(180deg);
  opacity: 1;
}

/* Subcategories Dropdown */
.subcategories-dropdown {
  position: absolute;
  top: 80%;
  left: 0;
  background: #fff;
  border: 1px solid #e0e0e0;
  border-radius: 12px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
  z-index: 100;
  min-width: 400px;
  animation: slideDown 0.2s ease-out;
  margin-top: 8px;
}

.subcategories-content {
  padding: 16px 0;
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0;
  max-height: 300px;
  overflow-y: auto;
}

.subcategory-link {
  display: block;
  padding: 12px 16px;
  color: #333;
  text-decoration: none;
  font-weight: 500;
  font-size: 14px;
  transition: all 0.2s ease;
  border-left: 3px solid transparent;
  border-bottom: 1px solid #f0f0f0;
  text-align: center;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.subcategory-link:hover {
  background: rgba(102, 126, 234, 0.1);
  color: #667eea;
  border-left-color: #667eea;
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.2);
}

.subcategory-link:nth-child(3n+1) {
  border-top-left-radius: 12px;
}

.subcategory-link:nth-child(3n) {
  border-top-right-radius: 12px;
}

.subcategory-link:nth-last-child(-n+3) {
  border-bottom: none;
}

.subcategory-link:nth-last-child(1) {
  border-bottom-left-radius: 12px;
}

.subcategory-link:nth-last-child(2) {
  border-bottom-right-radius: 12px;
}

/* RTL Support for Subcategories */
[dir="rtl"] .subcategories-dropdown {
  left: auto;
  right: 0;
}

[dir="rtl"] .subcategory-link {
  border-left: none;
  border-right: 3px solid transparent;
  text-align: center;
}

[dir="rtl"] .subcategory-link:hover {
  border-left-color: transparent;
  border-right-color: #667eea;
}

[dir="rtl"] .subcategory-link:nth-child(3n+1) {
  border-top-left-radius: 0;
  border-top-right-radius: 12px;
}

[dir="rtl"] .subcategory-link:nth-child(3n) {
  border-top-right-radius: 0;
  border-top-left-radius: 12px;
}

[dir="rtl"] .subcategory-link:nth-last-child(1) {
  border-bottom-left-radius: 0;
  border-bottom-right-radius: 12px;
}

[dir="rtl"] .subcategory-link:nth-last-child(2) {
  border-bottom-right-radius: 0;
  border-bottom-left-radius: 12px;
}

/* Mega Menu */
.mega-menu {
  position: absolute;
  top: calc(100% + 0px);
  right: 0;
  background: #fff;
  border: 1px solid #e0e0e0;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
  width: min(1000px, 90vw);
  z-index: 60;
  animation: slideDown 0.3s ease-out;
}

.mega-content {
  display: grid;
  grid-template-columns: 200px 1fr;
  gap: 0;
  padding: 0;
  min-height: 400px;
}

/* Main Categories Column */
.mega-main-categories {
  background: #f8f9ff;
  border-radius: 16px 0 0 16px;
  padding: 20px 0;
  border-right: 1px solid #e0e0e0;
}

.mega-category-item {
  position: relative;
  transition: all 0.2s ease;
}

.mega-category-item:hover {
  background: rgba(102, 126, 234, 0.05);
}

.mega-title {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-weight: 700;
  color: #333;
  padding: 12px 20px;
  cursor: pointer;
  transition: all 0.2s ease;
  border-right: 3px solid transparent;
  font-size: 15px;
}

.mega-title:hover {
  color: #667eea;
  background: rgba(102, 126, 234, 0.1);
  border-right-color: #667eea;
}

.mega-arrow {
  transition: transform 0.2s ease;
  opacity: 0.7;
}

.mega-category-item:hover .mega-arrow {
  transform: rotate(90deg);
  opacity: 1;
}

/* Subcategories Area */
.mega-subcategories-area {
  padding: 20px;
  display: flex;
  align-items: flex-start;
  position: relative;
}

.mega-subcategories {
  width: 100%;
}

.mega-subcategories-content {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0;
  max-height: 350px;
  overflow-y: auto;
  position: relative;
  z-index: 1;
}

.mega-subcategory-item {
  display: block;
  padding: 12px 16px;
  color: #333;
  text-decoration: none;
  font-weight: 500;
  font-size: 14px;
  transition: all 0.2s ease;
  border-left: 3px solid transparent;
  border-bottom: 1px solid #f0f0f0;
  text-align: center;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  cursor: pointer;
  position: relative;
}

.mega-subcategory-item:hover {
  background: rgba(102, 126, 234, 0.1);
  color: #667eea;
  border-left-color: #667eea;
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.2);
  z-index: 2;
}

.mega-subcategory-item:nth-child(3n+1) {
  border-top-left-radius: 12px;
}

.mega-subcategory-item:nth-child(3n) {
  border-top-right-radius: 12px;
}

.mega-subcategory-item:nth-last-child(-n+3) {
  border-bottom: none;
}

.mega-subcategory-item:nth-last-child(1) {
  border-bottom-left-radius: 12px;
}

.mega-subcategory-item:nth-last-child(2) {
  border-bottom-right-radius: 12px;
}

/* Placeholder when no subcategory is hovered */
.mega-placeholder {
  display: flex;
  flex-direction: column;
  gap: 16px;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  min-height: 350px;
}

.mega-placeholder img {
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.brands {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: center;
}

.brands img {
  width: 60px;
  height: 30px;
  object-fit: contain;
  border-radius: 6px;
  opacity: 0.8;
  transition: opacity 0.2s ease;
}

.brands img:hover {
  opacity: 1;
}

/* Right Actions (Legacy) */
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

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
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

/* Responsive Design */
@media (max-width: 1024px) {
  .main-header .container {
    grid-template-columns: 1fr 1.5fr 1fr;
    gap: 16px;
  }
  
  .nav-links {
    gap: 20px;
  }
  
  .mega-content {
    grid-template-columns: repeat(3, minmax(140px, 1fr)) 200px;
    gap: 20px;
    padding: 20px;
  }
}

@media (max-width: 768px) {
  .container {
    padding: 0 16px;
  }
  
  .top-bar {
    font-size: 12px;
    padding: 6px 0;
  }
  
  .main-header .container {
    grid-template-columns: 1fr;
    gap: 16px;
    padding: 12px 0;
  }
  
  .logo-section {
    justify-content: center;
  }
  
  .search-section {
    order: 2;
  }
  
  .actions-section {
    order: 3;
    justify-content: center;
    flex-wrap: wrap;
    gap: 12px;
  }
  
  .nav-bar {
    display: block;
  }
  
  .nav-links {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    justify-content: center;
    min-height: 35px;
  }
  
  .nav-link {
    padding: 6px 10px;
    font-size: 13px;
    min-width: fit-content;
  }
  
  .subcategories-dropdown {
    min-width: 320px;
    margin-top: 6px;
  }
  
  .subcategories-content {
    grid-template-columns: repeat(2, 1fr);
    max-height: 250px;
  }
  
  .subcategory-link {
    padding: 10px 12px;
    font-size: 13px;
  }
  
  .mega-content {
    grid-template-columns: 180px 1fr;
  }
  
  .mega-subcategories-content {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .bottom-nav {
    display: flex;
  }
  
  .mega-menu {
    display: none !important;
  }
  
  .lang-menu {
    width: 260px;
    right: -20px;
  }
  
  .profile-btn, .login-btn {
    padding: 8px 12px;
    font-size: 14px;
  }
  
  .action-btn {
    width: 40px;
    height: 40px;
  }
  
  .notification-badge, .cart-badge {
    width: 18px;
    height: 18px;
    font-size: 10px;
  }
  
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
  
  .form-row {
    grid-template-columns: 1fr;
    gap: 12px;
  }
}

@media (max-width: 480px) {
  .container {
    padding: 0 12px;
  }
  
  .top-bar {
    display: none;
  }
  
  .main-header {
    padding: 12px 0;
  }
  
  .logo-container {
    gap: 8px;
  }
  
  .logo-circle {
    width: 40px;
    height: 40px;
    font-size: 18px;
  }
  
  .brand-arabic {
    font-size: 16px;
  }
  
  .brand-english {
    font-size: 12px;
  }
  
  .search-box {
    padding: 12px 16px;
  }
  
  .search-input {
    font-size: 14px;
  }
  
  .actions-section {
    gap: 8px;
  }
  
  .profile-btn, .login-btn {
    padding: 6px 10px;
    font-size: 12px;
  }
  
  .action-btn {
    width: 36px;
    height: 36px;
  }
  
  .lang-btn {
    padding: 8px 10px;
    font-size: 12px;
  }
  
  .lang-menu {
    width: 240px;
    right: -10px;
  }
  
  .nav-bar {
    padding: 10px 0;
  }
  
  .nav-links {
    gap: 12px;
  }
  
  .nav-link {
    padding: 6px 10px;
    font-size: 13px;
  }
  
  .subcategories-dropdown {
    min-width: 280px;
    margin-top: 4px;
  }
  
  .subcategories-content {
    grid-template-columns: 1fr;
    max-height: 200px;
  }
  
  .subcategory-link {
    padding: 8px 12px;
    font-size: 12px;
  }
  
  .mega-content {
    grid-template-columns: 1fr;
    min-height: 300px;
  }
  
  .mega-main-categories {
    border-radius: 16px 16px 0 0;
    border-right: none;
    border-bottom: 1px solid #e0e0e0;
  }
  
  .mega-subcategories-content {
    grid-template-columns: 1fr;
  }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .app-header {
    background: #1a1a1a;
    border-bottom-color: #333;
  }
  
  .top-bar {
    background: linear-gradient(135deg, #4c63d2 0%, #5a4fcf 100%);
  }
  
  .nav-bar {
    background: #2a2a2a;
    border-bottom-color: #333;
  }
  
  .brand-arabic, .brand-english {
    color: #fff;
  }
  
  .search-box {
    background: #2a2a2a;
    border-color: #444;
  }
  
  .search-input {
    color: #fff;
  }
  
  .search-input::placeholder {
    color: #999;
  }
  
  .lang-btn, .profile-btn, .login-btn, .action-btn {
    background: #2a2a2a;
    border-color: #444;
    color: #fff;
  }
  
  
  .nav-link {
    color: #fff;
  }
  
  .subcategories-dropdown {
    background: #2a2a2a;
    border-color: #444;
  }
  
  .subcategory-link {
    color: #fff;
    border-bottom-color: #444;
  }
  
  .subcategory-link:hover {
    background: rgba(102, 126, 234, 0.2);
    color: #667eea;
  }
  
  .mega-main-categories {
    background: #1a1a1a;
    border-right-color: #444;
  }
  
  .mega-title {
    color: #fff;
  }
  
  .mega-title:hover {
    background: rgba(102, 126, 234, 0.2);
    color: #667eea;
  }
  
  .mega-subcategory-item {
    color: #000000;
    border-bottom-color: #444;
  }
  
  .mega-subcategory-item:hover {
    background: rgba(102, 126, 234, 0.2);
    color: #667eea;
  }
  
  .categories-btn {
    background: #2a2a2a;
    border-color: #444;
    color: #fff;
  }
}
</style>
