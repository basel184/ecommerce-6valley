// Minimal client init for auth state
export default defineNuxtPlugin(async () => {
  const { token, setToken, setUser } = useAuth()
  // Restore token
  if (process.client && !token.value) {
    const saved = localStorage.getItem('auth_token')
    if (saved) setToken(saved)
  }
  // Persist changes
  watch(token, (t: any) => {
    if (!process.client) return
    if (t && typeof t === 'string') localStorage.setItem('auth_token', t)
    else localStorage.removeItem('auth_token')
  })
  // Load profile if we have token
  if (token.value) {
    try {
      const { $get } = useApi()
      const me: any = await $get('v1/customer/info')
      if (me) setUser(me)
    } catch (e) {
      // invalid token -> clear
      setToken(null)
      setUser(null)
    }
  }
})
