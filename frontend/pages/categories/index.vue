<script setup lang="ts">
const { $get } = useApi()
const { data } = await useAsyncData('categories-list', () => $get('v1/categories'))
const items = computed(() => Array.isArray((data as any)?.value?.data) ? (data as any).value.data : ((Array.isArray((data as any)?.value) ? (data as any).value : [])) )
</script>

<template>
  <div style="padding:16px">
    <h1>Categories</h1>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:12px">
      <NuxtLink v-for="(c,i) in items" :key="i" :to="{ path: '/category/'+(c?.id || i) }" style="display:grid;gap:8px;border:1px solid #eee;border-radius:12px;padding:12px;text-decoration:none;color:inherit;">
        <div>{{ c?.name || c?.title || 'Category' }}</div>
      </NuxtLink>
    </div>
  </div>
</template>
