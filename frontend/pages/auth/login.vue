<script setup lang="ts">
const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref<string | null>(null)
const { $post, $get } = useApi()
const auth = useAuth()

async function login() {
  error.value = null
  loading.value = true
  try {
    const res: any = await $post('v1/auth/login', { email: email.value, password: password.value })
    // توقع أن الاستجابة تحتوي access_token
    if (res?.access_token) {
      auth.setToken(res.access_token)
      try {
        const me: any = await $get('v1/customer/info')
        if (me) auth.setUser(me)
      } catch {}
      await navigateTo('/')
    }
  } catch (e: any) {
    error.value = e?.data?.message || 'Login failed'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div style="max-width:360px;margin:40px auto;padding:16px;border:1px solid #eee;border-radius:8px;">
    <h2>تسجيل الدخول</h2>
    <div style="display:flex;flex-direction:column;gap:10px;">
      <input v-model="email" type="email" placeholder="البريد الإلكتروني" />
      <input v-model="password" type="password" placeholder="كلمة المرور" />
      <button :disabled="loading" @click="login">دخول</button>
      <p v-if="error" style="color:#c00">{{ error }}</p>
    </div>
  </div>
</template>
