import { ref } from 'vue'

let brandsCache: any[] | null = null
let loading = false
let ensurePromise: Promise<void> | null = null

export function useBrands() {
  const list = ref<any[]>(brandsCache || [])
  const ready = ref(!!brandsCache)
  const error = ref<any>(null)

  const { $get } = useApi() as any

  async function ensure() {
    if (brandsCache) { ready.value = true; list.value = brandsCache; return }
    if (ensurePromise) { await ensurePromise; return }
    ensurePromise = (async () => {
      loading = true
      try {
        let all: any[] = []
        let offset = 0
        const limit = 1000 // large batch to avoid pagination gaps
        // First page
        const first: any = await $get('v1/brands', { params: { limit, offset } })
        const pageArr = Array.isArray(first?.brands)
          ? first.brands
          : (Array.isArray(first?.data) ? first.data : (Array.isArray(first) ? first : []))
        all = all.concat(pageArr)
        const total = Number(first?.total_size ?? first?.total ?? pageArr.length)
        offset += pageArr.length
        // Additional pages if needed
        while (offset < total && pageArr.length > 0) {
          const r: any = await $get('v1/brands', { params: { limit, offset } })
          const arr = Array.isArray(r?.brands)
            ? r.brands
            : (Array.isArray(r?.data) ? r.data : (Array.isArray(r) ? r : []))
          if (!arr.length) break
          all = all.concat(arr)
          offset += arr.length
        }
        brandsCache = all
        list.value = all
        ready.value = true
      } catch (e) {
        error.value = e
      } finally {
        loading = false
        ensurePromise = null
      }
    })()
    await ensurePromise
  }

  async function ensureBrand(id: number | string) {
    await ensure()
    if (!brandsCache) return
    const s = String(id)
    if (brandsCache.find((b: any) => String(b?.id) === s)) return
    try {
      // Try common detail endpoints
      let bResp: any = null
      try { bResp = await $get(`v1/brands/details/${s}`) } catch {}
      if (!bResp) {
        try { bResp = await $get(`v1/brands/${s}`) } catch {}
      }
      if (!bResp) {
        try { bResp = await $get('v1/brands', { params: { id: s } }) } catch {}
      }
      const b = bResp?.brand || (Array.isArray(bResp?.brands) ? bResp.brands[0] : (Array.isArray(bResp?.data) ? bResp.data[0] : bResp))
      if (b) {
        brandsCache.push(b)
        list.value = brandsCache
      }
    } catch {}
  }

  function byId(id: number | string | null | undefined): any | null {
    if (!id || !brandsCache) return null
    const s = String(id)
    return brandsCache.find((b: any) => String(b?.id) === s) || null
  }

  function nameOf(id: number | string | null | undefined): string {
    const b = byId(id)
    return (b?.name || b?.translation?.name || '') as string
  }

  return { list, ready, error, ensure, ensureBrand, byId, nameOf }
}
