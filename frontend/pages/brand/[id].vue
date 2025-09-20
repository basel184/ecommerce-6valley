<script setup lang="ts">
const route = useRoute()
const { byBrand } = useProducts()
const id = route.params.id as string
const { data } = await useAsyncData(`brand-${id}`, () => byBrand(id))
const items = computed(() => {
  const val: any = (data as any)?.value
  if (Array.isArray(val)) return val
  if (Array.isArray(val?.data)) return val.data
  return []
})
</script>

<template>
  <div style="padding:16px;display:flex;flex-direction:column;gap:16px;">
    <SectionTitle title="Brand" />
    <ProductGrid :products="items" />
  </div>
 </template>
