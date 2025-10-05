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
  image: null as File | null
})

const profileLoading = ref(false)
const profileError = ref('')
const profileSuccess = ref('')

// Orders data
interface OrderItem {
  id: string
  product_id: string
  qty: number
  price: number
  product?: {
    name: string
    thumbnail: string
  }
}

interface Order {
  id: string
  order_status: string
  created_at: string
  order_amount: number
  details: OrderItem[]
}

const orders = ref<Order[]>([])
const ordersLoading = ref(false)
const ordersError = ref('')

// Wishlist data
interface WishlistItem {
  id: string
  product_id: string
  created_at: string
  productFullInfo?: {
    id?: string
    name?: string
    product_name?: string
    thumbnail?: string
    image?: string
    brand?: {
      name: string
    }
    unit_price?: number
    discount?: number
    current_stock?: number
    reviews_avg_rating?: number
    reviews_count?: number
    slug?: string
  }
}

const wishlist = ref<WishlistItem[]>([])
const wishlistLoading = ref(false)
const wishlistError = ref('')

// Addresses data
interface Address {
  id: string
  address_type: string
  contact_person_name: string
  contact_person_number: string
  phone?: string
  address: string
  city: string
  zip: string
  country: string
  latitude?: string | number
  longitude?: string | number
  is_billing?: number
}

const addresses = ref<Address[]>([])
const addressesLoading = ref(false)
const addressesError = ref('')

// Address form
const showAddressForm = ref(false)
const editingAddress = ref<Address | null>(null)
const addressForm = ref({
  contact_person_name: '',
  contact_person_number: '',
  phone: '', // Required field
  address: '',
  city: ''
})

const addressFormLoading = ref(false)
const addressFormError = ref('')

// Support tickets data
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

const supportTickets = ref<SupportTicket[]>([])
const supportLoading = ref(false)
const supportError = ref('')

// Support form
const showSupportForm = ref(false)
const supportForm = ref({
  subject: '',
  type: 'general',
  priority: 'medium',
  description: '',
  attachments: null as FileList | null
})

const supportFormLoading = ref(false)
const supportFormError = ref('')

// Coupons data
interface Coupon {
  id: string
  code: string
  coupon_type: string
  discount: number
  min_purchase: number
  max_discount: number
  expire_date: string
  status: number
}

const myCoupons = ref<Coupon[]>([])
const availableCoupons = ref<Coupon[]>([])
const couponsLoading = ref(false)
const couponsError = ref('')

// Coupon form
const showCouponForm = ref(false)
const couponForm = ref({
  code: ''
})

const couponFormLoading = ref(false)
const couponFormError = ref('')
const couponFormSuccess = ref('')

// Restock requests data
interface RestockRequest {
  id: string
  product_id: string
  product_name: string
  product_sku: string
  product_image: string
  message: string
  status: string
  created_at: string
}

const restockRequests = ref<RestockRequest[]>([])
const restockLoading = ref(false)
const restockError = ref('')

// Tracking data
interface TrackingResult {
  order_id: string
  status: string
  created_at: string
  order_amount: number
  delivery_man?: {
    name: string
    phone: string
  }
  tracking_history: Array<{
    status_name: string
    description: string
    created_at: string
    is_current: boolean
    is_completed: boolean
  }>
  order_items: Array<{
    id: string
    product_name: string
    product_image: string
    quantity: number
    price: number
  }>
}

const trackingForm = ref({
  searchTerm: '',
  searchType: 'order_id' as 'order_id' | 'phone'
})

const trackingResult = ref<TrackingResult | null>(null)
const trackingLoading = ref(false)
const trackingError = ref('')

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

// Load orders
const loadOrders = async () => {
  ordersLoading.value = true
  ordersError.value = ''

  try {
    const { $get } = useApi()
    const response = await $get('v1/customer/order/list')
    orders.value = response?.orders || []
  } catch (error: any) {
    console.error('Error loading orders:', error)
    ordersError.value = error?.data?.message || 'خطأ في تحميل الطلبات'
  } finally {
    ordersLoading.value = false
  }
}

// Order status mapping
const orderStatuses = {
  pending: { name: 'في الانتظار', color: '#f59e0b' },
  confirmed: { name: 'مؤكد', color: '#3b82f6' },
  processing: { name: 'قيد المعالجة', color: '#8b5cf6' },
  out_for_delivery: { name: 'خارج للتوصيل', color: '#06b6d4' },
  delivered: { name: 'تم التسليم', color: '#10b981' },
  canceled: { name: 'ملغي', color: '#ef4444' },
  failed: { name: 'فشل', color: '#ef4444' },
  returned: { name: 'مرتجع', color: '#6b7280' }
}

const getOrderStatus = (status: string) => {
  return orderStatuses[status as keyof typeof orderStatuses] || { name: status, color: '#6b7280' }
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

// Check if order can be reordered
const canReorder = (order: any) => {
  return order.order_status === 'delivered' || order.order_status === 'canceled'
}

// View order details
const viewOrderDetails = (orderId: string) => {
  // Navigate to order details page or show modal
  console.log('View order details:', orderId)
}

// Reorder items
const reorderItems = async (order: any) => {
  try {
    const { $post } = useApi()
    await $post('v1/customer/order/again', { order_id: order.id })
    // Show success message or redirect to cart
    console.log('Items added to cart for reorder')
  } catch (error: any) {
    console.error('Error reordering items:', error)
  }
}

// Load wishlist
const loadWishlist = async () => {
  wishlistLoading.value = true
  wishlistError.value = ''

  try {
    const { $get } = useApi()
    const response = await $get('v1/customer/wish-list/')
    wishlist.value = response || []
  } catch (error: any) {
    console.error('Error loading wishlist:', error)
    wishlistError.value = error?.data?.message || 'خطأ في تحميل قائمة الرغبات'
  } finally {
    wishlistLoading.value = false
  }
}

// Remove from wishlist
const removeFromWishlist = async (productId: string) => {
  try {
    const { $del } = useApi()
    await $del('v1/customer/wish-list/remove', {
      params: {
        product_id: productId
      }
    })
    
    // Remove from local list
    wishlist.value = wishlist.value.filter(item => item.product_id !== productId)
  } catch (error: any) {
    console.error('Error removing from wishlist:', error)
  }
}

// Add to cart
const addToCart = async (product: any) => {
  try {
    const { $post } = useApi()
    await $post('v1/cart/add', {
      product_id: product.id || product.product_id,
      quantity: 1
    })
    
    // Show success message or handle cart update
    console.log('Added to cart:', product.name)
  } catch (error: any) {
    console.error('Error adding to cart:', error)
  }
}

// Load addresses
const loadAddresses = async () => {
  addressesLoading.value = true
  addressesError.value = ''

  try {
    const { $get } = useApi()
    const response = await $get('v1/customer/address/list')
    addresses.value = response || []
  } catch (error: any) {
    console.error('Error loading addresses:', error)
    addressesError.value = error?.data?.message || 'خطأ في تحميل العناوين'
  } finally {
    addressesLoading.value = false
  }
}

// Open address form
const openAddressForm = () => {
  editingAddress.value = null
  addressForm.value = {
    contact_person_name: '',
    contact_person_number: '',
    phone: '', // Required field
    address: '',
    city: ''
  }
  addressFormError.value = ''
  showAddressForm.value = true
}

// Edit address
const editAddress = (address: Address) => {
  editingAddress.value = address
  addressForm.value = {
    contact_person_name: address.contact_person_name || '',
    contact_person_number: address.contact_person_number || '',
    phone: address.phone || address.contact_person_number || '', // Use phone or fallback to contact_person_number
    address: address.address || '',
    city: address.city || ''
  }
  addressFormError.value = ''
  showAddressForm.value = true
}

// Close address form
const closeAddressForm = () => {
  showAddressForm.value = false
  editingAddress.value = null
  addressFormError.value = ''
}

// Save address
const saveAddress = async () => {
  addressFormLoading.value = true
  addressFormError.value = ''

  try {
    const { $post, $put } = useApi()
    
    // Prepare form data with required fields - ensure all required fields are present
    const formData = {
      address_type: 'home', // Default value
      contact_person_name: addressForm.value.contact_person_name,
      contact_person_number: addressForm.value.contact_person_number,
      address: addressForm.value.address,
      city: addressForm.value.city,
      zip: '', // Default empty
      country: 'Saudi Arabia', // Default value
      // Required fields with fallbacks - ensure they are not empty
      phone: addressForm.value.phone || addressForm.value.contact_person_number || '',
      latitude: '24.7136', // Default Riyadh coordinates
      longitude: '46.6753', // Default Riyadh coordinates
      is_billing: 1 // Default value
    }

    console.log('Sending address form data:', formData) // Debug log
    
    if (editingAddress.value) {
      // Update existing address
      await $put('v1/customer/address/update', {
        id: editingAddress.value.id,
        ...formData
      })
    } else {
      // Add new address
      await $post('v1/customer/address/add', formData)
    }
    
    await loadAddresses()
    closeAddressForm()
  } catch (error: any) {
    console.error('Error saving address:', error)
    addressFormError.value = error?.data?.message || 'خطأ في حفظ العنوان'
  } finally {
    addressFormLoading.value = false
  }
}

// Delete address
const deleteAddress = async (addressId: string) => {
  if (!confirm('هل أنت متأكد من حذف هذا العنوان؟')) return

  try {
    const { $del } = useApi()
    await $del('v1/customer/address', { id: addressId })
    await loadAddresses()
  } catch (error: any) {
    console.error('Error deleting address:', error)
  }
}

// Get address type name
const getAddressTypeName = (type: string) => {
  const types = {
    home: 'منزل',
    work: 'عمل',
    other: 'أخرى'
  }
  return types[type as keyof typeof types] || type
}

// Load support tickets
const loadSupportTickets = async () => {
  supportLoading.value = true
  supportError.value = ''

  try {
    const { $get } = useApi()
    const response = await $get('v1/customer/support-ticket/get')
    supportTickets.value = response || []
  } catch (error: any) {
    console.error('Error loading support tickets:', error)
    supportError.value = error?.data?.message || 'خطأ في تحميل تذاكر الدعم'
  } finally {
    supportLoading.value = false
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
  supportFormError.value = ''
  showSupportForm.value = true
}

// Close support form
const closeSupportForm = () => {
  showSupportForm.value = false
  supportFormError.value = ''
}

// Create support ticket
const createSupportTicket = async () => {
  supportFormLoading.value = true
  supportFormError.value = ''

  try {
    const { $post } = useApi()
    const formData = new FormData()
    
    formData.append('subject', supportForm.value.subject)
    formData.append('type', supportForm.value.type)
    formData.append('priority', supportForm.value.priority)
    formData.append('description', supportForm.value.description)
    
    if (supportForm.value.attachments) {
      for (let i = 0; i < supportForm.value.attachments.length; i++) {
        formData.append('image[]', supportForm.value.attachments[i])
      }
    }

    await $post('v1/customer/support-ticket/create', formData)
    await loadSupportTickets()
    closeSupportForm()
  } catch (error: any) {
    console.error('Error creating support ticket:', error)
    supportFormError.value = error?.data?.message || 'خطأ في إنشاء تذكرة الدعم'
  } finally {
    supportFormLoading.value = false
  }
}

// View ticket details
const viewTicket = (ticketId: string) => {
  // Navigate to ticket details or show modal
  console.log('View ticket:', ticketId)
}

// Close ticket
const closeTicket = async (ticketId: string) => {
  if (!confirm('هل أنت متأكد من إغلاق هذه التذكرة؟')) return

  try {
    const { $get } = useApi()
    await $get(`v1/customer/support-ticket/close/${ticketId}`)
    await loadSupportTickets()
  } catch (error: any) {
    console.error('Error closing ticket:', error)
  }
}

// Get ticket status name
const getTicketStatusName = (status: string) => {
  const statuses = {
    open: 'مفتوح',
    closed: 'مغلق',
    pending: 'في الانتظار'
  }
  return statuses[status as keyof typeof statuses] || status
}

// Get ticket type name
const getTicketTypeName = (type: string) => {
  const types = {
    general: 'استفسار عام',
    technical: 'مشكلة تقنية',
    order: 'مشكلة في الطلب',
    refund: 'استرداد',
    other: 'أخرى'
  }
  return types[type as keyof typeof types] || type
}

// Get priority name
const getPriorityName = (priority: string) => {
  const priorities = {
    low: 'منخفض',
    medium: 'متوسط',
    high: 'عالي',
    urgent: 'عاجل'
  }
  return priorities[priority as keyof typeof priorities] || priority
}

// Load coupons
const loadCoupons = async () => {
  couponsLoading.value = true
  couponsError.value = ''

  try {
    const { $get } = useApi()
    
    // Load my coupons
    const myCouponsResponse = await $get('v1/coupon/list')
    myCoupons.value = myCouponsResponse || []
    
    // Load available coupons
    const availableCouponsResponse = await $get('v1/coupon/applicable-list')
    availableCoupons.value = availableCouponsResponse || []
  } catch (error: any) {
    console.error('Error loading coupons:', error)
    couponsError.value = error?.data?.message || 'خطأ في تحميل القسائم'
  } finally {
    couponsLoading.value = false
  }
}

// Open coupon form
const openCouponForm = () => {
  couponForm.value = { code: '' }
  couponFormError.value = ''
  couponFormSuccess.value = ''
  showCouponForm.value = true
}

// Close coupon form
const closeCouponForm = () => {
  showCouponForm.value = false
  couponFormError.value = ''
  couponFormSuccess.value = ''
}

// Apply coupon code
const applyCouponCode = async () => {
  couponFormLoading.value = true
  couponFormError.value = ''
  couponFormSuccess.value = ''

  try {
    const { $get } = useApi()
    const response = await $get('v1/coupon/apply', {
      params: { code: couponForm.value.code }
    })
    
    couponFormSuccess.value = 'تم تطبيق القسيمة بنجاح!'
    await loadCoupons()
    
    setTimeout(() => {
      closeCouponForm()
    }, 2000)
  } catch (error: any) {
    console.error('Error applying coupon:', error)
    couponFormError.value = error?.data?.message || 'خطأ في تطبيق القسيمة'
  } finally {
    couponFormLoading.value = false
  }
}

// Apply coupon directly
const applyCoupon = async (code: string) => {
  try {
    const { $get } = useApi()
    await $get('v1/coupon/apply', {
      params: { code }
    })
    await loadCoupons()
  } catch (error: any) {
    console.error('Error applying coupon:', error)
  }
}

// Get coupon type name
const getCouponTypeName = (type: string) => {
  const types = {
    discount_on_purchase: 'خصم على المشتريات',
    free_delivery: 'توصيل مجاني',
    first_order: 'خصم الطلب الأول'
  }
  return types[type as keyof typeof types] || type
}

// Load restock requests
const loadRestockRequests = async () => {
  restockLoading.value = true
  restockError.value = ''

  try {
    const { $get } = useApi()
    const response = await $get('v1/customer/restock-requests/list')
    restockRequests.value = response || []
  } catch (error: any) {
    console.error('Error loading restock requests:', error)
    restockError.value = error?.data?.message || 'خطأ في تحميل طلبات إعادة التخزين'
  } finally {
    restockLoading.value = false
  }
}

// Delete restock request
const deleteRestockRequest = async (requestId: string) => {
  if (!confirm('هل أنت متأكد من إلغاء طلب إعادة التخزين؟')) return

  try {
    const { $post } = useApi()
    await $post('v1/customer/restock-requests/delete', {
      id: requestId
    })
    await loadRestockRequests()
  } catch (error: any) {
    console.error('Error deleting restock request:', error)
  }
}

// Get restock status name
const getRestockStatusName = (status: string) => {
  const statuses = {
    pending: 'في الانتظار',
    approved: 'موافق عليه',
    rejected: 'مرفوض',
    completed: 'مكتمل'
  }
  return statuses[status as keyof typeof statuses] || status
}

// Track order
const trackOrder = async () => {
  trackingLoading.value = true
  trackingError.value = ''
  trackingResult.value = null

  try {
    const { $get } = useApi()
    const params = trackingForm.value.searchType === 'order_id' 
      ? { order_id: trackingForm.value.searchTerm }
      : { phone: trackingForm.value.searchTerm }
    
    const response = await $get('v1/order/track', { params })
    trackingResult.value = response
  } catch (error: any) {
    console.error('Error tracking order:', error)
    trackingError.value = error?.data?.message || 'لم يتم العثور على الطلب'
  } finally {
    trackingLoading.value = false
  }
}

// Clear tracking
const clearTracking = () => {
  trackingResult.value = null
  trackingError.value = ''
  trackingForm.value = {
    searchTerm: '',
    searchType: 'order_id'
  }
}

// Get order status name
const getOrderStatusName = (status: string) => {
  const statuses = {
    pending: 'في الانتظار',
    confirmed: 'مؤكد',
    processing: 'قيد المعالجة',
    shipped: 'تم الشحن',
    delivered: 'تم التسليم',
    cancelled: 'ملغي',
    returned: 'مرتجع'
  }
  return statuses[status as keyof typeof statuses] || status
}

// Load data on mount
onMounted(() => {
  loadUserData()
  loadOrders()
  loadWishlist()
  loadAddresses()
  loadSupportTickets()
  loadCoupons()
  loadRestockRequests()
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
                  @change="profileForm.image = ($event.target as HTMLInputElement)?.files?.[0] || null"
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

            <!-- Loading State -->
            <div v-if="ordersLoading" class="loading-state">
              <div class="spinner"></div>
              <p>جاري تحميل الطلبات...</p>
            </div>

            <!-- Error State -->
            <div v-else-if="ordersError" class="error-state">
              <div class="error-icon">⚠️</div>
              <h3>خطأ في تحميل الطلبات</h3>
              <p>{{ ordersError }}</p>
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
              <div v-for="order in orders" :key="order.id" class="order-card">
                <div class="order-header">
                  <div class="order-info">
                    <h3>طلب رقم #{{ order.id }}</h3>
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

                <div class="order-items">
                  <div v-for="item in order.details" :key="item.id" class="order-item">
                    <div class="item-image">
                      <img 
                        :src="item.product?.thumbnail || '/images/placeholder.png'" 
                        :alt="item.product?.name"
                        @error="($event.target as HTMLImageElement).src = '/images/placeholder.png'"
                      />
                    </div>
                    <div class="item-details">
                      <h4>{{ item.product?.name || 'منتج' }}</h4>
                      <p>الكمية: {{ item.qty }}</p>
                      <p class="item-price">{{ formatCurrency(item.price) }}</p>
                    </div>
                  </div>
                </div>

                <div class="order-footer">
                  <div class="order-total">
                    <span>المجموع: {{ formatCurrency(order.order_amount) }}</span>
                  </div>
                  <div class="order-actions">
                    <button class="action-btn secondary" @click="viewOrderDetails(order.id)">
                      عرض التفاصيل
                    </button>
                    <button 
                      v-if="canReorder(order)" 
                      class="action-btn primary" 
                      @click="reorderItems(order)"
                    >
                      إعادة الطلب
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Restock Tab -->
          <div v-if="activeTab === 'restock'" class="tab-content">
            <div class="tab-header">
              <h2>طلبات إعادة التخزين</h2>
              <p>إدارة طلبات إعادة التخزين</p>
            </div>

            <!-- Loading State -->
            <div v-if="restockLoading" class="loading-state">
              <div class="spinner"></div>
              <p>جاري تحميل طلبات إعادة التخزين...</p>
            </div>

            <!-- Error State -->
            <div v-else-if="restockError" class="error-state">
              <div class="error-icon">⚠️</div>
              <h3>خطأ في تحميل طلبات إعادة التخزين</h3>
              <p>{{ restockError }}</p>
              <button @click="loadRestockRequests" class="retry-btn">إعادة المحاولة</button>
            </div>

            <!-- Empty State -->
            <div v-else-if="restockRequests.length === 0" class="empty-state">
              <div class="empty-icon">🔄</div>
              <h3>لا توجد طلبات إعادة تخزين</h3>
              <p>لم تقم بإنشاء أي طلبات إعادة تخزين بعد</p>
            </div>

            <!-- Restock Requests List -->
            <div v-else class="restock-requests-list">
              <div v-for="request in restockRequests" :key="request.id" class="restock-card">
                <div class="restock-header">
                  <div class="restock-info">
                    <h3>{{ request.product_name || 'منتج' }}</h3>
                    <p class="restock-date">{{ formatDate(request.created_at) }}</p>
                  </div>
                  <div class="restock-status">
                    <span 
                      class="status-badge" 
                      :class="request.status"
                    >
                      {{ getRestockStatusName(request.status) }}
                    </span>
                  </div>
                </div>

                <div class="restock-details">
                  <div class="product-info">
                    <div class="product-image">
                      <img 
                        :src="request.product_image || '/images/placeholder.png'" 
                        :alt="request.product_name"
                        @error="($event.target as HTMLImageElement).src = '/images/placeholder.png'"
                      />
                    </div>
                    <div class="product-details">
                      <p class="product-name">{{ request.product_name || 'منتج' }}</p>
                      <p class="product-sku" v-if="request.product_sku">
                        SKU: {{ request.product_sku }}
                      </p>
                    </div>
                  </div>
                  
                  <div class="restock-message" v-if="request.message">
                    <p><strong>رسالتك:</strong> {{ request.message }}</p>
                  </div>
                </div>

                <div class="restock-actions">
                  <button 
                    v-if="request.status === 'pending'" 
                    class="action-btn danger" 
                    @click="deleteRestockRequest(request.id)"
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

          <!-- Wishlist Tab -->
          <div v-if="activeTab === 'wishlist'" class="tab-content">
            <div class="tab-header">
              <h2>قائمة الرغبات</h2>
              <p>المنتجات التي تحبها</p>
            </div>

            <!-- Loading State -->
            <div v-if="wishlistLoading" class="loading-state">
              <div class="spinner"></div>
              <p>جاري تحميل قائمة الرغبات...</p>
            </div>

            <!-- Error State -->
            <div v-else-if="wishlistError" class="error-state">
              <div class="error-icon">⚠️</div>
              <h3>خطأ في تحميل قائمة الرغبات</h3>
              <p>{{ wishlistError }}</p>
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
              <div v-for="item in wishlist" :key="item.id" class="wishlist-item">
                <div class="item-image">
                  <img 
                    :src="item.productFullInfo?.thumbnail || item.productFullInfo?.image || '/images/placeholder.png'" 
                    :alt="item.productFullInfo?.name || item.productFullInfo?.product_name"
                    @error="($event.target as HTMLImageElement).src = '/images/placeholder.png'"
                  />
                  <button 
                    class="remove-btn" 
                    @click="removeFromWishlist(item.product_id)"
                    :disabled="wishlistLoading"
                  >
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                      <path d="M19 6.41L17.59 5L12 10.59L6.41 5L5 6.41L10.59 12L5 17.59L6.41 19L12 13.41L17.59 19L19 17.59L13.41 12z"/>
                    </svg>
                  </button>
                </div>
                
                <div class="item-details">
                  <h3 class="item-name">{{ item.productFullInfo?.name || item.productFullInfo?.product_name || 'منتج' }}</h3>
                  <p v-if="item.productFullInfo?.brand?.name" class="item-brand">
                    {{ item.productFullInfo.brand.name }}
                  </p>
                  
                  <div class="item-rating" v-if="item.productFullInfo?.reviews_avg_rating">
                    <div class="stars">
                      <span v-for="i in 5" :key="i" class="star" :class="{ filled: i <= Math.round(item.productFullInfo.reviews_avg_rating) }">
                        ★
                      </span>
                    </div>
                    <span class="rating-text">({{ item.productFullInfo.reviews_count || 0 }})</span>
                  </div>

                  <div class="item-price">
                    <span v-if="item.productFullInfo?.discount && item.productFullInfo?.unit_price" class="original-price">
                      {{ formatCurrency(item.productFullInfo.unit_price) }}
                    </span>
                    <span class="current-price">
                      {{ formatCurrency((item.productFullInfo?.unit_price || 0) - (item.productFullInfo?.discount || 0)) }}
                    </span>
                    <span v-if="item.productFullInfo?.discount && item.productFullInfo?.unit_price" class="discount-badge">
                      -{{ Math.round((item.productFullInfo.discount / item.productFullInfo.unit_price) * 100) }}%
                    </span>
                  </div>

                  <div class="item-stock" :class="{ 'out-of-stock': !item.productFullInfo?.current_stock }">
                    <span v-if="item.productFullInfo?.current_stock">
                      متوفر ({{ item.productFullInfo.current_stock }} قطعة)
                    </span>
                    <span v-else>غير متوفر</span>
                  </div>
                </div>

                <div class="item-actions">
                  <button 
                    class="action-btn primary" 
                    @click="addToCart(item.productFullInfo)"
                    :disabled="!item.productFullInfo?.current_stock || wishlistLoading"
                  >
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                      <path d="M7 18a2 2 0 1 0 0 4a2 2 0 0 0 0-4m10 0a2 2 0 1 0 .001 4.001A2 2 0 0 0 17 18M7.16 14h9.53c.75 0 1.4-.42 1.73-1.05L22 7H6.21L5.27 5H2v2h2l3.6 7.59l-1.35 2.44A2 2 0 0 0 8 20h12v-2H8l1.1-2h8.59l-1.45 2H7.16Z"/>
                    </svg>
                    أضف للسلة
                  </button>
                  <NuxtLink 
                    :to="`/product/${item.productFullInfo?.slug || item.productFullInfo?.id || item.product_id}`" 
                    class="action-btn secondary"
                  >
                    عرض المنتج
                  </NuxtLink>
                </div>
              </div>
            </div>
          </div>

          <!-- Addresses Tab -->
          <div v-if="activeTab === 'addresses'" class="tab-content">
            <div class="tab-header">
              <h2>عنواني</h2>
              <p>إدارة عناوينك</p>
              <button class="add-address-btn" @click="openAddressForm">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                </svg>
                إضافة عنوان جديد
              </button>
            </div>

            <!-- Loading State -->
            <div v-if="addressesLoading" class="loading-state">
              <div class="spinner"></div>
              <p>جاري تحميل العناوين...</p>
            </div>

            <!-- Error State -->
            <div v-else-if="addressesError" class="error-state">
              <div class="error-icon">⚠️</div>
              <h3>خطأ في تحميل العناوين</h3>
              <p>{{ addressesError }}</p>
              <button @click="loadAddresses" class="retry-btn">إعادة المحاولة</button>
            </div>

            <!-- Empty State -->
            <div v-else-if="addresses.length === 0" class="empty-state">
              <div class="empty-icon">📍</div>
              <h3>لا توجد عناوين</h3>
              <p>لم تقم بإضافة أي عناوين بعد</p>
              <button @click="openAddressForm" class="add-address-btn">إضافة عنوان جديد</button>
            </div>

            <!-- Addresses List -->
            <div v-else class="addresses-list">
              <div v-for="address in addresses" :key="address.id" class="address-card">
                <div class="address-header">
                  <div class="address-type">
                    <span class="type-badge" :class="address.address_type">
                      {{ getAddressTypeName(address.address_type) }}
                    </span>
                  </div>
                  <div class="address-actions">
                    <button class="action-btn secondary" @click="editAddress(address)">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
                      </svg>
                      تعديل
                    </button>
                    <button class="action-btn danger" @click="deleteAddress(address.id)">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                      </svg>
                      حذف
                    </button>
                  </div>
                </div>

                <div class="address-details">
                  <div class="contact-info">
                    <h3>{{ address.contact_person_name }}</h3>
                    <p>{{ address.contact_person_number }}</p>
                  </div>
                  
                  <div class="address-info">
                    <p class="address-text">{{ address.address }}</p>
                    <p class="city-zip">{{ address.city }}, {{ address.zip }}</p>
                    <p class="country">{{ address.country }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Support Tab -->
          <div v-if="activeTab === 'support'" class="tab-content">
            <div class="tab-header">
              <h2>تذاكر الدعم</h2>
              <p>إدارة تذاكر الدعم</p>
              <button class="create-ticket-btn" @click="openSupportForm">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                </svg>
                إنشاء تذكرة جديدة
              </button>
            </div>

            <!-- Loading State -->
            <div v-if="supportLoading" class="loading-state">
              <div class="spinner"></div>
              <p>جاري تحميل تذاكر الدعم...</p>
            </div>

            <!-- Error State -->
            <div v-else-if="supportError" class="error-state">
              <div class="error-icon">⚠️</div>
              <h3>خطأ في تحميل تذاكر الدعم</h3>
              <p>{{ supportError }}</p>
              <button @click="loadSupportTickets" class="retry-btn">إعادة المحاولة</button>
            </div>

            <!-- Empty State -->
            <div v-else-if="supportTickets.length === 0" class="empty-state">
              <div class="empty-icon">🎫</div>
              <h3>لا توجد تذاكر دعم</h3>
              <p>لم تقم بإنشاء أي تذاكر دعم بعد</p>
              <button @click="openSupportForm" class="create-ticket-btn">إنشاء تذكرة جديدة</button>
            </div>

            <!-- Support Tickets List -->
            <div v-else class="support-tickets-list">
              <div v-for="ticket in supportTickets" :key="ticket.id" class="ticket-card">
                <div class="ticket-header">
                  <div class="ticket-info">
                    <h3>{{ ticket.subject }}</h3>
                    <p class="ticket-date">{{ formatDate(ticket.created_at) }}</p>
                  </div>
                  <div class="ticket-status">
                    <span 
                      class="status-badge" 
                      :class="ticket.status"
                    >
                      {{ getTicketStatusName(ticket.status) }}
                    </span>
                    <span 
                      class="priority-badge" 
                      :class="ticket.priority"
                    >
                      {{ getPriorityName(ticket.priority) }}
                    </span>
                  </div>
                </div>

                <div class="ticket-details">
                  <div class="ticket-type">
                    <span class="type-label">النوع:</span>
                    <span class="type-value">{{ getTicketTypeName(ticket.type) }}</span>
                  </div>
                  
                  <div class="ticket-description">
                    <p>{{ ticket.description }}</p>
                  </div>
                </div>

                <div class="ticket-actions">
                  <button class="action-btn secondary" @click="viewTicket(ticket.id)">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                      <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                    </svg>
                    عرض التفاصيل
                  </button>
                  <button 
                    v-if="ticket.status === 'open'" 
                    class="action-btn danger" 
                    @click="closeTicket(ticket.id)"
                  >
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                      <path d="M19 6.41L17.59 5L12 10.59L6.41 5L5 6.41L10.59 12L5 17.59L6.41 19L12 13.41L17.59 19L19 17.59L13.41 12z"/>
                    </svg>
                    إغلاق التذكرة
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Coupons Tab -->
          <div v-if="activeTab === 'coupons'" class="tab-content">
            <div class="tab-header">
              <h2>قسائم التخفيض</h2>
              <p>قسائمك المتاحة</p>
              <button class="apply-coupon-btn" @click="openCouponForm">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                </svg>
                تطبيق قسيمة
              </button>
            </div>

            <!-- Loading State -->
            <div v-if="couponsLoading" class="loading-state">
              <div class="spinner"></div>
              <p>جاري تحميل القسائم...</p>
            </div>

            <!-- Error State -->
            <div v-else-if="couponsError" class="error-state">
              <div class="error-icon">⚠️</div>
              <h3>خطأ في تحميل القسائم</h3>
              <p>{{ couponsError }}</p>
              <button @click="loadCoupons" class="retry-btn">إعادة المحاولة</button>
            </div>

            <!-- Empty State -->
            <div v-else-if="myCoupons.length === 0 && availableCoupons.length === 0" class="empty-state">
              <div class="empty-icon">🎟️</div>
              <h3>لا توجد قسائم</h3>
              <p>لا توجد قسائم متاحة حالياً</p>
            </div>

            <!-- Coupons Content -->
            <div v-else class="coupons-content">
              <!-- My Coupons -->
              <div v-if="myCoupons.length > 0" class="coupons-section">
                <h3 class="section-title">قسائمي</h3>
                <div class="coupons-grid">
                  <div v-for="coupon in myCoupons" :key="coupon.id" class="coupon-card my-coupon">
                    <div class="coupon-header">
                      <div class="coupon-code">{{ coupon.code }}</div>
                      <div class="coupon-type">{{ getCouponTypeName(coupon.coupon_type) }}</div>
                    </div>
                    
                    <div class="coupon-details">
                      <div class="coupon-discount">
                        <span v-if="coupon.coupon_type === 'discount_on_purchase'">
                          خصم {{ coupon.discount }}%
                        </span>
                        <span v-else-if="coupon.coupon_type === 'free_delivery'">
                          توصيل مجاني
                        </span>
                        <span v-else>
                          خصم {{ coupon.discount }} ﷼
                        </span>
                      </div>
                      
                      <div class="coupon-conditions">
                        <p v-if="coupon.min_purchase > 0">
                          الحد الأدنى للشراء: {{ formatCurrency(coupon.min_purchase) }}
                        </p>
                        <p v-if="coupon.max_discount > 0">
                          الحد الأقصى للخصم: {{ formatCurrency(coupon.max_discount) }}
                        </p>
                      </div>
                    </div>

                    <div class="coupon-footer">
                      <div class="coupon-expiry">
                        <span class="expiry-label">ينتهي في:</span>
                        <span class="expiry-date">{{ formatDate(coupon.expire_date) }}</span>
                      </div>
                      <div class="coupon-status">
                        <span class="status-badge active">نشط</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Available Coupons -->
              <div v-if="availableCoupons.length > 0" class="coupons-section">
                <h3 class="section-title">قسائم متاحة</h3>
                <div class="coupons-grid">
                  <div v-for="coupon in availableCoupons" :key="coupon.id" class="coupon-card available-coupon">
                    <div class="coupon-header">
                      <div class="coupon-code">{{ coupon.code }}</div>
                      <div class="coupon-type">{{ getCouponTypeName(coupon.coupon_type) }}</div>
                    </div>
                    
                    <div class="coupon-details">
                      <div class="coupon-discount">
                        <span v-if="coupon.coupon_type === 'discount_on_purchase'">
                          خصم {{ coupon.discount }}%
                        </span>
                        <span v-else-if="coupon.coupon_type === 'free_delivery'">
                          توصيل مجاني
                        </span>
                        <span v-else>
                          خصم {{ coupon.discount }} ﷼
                        </span>
                      </div>
                      
                      <div class="coupon-conditions">
                        <p v-if="coupon.min_purchase > 0">
                          الحد الأدنى للشراء: {{ formatCurrency(coupon.min_purchase) }}
                        </p>
                        <p v-if="coupon.max_discount > 0">
                          الحد الأقصى للخصم: {{ formatCurrency(coupon.max_discount) }}
                        </p>
                      </div>
                    </div>

                    <div class="coupon-footer">
                      <div class="coupon-expiry">
                        <span class="expiry-label">ينتهي في:</span>
                        <span class="expiry-date">{{ formatDate(coupon.expire_date) }}</span>
                      </div>
                      <button class="apply-btn" @click="applyCoupon(coupon.code)">
                        تطبيق
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Tracking Tab -->
          <div v-if="activeTab === 'tracking'" class="tab-content">
            <div class="tab-header">
              <h2>تتبع الطلب</h2>
              <p>تتبع حالة طلبك</p>
            </div>

            <!-- Search Form -->
            <div class="tracking-search">
              <form @submit.prevent="trackOrder" class="search-form">
                <div class="search-input-group">
                  <input
                    v-model="trackingForm.searchTerm"
                    type="text"
                    :placeholder="trackingForm.searchType === 'order_id' ? 'أدخل رقم الطلب' : 'أدخل رقم الهاتف'"
                    required
                    :disabled="trackingLoading"
                    class="search-input"
                  />
                  <select v-model="trackingForm.searchType" class="search-type-select" :disabled="trackingLoading">
                    <option value="order_id">رقم الطلب</option>
                    <option value="phone">رقم الهاتف</option>
                  </select>
                  <button type="submit" class="search-btn" :disabled="trackingLoading">
                    <span v-if="trackingLoading">جاري البحث...</span>
                    <span v-else>تتبع</span>
                  </button>
            </div>
              </form>
          </div>

            <!-- Loading State -->
            <div v-if="trackingLoading" class="loading-state">
              <div class="spinner"></div>
              <p>جاري البحث عن الطلب...</p>
        </div>

            <!-- Error State -->
            <div v-else-if="trackingError" class="error-state">
              <div class="error-icon">⚠️</div>
              <h3>لم يتم العثور على الطلب</h3>
              <p>{{ trackingError }}</p>
              <button @click="clearTracking" class="retry-btn">بحث جديد</button>
      </div>

            <!-- Tracking Results -->
            <div v-else-if="trackingResult" class="tracking-result">
              <div class="order-summary">
                <div class="order-header">
                  <h3>طلب رقم: {{ trackingResult.order_id }}</h3>
                  <span class="order-status" :class="trackingResult.status">
                    {{ getOrderStatusName(trackingResult.status) }}
                  </span>
    </div>
                
                <div class="order-details">
                  <div class="detail-row">
                    <span class="label">تاريخ الطلب:</span>
                    <span class="value">{{ formatDate(trackingResult.created_at) }}</span>
                  </div>
                  <div class="detail-row">
                    <span class="label">المبلغ الإجمالي:</span>
                    <span class="value">{{ formatCurrency(trackingResult.order_amount) }}</span>
                  </div>
                  <div class="detail-row" v-if="trackingResult.delivery_man">
                    <span class="label">مندوب التوصيل:</span>
                    <span class="value">{{ trackingResult.delivery_man.name }}</span>
                  </div>
                  <div class="detail-row" v-if="trackingResult.delivery_man?.phone">
                    <span class="label">رقم المندوب:</span>
                    <span class="value">{{ trackingResult.delivery_man.phone }}</span>
                  </div>
                </div>
              </div>

              <!-- Tracking Timeline -->
              <div class="tracking-timeline">
                <h4>مسار التتبع</h4>
                <div class="timeline">
                  <div 
                    v-for="(step, index) in trackingResult.tracking_history" 
                    :key="index"
                    class="timeline-item"
                    :class="{ active: step.is_current, completed: step.is_completed }"
                  >
                    <div class="timeline-marker">
                      <div class="marker-dot"></div>
                    </div>
                    <div class="timeline-content">
                      <h5>{{ step.status_name }}</h5>
                      <p>{{ step.description }}</p>
                      <span class="timeline-date">{{ formatDate(step.created_at) }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Order Items -->
              <div class="order-items" v-if="trackingResult.order_items">
                <h4>عناصر الطلب</h4>
                <div class="items-list">
                  <div v-for="item in trackingResult.order_items" :key="item.id" class="order-item">
                    <div class="item-image">
                      <img 
                        :src="item.product_image || '/images/placeholder.png'" 
                        :alt="item.product_name"
                        @error="($event.target as HTMLImageElement).src = '/images/placeholder.png'"
                      />
                    </div>
                    <div class="item-details">
                      <h5>{{ item.product_name }}</h5>
                      <p>الكمية: {{ item.quantity }}</p>
                      <p>السعر: {{ formatCurrency(item.price) }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Empty State -->
            <div v-else class="empty-state">
              <div class="empty-icon">🚚</div>
              <h3>تتبع طلبك</h3>
              <p>أدخل رقم الطلب أو رقم الهاتف لتتبع حالة طلبك</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Address Form Modal -->
    <teleport to="body">
      <div v-if="showAddressForm" class="modal-overlay" @click.self="closeAddressForm">
        <div class="modal-content">
          <div class="modal-header">
            <h2>{{ editingAddress ? 'تعديل العنوان' : 'إضافة عنوان جديد' }}</h2>
            <button class="close-btn" @click="closeAddressForm">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19 6.41L17.59 5L12 10.59L6.41 5L5 6.41L10.59 12L5 17.59L6.41 19L12 13.41L17.59 19L19 17.59L13.41 12z"/>
              </svg>
            </button>
          </div>

          <form @submit.prevent="saveAddress" class="address-form">
            <div class="form-row">
              <div class="form-group">
                <label for="contact_person_name">اسم المستلم</label>
                <input
                  id="contact_person_name"
                  v-model="addressForm.contact_person_name"
                  type="text"
                  required
                  :disabled="addressFormLoading"
                />
              </div>
              <div class="form-group">
                <label for="phone">رقم الهاتف (مطلوب)</label>
                <input
                  id="phone"
                  v-model="addressForm.phone"
                  type="tel"
                  required
                  placeholder="مثال: +966501234567"
                  :disabled="addressFormLoading"
                />
              </div>
            </div>

            <div class="form-group">
              <label for="address">العنوان التفصيلي</label>
              <textarea
                id="address"
                v-model="addressForm.address"
                required
                rows="3"
                :disabled="addressFormLoading"
              ></textarea>
            </div>

            <div class="form-group">
              <label for="city">المدينة</label>
              <input
                id="city"
                v-model="addressForm.city"
                type="text"
                required
                :disabled="addressFormLoading"
              />
            </div>

            <div v-if="addressFormError" class="error-message">
              {{ addressFormError }}
            </div>

            <div class="form-actions">
              <button type="button" class="action-btn secondary" @click="closeAddressForm" :disabled="addressFormLoading">
                إلغاء
              </button>
              <button type="submit" class="action-btn primary" :disabled="addressFormLoading">
                <span v-if="addressFormLoading">جاري الحفظ...</span>
                <span v-else>{{ editingAddress ? 'تحديث' : 'إضافة' }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </teleport>

    <!-- Support Form Modal -->
    <teleport to="body">
      <div v-if="showSupportForm" class="modal-overlay" @click.self="closeSupportForm">
        <div class="modal-content">
          <div class="modal-header">
            <h2>إنشاء تذكرة دعم جديدة</h2>
            <button class="close-btn" @click="closeSupportForm">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19 6.41L17.59 5L12 10.59L6.41 5L5 6.41L10.59 12L5 17.59L6.41 19L12 13.41L17.59 19L19 17.59L13.41 12z"/>
              </svg>
            </button>
          </div>

          <form @submit.prevent="createSupportTicket" class="support-form">
            <div class="form-group">
              <label for="subject">موضوع التذكرة</label>
              <input
                id="subject"
                v-model="supportForm.subject"
                type="text"
                required
                :disabled="supportFormLoading"
                placeholder="اكتب موضوع التذكرة"
              />
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="type">نوع التذكرة</label>
                <select id="type" v-model="supportForm.type" required>
                  <option value="general">استفسار عام</option>
                  <option value="technical">مشكلة تقنية</option>
                  <option value="order">مشكلة في الطلب</option>
                  <option value="refund">استرداد</option>
                  <option value="other">أخرى</option>
                </select>
              </div>
              <div class="form-group">
                <label for="priority">الأولوية</label>
                <select id="priority" v-model="supportForm.priority" required>
                  <option value="low">منخفض</option>
                  <option value="medium">متوسط</option>
                  <option value="high">عالي</option>
                  <option value="urgent">عاجل</option>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label for="description">وصف المشكلة</label>
              <textarea
                id="description"
                v-model="supportForm.description"
                required
                rows="5"
                :disabled="supportFormLoading"
                placeholder="اكتب وصفاً مفصلاً للمشكلة أو الاستفسار"
              ></textarea>
            </div>

            <div class="form-group">
              <label for="attachments">مرفقات (اختياري)</label>
              <input
                id="attachments"
                type="file"
                multiple
                accept="image/*,.pdf,.doc,.docx"
                @change="supportForm.attachments = ($event.target as HTMLInputElement)?.files"
                :disabled="supportFormLoading"
              />
              <p class="form-help">يمكنك إرفاق الصور أو المستندات (PDF, DOC, DOCX)</p>
            </div>

            <div v-if="supportFormError" class="error-message">
              {{ supportFormError }}
            </div>

            <div class="form-actions">
              <button type="button" class="action-btn secondary" @click="closeSupportForm" :disabled="supportFormLoading">
                إلغاء
              </button>
              <button type="submit" class="action-btn primary" :disabled="supportFormLoading">
                <span v-if="supportFormLoading">جاري الإرسال...</span>
                <span v-else>إرسال التذكرة</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </teleport>

    <!-- Coupon Form Modal -->
    <teleport to="body">
      <div v-if="showCouponForm" class="modal-overlay" @click.self="closeCouponForm">
        <div class="modal-content">
          <div class="modal-header">
            <h2>تطبيق قسيمة</h2>
            <button class="close-btn" @click="closeCouponForm">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19 6.41L17.59 5L12 10.59L6.41 5L5 6.41L10.59 12L5 17.59L6.41 19L12 13.41L17.59 19L19 17.59L13.41 12z"/>
              </svg>
            </button>
          </div>

          <form @submit.prevent="applyCouponCode" class="coupon-form">
            <div class="form-group">
              <label for="coupon_code">كود القسيمة</label>
              <input
                id="coupon_code"
                v-model="couponForm.code"
                type="text"
                required
                :disabled="couponFormLoading"
                placeholder="أدخل كود القسيمة"
              />
            </div>

            <div v-if="couponFormError" class="error-message">
              {{ couponFormError }}
            </div>

            <div v-if="couponFormSuccess" class="success-message">
              {{ couponFormSuccess }}
            </div>

            <div class="form-actions">
              <button type="button" class="action-btn secondary" @click="closeCouponForm" :disabled="couponFormLoading">
                إلغاء
              </button>
              <button type="submit" class="action-btn primary" :disabled="couponFormLoading">
                <span v-if="couponFormLoading">جاري التطبيق...</span>
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

/* Loading State */
.loading-state {
  text-align: center;
  padding: 60px 20px;
  color: #6b7280;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f4f6;
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

.retry-btn {
  background: #6b46c1;
  color: white;
  border: none;
  padding: 12px 24px;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  margin-top: 16px;
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

.shop-btn {
  background: #6b46c1;
  color: white;
  text-decoration: none;
  padding: 12px 24px;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  display: inline-block;
  margin-top: 16px;
}

/* Orders List */
.orders-list {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.order-card {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.order-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 16px;
  padding-bottom: 16px;
  border-bottom: 1px solid #f3f4f6;
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
  padding: 6px 12px;
  border-radius: 20px;
  color: white;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
}

.order-items {
  margin-bottom: 16px;
}

.order-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 0;
  border-bottom: 1px solid #f9fafb;
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
  flex-shrink: 0;
}

.item-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.item-details {
  flex: 1;
}

.item-details h4 {
  font-size: 16px;
  font-weight: 600;
  color: #111827;
  margin: 0 0 4px;
}

.item-details p {
  font-size: 14px;
  color: #6b7280;
  margin: 0 0 4px;
}

.item-price {
  font-weight: 600;
  color: #111827;
}

.order-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 16px;
  border-top: 1px solid #f3f4f6;
}

.order-total {
  font-size: 16px;
  font-weight: 600;
  color: #111827;
}

.order-actions {
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

/* Wishlist Grid */
.wishlist-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
}

.wishlist-item {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  transition: all 0.2s;
  position: relative;
}

.wishlist-item:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  transform: translateY(-2px);
}

.item-image {
  position: relative;
  width: 100%;
  height: 200px;
  border-radius: 8px;
  overflow: hidden;
  background: #f3f4f6;
  margin-bottom: 12px;
}

.item-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.remove-btn {
  position: absolute;
  top: 8px;
  right: 8px;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.7);
  color: white;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.remove-btn:hover {
  background: rgba(239, 68, 68, 0.9);
  transform: scale(1.1);
}

.item-details {
  margin-bottom: 16px;
}

.item-name {
  font-size: 16px;
  font-weight: 600;
  color: #111827;
  margin: 0 0 4px;
  line-height: 1.4;
}

.item-brand {
  font-size: 14px;
  color: #6b7280;
  margin: 0 0 8px;
}

.item-rating {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
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
  color: #fbbf24;
}

.rating-text {
  font-size: 12px;
  color: #6b7280;
}

.item-price {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
  flex-wrap: wrap;
}

.original-price {
  font-size: 14px;
  color: #9ca3af;
  text-decoration: line-through;
}

.current-price {
  font-size: 18px;
  font-weight: 700;
  color: #111827;
}

.discount-badge {
  background: #ef4444;
  color: white;
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 600;
}

.item-stock {
  font-size: 14px;
  font-weight: 500;
}

.item-stock:not(.out-of-stock) {
  color: #10b981;
}

.item-stock.out-of-stock {
  color: #ef4444;
}

.item-actions {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.item-actions .action-btn {
  width: 100%;
  justify-content: center;
  display: flex;
  align-items: center;
  gap: 8px;
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

  .order-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }

  .order-footer {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
  }

  .order-actions {
    width: 100%;
    justify-content: stretch;
  }

  .action-btn {
    flex: 1;
    text-align: center;
  }

  .wishlist-grid {
    grid-template-columns: 1fr;
    gap: 16px;
  }

  .wishlist-item {
    padding: 12px;
  }

  .item-image {
    height: 150px;
  }

  .item-actions {
    flex-direction: row;
  }

  .item-actions .action-btn {
    flex: 1;
  }
}

/* Addresses */
.add-address-btn {
  background: #6b46c1;
  color: white;
  border: none;
  padding: 10px 16px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: background-color 0.2s;
}

.add-address-btn:hover {
  background: #553c9a;
}

.addresses-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 20px;
}

.address-card {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  transition: all 0.2s;
}

.address-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  transform: translateY(-2px);
}

.address-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  padding-bottom: 16px;
  border-bottom: 1px solid #f3f4f6;
}

.type-badge {
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
}

.type-badge.home {
  background: #dbeafe;
  color: #1e40af;
}

.type-badge.work {
  background: #dcfce7;
  color: #166534;
}

.type-badge.other {
  background: #f3f4f6;
  color: #374151;
}

.address-actions {
  display: flex;
  gap: 8px;
}

.action-btn.danger {
  background: #ef4444;
  color: white;
}

.action-btn.danger:hover {
  background: #dc2626;
}

.address-details {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.contact-info h3 {
  font-size: 16px;
  font-weight: 600;
  color: #111827;
  margin: 0 0 4px;
}

.contact-info p {
  font-size: 14px;
  color: #6b7280;
  margin: 0;
}

.address-info p {
  margin: 0 0 4px;
  font-size: 14px;
  color: #374151;
}

.address-text {
  font-weight: 500;
  line-height: 1.5;
}

.city-zip {
  color: #6b7280;
}

.country {
  color: #9ca3af;
  font-size: 13px;
}

/* Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 100;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.modal-content {
  background: #fff;
  border-radius: 16px;
  width: 100%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px 16px;
  border-bottom: 1px solid #e5e7eb;
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
  cursor: pointer;
  padding: 4px;
  border-radius: 6px;
  color: #6b7280;
  display: flex;
  align-items: center;
  justify-content: center;
}

.close-btn:hover {
  background: #f3f4f6;
  color: #374151;
}

.address-form {
  padding: 24px;
}

.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 24px;
  padding-top: 20px;
  border-top: 1px solid #e5e7eb;
}

.form-group textarea {
  padding: 12px 16px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 16px;
  transition: border-color 0.2s;
  resize: vertical;
  min-height: 80px;
  font-family: inherit;
}

.form-group textarea:focus {
  outline: none;
  border-color: #6b46c1;
  box-shadow: 0 0 0 3px rgba(107, 70, 193, 0.1);
}

.form-group select {
  padding: 12px 16px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 16px;
  transition: border-color 0.2s;
  background: white;
  cursor: pointer;
}

.form-group select:focus {
  outline: none;
  border-color: #6b46c1;
  box-shadow: 0 0 0 3px rgba(107, 70, 193, 0.1);
}

.form-group select:disabled {
  background: #f9fafb;
  color: #6b7280;
  cursor: not-allowed;
}

/* Responsive for addresses */
@media (max-width: 768px) {
  .addresses-list {
    grid-template-columns: 1fr;
    gap: 16px;
  }

  .address-card {
    padding: 16px;
  }

  .address-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }

  .address-actions {
    width: 100%;
    justify-content: stretch;
  }

  .address-actions .action-btn {
    flex: 1;
    text-align: center;
  }

  .modal-content {
    margin: 10px;
    max-width: none;
  }

  .modal-header,
  .address-form {
    padding: 16px;
  }

  .form-actions {
    flex-direction: column;
  }

  .form-actions .action-btn {
    width: 100%;
  }
}

/* Support Tickets */
.create-ticket-btn {
  background: #6b46c1;
  color: white;
  border: none;
  padding: 10px 16px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: background-color 0.2s;
}

.create-ticket-btn:hover {
  background: #553c9a;
}

.support-tickets-list {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.ticket-card {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  transition: all 0.2s;
}

.ticket-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  transform: translateY(-2px);
}

.ticket-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 16px;
  padding-bottom: 16px;
  border-bottom: 1px solid #f3f4f6;
}

.ticket-info h3 {
  font-size: 18px;
  font-weight: 600;
  color: #111827;
  margin: 0 0 4px;
}

.ticket-date {
  font-size: 14px;
  color: #6b7280;
  margin: 0;
}

.ticket-status {
  display: flex;
  flex-direction: column;
  gap: 8px;
  align-items: flex-end;
}

.status-badge {
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
}

.status-badge.open {
  background: #dcfce7;
  color: #166534;
}

.status-badge.closed {
  background: #f3f4f6;
  color: #6b7280;
}

.status-badge.pending {
  background: #fef3c7;
  color: #92400e;
}

.priority-badge {
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
}

.priority-badge.low {
  background: #dcfce7;
  color: #166534;
}

.priority-badge.medium {
  background: #fef3c7;
  color: #92400e;
}

.priority-badge.high {
  background: #fecaca;
  color: #991b1b;
}

.priority-badge.urgent {
  background: #fecaca;
  color: #7f1d1d;
}

.ticket-details {
  margin-bottom: 16px;
}

.ticket-type {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
}

.type-label {
  font-size: 14px;
  color: #6b7280;
  font-weight: 500;
}

.type-value {
  font-size: 14px;
  color: #374151;
  font-weight: 600;
}

.ticket-description {
  background: #f9fafb;
  padding: 12px;
  border-radius: 8px;
  border-left: 4px solid #6b46c1;
}

.ticket-description p {
  margin: 0;
  font-size: 14px;
  color: #374151;
  line-height: 1.5;
}

.ticket-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}

.form-help {
  font-size: 12px;
  color: #6b7280;
  margin: 4px 0 0;
}

/* Responsive for support */
@media (max-width: 768px) {
  .ticket-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }

  .ticket-status {
    flex-direction: row;
    align-items: center;
    width: 100%;
    justify-content: space-between;
  }

  .ticket-actions {
    flex-direction: column;
    width: 100%;
  }

  .ticket-actions .action-btn {
    width: 100%;
    text-align: center;
  }
}

/* Coupons */
.apply-coupon-btn {
  background: #6b46c1;
  color: white;
  border: none;
  padding: 10px 16px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: background-color 0.2s;
}

.apply-coupon-btn:hover {
  background: #553c9a;
}

.coupons-content {
  display: flex;
  flex-direction: column;
  gap: 32px;
}

.coupons-section {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.section-title {
  font-size: 20px;
  font-weight: 700;
  color: #111827;
  margin: 0;
  padding-bottom: 12px;
  border-bottom: 2px solid #e5e7eb;
}

.coupons-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 20px;
}

.coupon-card {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  transition: all 0.2s;
  position: relative;
  overflow: hidden;
}

.coupon-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  transform: translateY(-2px);
}

.coupon-card.my-coupon {
  border-left: 4px solid #10b981;
}

.coupon-card.available-coupon {
  border-left: 4px solid #6b46c1;
}

.coupon-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  padding-bottom: 16px;
  border-bottom: 1px solid #f3f4f6;
}

.coupon-code {
  font-size: 18px;
  font-weight: 700;
  color: #111827;
  font-family: 'Courier New', monospace;
  letter-spacing: 1px;
}

.coupon-type {
  background: #f3f4f6;
  color: #374151;
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
}

.coupon-details {
  margin-bottom: 16px;
}

.coupon-discount {
  font-size: 24px;
  font-weight: 700;
  color: #10b981;
  margin-bottom: 12px;
}

.coupon-conditions {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.coupon-conditions p {
  margin: 0;
  font-size: 14px;
  color: #6b7280;
}

.coupon-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 16px;
  border-top: 1px solid #f3f4f6;
}

.coupon-expiry {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.expiry-label {
  font-size: 12px;
  color: #6b7280;
  font-weight: 500;
}

.expiry-date {
  font-size: 14px;
  color: #374151;
  font-weight: 600;
}

.coupon-status .status-badge {
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
}

.status-badge.active {
  background: #dcfce7;
  color: #166534;
}

.apply-btn {
  background: #6b46c1;
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s;
}

.apply-btn:hover {
  background: #553c9a;
}

.success-message {
  background: #dcfce7;
  color: #166534;
  padding: 12px 16px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  margin-bottom: 16px;
}

/* Responsive for coupons */
@media (max-width: 768px) {
  .coupons-grid {
    grid-template-columns: 1fr;
    gap: 16px;
  }

  .coupon-card {
    padding: 16px;
  }

  .coupon-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }

  .coupon-footer {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }

  .apply-btn {
    width: 100%;
    text-align: center;
  }
}

/* Restock Requests */
.restock-requests-list {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.restock-card {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  transition: all 0.2s;
}

.restock-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  transform: translateY(-2px);
}

.restock-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 16px;
  padding-bottom: 16px;
  border-bottom: 1px solid #f3f4f6;
}

.restock-info h3 {
  font-size: 18px;
  font-weight: 600;
  color: #111827;
  margin: 0 0 4px;
}

.restock-date {
  font-size: 14px;
  color: #6b7280;
  margin: 0;
}

.restock-status .status-badge {
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
}

.status-badge.pending {
  background: #fef3c7;
  color: #92400e;
}

.status-badge.approved {
  background: #dcfce7;
  color: #166534;
}

.status-badge.rejected {
  background: #fecaca;
  color: #991b1b;
}

.status-badge.completed {
  background: #dbeafe;
  color: #1e40af;
}

.restock-details {
  margin-bottom: 16px;
}

.product-info {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 16px;
}

.product-image {
  width: 60px;
  height: 60px;
  border-radius: 8px;
  overflow: hidden;
  background: #f3f4f6;
  flex-shrink: 0;
}

.product-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.product-details {
  flex: 1;
}

.product-name {
  font-size: 16px;
  font-weight: 600;
  color: #111827;
  margin: 0 0 4px;
}

.product-sku {
  font-size: 14px;
  color: #6b7280;
  margin: 0;
}

.restock-message {
  background: #f9fafb;
  padding: 12px;
  border-radius: 8px;
  border-left: 4px solid #6b46c1;
}

.restock-message p {
  margin: 0;
  font-size: 14px;
  color: #374151;
  line-height: 1.5;
}

.restock-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}

/* Responsive for restock */
@media (max-width: 768px) {
  .restock-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }

  .product-info {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }

  .product-image {
    width: 80px;
    height: 80px;
  }

  .restock-actions {
    flex-direction: column;
    width: 100%;
  }

  .restock-actions .action-btn {
    width: 100%;
    text-align: center;
  }
}

/* Tracking */
.tracking-search {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 24px;
  margin-bottom: 24px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.search-form {
  width: 100%;
}

.search-input-group {
  display: flex;
  gap: 12px;
  align-items: center;
}

.search-input {
  flex: 1;
  padding: 12px 16px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 16px;
  transition: border-color 0.2s;
}

.search-input:focus {
  outline: none;
  border-color: #6b46c1;
  box-shadow: 0 0 0 3px rgba(107, 70, 193, 0.1);
}

.search-type-select {
  padding: 12px 16px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 16px;
  background: white;
  cursor: pointer;
  min-width: 140px;
}

.search-type-select:focus {
  outline: none;
  border-color: #6b46c1;
  box-shadow: 0 0 0 3px rgba(107, 70, 193, 0.1);
}

.search-btn {
  background: #6b46c1;
  color: white;
  border: none;
  padding: 12px 24px;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s;
  min-width: 100px;
}

.search-btn:hover:not(:disabled) {
  background: #553c9a;
}

.search-btn:disabled {
  background: #9ca3af;
  cursor: not-allowed;
}

.tracking-result {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.order-summary {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.order-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding-bottom: 16px;
  border-bottom: 1px solid #f3f4f6;
}

.order-header h3 {
  font-size: 20px;
  font-weight: 700;
  color: #111827;
  margin: 0;
}

.order-status {
  padding: 8px 16px;
  border-radius: 20px;
  font-size: 14px;
  font-weight: 600;
  text-transform: uppercase;
}

.order-status.pending {
  background: #fef3c7;
  color: #92400e;
}

.order-status.confirmed {
  background: #dbeafe;
  color: #1e40af;
}

.order-status.processing {
  background: #e0e7ff;
  color: #3730a3;
}

.order-status.shipped {
  background: #dcfce7;
  color: #166534;
}

.order-status.delivered {
  background: #dcfce7;
  color: #166534;
}

.order-status.cancelled {
  background: #fecaca;
  color: #991b1b;
}

.order-status.returned {
  background: #f3f4f6;
  color: #6b7280;
}

.order-details {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 16px;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
}

.detail-row .label {
  font-weight: 500;
  color: #6b7280;
}

.detail-row .value {
  font-weight: 600;
  color: #111827;
}

.tracking-timeline {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.tracking-timeline h4 {
  font-size: 18px;
  font-weight: 700;
  color: #111827;
  margin: 0 0 20px;
}

.timeline {
  position: relative;
  padding-left: 20px;
}

.timeline::before {
  content: '';
  position: absolute;
  left: 8px;
  top: 0;
  bottom: 0;
  width: 2px;
  background: #e5e7eb;
}

.timeline-item {
  position: relative;
  margin-bottom: 24px;
  padding-left: 20px;
}

.timeline-item:last-child {
  margin-bottom: 0;
}

.timeline-marker {
  position: absolute;
  left: -12px;
  top: 4px;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: #e5e7eb;
  display: flex;
  align-items: center;
  justify-content: center;
}

.timeline-item.completed .timeline-marker {
  background: #10b981;
}

.timeline-item.active .timeline-marker {
  background: #6b46c1;
  box-shadow: 0 0 0 4px rgba(107, 70, 193, 0.2);
}

.marker-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: white;
}

.timeline-content h5 {
  font-size: 16px;
  font-weight: 600;
  color: #111827;
  margin: 0 0 4px;
}

.timeline-content p {
  font-size: 14px;
  color: #6b7280;
  margin: 0 0 8px;
  line-height: 1.5;
}

.timeline-date {
  font-size: 12px;
  color: #9ca3af;
  font-weight: 500;
}

.order-items {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.order-items h4 {
  font-size: 18px;
  font-weight: 700;
  color: #111827;
  margin: 0 0 20px;
}

.items-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.order-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  background: #f9fafb;
  border-radius: 8px;
}

.item-image {
  width: 60px;
  height: 60px;
  border-radius: 8px;
  overflow: hidden;
  background: #f3f4f6;
  flex-shrink: 0;
}

.item-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.item-details {
  flex: 1;
}

.item-details h5 {
  font-size: 16px;
  font-weight: 600;
  color: #111827;
  margin: 0 0 4px;
}

.item-details p {
  font-size: 14px;
  color: #6b7280;
  margin: 0;
}

/* Responsive for tracking */
@media (max-width: 768px) {
  .search-input-group {
    flex-direction: column;
    gap: 12px;
  }

  .search-input,
  .search-type-select,
  .search-btn {
    width: 100%;
  }

  .order-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }

  .order-details {
    grid-template-columns: 1fr;
  }

  .timeline {
    padding-left: 16px;
  }

  .timeline-item {
    padding-left: 16px;
  }

  .timeline-marker {
    left: -8px;
  }

  .order-item {
    flex-direction: column;
    align-items: flex-start;
    text-align: center;
  }

  .item-image {
    width: 80px;
    height: 80px;
  }
}
</style>

