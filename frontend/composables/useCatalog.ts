// Catalog data helpers: categories and brands for filters
export function useCatalog() {
  const { $get } = useApi()

  // Categories: tree with childes; used to render filter lists
  const categories = (params: any = {}) => {
    const qs = new URLSearchParams(params).toString()
    return $get(`v1/categories${qs ? `?${qs}` : ''}`)
  }

  // Brands: returns { total_size, limit, offset, brands: [] }
  const brands = (params: any = {}) => {
    const defaults = { limit: 200, offset: 1 }
    const merged = { ...defaults, ...params }
    const qs = new URLSearchParams(merged as Record<string, string>).toString()
    return $get(`v1/brands?${qs}`)
  }

  return { categories, brands }
}
