<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'

// Interfaces
interface Address {
  id: string
  address_type: string
  contact_person_name: string
  contact_person_number: string
  phone?: string
  address: string
  city: string
  zip?: string
  country: string
  latitude?: string
  longitude?: string
  is_billing?: number
}

const { t } = useI18n()
const { $get, $post, $put, $del } = useApi()

// Addresses data
const addresses = ref<Address[]>([])
const loading = ref(false)
const error = ref('')

// Address form
const showAddressForm = ref(false)
const editingAddress = ref<Address | null>(null)
const addressForm = ref({
  address_type: 'home',
  contact_person_name: '',
  contact_person_number: '',
  phone: '', // Required field
  address: '',
  city: '',
  zip: '',
  country: 'Saudi Arabia',
  latitude: '24.7136', // Default Riyadh coordinates
  longitude: '46.6753',
  is_billing: 1 // Required field (1 for yes, 0 for no)
})

const formLoading = ref(false)
const formError = ref('')

// Load addresses
const loadAddresses = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await $get('v1/customer/address/list')
    addresses.value = response || []
  } catch (err: any) {
    console.error('Error loading addresses:', err)
    error.value = err?.data?.message || 'خطأ في تحميل العناوين'
  } finally {
    loading.value = false
  }
}

// Open add address form
const openAddForm = () => {
  editingAddress.value = null
  addressForm.value = {
    address_type: 'home',
    contact_person_name: '',
    contact_person_number: '',
    phone: '', // Required field
    address: '',
    city: '',
    zip: '',
    country: 'Saudi Arabia',
    latitude: '24.7136', // Default Riyadh coordinates
    longitude: '46.6753',
    is_billing: 1 // Required field (1 for yes, 0 for no)
  }
  formError.value = ''
  showAddressForm.value = true
}

// Open edit address form
const openEditForm = (address: Address) => {
  editingAddress.value = address
  addressForm.value = {
    address_type: address.address_type || 'home',
    contact_person_name: address.contact_person_name || '',
    contact_person_number: address.contact_person_number || '',
    phone: address.phone || address.contact_person_number || '', // Use phone or fallback to contact_person_number
    address: address.address || '',
    city: address.city || '',
    zip: address.zip || '',
    country: address.country || 'Saudi Arabia',
    latitude: address.latitude || '24.7136',
    longitude: address.longitude || '46.6753',
    is_billing: address.is_billing !== undefined ? address.is_billing : 1
  }
  formError.value = ''
  showAddressForm.value = true
}

// Close form
const closeForm = () => {
  showAddressForm.value = false
  editingAddress.value = null
  formError.value = ''
}

// Submit address form
const submitAddress = async () => {
  formLoading.value = true
  formError.value = ''

  try {
    // Prepare form data with required fields - ensure all required fields are present
    const formData = {
      address_type: addressForm.value.address_type,
      contact_person_name: addressForm.value.contact_person_name,
      contact_person_number: addressForm.value.contact_person_number,
      address: addressForm.value.address,
      city: addressForm.value.city,
      zip: addressForm.value.zip,
      country: addressForm.value.country,
      // Required fields with fallbacks - ensure they are not empty
      phone: addressForm.value.phone || addressForm.value.contact_person_number || '',
      latitude: addressForm.value.latitude ? String(addressForm.value.latitude) : '24.7136',
      longitude: addressForm.value.longitude ? String(addressForm.value.longitude) : '46.6753',
      is_billing: Number(addressForm.value.is_billing) || 1
    }

    console.log('Sending form data:', formData) // Debug log

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
    
    closeForm()
    await loadAddresses()
  } catch (err: any) {
    console.error('Error saving address:', err)
    formError.value = err?.data?.message || 'خطأ في حفظ العنوان'
  } finally {
    formLoading.value = false
  }
}

// Delete address
const deleteAddress = async (addressId: string) => {
  if (!confirm('هل أنت متأكد من حذف هذا العنوان؟')) return

  try {
    await $del('v1/customer/address', {
      params: {
        id: addressId
      }
    })
    await loadAddresses()
  } catch (err: any) {
    console.error('Error deleting address:', err)
  }
}

// Get address type name
const getAddressTypeName = (type: string) => {
  const types: Record<string, string> = {
    home: 'المنزل',
    work: 'العمل',
    other: 'أخرى'
  }
  return types[type] || type
}

// Load data on mount
onMounted(() => {
  loadAddresses()
})
</script>

<template>
  <div class="addresses-page" dir="rtl">
    <div class="container">
      <div class="page-header">
        <h1>عنواني</h1>
        <p>إدارة عناوينك المحفوظة</p>
      </div>

      <div class="addresses-content">
        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
          <div class="spinner"></div>
          <p>جاري تحميل العناوين...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="error-state">
          <div class="error-icon">⚠️</div>
          <h3>خطأ في التحميل</h3>
          <p>{{ error }}</p>
          <button @click="loadAddresses" class="retry-btn">إعادة المحاولة</button>
        </div>

        <!-- Addresses List -->
        <div v-else class="addresses-section">
          <div class="section-header">
            <h2>العناوين المحفوظة</h2>
            <button @click="openAddForm" class="add-btn">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
              </svg>
              إضافة عنوان جديد
            </button>
          </div>

          <!-- Empty State -->
          <div v-if="addresses.length === 0" class="empty-state">
            <div class="empty-icon">📍</div>
            <h3>لا توجد عناوين محفوظة</h3>
            <p>قم بإضافة عنوان لتسهيل عملية الطلب</p>
            <button @click="openAddForm" class="add-first-btn">إضافة أول عنوان</button>
          </div>

          <!-- Addresses Grid -->
          <div v-else class="addresses-grid">
            <ClientOnly>
              <div v-for="address in addresses" :key="address.id" class="address-card">
                <div class="address-header">
                  <div class="address-type">
                    <span class="type-badge">{{ getAddressTypeName(address.address_type) }}</span>
                  </div>
                  <div class="address-actions">
                    <button @click="openEditForm(address)" class="action-btn edit">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
                      </svg>
                      تعديل
                    </button>
                    <button @click="deleteAddress(address.id)" class="action-btn delete">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                      </svg>
                      حذف
                    </button>
                  </div>
                </div>

                <div class="address-details">
                  <div class="contact-info">
                    <h4>{{ address.contact_person_name }}</h4>
                    <p>{{ address.contact_person_number }}</p>
                  </div>
                  <div class="address-info">
                    <p>{{ address.address }}</p>
                    <p>{{ address.city }}, {{ address.zip }}</p>
                    <p>{{ address.country }}</p>
                  </div>
                </div>
              </div>
            </ClientOnly>
          </div>
        </div>
      </div>
    </div>

    <!-- Address Form Modal -->
    <teleport to="body">
      <div v-if="showAddressForm" class="modal-overlay" @click.self="closeForm">
        <div class="modal-content">
          <div class="modal-header">
            <h2>{{ editingAddress ? 'تعديل العنوان' : 'إضافة عنوان جديد' }}</h2>
            <button @click="closeForm" class="close-btn">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19 6.41L17.59 5L12 10.59L6.41 5L5 6.41L10.59 12L5 17.59L6.41 19L12 13.41L17.59 19L19 17.59L13.41 12z"/>
              </svg>
            </button>
          </div>

          <form @submit.prevent="submitAddress" class="address-form">
            <div class="form-group">
              <label for="address_type">نوع العنوان</label>
              <select id="address_type" v-model="addressForm.address_type" required>
                <option value="home">المنزل</option>
                <option value="work">العمل</option>
                <option value="other">أخرى</option>
              </select>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="contact_person_name">اسم المستلم</label>
                <input
                  id="contact_person_name"
                  v-model="addressForm.contact_person_name"
                  type="text"
                  required
                  :disabled="formLoading"
                />
              </div>
              <div class="form-group">
                <label for="contact_person_number">رقم الهاتف</label>
                <input
                  id="contact_person_number"
                  v-model="addressForm.contact_person_number"
                  type="tel"
                  required
                  :disabled="formLoading"
                />
              </div>
            </div>

            <div class="form-group">
              <label for="phone">رقم الهاتف (مطلوب)</label>
              <input
                id="phone"
                v-model="addressForm.phone"
                type="tel"
                required
                placeholder="مثال: +966501234567"
                :disabled="formLoading"
              />
            </div>

            <div class="form-group">
              <label for="address">العنوان التفصيلي</label>
              <textarea
                id="address"
                v-model="addressForm.address"
                rows="3"
                required
                :disabled="formLoading"
              ></textarea>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="city">المدينة</label>
                <input
                  id="city"
                  v-model="addressForm.city"
                  type="text"
                  required
                  :disabled="formLoading"
                />
              </div>
              <div class="form-group">
                <label for="zip">الرمز البريدي</label>
                <input
                  id="zip"
                  v-model="addressForm.zip"
                  type="text"
                  :disabled="formLoading"
                />
              </div>
            </div>

            <div class="form-group">
              <label for="country">البلد</label>
              <input
                id="country"
                v-model="addressForm.country"
                type="text"
                required
                :disabled="formLoading"
              />
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="latitude">خط العرض</label>
                <input
                  id="latitude"
                  v-model.number="addressForm.latitude"
                  type="number"
                  step="any"
                  placeholder="24.7136"
                  :disabled="formLoading"
                />
              </div>
              <div class="form-group">
                <label for="longitude">خط الطول</label>
                <input
                  id="longitude"
                  v-model.number="addressForm.longitude"
                  type="number"
                  step="any"
                  placeholder="46.6753"
                  :disabled="formLoading"
                />
              </div>
            </div>

            <div class="form-group">
              <label for="is_billing">نوع العنوان</label>
              <select id="is_billing" v-model.number="addressForm.is_billing" :disabled="formLoading">
                <option :value="1">عنوان الفواتير</option>
                <option :value="0">عنوان الشحن فقط</option>
              </select>
            </div>

            <div v-if="formError" class="error-message">
              {{ formError }}
            </div>

            <div class="form-actions">
              <button type="button" @click="closeForm" class="cancel-btn" :disabled="formLoading">
                إلغاء
              </button>
              <button type="submit" class="submit-btn" :disabled="formLoading">
                <span v-if="formLoading">جاري الحفظ...</span>
                <span v-else>{{ editingAddress ? 'تحديث' : 'إضافة' }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </teleport>
  </div>
</template>

<style scoped>
.addresses-page {
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

/* Addresses Section */
.addresses-section {
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

/* Addresses Grid */
.addresses-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 24px;
}

.address-card {
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 20px;
  transition: box-shadow 0.2s;
}

.address-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.address-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.type-badge {
  background: #f3f4f6;
  color: #374151;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}

.address-actions {
  display: flex;
  gap: 8px;
}

.action-btn {
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
  display: flex;
  align-items: center;
  gap: 4px;
}

.action-btn.edit {
  background: #f3f4f6;
  color: #374151;
  border: 1px solid #d1d5db;
}

.action-btn.edit:hover {
  background: #e5e7eb;
}

.action-btn.delete {
  background: #fee;
  color: #dc2626;
  border: 1px solid #fecaca;
}

.action-btn.delete:hover {
  background: #fecaca;
}

.address-details {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.contact-info h4 {
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
  font-size: 14px;
  color: #374151;
  margin: 0 0 4px;
  line-height: 1.5;
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

.address-form {
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
  .addresses-grid {
    grid-template-columns: 1fr;
    gap: 16px;
  }

  .section-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
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