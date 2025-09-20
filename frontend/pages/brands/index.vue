<script setup lang="ts">
const { $get } = useApi()
const { data } = await useAsyncData('brands-list', () => $get('v1/brands'))
const items = computed(() => Array.isArray((data as any)?.value?.data) ? (data as any).value.data : ((Array.isArray((data as any)?.value) ? (data as any).value : [])) )
</script>

<template>
  <div style="padding:16px">
    <h1>Brands</h1>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:12px">
      <NuxtLink v-for="(b,i) in items" :key="i" :to="`/brand/${b?.id || b?.slug || i}`" style="display:grid;place-items:center;border:1px solid #eee;border-radius:12px;padding:12px;text-align:center;text-decoration:none;color:inherit;">
        <img :src="b?.logo_full_url || b?.image || 'https://dummyimage.com/180x80/fff/aaa'" alt="brand" style="width:100%;height:60px;object-fit:contain" />
        <div style="margin-top:8px">{{ b?.name || b?.title || 'Brand' }}</div>
      </NuxtLink>
    </div>
  </div>
  
</template>
