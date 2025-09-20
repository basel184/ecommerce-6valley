<script setup lang="ts">
const props = defineProps<{ categories: any[] }>()

const toLink = (c: any) => {
  const id = c?.id || c?.category_id
  const slug = c?.slug || c?.category_slug
  if (slug) return `/category/${encodeURIComponent(String(slug))}`
  if (id) return `/category/${encodeURIComponent(String(id))}`
  return null
}

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
  e.target.style.display = 'none'
}
</script>

<template>
  <div class="pills">
    <template v-for="c in categories" :key="c.id || c.slug">
      <NuxtLink v-if="toLink(c)" class="pill" :to="toLink(c)">
        <img v-if="c.icon_full_url" :src="toSrc(c.icon_full_url)" :alt="c.name" @error="onErr" class="category-icon" />
        <span class="category-name">{{ c.name }}</span>
      </NuxtLink>
      <span v-else class="pill" style="opacity:.6; cursor:default;">
        <img v-if="c.icon_full_url" :src="toSrc(c.icon_full_url)" :alt="c.name" @error="onErr" class="category-icon" />
        <span class="category-name">{{ c.name }}</span>
      </span>
    </template>
  </div>
</template>

<style scoped>
.pills { display:flex; flex-wrap:wrap; gap:8px }
.pill { 
  border:1px solid #e6e6e6; 
  padding:8px 12px; 
  border-radius:999px; 
  background:#fff; 
  color:#333; 
  text-decoration:none;
  display:flex;
  align-items:center;
  gap:6px;
  transition:all 0.2s;
}
.pill:hover { border-color:#ccc; transform:translateY(-1px) }
.category-icon { 
  width:20px; 
  height:20px; 
  object-fit:contain; 
  display:block 
}
.category-name { font-size:13px; font-weight:500 }
</style>
