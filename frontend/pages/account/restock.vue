<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'

// Types
interface RestockRequest {
  id: string
  product_name: string
  quantity: number
  notes?: string
  admin_notes?: string
  status: string
  created_at: string
}

const { t } = useI18n()
const { $get, $post, $del } = useApi()

// Restock requests data
const restockRequests = ref<RestockRequest[]>([])
const loading = ref(false)
const error = ref('')

// Restock form
const showRestockForm = ref(false)
const restockForm = ref({
  product_id: '',
  product_name: '',
  quantity: 1,
  notes: ''
})

const formLoading = ref(false)
const formError = ref('')

// Load restock requests
const loadRestockRequests = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await $get('v1/customer/restock-requests')
    restockRequests.value = response || []
  } catch (err: any) {
    console.error('Error loading restock requests:', err)
    error.value = err?.data?.message || 'خطأ في تحميل طلبات إعادة التخزين'
  } finally {
    loading.value = false
  }
}

// Open restock form
const openRestockForm = () => {
  restockForm.value = {
    product_id: '',
    product_name: '',
    quantity: 1,
    notes: ''
  }
  formError.value = ''
  showRestockForm.value = true
}

// Close form
const closeForm = () => {
  showRestockForm.value = false
  formError.value = ''
}

// Submit restock request
const submitRestockRequest = async () => {
  formLoading.value = true
  formError.value = ''

  try {
    await $post('v1/customer/restock-request', restockForm.value)
    closeForm()
    await loadRestockRequests()
  } catch (err: any) {
    console.error('Error submitting restock request:', err)
    formError.value = err?.data?.message || 'خطأ في إرسال طلب إعادة التخزين'
  } finally {
    formLoading.value = false
  }
}

// Cancel restock request
const cancelRestockRequest = async (requestId: string) => {
  if (!confirm('هل أنت متأكد من إلغاء طلب إعادة التخزين؟')) return

  try {
    await $del('v1/customer/restock-request', {
      id: requestId
    })
    await loadRestockRequests()
  } catch (err: any) {
    console.error('Error canceling restock request:', err)
  }
}

// Get status name
const getStatusName = (status: string) => {
  const statuses = {
    pending: { name: 'في الانتظار', color: '#f59e0b' },
    approved: { name: 'موافق عليه', color: '#10b981' },
    rejected: { name: 'مرفوض', color: '#ef4444' },
    fulfilled: { name: 'تم التنفيذ', color: '#3b82f6' }
  }
  return statuses[status as keyof typeof statuses] || { name: status, color: '#6b7280' }
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

// Load data on mount
onMounted(() => {
  loadRestockRequests()
})
</script>

<template>
  <div class="restock-page" dir="rtl">
    <div class="container">
      <div class="page-header">
        <h1>طلبات إعادة التخزين</h1>
        <p>اطلب إعادة تخزين المنتجات غير المتوفرة</p>
      </div>

      <div class="restock-content">
        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
          <div class="spinner"></div>
          <p>جاري تحميل طلبات إعادة التخزين...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="error-state">
          <div class="error-icon">⚠️</div>
          <h3>خطأ في التحميل</h3>
          <p>{{ error }}</p>
          <button @click="loadRestockRequests" class="retry-btn">إعادة المحاولة</button>
        </div>

        <!-- Restock Requests List -->
        <div v-else class="restock-section">
          <div class="section-header">
            <h2>طلبات إعادة التخزين</h2>
            <button @click="openRestockForm" class="add-btn">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
              </svg>
              طلب إعادة تخزين جديد
            </button>
          </div>

          <!-- Empty State -->
          <div v-if="restockRequests.length === 0" class="empty-state">
            <div class="empty-icon">🔄</div>
            <h3>لا توجد طلبات إعادة تخزين</h3>
            <p>لم تقم بإنشاء أي طلبات إعادة تخزين بعد</p>
            <button @click="openRestockForm" class="add-first-btn">إنشاء أول طلب</button>
          </div>

          <!-- Requests List -->
          <div v-else class="requests-list">
            <div v-for="request in restockRequests" :key="request.id" class="request-card">
              <div class="request-header">
                <div class="request-info">
                  <h3>{{ request.product_name }}</h3>
                  <p class="request-date">{{ formatDate(request.created_at) }}</p>
                </div>
                <div class="request-status">
                  <span 
                    class="status-badge"
                    :style="{ backgroundColor: getStatusName(request.status).color }"
                  >
                    {{ getStatusName(request.status).name }}
                  </span>
                </div>
              </div>

              <div class="request-details">
                <div class="detail-row">
                  <span class="label">الكمية المطلوبة:</span>
                  <span class="value">{{ request.quantity }}</span>
                </div>
                <div v-if="request.notes" class="detail-row">
                  <span class="label">ملاحظات:</span>
                  <span class="value">{{ request.notes }}</span>
                </div>
                <div v-if="request.admin_notes" class="detail-row">
                  <span class="label">رد الإدارة:</span>
                  <span class="value">{{ request.admin_notes }}</span>
                </div>
              </div>

              <div class="request-actions">
                <button 
                  v-if="request.status === 'pending'"
                  @click="cancelRestockRequest(request.id)"
                  class="action-btn danger"
                >
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                  </svg>
                  إلغاء الطلب
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Restock Form Modal -->
    <div v-if="showRestockForm" class="modal-overlay" @click.self="closeForm">
        <div class="modal-content">
          <div class="modal-header">
            <h2>طلب إعادة تخزين جديد</h2>
            <button @click="closeForm" class="close-btn">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19 6.41L17.59 5L12 10.59L6.41 5L5 6.41L10.59 12L5 17.59L6.41 19L12 13.41L17.59 19L19 17.59L13.41 12z"/>
              </svg>
            </button>
          </div>

          <form @submit.prevent="submitRestockRequest" class="restock-form">
            <div class="form-group">
              <label for="product_name">اسم المنتج</label>
              <input
                id="product_name"
                v-model="restockForm.product_name"
                type="text"
                required
                placeholder="أدخل اسم المنتج المطلوب"
                :disabled="formLoading"
              />
            </div>

            <div class="form-group">
              <label for="quantity">الكمية المطلوبة</label>
              <input
                id="quantity"
                v-model.number="restockForm.quantity"
                type="number"
                min="1"
                required
                :disabled="formLoading"
              />
            </div>

            <div class="form-group">
              <label for="notes">ملاحظات إضافية (اختياري)</label>
              <textarea
                id="notes"
                v-model="restockForm.notes"
                rows="4"
                placeholder="أي ملاحظات إضافية حول طلب إعادة التخزين..."
                :disabled="formLoading"
              ></textarea>
            </div>

            <div v-if="formError" class="error-message">
              {{ formError }}
            </div>

            <div class="form-actions">
              <button type="button" @click="closeForm" class="cancel-btn" :disabled="formLoading">
                إلغاء
              </button>
              <button type="submit" class="submit-btn" :disabled="formLoading">
                <span v-if="formLoading">جاري الإرسال...</span>
                <span v-else>إرسال الطلب</span>
              </button>
            </div>
          </form>
        </div>
      </div>
  </div>
</template>

<style scoped>
.restock-page {
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

/* Restock Section */
.restock-section {
  background: white;
  border-radius: 12px;
  padding: 32px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.section-header h2 {
  font-size: 24px;
  font-weight: 700;
  color: #111827;
  margin: 0;
}

.add-btn {
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

.add-btn:hover {
  background: #553c9a;
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
  margin: 0 0 24px;
}

.add-first-btn {
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

.add-first-btn:hover {
  background: #553c9a;
}

/* Requests List */
.requests-list {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.request-card {
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 24px;
  transition: box-shadow 0.2s;
}

.request-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.request-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  padding-bottom: 16px;
  border-bottom: 1px solid #e5e7eb;
}

.request-info h3 {
  font-size: 18px;
  font-weight: 600;
  color: #111827;
  margin: 0 0 4px;
}

.request-date {
  font-size: 14px;
  color: #6b7280;
  margin: 0;
}

.status-badge {
  color: white;
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}

.request-details {
  margin-bottom: 16px;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 8px;
  font-size: 14px;
}

.detail-row:last-child {
  margin-bottom: 0;
}

.label {
  font-weight: 600;
  color: #374151;
}

.value {
  color: #6b7280;
}

.request-actions {
  display: flex;
  gap: 12px;
}

.action-btn {
  padding: 8px 16px;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
  display: flex;
  align-items: center;
  gap: 6px;
}

.action-btn.danger {
  background: #fee;
  color: #dc2626;
  border: 1px solid #fecaca;
}

.action-btn.danger:hover {
  background: #fecaca;
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
  max-width: 500px;
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

.restock-form {
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

.form-group input,
.form-group textarea {
  width: 100%;
  padding: 12px 16px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 16px;
  transition: border-color 0.2s;
  box-sizing: border-box;
}

.form-group input:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #6b46c1;
  box-shadow: 0 0 0 3px rgba(107, 70, 193, 0.1);
}

.form-group input:disabled,
.form-group textarea:disabled {
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
  .section-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
  }

  .request-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }

  .detail-row {
    flex-direction: column;
    gap: 4px;
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
