<script setup lang="ts">
defineProps<{ title: string; subtitle?: string }>()
</script>

<template>
  <div style="display:flex;justify-content:space-between;align-items:end;margin:8px 0 4px;">
    <h3 style="margin:0">{{ title }}</h3>
    <small v-if="subtitle" style="opacity:.6">{{ subtitle }}</small>
  </div>
  <hr style="border:none;border-top:1px solid #eee;margin:6px 0 12px;" />
  <slot />
  <div style="height:8px"></div>
  <hr style="border:none;border-top:1px solid #f3f3f3;margin:12px 0;" />
</template>
