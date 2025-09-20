export default defineNuxtPlugin(async () => {
  const cookie = useCookie<number | null>('guest_id', { sameSite: 'lax' })
  const { setGuestId, guestId } = useGuest()

  if (cookie.value && !guestId.value) setGuestId(cookie.value)
  if (cookie.value) return

  const { $get } = useApi()
  try {
    const res: any = await $get('v1/get-guest-id')
    const id = res?.guest_id || res?.id || res
    if (id) {
      const n = Number(id)
      cookie.value = n
      setGuestId(n)
    }
  } catch {
    // ignore on SSR
  }
})
