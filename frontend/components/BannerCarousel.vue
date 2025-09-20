<script setup lang="ts">
import { Swiper, SwiperSlide } from 'swiper/vue'
import { Navigation, Pagination, Autoplay } from 'swiper/modules'
import 'swiper/css'
import 'swiper/css/navigation'
import 'swiper/css/pagination'

defineProps<{ banners: any[] }>()

const modules = [Navigation, Pagination, Autoplay]

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
  e.target.src = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="600" height="300"><rect width="100%" height="100%" fill="%23f3f4f6"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="%239ca3af" font-size="20">No image</text></svg>'
}
</script>

<template>
  <div class="carousel" dir="rtl">
    <ClientOnly>
      <Swiper
        class="hero-swiper"
        :modules="modules"
        navigation
        pagination
        :loop="true"
        :slides-per-view="1"
        :autoplay="{ delay: 4000, disableOnInteraction: false }"
        :spaceBetween="12"
        :dir="'rtl'"
      >
        <SwiperSlide v-for="(b,i) in banners" :key="b?.id || i">
          <div class="slide">
            <a v-if="b?.url && b?.url !== '#'" :href="b.url" target="_blank" class="banner-link">
              <img :src="toSrc(b?.photo_full_url || b?.image_full_url || b?.image || b?.photo)" :alt="b?.title || 'Banner'" @error="onErr" />
              <div v-if="b?.title" class="caption">{{ b.title }}</div>
            </a>
            <template v-else>
              <img :src="toSrc(b?.photo_full_url || b?.image_full_url || b?.image || b?.photo)" :alt="b?.title || 'Banner'" @error="onErr" />
              <div v-if="b?.title" class="caption">{{ b.title }}</div>
            </template>
          </div>
        </SwiperSlide>
      </Swiper>
    </ClientOnly>
  </div>
  
</template>

<style scoped>
.hero-swiper { --swiper-navigation-color:#111827; --swiper-theme-color:#111827 }
.slide { position:relative }
img { width:100%; height:220px; object-fit:cover; border-radius:14px; border:1px solid #eee }
.caption { position:absolute; left:12px; bottom:12px; background:rgba(0,0,0,.45); color:#fff; padding:6px 10px; border-radius:8px; font-size:12px }
.hero-swiper :deep(.swiper-button-prev),
.hero-swiper :deep(.swiper-button-next){ width:36px; height:36px; border-radius:999px; background:#fff; color:#111827; border:1px solid #e5e7eb; box-shadow:0 4px 14px rgba(0,0,0,.08) }
.hero-swiper :deep(.swiper-button-prev)::after,
.hero-swiper :deep(.swiper-button-next)::after{ font-size:14px }
.banner-link { display:block; text-decoration:none; color:inherit }
.banner-link img { display:block }
@media (min-width: 900px){ img { height:300px } }
</style>
