<script setup lang="ts">
defineProps<{ brands: any[] }>()

const cfg = useRuntimeConfig() as any
const assetBase = (cfg?.public?.apiBase || '').replace(/\/api(?:\/v\d+)?$/, '')
const fixPath = (s: string) => {
  let p = s.trim().replace(/\\/g, '/').replace(/^public\//, '').replace(/^app\/public\//, 'storage/')
  p = p.replace(/\/+/g, '/').replace(/^\//, '')
  return p
}
const toSrc = (u: any): string => {
  if (!u) return ''
  if (Array.isArray(u)) return toSrc(u[0])
  let s: any = u
  if (typeof u === 'string') {
    const t = u.trim()
    if (t.startsWith('[') || t.startsWith('{')) {
      try { return toSrc(JSON.parse(t)) } catch { /* ignore */ }
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
  e.target.src = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="80" height="40"><rect width="100%" height="100%" fill="%23f3f4f6"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="%239ca3af" font-size="10">Brand</text></svg>'
}
</script>

<template>
  <div class="brands">
    <div v-for="b in brands" :key="b.id" class="brand">
      <img v-if="b.image" :src="toSrc(b.image)" :alt="b.name || b.title || 'Brand'" @error="onErr" class="brand-image" />
      <span v-else class="brand-name">{{ b.name || b.title || 'Brand' }}</span>
    </div>
  </div>
</template>

<style scoped>
.brands { display:flex; gap:10px; overflow:auto; padding:8px 0 }
.brand { 
  border:1px solid #eee; 
  border-radius:8px; 
  padding:8px 12px; 
  background:#fff; 
  white-space:nowrap;
  display:flex;
  align-items:center;
  justify-content:center;
  min-width:80px;
  height:40px;
}
.brand-image { 
  max-width:60px; 
  max-height:30px; 
  object-fit:contain; 
  display:block 
}
.brand-name { font-size:12px; font-weight:500 }
</style>
