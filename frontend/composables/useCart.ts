// Reactive Cart composable integrating with backend REST endpoints
// Nuxt auto-imports defineComponent APIs at runtime; declare ref for TS when types aren't generated
declare function ref<T = any>(v?: T): { value: T }
export function useCart() {
  const { $get, $post, $put, $del } = useApi()

  const items = ref<any[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  const list = async () => {
    loading.value = true
    error.value = null
    try {
      const res: any = await $get('v1/cart/')
      try {
        const plain = JSON.parse(JSON.stringify(res))
        console.log('[Cart:list] length:', Array.isArray(plain) ? plain.length : 'not-array')
        console.log('[Cart:list] first:', plain?.[0])
      } catch {}
      // API returns an array of cart rows
      items.value = Array.isArray(res) ? res : []
      return items.value
    } catch (e: any) {
      error.value = e?.message || 'Failed to load cart'
      throw e
    } finally {
      loading.value = false
    }
  }

  const keyFor = (product: any): number | null => {
    const pid = product?.id || product?.product_id || product?.product?.id
    if (!pid) return null
    // Try to also match by variant if exists
    const variant = product?.variant || product?.current_variant || product?.chosen_variant
    const found = items.value.find((it: any) => {
      const itPid = it?.product_id || it?.product?.id
      const itVar = it?.variant
      return String(itPid) === String(pid) && (!variant || !itVar || String(variant) === String(itVar))
    })
    return found?.id ?? found?.key ?? null
  }

  const qtyOf = (product: any): number => {
    const pid = product?.id || product?.product_id || product?.product?.id
    if (!pid) return 0
    const it = items.value.find((x: any) => String(x?.product_id || x?.product?.id) === String(pid))
    return Number(it?.quantity || it?.qty || 0)
  }

  const add = async (payload: { product_id: number; quantity: number; variant?: string; color?: string }) => {
    await $post('v1/cart/add', { id: payload.product_id, quantity: payload.quantity, variant: payload.variant, color: payload.color })
    return list()
  }

  const update = async (payload: { key: number; quantity: number }) => {
    await $put('v1/cart/update', payload)
    return list()
  }

  const updateByProduct = async (product: any, quantity: number) => {
    const key = keyFor(product)
    if (!key) {
      // No key yet: treat as add
      const pid = product?.id || product?.product_id || product?.product?.id
      if (!pid) return list()
      await add({ product_id: Number(pid), quantity })
      return items.value
    }
    await update({ key: Number(key), quantity: Number(quantity) })
    return items.value
  }

  const remove = async (key: number) => {
    await $del('v1/cart/remove', { key })
    return list()
  }

  const removeByProduct = async (product: any) => {
    const key = keyFor(product)
    if (!key) return items.value
    await remove(Number(key))
    return items.value
  }

  const clearAll = async () => {
    // API requires a key param but value is unused; pass a sentinel
    await $del('v1/cart/remove-all', { key: 'all' })
    return list()
  }

  return { items, loading, error, list, add, update, updateByProduct, remove, removeByProduct, clearAll, keyFor, qtyOf }
}
