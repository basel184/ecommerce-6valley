<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const { $get, $post } = useApi()

// Coupons data
const coupons = ref([])
const availableCoupons = ref([])
const loading = ref(false)
const error = ref('')

// Coupon form
const showCouponForm = ref(false)
const couponForm = ref({
  code: ''
})

const formLoading = ref(false)
const formError = ref('')
const formSuccess = ref('')

// Load coupons
const loadCoupons = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await $get('v1/customer/coupons')
    coupons.value = response?.my_coupons || []
    availableCoupons.value = response?.available_coupons || []
  } catch (err: any) {
    console.error('Error loading coupons:', err)
    error.value = err?.data?.message || 'خطأ في تحميل القسائم'
  } finally {
    loading.value = false
  }
}

// Open coupon form
const openCouponForm = () => {
  couponForm.value = { code: '' }
  formError.value = ''
  formSuccess.value = ''
  showCouponForm.value = true
}

// Close form
const closeForm = () => {
  showCouponForm.value = false
  formError.value = ''
  formSuccess.value = ''
}

// Apply coupon
const applyCoupon = async () => {
  formLoading.value = true
  formError.value = ''
  formSuccess.value = ''

  try {
    const response = await $post('v1/customer/coupon/apply', couponForm.value)
    if (response) {
      formSuccess.value = 'تم تطبيق القسيمة بنجاح'
      await loadCoupons()
      setTimeout(() => {
        closeForm()
      }, 2000)
    }
  } catch (err: any) {
    console.error('Error applying coupon:', err)
    formError.value = err?.data?.message || 'خطأ في تطبيق القسيمة'
  } finally {
    formLoading.value = false
  }
}

// Get coupon type name
const getCouponTypeName = (type: string) => {
  const types = {
    free_delivery: 'شحن مجاني',
    first_order: 'خصم الطلب الأول',
    percentage: 'خصم نسبي',
    amount: 'خصم مبلغ ثابت',
    buy_x_get_y: 'اشتري X واحصل على Y'
  }
  return types[type] || type
}

// Get discount text
const getDiscountText = (coupon: any) => {
  if (coupon.discount_type === 'percentage') {
    return `${coupon.discount}% خصم`
  } else if (coupon.discount_type === 'amount') {
    return `${coupon.discount} ﷼ خصم`
  } else if (coupon.discount_type === 'free_delivery') {
    return 'شحن مجاني'
  } else if (coupon.discount_type === 'buy_x_get_y') {
    return `اشتري ${coupon.buy_quantity} واحصل على ${coupon.get_quantity}`
  }
  return 'خصم'
}

// Check if coupon is expired
const isExpired = (expiryDate: string) => {
  if (!expiryDate) return false
  return new Date(expiryDate) < new Date()
}

// Check if coupon is used
const isUsed = (coupon: any) => {
  return coupon.used_at !== null
}

// Format date
const formatDate = (dateString: string) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('ar-SA', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit'
  })
}

// Get coupon status
const getCouponStatus = (coupon: any) => {
  if (isUsed(coupon)) {
    return { name: 'مستخدم', color: '#6b7280' }
  } else if (isExpired(coupon.expiry_date)) {
    return { name: 'منتهي الصلاحية', color: '#ef4444' }
  } else {
    return { name: 'متاح', color: '#10b981' }
  }
}

// Load data on mount
onMounted(() => {
  loadCoupons()
})
</script>

<template>
  <div class="coupons-page" dir="rtl">
    <div class="container">
      <div class="page-header">
        <h1>قسائم التخفيض</h1>
        <p>إدارة قسائمك وخصوماتك</p>
      </div>

      <div class="coupons-content">
        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
          <div class="spinner"></div>
          <p>جاري تحميل القسائم...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="error-state">
          <div class="error-icon">⚠️</div>
          <h3>خطأ في التحميل</h3>
          <p>{{ error }}</p>
          <button @click="loadCoupons" class="retry-btn">إعادة المحاولة</button>
        </div>

        <!-- Coupons Content -->
        <div v-else class="coupons-sections">
          <!-- Apply Coupon Section -->
          <div class="apply-coupon-section">
            <div class="section-header">
              <h2>تطبيق قسيمة جديدة</h2>
              <button @click="openCouponForm" class="apply-btn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                </svg>
                تطبيق قسيمة
              </button>
            </div>
          </div>

          <!-- My Coupons Section -->
          <div class="my-coupons-section">
            <h2>قسائمي</h2>
            
            <!-- Empty State -->
            <div v-if="coupons.length === 0" class="empty-state">
              <div class="empty-icon">🎟️</div>
              <h3>لا توجد قسائم</h3>
              <p>لم تحصل على أي قسائم بعد</p>
            </div>

            <!-- Coupons List -->
            <div v-else class="coupons-grid">
              <ClientOnly>
                <div v-for="coupon in coupons" :key="coupon.id" class="coupon-card">
                  <div class="coupon-header">
                    <div class="coupon-code">
                      <h3>{{ coupon.code }}</h3>
                      <p class="coupon-type">{{ getCouponTypeName(coupon.discount_type) }}</p>
                    </div>
                    <div class="coupon-status">
                      <span 
                        class="status-badge"
                        :style="{ backgroundColor: getCouponStatus(coupon).color }"
                      >
                        {{ getCouponStatus(coupon).name }}
                      </span>
                    </div>
                  </div>

                  <div class="coupon-details">
                    <div class="discount-info">
                      <span class="discount-text">{{ getDiscountText(coupon) }}</span>
                      <span v-if="coupon.minimum_amount" class="min-amount">
                        الحد الأدنى: {{ coupon.minimum_amount }} ﷼
                      </span>
                    </div>
                    
                    <div class="coupon-meta">
                      <div v-if="coupon.expiry_date" class="expiry-date">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                          <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/>
                        </svg>
                        ينتهي في: {{ formatDate(coupon.expiry_date) }}
                      </div>
                      <div v-if="coupon.used_at" class="used-date">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                          <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                        </svg>
                        استخدم في: {{ formatDate(coupon.used_at) }}
                      </div>
                    </div>
                  </div>

                  <div class="coupon-actions">
                    <button 
                      v-if="!isUsed(coupon) && !isExpired(coupon.expiry_date)"
                      class="use-btn"
                      @click="() => {}"
                    >
                      استخدام القسيمة
                    </button>
                    <button 
                      v-else
                      class="disabled-btn"
                      disabled
                    >
                      {{ isUsed(coupon) ? 'مستخدم' : 'منتهي الصلاحية' }}
                    </button>
                  </div>
                </div>
              </ClientOnly>
            </div>
          </div>

          <!-- Available Coupons Section -->
          <div v-if="availableCoupons.length > 0" class="available-coupons-section">
            <h2>قسائم متاحة</h2>
            <div class="coupons-grid">
              <div v-for="coupon in availableCoupons" :key="coupon.id" class="coupon-card available">
                <div class="coupon-header">
                  <div class="coupon-code">
                    <h3>{{ coupon.code }}</h3>
                    <p class="coupon-type">{{ getCouponTypeName(coupon.discount_type) }}</p>
                  </div>
                  <div class="coupon-status">
                    <span class="status-badge available">متاح</span>
                  </div>
                </div>

                <div class="coupon-details">
                  <div class="discount-info">
                    <span class="discount-text">{{ getDiscountText(coupon) }}</span>
                    <span v-if="coupon.minimum_amount" class="min-amount">
                      الحد الأدنى: {{ coupon.minimum_amount }} ﷼
                    </span>
                  </div>
                  
                  <div class="coupon-meta">
                    <div v-if="coupon.expiry_date" class="expiry-date">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/>
                      </svg>
                      ينتهي في: {{ formatDate(coupon.expiry_date) }}
                    </div>
                  </div>
                </div>

                <div class="coupon-actions">
                  <button class="claim-btn" @click="() => {}">
                    الحصول على القسيمة
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Apply Coupon Modal -->
    <teleport to="body">
      <div v-if="showCouponForm" class="modal-overlay" @click.self="closeForm">
        <div class="modal-content">
          <div class="modal-header">
            <h2>تطبيق قسيمة</h2>
            <button @click="closeForm" class="close-btn">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19 6.41L17.59 5L12 10.59L6.41 5L5 6.41L10.59 12L5 17.59L6.41 19L12 13.41L17.59 19L19 17.59L13.41 12z"/>
              </svg>
            </button>
          </div>

          <form @submit.prevent="applyCoupon" class="coupon-form">
            <div class="form-group">
              <label for="code">كود القسيمة</label>
              <input
                id="code"
                v-model="couponForm.code"
                type="text"
                required
                placeholder="أدخل كود القسيمة"
                :disabled="formLoading"
              />
            </div>

            <div v-if="formError" class="error-message">
              {{ formError }}
            </div>

            <div v-if="formSuccess" class="success-message">
              {{ formSuccess }}
            </div>

            <div class="form-actions">
              <button type="button" @click="closeForm" class="cancel-btn" :disabled="formLoading">
                إلغاء
              </button>
              <button type="submit" class="submit-btn" :disabled="formLoading">
                <span v-if="formLoading">جاري التطبيق...</span>
                <span v-else>تطبيق القسيمة</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </teleport>
  </div>
</template>

<style scoped>
.coupons-page {
  min-height: 100vh;
  background: #f8fafc;
  padding: 20px 0;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
}

.page-header {
  text-align: center;
  margin-bottom: 40px;
}

.page-header h1 {
  font-size: 32px;
  font-weight: 700;
  color: #111827;
  margin: 0 0 8px;
}

.page-header p {
  font-size: 16px;
  color: #6b7280;
  margin: 0;
}

/* Loading State */
.loading-state {
  text-align: center;
  padding: 60px 20px;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #e5e7eb;
  border-top: 4px solid #6b46c1;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 16px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Error State */
.error-state {
  text-align: center;
  padding: 60px 20px;
  color: #6b7280;
}

.error-icon {
  font-size: 48px;
  margin-bottom: 16px;
}

.error-state h3 {
  font-size: 24px;
  font-weight: 600;
  color: #374151;
  margin: 0 0 12px;
}

.error-state p {
  font-size: 16px;
  margin: 0 0 24px;
}

.retry-btn {
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

.retry-btn:hover {
  background: #553c9a;
}

/* Coupons Sections */
.coupons-sections {
  display: flex;
  flex-direction: column;
  gap: 32px;
}

/* Apply Coupon Section */
.apply-coupon-section {
  background: white;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  border: 2px solid #6b46c1;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.section-header h2 {
  font-size: 20px;
  font-weight: 700;
  color: #111827;
  margin: 0;
}

.apply-btn {
  background: #6b46c1;
  color: white;
  border: none;
  padding: 12px 20px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s;
  display: flex;
  align-items: center;
  gap: 8px;
}

.apply-btn:hover {
  background: #553c9a;
}

/* My Coupons Section */
.my-coupons-section,
.available-coupons-section {
  background: white;
  border-radius: 12px;
  padding: 32px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.my-coupons-section h2,
.available-coupons-section h2 {
  font-size: 24px;
  font-weight: 700;
  color: #111827;
  margin: 0 0 24px;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 60px 20px;
  color: #6b7280;
}

.empty-icon {
  font-size: 48px;
  margin-bottom: 16px;
}

.empty-state h3 {
  font-size: 24px;
  font-weight: 600;
  color: #374151;
  margin: 0 0 12px;
}

.empty-state p {
  font-size: 16px;
  margin: 0;
}

/* Coupons Grid */
.coupons-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 20px;
}

.coupon-card {
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 20px;
  transition: box-shadow 0.2s;
  position: relative;
}

.coupon-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.coupon-card.available {
  border-color: #10b981;
  background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
}

.coupon-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  padding-bottom: 16px;
  border-bottom: 1px solid #e5e7eb;
}

.coupon-code h3 {
  font-size: 18px;
  font-weight: 700;
  color: #111827;
  margin: 0 0 4px;
  font-family: 'Courier New', monospace;
}

.coupon-type {
  font-size: 14px;
  color: #6b7280;
  margin: 0;
}

.status-badge {
  color: white;
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
}

.status-badge.available {
  background: #10b981;
}

.coupon-details {
  margin-bottom: 16px;
}

.discount-info {
  margin-bottom: 12px;
}

.discount-text {
  font-size: 20px;
  font-weight: 700;
  color: #111827;
  display: block;
  margin-bottom: 4px;
}

.min-amount {
  font-size: 12px;
  color: #6b7280;
}

.coupon-meta {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.expiry-date,
.used-date {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: #6b7280;
}

.coupon-actions {
  display: flex;
  gap: 8px;
}

.use-btn,
.claim-btn {
  flex: 1;
  background: #6b46c1;
  color: white;
  border: none;
  padding: 10px 16px;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s;
}

.use-btn:hover,
.claim-btn:hover {
  background: #553c9a;
}

.disabled-btn {
  flex: 1;
  background: #f3f4f6;
  color: #6b7280;
  border: 1px solid #d1d5db;
  padding: 10px 16px;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 600;
  cursor: not-allowed;
}

/* Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.modal-content {
  background: white;
  border-radius: 12px;
  width: 100%;
  max-width: 400px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px 24px 0;
}

.modal-header h2 {
  font-size: 20px;
  font-weight: 700;
  color: #111827;
  margin: 0;
}

.close-btn {
  background: none;
  border: none;
  color: #6b7280;
  cursor: pointer;
  padding: 8px;
  border-radius: 6px;
  transition: background-color 0.2s;
}

.close-btn:hover {
  background: #f3f4f6;
}

.coupon-form {
  padding: 24px;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  font-size: 14px;
  font-weight: 600;
  color: #374151;
  margin-bottom: 6px;
}

.form-group input {
  width: 100%;
  padding: 12px 16px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 16px;
  transition: border-color 0.2s;
  box-sizing: border-box;
  font-family: 'Courier New', monospace;
  text-transform: uppercase;
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

.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 24px;
}

.cancel-btn {
  background: #f3f4f6;
  color: #374151;
  border: 1px solid #d1d5db;
  padding: 12px 24px;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s;
}

.cancel-btn:hover:not(:disabled) {
  background: #e5e7eb;
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

/* Responsive */
@media (max-width: 768px) {
  .coupons-grid {
    grid-template-columns: 1fr;
    gap: 16px;
  }

  .section-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
  }

  .form-actions {
    flex-direction: column;
  }

  .cancel-btn,
  .submit-btn {
    width: 100%;
  }
}
</style>