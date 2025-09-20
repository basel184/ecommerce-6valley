// Centralized product API helpers
export function useProducts() {
  const { $get, $post } = useApi()

  // Lists
  const latest = () => $get('v1/products/latest')
  const featured = () => $get('v1/products/featured')
  const topRated = () => $get('v1/products/top-rated')
  const bestSellings = () => $get('v1/products/best-sellings')
  const newArrival = () => $get('v1/products/new-arrival')
  const discounted = () => $get('v1/products/discounted-product')
  const justForYou = () => $get('v1/products/just-for-you')
  const clearance = () => $get('v1/products/clearance-sale')

  // Category & Brand
  const byCategory = (categoryId: string | number) => $get(`v1/categories/products/${categoryId}`)
  const byBrand = (brandId: string | number) => $get(`v1/brands/products/${brandId}`)

  // Search & filter
  const search = (q: string) => $get(`v1/products/search?q=${encodeURIComponent(q)}`)
  const filter = (body: any) => $post('v1/products/filter', body)

  // Details and related
  const details = (slug: string) => $get(`v1/products/details/${encodeURIComponent(slug)}`)
  const related = (productId: string | number) => $get(`v1/products/related-products/${productId}`)

  return {
    latest, featured, topRated, bestSellings, newArrival, discounted, justForYou, clearance,
    byCategory, byBrand, search, filter, details, related
  }
}
