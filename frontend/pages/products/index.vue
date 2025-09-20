<script setup lang="ts">
const route = useRoute()
const { search } = useProducts()
const q = computed(() => String((route.query.q as string) || ''))
const { data } = await useAsyncData('search', () => search(q.value))
watch(q, async () => {
  ;(data as any).value = await search(q.value)
})
const items = computed(() => {
  const val: any = (data as any)?.value
  if (Array.isArray(val)) return val
  if (Array.isArray(val?.data)) return val.data
  return []
})
</script>

<template>
  <div style="padding:16px;display:flex;flex-direction:column;gap:12px;">
    <SectionTitle title="Search" />
    <ProductGrid :products="items" />
  </div>
</template>
