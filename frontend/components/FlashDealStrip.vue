<script setup lang="ts">
defineProps<{ products: any[]; title?: string }>()
</script>

<template>
  <SectionTitle :title="title || 'Flash deals'" />
  <ProductGrid :products="products" />
</template>
