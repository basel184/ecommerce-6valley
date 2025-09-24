<script setup lang="ts">
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const auth = useAuth()

// Active tab
const activeTab = ref('profile')

// User data
const user = ref({
  id: null,
  f_name: '',
  l_name: '',
  email: '',
  phone: '',
  image: '',
  created_at: ''
})

// Profile form
const profileForm = ref({
  f_name: '',
  l_name: '',
  email: '',
  phone: '',
  image: null
})

const profileLoading = ref(false)
const profileError = ref('')
const profileSuccess = ref('')

// Load user data
const loadUserData = async () => {
  try {
    const { $get } = useApi()
    const userData = await $get('v1/customer/info')
    user.value = userData
    profileForm.value = {
      f_name: userData.f_name || '',
      l_name: userData.l_name || '',
      email: userData.email || '',
      phone: userData.phone || '',
      image: null
    }
  } catch (error) {
    console.error('Error loading user data:', error)
  }
}

// Update profile
const updateProfile = async () => {
  profileLoading.value = true
  profileError.value = ''
  profileSuccess.value = ''

  try {
    const { $put } = useApi()
    const response = await $put('v1/customer/update-profile', profileForm.value)
    
    if (response) {
      profileSuccess.value = 'تم تحديث الملف الشخصي بنجاح'
      await loadUserData()
    }
  } catch (error: any) {
    console.error('Profile update error:', error)
    profileError.value = error?.data?.message || 'خطأ في تحديث الملف الشخصي'
  } finally {
    profileLoading.value = false
  }
}

// Load data on mount
onMounted(() => {
  loadUserData()
})

// Tab navigation
const tabs = [
  { id: 'profile', name: 'معلومات الملف الشخصي', icon: '👤' },
  { id: 'orders', name: 'طلباتي', icon: '📦' },
  { id: 'restock', name: 'طلبات إعادة التخزين', icon: '🔄' },
  { id: 'wishlist', name: 'قائمة الرغبات', icon: '❤️' },
  { id: 'addresses', name: 'عنواني', icon: '📍' },
  { id: 'support', name: 'بطاقة الدعم', icon: '🎫' },
  { id: 'coupons', name: 'قسائم التخفيض', icon: '🎟️' },
  { id: 'tracking', name: 'تتبع الطلب', icon: '🚚' }
]
</script>

<template>
  <div class="account-page" dir="rtl">
    <div class="container">
      <div class="account-header">
        <h1>حسابي</h1>
        <p>إدارة معلوماتك الشخصية وطلباتك</p>
      </div>

      <div class="account-content">
        <!-- Sidebar -->
        <div class="account-sidebar">
          <div class="user-info">
            <div class="user-avatar">
              <img v-if="user.image" :src="user.image" :alt="user.f_name" />
              <div v-else class="avatar-placeholder">
                {{ (user.f_name || 'U').charAt(0).toUpperCase() }}
              </div>
            </div>
            <div class="user-details">
              <h3>{{ user.f_name }} {{ user.l_name }}</h3>
              <p>{{ user.email }}</p>
            </div>
          </div>

          <nav class="account-nav">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              class="nav-item"
              :class="{ active: activeTab === tab.id }"
              @click="activeTab = tab.id"
            >
              <span class="nav-icon">{{ tab.icon }}</span>
              <span class="nav-text">{{ tab.name }}</span>
            </button>
          </nav>
        </div>

        <!-- Main Content -->
        <div class="account-main">
          <!-- Profile Tab -->
          <div v-if="activeTab === 'profile'" class="tab-content">
            <div class="tab-header">
              <h2>معلومات الملف الشخصي</h2>
              <p>إدارة معلوماتك الشخصية</p>
            </div>

            <form @submit.prevent="updateProfile" class="profile-form">
              <div class="form-row">
                <div class="form-group">
                  <label for="f_name">الاسم الأول</label>
                  <input
                    id="f_name"
                    v-model="profileForm.f_name"
                    type="text"
                    required
                    :disabled="profileLoading"
                  />
                </div>
                <div class="form-group">
                  <label for="l_name">الاسم الأخير</label>
                  <input
                    id="l_name"
                    v-model="profileForm.l_name"
                    type="text"
                    required
                    :disabled="profileLoading"
                  />
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label for="email">البريد الإلكتروني</label>
                  <input
                    id="email"
                    v-model="profileForm.email"
                    type="email"
                    required
                    :disabled="profileLoading"
                  />
                </div>
                <div class="form-group">
                  <label for="phone">رقم الهاتف</label>
                  <input
                    id="phone"
                    v-model="profileForm.phone"
                    type="tel"
                    :disabled="profileLoading"
                  />
                </div>
              </div>

              <div class="form-group">
                <label for="image">صورة الملف الشخصي</label>
                <input
                  id="image"
                  type="file"
                  accept="image/*"
                  @change="profileForm.image = $event.target.files[0]"
                  :disabled="profileLoading"
                />
              </div>

              <div v-if="profileError" class="error-message">
                {{ profileError }}
              </div>

              <div v-if="profileSuccess" class="success-message">
                {{ profileSuccess }}
              </div>

              <button type="submit" class="submit-btn" :disabled="profileLoading">
                <span v-if="profileLoading">جاري الحفظ...</span>
                <span v-else>حفظ التغييرات</span>
              </button>
            </form>
          </div>

          <!-- Orders Tab -->
          <div v-if="activeTab === 'orders'" class="tab-content">
            <div class="tab-header">
              <h2>طلباتي</h2>
              <p>عرض وإدارة طلباتك</p>
            </div>
            <div class="coming-soon">
              <h3>قريباً</h3>
              <p>سيتم إضافة صفحة الطلبات قريباً</p>
            </div>
          </div>

          <!-- Restock Tab -->
          <div v-if="activeTab === 'restock'" class="tab-content">
            <div class="tab-header">
              <h2>طلبات إعادة التخزين</h2>
              <p>إدارة طلبات إعادة التخزين</p>
            </div>
            <div class="coming-soon">
              <h3>قريباً</h3>
              <p>سيتم إضافة صفحة طلبات إعادة التخزين قريباً</p>
            </div>
          </div>

          <!-- Wishlist Tab -->
          <div v-if="activeTab === 'wishlist'" class="tab-content">
            <div class="tab-header">
              <h2>قائمة الرغبات</h2>
              <p>المنتجات التي تحبها</p>
            </div>
            <div class="coming-soon">
              <h3>قريباً</h3>
              <p>سيتم إضافة صفحة قائمة الرغبات قريباً</p>
            </div>
          </div>

          <!-- Addresses Tab -->
          <div v-if="activeTab === 'addresses'" class="tab-content">
            <div class="tab-header">
              <h2>عنواني</h2>
              <p>إدارة عناوينك</p>
            </div>
            <div class="coming-soon">
              <h3>قريباً</h3>
              <p>سيتم إضافة صفحة العناوين قريباً</p>
            </div>
          </div>

          <!-- Support Tab -->
          <div v-if="activeTab === 'support'" class="tab-content">
            <div class="tab-header">
              <h2>بطاقة الدعم</h2>
              <p>إدارة تذاكر الدعم</p>
            </div>
            <div class="coming-soon">
              <h3>قريباً</h3>
              <p>سيتم إضافة صفحة الدعم قريباً</p>
            </div>
          </div>

          <!-- Coupons Tab -->
          <div v-if="activeTab === 'coupons'" class="tab-content">
            <div class="tab-header">
              <h2>قسائم التخفيض</h2>
              <p>قسائمك المتاحة</p>
            </div>
            <div class="coming-soon">
              <h3>قريباً</h3>
              <p>سيتم إضافة صفحة القسائم قريباً</p>
            </div>
          </div>

          <!-- Tracking Tab -->
          <div v-if="activeTab === 'tracking'" class="tab-content">
            <div class="tab-header">
              <h2>تتبع الطلب</h2>
              <p>تتبع حالة طلباتك</p>
            </div>
            <div class="coming-soon">
              <h3>قريباً</h3>
              <p>سيتم إضافة صفحة التتبع قريباً</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.account-page {
  min-height: 100vh;
  background: #f8fafc;
  padding: 20px 0;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
}

.account-header {
  text-align: center;
  margin-bottom: 40px;
}

.account-header h1 {
  font-size: 32px;
  font-weight: 700;
  color: #111827;
  margin: 0 0 8px;
}

.account-header p {
  font-size: 16px;
  color: #6b7280;
  margin: 0;
}

.account-content {
  display: grid;
  grid-template-columns: 300px 1fr;
  gap: 30px;
}

/* Sidebar */
.account-sidebar {
  background: white;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  height: fit-content;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 24px;
  padding-bottom: 24px;
  border-bottom: 1px solid #e5e7eb;
}

.user-avatar {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  overflow: hidden;
  background: #f3f4f6;
  display: flex;
  align-items: center;
  justify-content: center;
}

.user-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-placeholder {
  font-size: 20px;
  font-weight: 600;
  color: #6b7280;
}

.user-details h3 {
  font-size: 16px;
  font-weight: 600;
  color: #111827;
  margin: 0 0 4px;
}

.user-details p {
  font-size: 14px;
  color: #6b7280;
  margin: 0;
}

.account-nav {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  border: none;
  background: none;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
  text-align: right;
  width: 100%;
}

.nav-item:hover {
  background: #f3f4f6;
}

.nav-item.active {
  background: #6b46c1;
  color: white;
}

.nav-icon {
  font-size: 18px;
}

.nav-text {
  font-size: 14px;
  font-weight: 500;
}

/* Main Content */
.account-main {
  background: white;
  border-radius: 12px;
  padding: 32px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.tab-header {
  margin-bottom: 32px;
}

.tab-header h2 {
  font-size: 24px;
  font-weight: 700;
  color: #111827;
  margin: 0 0 8px;
}

.tab-header p {
  font-size: 16px;
  color: #6b7280;
  margin: 0;
}

/* Profile Form */
.profile-form {
  max-width: 600px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  margin-bottom: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-group label {
  font-size: 14px;
  font-weight: 600;
  color: #374151;
}

.form-group input {
  padding: 12px 16px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 16px;
  transition: border-color 0.2s;
}

.form-group input:focus {
  outline: none;
  border-color: #6b46c1;
  box-shadow: 0 0 0 3px rgba(107, 70, 193, 0.1);
}

.form-group input:disabled {
  background: #f9fafb;
  color: #6b7280;
}

.submit-btn {
  background: #6b46c1;
  color: white;
  border: none;
  padding: 12px 24px;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s;
}

.submit-btn:hover:not(:disabled) {
  background: #553c9a;
}

.submit-btn:disabled {
  background: #ccc;
  cursor: not-allowed;
}

.error-message {
  background: #fee;
  color: #c53030;
  padding: 12px 16px;
  border-radius: 8px;
  font-size: 14px;
  margin-bottom: 16px;
  border: 1px solid #feb2b2;
}

.success-message {
  background: #f0fdf4;
  color: #166534;
  padding: 12px 16px;
  border-radius: 8px;
  font-size: 14px;
  margin-bottom: 16px;
  border: 1px solid #bbf7d0;
}

/* Coming Soon */
.coming-soon {
  text-align: center;
  padding: 60px 20px;
  color: #6b7280;
}

.coming-soon h3 {
  font-size: 24px;
  font-weight: 600;
  color: #374151;
  margin: 0 0 12px;
}

.coming-soon p {
  font-size: 16px;
  margin: 0;
}

/* Responsive */
@media (max-width: 768px) {
  .account-content {
    grid-template-columns: 1fr;
    gap: 20px;
  }

  .account-sidebar {
    order: 2;
  }

  .account-main {
    order: 1;
    padding: 20px;
  }

  .form-row {
    grid-template-columns: 1fr;
    gap: 16px;
  }

  .account-nav {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
  }

  .nav-item {
    flex-direction: column;
    text-align: center;
    padding: 16px 8px;
  }

  .nav-text {
    font-size: 12px;
  }
}
</style>
