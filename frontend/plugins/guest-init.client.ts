export default defineNuxtPlugin(async () => {
  const cookie = useCookie<number | null>('guest_id', { sameSite: 'lax' })
  const { guestId, setGuestId } = useGuest()

  // 1) Restore from cookie/localStorage into state
  if (cookie.value && !guestId.value) setGuestId(cookie.value)
  if (process.client) {
    const stored = localStorage.getItem('guest_id')
    if (stored && !guestId.value) setGuestId(Number(stored))
  }

  // 2) Ensure guest_id exists from backend if missing
  if (!cookie.value && !guestId.value) {
    const { $get } = useApi()
    try {
      const res: any = await $get('v1/get-guest-id')
      const id = res?.guest_id || res?.id || res
      if (id) {
        cookie.value = Number(id)
        setGuestId(Number(id))
        if (process.client) localStorage.setItem('guest_id', String(id))
      }
    } catch {
      // ignore
    }
  }
})
