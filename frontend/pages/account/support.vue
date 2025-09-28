<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'

// Types
interface SupportTicket {
  id: string
  subject: string
  type: string
  priority: string
  status: string
  description: string
  created_at: string
  updated_at: string
}

const { t } = useI18n()
const { $get, $post } = useApi()

// Support tickets data
const tickets = ref<SupportTicket[]>([])
const loading = ref(false)
const error = ref('')

// Support form
const showSupportForm = ref(false)
const supportForm = ref({
  subject: '',
  type: 'general',
  priority: 'medium',
  description: '',
  attachments: null as FileList | null
})

const formLoading = ref(false)
const formError = ref('')

// Ticket types
const ticketTypes = [
  { value: 'general', name: 'استفسار عام' },
  { value: 'technical', name: 'مشكلة تقنية' },
  { value: 'order', name: 'مشكلة في الطلب' },
  { value: 'refund', name: 'استرداد' },
  { value: 'other', name: 'أخرى' }
]

// Priority levels
const priorityLevels = [
  { value: 'low', name: 'منخفض', color: '#10b981' },
  { value: 'medium', name: 'متوسط', color: '#f59e0b' },
  { value: 'high', name: 'عالي', color: '#ef4444' },
  { value: 'urgent', name: 'عاجل', color: '#dc2626' }
]

// Load support tickets
const loadTickets = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await $get('v1/customer/support-tickets')
    tickets.value = response || []
  } catch (err: any) {
    console.error('Error loading support tickets:', err)
    error.value = err?.data?.message || 'خطأ في تحميل تذاكر الدعم'
  } finally {
    loading.value = false
  }
}

// Open support form
const openSupportForm = () => {
  supportForm.value = {
    subject: '',
    type: 'general',
    priority: 'medium',
    description: '',
    attachments: null
  }
  formError.value = ''
  showSupportForm.value = true
}

// Close form
const closeForm = () => {
  showSupportForm.value = false
  formError.value = ''
}

// Submit support ticket
const submitSupportTicket = async () => {
  formLoading.value = true
  formError.value = ''

  try {
    await $post('v1/customer/support-ticket', supportForm.value)
    closeForm()
    await loadTickets()
  } catch (err: any) {
    console.error('Error submitting support ticket:', err)
    formError.value = err?.data?.message || 'خطأ في إرسال تذكرة الدعم'
  } finally {
    formLoading.value = false
  }
}

// Get ticket type name
const getTicketTypeName = (type: string) => {
  const typeObj = ticketTypes.find(t => t.value === type)
  return typeObj ? typeObj.name : type
}

// Get priority info
const getPriorityInfo = (priority: string) => {
  const priorityObj = priorityLevels.find(p => p.value === priority)
  return priorityObj || { name: priority, color: '#6b7280' }
}

// Get status name
const getStatusName = (status: string) => {
  const statuses = {
    open: { name: 'مفتوح', color: '#3b82f6' },
    in_progress: { name: 'قيد المعالجة', color: '#f59e0b' },
    resolved: { name: 'تم الحل', color: '#10b981' },
    closed: { name: 'مغلق', color: '#6b7280' }
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
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Load data on mount
onMounted(() => {
  loadTickets()
})
</script>

<template>
  <div class="support-page" dir="rtl">
    <div class="container">
      <div class="page-header">
        <h1>بطاقة الدعم</h1>
        <p>إدارة تذاكر الدعم والاستفسارات</p>
      </div>

      <div class="support-content">
        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
          <div class="spinner"></div>
          <p>جاري تحميل تذاكر الدعم...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="error-state">
          <div class="error-icon">⚠️</div>
          <h3>خطأ في التحميل</h3>
          <p>{{ error }}</p>
          <button @click="loadTickets" class="retry-btn">إعادة المحاولة</button>
        </div>

        <!-- Support Tickets List -->
        <div v-else class="support-section">
          <div class="section-header">
            <h2>تذاكر الدعم</h2>
            <button @click="openSupportForm" class="add-btn">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
              </svg>
              تذكرة دعم جديدة
            </button>
          </div>

          <!-- Empty State -->
          <div v-if="tickets.length === 0" class="empty-state">
            <div class="empty-icon">🎫</div>
            <h3>لا توجد تذاكر دعم</h3>
            <p>لم تقم بإنشاء أي تذاكر دعم بعد</p>
            <button @click="openSupportForm" class="add-first-btn">إنشاء أول تذكرة</button>
          </div>

          <!-- Tickets List -->
          <div v-else class="tickets-list">
            <div v-for="ticket in tickets" :key="ticket.id" class="ticket-card">
              <div class="ticket-header">
                <div class="ticket-info">
                  <h3>{{ ticket.subject }}</h3>
                  <p class="ticket-id">#{{ ticket.id }}</p>
                </div>
                <div class="ticket-badges">
                  <span 
                    class="status-badge"
                    :style="{ backgroundColor: getStatusName(ticket.status).color }"
                  >
                    {{ getStatusName(ticket.status).name }}
                  </span>
                  <span 
                    class="priority-badge"
                    :style="{ backgroundColor: getPriorityInfo(ticket.priority).color }"
                  >
                    {{ getPriorityInfo(ticket.priority).name }}
                  </span>
                </div>
              </div>

              <div class="ticket-details">
                <div class="detail-row">
                  <span class="label">النوع:</span>
                  <span class="value">{{ getTicketTypeName(ticket.type) }}</span>
                </div>
                <div class="detail-row">
                  <span class="label">تاريخ الإنشاء:</span>
                  <span class="value">{{ formatDate(ticket.created_at) }}</span>
                </div>
                <div v-if="ticket.updated_at !== ticket.created_at" class="detail-row">
                  <span class="label">آخر تحديث:</span>
                  <span class="value">{{ formatDate(ticket.updated_at) }}</span>
                </div>
              </div>

              <div class="ticket-description">
                <p>{{ ticket.description }}</p>
              </div>

              <div class="ticket-actions">
                <NuxtLink :to="`/account/support/${ticket.id}`" class="action-btn primary">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                  </svg>
                  عرض التفاصيل
                </NuxtLink>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Support Form Modal -->
    <div v-if="showSupportForm" class="modal-overlay" @click.self="closeForm">
        <div class="modal-content">
          <div class="modal-header">
            <h2>تذكرة دعم جديدة</h2>
            <button @click="closeForm" class="close-btn">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19 6.41L17.59 5L12 10.59L6.41 5L5 6.41L10.59 12L5 17.59L6.41 19L12 13.41L17.59 19L19 17.59L13.41 12z"/>
              </svg>
            </button>
          </div>

          <form @submit.prevent="submitSupportTicket" class="support-form">
            <div class="form-group">
              <label for="subject">موضوع التذكرة</label>
              <input
                id="subject"
                v-model="supportForm.subject"
                type="text"
                required
                placeholder="أدخل موضوع التذكرة"
                :disabled="formLoading"
              />
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="type">نوع التذكرة</label>
                <select id="type" v-model="supportForm.type" required :disabled="formLoading">
                  <option v-for="type in ticketTypes" :key="type.value" :value="type.value">
                    {{ type.name }}
                  </option>
                </select>
              </div>
              <div class="form-group">
                <label for="priority">الأولوية</label>
                <select id="priority" v-model="supportForm.priority" required :disabled="formLoading">
                  <option v-for="priority in priorityLevels" :key="priority.value" :value="priority.value">
                    {{ priority.name }}
                  </option>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label for="description">وصف المشكلة</label>
              <textarea
                id="description"
                v-model="supportForm.description"
                rows="6"
                required
                placeholder="اشرح مشكلتك بالتفصيل..."
                :disabled="formLoading"
              ></textarea>
            </div>

            <div class="form-group">
              <label for="attachments">مرفقات (اختياري)</label>
              <input
                id="attachments"
                type="file"
                multiple
                accept="image/*,.pdf,.doc,.docx"
                @change="supportForm.attachments = ($event.target as HTMLInputElement).files"
                :disabled="formLoading"
              />
              <p class="help-text">يمكنك رفع الصور أو المستندات (PDF, Word)</p>
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
                <span v-else>إرسال التذكرة</span>
              </button>
            </div>
          </form>
        </div>
      </div>
  </div>
</template>

<style scoped>
.support-page {
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

/* Support Section */
.support-section {
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

/* Tickets List */
.tickets-list {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.ticket-card {
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 24px;
  transition: box-shadow 0.2s;
}

.ticket-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.ticket-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  padding-bottom: 16px;
  border-bottom: 1px solid #e5e7eb;
}

.ticket-info h3 {
  font-size: 18px;
  font-weight: 600;
  color: #111827;
  margin: 0 0 4px;
}

.ticket-id {
  font-size: 14px;
  color: #6b7280;
  margin: 0;
}

.ticket-badges {
  display: flex;
  gap: 8px;
}

.status-badge,
.priority-badge {
  color: white;
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
}

.ticket-details {
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

.ticket-description {
  margin-bottom: 16px;
}

.ticket-description p {
  font-size: 14px;
  color: #374151;
  line-height: 1.6;
  margin: 0;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.ticket-actions {
  display: flex;
  gap: 12px;
}

.action-btn {
  padding: 8px 16px;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  text-decoration: none;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
  display: flex;
  align-items: center;
  gap: 6px;
}

.action-btn.primary {
  background: #6b46c1;
  color: white;
}

.action-btn.primary:hover {
  background: #553c9a;
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
  max-width: 600px;
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

.support-form {
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
.form-group select,
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
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #6b46c1;
  box-shadow: 0 0 0 3px rgba(107, 70, 193, 0.1);
}

.form-group input:disabled,
.form-group select:disabled,
.form-group textarea:disabled {
  background: #f9fafb;
  color: #6b7280;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

.help-text {
  font-size: 12px;
  color: #6b7280;
  margin: 4px 0 0;
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

  .ticket-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }

  .ticket-badges {
    align-self: flex-start;
  }

  .detail-row {
    flex-direction: column;
    gap: 4px;
  }

  .form-row {
    grid-template-columns: 1fr;
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
