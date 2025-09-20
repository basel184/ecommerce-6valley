<script setup lang="ts">
import { computed } from 'vue'
const props = defineProps<{ banners: any[]; columns?: number }>()
const gridCols = computed(() => {
  const c = props.columns ?? 0
  if (c === 1) return 'repeat(1, 1fr)'
  if (c === 2) return 'repeat(2, 1fr)'
  if (c === 3) return 'repeat(3, 1fr)'
  return 'repeat(auto-fill,minmax(280px,1fr))'
})

const cfg = useRuntimeConfig() as any
const assetBase = (cfg?.public?.apiBase || '').replace(/\/api(?:\/v\d+)?$/, '')
const fixPath = (s: string) => {
  let p = s.trim().replace(/\\/g, '/').replace(/^public\//, '').replace(/^app\/public\//, 'storage/')
  p = p.replace(/\/+/g, '/').replace(/^\//, '')
  return p
}
const toSrc = (u: any) => {
  if (!u) return ''
  if (Array.isArray(u)) return toSrc(u[0])
  let s: any = u
  if (typeof u === 'string') {
    const t = u.trim()
    if (t.startsWith('[') || t.startsWith('{')) {
      try { return toSrc(JSON.parse(t)) } catch {}
    }
    s = t
  } else if (typeof u === 'object') {
    s = (u as any).path || (u as any).url || (u as any).image || ''
  }
  s = (typeof s === 'string' ? s : '').trim()
  if (!s) return ''
  if (/^(https?:|data:|blob:)/i.test(s)) return s
  return `${assetBase}/${fixPath(s)}`
}
const onErr = (e: any) => {
  e.target.src = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="560" height="120"><rect width="100%" height="100%" fill="%23f3f4f6"/></svg>'
}
</script>

<template>
  <div class="row" :style="{ gridTemplateColumns: gridCols }" dir="rtl">
    <a v-for="(b,i) in banners" :key="b?.id || i" 
       v-if="b?.url && b?.url !== '#'" 
       :href="b.url" 
       target="_blank" 
       class="banner-link">
      <img :src="toSrc(b?.photo_full_url || b?.image_full_url || b?.image || b?.photo)" class="banner" @error="onErr" />
    </a>
    <img v-for="(b,i) in banners" 
         v-else
         :key="b?.id || i" 
         :src="toSrc(b?.photo_full_url || b?.image_full_url || b?.image || b?.photo)" 
         class="banner" 
         @error="onErr" />
  </div>
</template>

<style scoped>
.row { display:grid; grid-template-columns: repeat(auto-fill,minmax(280px,1fr)); gap:10px }
.banner { width:100%; height:120px; object-fit:cover; border-radius:10px; border:1px solid #eee }
.banner-link { display:block; text-decoration:none; color:inherit }
.banner-link .banner { display:block }
</style>
