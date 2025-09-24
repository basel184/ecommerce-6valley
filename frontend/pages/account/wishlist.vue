<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const { $get, $del } = useApi()

// Wishlist data
const wishlist = ref([])
const loading = ref(false)
const error = ref('')

// Load wishlist
const loadWishlist = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await $get('v1/customer/wish-list')
    wishlist.value = response || []
  } catch (err: any) {
    console.error('Error loading wishlist:', err)
    error.value = err?.data?.message || 'خطأ في تحميل قائمة الرغبات'
  } finally {
    loading.value = false
  }
}

// Remove from wishlist
const removeFromWishlist = async (productId: string) => {
  try {
    await $del('v1/customer/wish-list/remove', {
      product_id: productId
    })
    
    // Remove from local list
    wishlist.value = wishlist.value.filter(item => item.product_id !== productId)
  } catch (err: any) {
    console.error('Error removing from wishlist:', err)
  }
}

// Add to cart
const addToCart = async (product: any) => {
  try {
    const { $post } = useApi()
    await $post('v1/cart/add', {
      product_id: product.product_id,
      quantity: 1
    })
    
    // Show success message or handle cart update
    console.log('Added to cart:', product.name)
  } catch (err: any) {
    console.error('Error adding to cart:', err)
  }
}

// Format currency
const formatCurrency = (amount: number) => {
  if (!amount || isNaN(amount)) return '0 ﷼'
  return `${amount.toLocaleString()} ﷼`
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
  loadWishlist()
})
</script>

<template>
  <div class="wishlist-page" dir="rtl">
    <div class="container">
      <div class="page-header">
        <h1>قائمة الرغبات</h1>
        <p>المنتجات التي تحبها</p>
      </div>

      <div class="wishlist-content">
        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
          <div class="spinner"></div>
          <p>جاري تحميل قائمة الرغبات...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="error-state">
          <div class="error-icon">⚠️</div>
          <h3>خطأ في التحميل</h3>
          <p>{{ error }}</p>
          <button @click="loadWishlist" class="retry-btn">إعادة المحاولة</button>
        </div>

        <!-- Empty State -->
        <div v-else-if="wishlist.length === 0" class="empty-state">
          <div class="empty-icon">❤️</div>
          <h3>قائمة الرغبات فارغة</h3>
          <p>لم تقم بإضافة أي منتجات إلى قائمة الرغبات بعد</p>
          <NuxtLink to="/shop" class="shop-btn">تسوق الآن</NuxtLink>
        </div>

        <!-- Wishlist Grid -->
        <div v-else class="wishlist-grid">
          <ClientOnly>
            <div v-for="item in wishlist" :key="item.id" class="wishlist-item">
            <div class="item-image">
              <img 
                :src="item.product?.thumbnail || '/placeholder-product.jpg'" 
                :alt="item.product?.name"
              />
              <button 
                class="remove-btn"
                @click="removeFromWishlist(item.product_id)"
                title="إزالة من قائمة الرغبات"
              >
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M19 6.41L17.59 5L12 10.59L6.41 5L5 6.41L10.59 12L5 17.59L6.41 19L12 13.41L17.59 19L19 17.59L13.41 12z"/>
                </svg>
              </button>
            </div>

            <div class="item-info">
              <h3 class="item-name">{{ item.product?.name || 'منتج محذوف' }}</h3>
              <p class="item-brand">{{ item.product?.brand?.name || '' }}</p>
              
              <div class="item-rating" v-if="item.product?.reviews_avg_rating">
                <div class="stars">
                  <span 
                    v-for="i in 5" 
                    :key="i" 
                    class="star" 
                    :class="{ filled: i <= Math.round(item.product.reviews_avg_rating) }"
                  >
                    ★
                  </span>
                </div>
                <span class="rating-text">({{ item.product.reviews_count || 0 }})</span>
              </div>

              <div class="item-price">
                <span class="current-price">{{ formatCurrency(item.product?.unit_price || 0) }}</span>
                <span v-if="item.product?.discount" class="old-price">
                  {{ formatCurrency(item.product?.unit_price + item.product?.discount) }}
                </span>
              </div>

              <div class="item-actions">
                <button 
                  class="add-to-cart-btn"
                  @click="addToCart(item.product)"
                  :disabled="!item.product?.current_stock"
                >
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M7 4V2C7 1.45 7.45 1 8 1H16C16.55 1 17 1.45 17 2V4H20C20.55 4 21 4.45 21 5S20.55 6 20 6H19V19C19 20.1 18.1 21 17 21H7C5.9 21 5 20.1 5 19V6H4C3.45 6 3 5.55 3 5S3.45 4 4 4H7ZM9 3V4H15V3H9ZM7 6V19H17V6H7Z"/>
                  </svg>
                  أضف للسلة
                </button>
                <NuxtLink 
                  :to="`/product/${item.product?.slug}`" 
                  class="view-btn"
                >
                  عرض المنتج
                </NuxtLink>
              </div>

              <div class="item-meta">
                <span class="added-date">أضيف في: {{ formatDate(item.created_at) }}</span>
              </div>
            </div>
          </ClientOnly>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.wishlist-page {
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

/* Wishlist Grid */
.wishlist-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 24px;
}

.wishlist-item {
  background: white;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  border: 1px solid #e5e7eb;
  transition: box-shadow 0.2s;
  position: relative;
}

.wishlist-item:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.item-image {
  position: relative;
  width: 100%;
  height: 200px;
  border-radius: 8px;
  overflow: hidden;
  background: #f3f4f6;
  margin-bottom: 16px;
}

.item-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.remove-btn {
  position: absolute;
  top: 8px;
  left: 8px;
  background: rgba(0, 0, 0, 0.7);
  color: white;
  border: none;
  border-radius: 50%;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background-color 0.2s;
}

.remove-btn:hover {
  background: rgba(0, 0, 0, 0.9);
}

.item-info {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.item-name {
  font-size: 16px;
  font-weight: 600;
  color: #111827;
  margin: 0;
  line-height: 1.4;
}

.item-brand {
  font-size: 14px;
  color: #6b7280;
  margin: 0;
}

.item-rating {
  display: flex;
  align-items: center;
  gap: 8px;
}

.stars {
  display: flex;
  gap: 2px;
}

.star {
  color: #d1d5db;
  font-size: 14px;
}

.star.filled {
  color: #f59e0b;
}

.rating-text {
  font-size: 12px;
  color: #6b7280;
}

.item-price {
  display: flex;
  align-items: center;
  gap: 8px;
}

.current-price {
  font-size: 18px;
  font-weight: 700;
  color: #111827;
}

.old-price {
  font-size: 14px;
  color: #9ca3af;
  text-decoration: line-through;
}

.item-actions {
  display: flex;
  gap: 8px;
  margin-top: 12px;
}

.add-to-cart-btn {
  flex: 1;
  background: #6b46c1;
  color: white;
  border: none;
  padding: 10px 16px;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}

.add-to-cart-btn:hover:not(:disabled) {
  background: #553c9a;
}

.add-to-cart-btn:disabled {
  background: #ccc;
  cursor: not-allowed;
}

.view-btn {
  background: #f3f4f6;
  color: #374151;
  text-decoration: none;
  padding: 10px 16px;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  text-align: center;
  transition: background-color 0.2s;
  border: 1px solid #d1d5db;
}

.view-btn:hover {
  background: #e5e7eb;
}

.item-meta {
  margin-top: 8px;
  padding-top: 8px;
  border-top: 1px solid #f3f4f6;
}

.added-date {
  font-size: 12px;
  color: #9ca3af;
}

/* Responsive */
@media (max-width: 768px) {
  .wishlist-grid {
    grid-template-columns: 1fr;
    gap: 16px;
  }

  .item-actions {
    flex-direction: column;
  }

  .add-to-cart-btn,
  .view-btn {
    width: 100%;
  }
}
</style>
