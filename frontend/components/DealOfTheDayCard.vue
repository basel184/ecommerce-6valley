<script setup lang="ts">
import { ref } from 'vue'
const props = defineProps<{ product: any }> ()
const cart = useCart()
onMounted(() => cart.list())
const busy = ref(false)
const withBusy = async (fn: () => Promise<any>) => { busy.value = true; try { return await fn() } finally { busy.value = false } }
</script>

<template>
  <div class="deal">
    <h3>Deal of the day</h3>
    <ProductCard
      v-if="product"
      :product="product"
      :qty="cart.qtyOf(product)"
      :busy="busy"
      @add="() => withBusy(() => cart.updateByProduct(product, 1))"
      @update="(e:any) => withBusy(() => cart.updateByProduct(product, e.qty))"
      @remove="() => withBusy(() => cart.removeByProduct(product))"
    />
  </div>
</template>

<style scoped>
.deal { border:1px solid #eee; border-radius:12px; padding:12px; background:#fff }
</style>
