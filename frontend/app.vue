<template>
  <div>
    <NuxtLayout>
      <NuxtPage />
    </NuxtLayout>
  </div>
</template>

<script setup>
// Force Arabic locale on app initialization
const { $i18n } = useNuxtApp()
if (process.client) {
  $i18n.locale.value = 'ar'
  document.documentElement.setAttribute('lang', 'ar')
  document.documentElement.setAttribute('dir', 'rtl')
}
</script>
