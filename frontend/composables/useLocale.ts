import { ref, computed, onMounted, watch } from 'vue'

// Simple locale manager (ar/en) with persistence
const state = ref<'ar' | 'en'>('ar')

function applyToDocument(locale: 'ar' | 'en') {
  if (process.server) return
  const dir = locale === 'ar' ? 'rtl' : 'ltr'
  document.documentElement.setAttribute('lang', locale)
  document.documentElement.setAttribute('dir', dir)
}

export function useLocale() {
  if (process.client) {
    const saved = localStorage.getItem('locale') as 'ar' | 'en' | null
    if (saved && (saved === 'ar' || saved === 'en')) state.value = saved
  }

  onMounted(() => applyToDocument(state.value))

  watch(state, (v) => {
    if (process.client) localStorage.setItem('locale', v)
    applyToDocument(v)
  }, { immediate: false })

  const dir = computed(() => state.value === 'ar' ? 'rtl' : 'ltr')
  const isRTL = computed(() => dir.value === 'rtl')

  function toggle() {
    state.value = state.value === 'ar' ? 'en' : 'ar'
  }

  function setLocale(v: 'ar' | 'en') { state.value = v }

  return { locale: state, dir, isRTL, toggle, setLocale }
}
