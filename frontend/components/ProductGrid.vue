<script setup lang="ts">
import { ref } from 'vue'
import { Swiper, SwiperSlide } from 'swiper/vue'
import { Navigation } from 'swiper/modules'
import 'swiper/css'
import 'swiper/css/navigation'

const props = defineProps<{ products: any[] }>()
const cart = useCart()
onMounted(() => cart.list())
// Per-product busy map to avoid global loader
const busy: any = ref<Record<string, boolean>>({})
const keyOf = (p: any) => String(p?.id || p?.product_id || p?.product?.id || '')
const withBusy = async (p: any, fn: () => Promise<any>) => {
  const k = keyOf(p)
  if (!k) return fn()
  busy.value[k] = true
  try { return await fn() } finally { delete busy.value[k] }
}

const modules = [Navigation]
const breakpoints = {
  0: { slidesPerView: 1.2, spaceBetween: 10 },
  480: { slidesPerView: 2.2, spaceBetween: 10 },
  640: { slidesPerView: 3, spaceBetween: 12 },
  900: { slidesPerView: 5, spaceBetween: 12 },
  1200: { slidesPerView: 7, spaceBetween: 14 }
}
</script>

<template>
  <div class="swiper-wrap" dir="rtl">
    <Swiper
      :modules="modules"
      navigation
      :breakpoints="breakpoints"
      :space-between="12"
      class="grid-swiper"
    >
      <SwiperSlide v-for="(p,i) in products" :key="p?.id || p?.slug || i">
        <div class="slide-inner">
          <ProductCard
            :product="p"
            :qty="cart.qtyOf(p)"
            :busy="busy[keyOf(p)]"
            @add="() => withBusy(p, () => cart.updateByProduct(p, 1))"
            @update="(e:any) => withBusy(p, () => cart.updateByProduct(p, e.qty))"
            @remove="() => withBusy(p, () => cart.removeByProduct(p))"
          />
        </div>
      </SwiperSlide>
    </Swiper>
  </div>
</template>

<style scoped>
.swiper-wrap { position: relative }
.grid-swiper :deep(.swiper-button-prev),
.grid-swiper :deep(.swiper-button-next){
  width:36px; height:36px; border-radius:999px; background:#fff; color:#111827; border:1px solid #e5e7eb; box-shadow:0 4px 14px rgba(0,0,0,.08)
}
.grid-swiper :deep(.swiper-button-prev)::after,
.grid-swiper :deep(.swiper-button-next)::after{ font-size:14px }
.slide-inner{ padding: 2px }
</style>
