export default defineNuxtPlugin(() => {
  const { $i18n } = useNuxtApp()
  
  // Force Arabic locale on client side
  if (process.client) {
    $i18n.locale.value = 'ar'
    document.documentElement.setAttribute('lang', 'ar')
    document.documentElement.setAttribute('dir', 'rtl')
    
    // Set cookie to remember the language choice
    const cookie = useCookie('i18n_redirected', { 
      default: () => 'ar',
      sameSite: 'lax'
    })
    cookie.value = 'ar'
  }
})
