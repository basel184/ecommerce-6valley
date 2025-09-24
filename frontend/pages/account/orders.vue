<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const { $get } = useApi()

// Orders data
const orders = ref([])
const loading = ref(false)
const error = ref('')

// Order statuses
const orderStatuses = {
  pending: { name: 'في الانتظار', color: '#f59e0b' },
  confirmed: { name: 'مؤكد', color: '#3b82f6' },
  processing: { name: 'قيد المعالجة', color: '#8b5cf6' },
  shipped: { name: 'تم الشحن', color: '#06b6d4' },
  delivered: { name: 'تم التسليم', color: '#10b981' },
  cancelled: { name: 'ملغي', color: '#ef4444' },
  returned: { name: 'مرتجع', color: '#6b7280' }
}

// Load orders
const loadOrders = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await $get('v1/customer/order/list')
    orders.value = response || []
  } catch (err: any) {
    console.error('Error loading orders:', err)
    error.value = err?.data?.message || 'خطأ في تحميل الطلبات'
  } finally {
    loading.value = false
  }
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

// Format currency
const formatCurrency = (amount: number) => {
  if (!amount || isNaN(amount)) return '0 ﷼'
  return `${amount.toLocaleString()} ﷼`
}

// Get order status
const getOrderStatus = (status: string) => {
  return orderStatuses[status] || { name: status, color: '#6b7280' }
}

// Load data on mount
onMounted(() => {
  loadOrders()
})
</script>

<template>
  <div class="orders-page" dir="rtl">
    <div class="container">
      <div class="page-header">
        <h1>طلباتي</h1>
        <p>عرض وإدارة جميع طلباتك</p>
      </div>

      <div class="orders-content">
        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
          <div class="spinner"></div>
          <p>جاري تحميل الطلبات...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="error-state">
          <div class="error-icon">⚠️</div>
          <h3>خطأ في التحميل</h3>
          <p>{{ error }}</p>
          <button @click="loadOrders" class="retry-btn">إعادة المحاولة</button>
        </div>

        <!-- Empty State -->
        <div v-else-if="orders.length === 0" class="empty-state">
          <div class="empty-icon">📦</div>
          <h3>لا توجد طلبات</h3>
          <p>لم تقم بإنشاء أي طلبات بعد</p>
          <NuxtLink to="/shop" class="shop-btn">تسوق الآن</NuxtLink>
        </div>

        <!-- Orders List -->
        <div v-else class="orders-list">
          <ClientOnly>
            <div v-for="order in orders" :key="order.id" class="order-card">
            <div class="order-header">
              <div class="order-info">
                <h3>طلب #{{ order.id }}</h3>
                <p class="order-date">{{ formatDate(order.created_at) }}</p>
              </div>
              <div class="order-status">
                <span 
                  class="status-badge"
                  :style="{ backgroundColor: getOrderStatus(order.order_status).color }"
                >
                  {{ getOrderStatus(order.order_status).name }}
                </span>
              </div>
            </div>

            <div class="order-details">
              <div class="order-items">
                <div v-for="item in order.details" :key="item.id" class="order-item">
                  <div class="item-image">
                    <img 
                      :src="item.product?.thumbnail || '/placeholder-product.jpg'" 
                      :alt="item.product?.name"
                    />
                  </div>
                  <div class="item-info">
                    <h4>{{ item.product?.name || 'منتج محذوف' }}</h4>
                    <p>الكمية: {{ item.quantity }}</p>
                    <p>السعر: {{ formatCurrency(item.price) }}</p>
                  </div>
                </div>
              </div>

              <div class="order-summary">
                <div class="summary-row">
                  <span>المجموع الفرعي:</span>
                  <span>{{ formatCurrency(order.order_amount) }}</span>
                </div>
                <div class="summary-row">
                  <span>رسوم الشحن:</span>
                  <span>{{ formatCurrency(order.shipping_cost || 0) }}</span>
                </div>
                <div class="summary-row">
                  <span>الخصم:</span>
                  <span>-{{ formatCurrency(order.discount_amount || 0) }}</span>
                </div>
                <div class="summary-row total">
                  <span>المجموع الكلي:</span>
                  <span>{{ formatCurrency(order.order_amount + (order.shipping_cost || 0) - (order.discount_amount || 0)) }}</span>
                </div>
              </div>
            </div>

            <div class="order-actions">
              <NuxtLink :to="`/account/orders/${order.id}`" class="action-btn primary">
                عرض التفاصيل
              </NuxtLink>
              <button v-if="order.order_status === 'delivered'" class="action-btn secondary">
                إعادة الطلب
              </button>
              <button v-if="['pending', 'confirmed'].includes(order.order_status)" class="action-btn danger">
                إلغاء الطلب
              </button>
            </div>
          </ClientOnly>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.orders-page {
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

.shop-btn {
  background: #6b46c1;
  color: white;
  text-decoration: none;
  padding: 12px 24px;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  display: inline-block;
  transition: background-color 0.2s;
}

.shop-btn:hover {
  background: #553c9a;
}

/* Orders List */
.orders-list {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.order-card {
  background: white;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  border: 1px solid #e5e7eb;
}

.order-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding-bottom: 20px;
  border-bottom: 1px solid #e5e7eb;
}

.order-info h3 {
  font-size: 18px;
  font-weight: 600;
  color: #111827;
  margin: 0 0 4px;
}

.order-date {
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

.order-details {
  margin-bottom: 20px;
}

.order-items {
  margin-bottom: 20px;
}

.order-item {
  display: flex;
  gap: 16px;
  padding: 16px 0;
  border-bottom: 1px solid #f3f4f6;
}

.order-item:last-child {
  border-bottom: none;
}

.item-image {
  width: 60px;
  height: 60px;
  border-radius: 8px;
  overflow: hidden;
  background: #f3f4f6;
}

.item-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.item-info h4 {
  font-size: 16px;
  font-weight: 600;
  color: #111827;
  margin: 0 0 8px;
}

.item-info p {
  font-size: 14px;
  color: #6b7280;
  margin: 0 0 4px;
}

.order-summary {
  background: #f9fafb;
  padding: 16px;
  border-radius: 8px;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 8px;
  font-size: 14px;
}

.summary-row:last-child {
  margin-bottom: 0;
}

.summary-row.total {
  font-weight: 600;
  font-size: 16px;
  color: #111827;
  padding-top: 8px;
  border-top: 1px solid #e5e7eb;
}

.order-actions {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
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
}

.action-btn.primary {
  background: #6b46c1;
  color: white;
}

.action-btn.primary:hover {
  background: #553c9a;
}

.action-btn.secondary {
  background: #f3f4f6;
  color: #374151;
  border: 1px solid #d1d5db;
}

.action-btn.secondary:hover {
  background: #e5e7eb;
}

.action-btn.danger {
  background: #fee;
  color: #dc2626;
  border: 1px solid #fecaca;
}

.action-btn.danger:hover {
  background: #fecaca;
}

/* Responsive */
@media (max-width: 768px) {
  .order-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }

  .order-item {
    flex-direction: column;
    gap: 12px;
  }

  .item-image {
    width: 100%;
    height: 120px;
  }

  .order-actions {
    flex-direction: column;
  }

  .action-btn {
    text-align: center;
  }
}
</style>
