# ميزة المفضلة (Wishlist)

## 📋 الوصف
تم إضافة ميزة المفضلة لصفحة المنتج، تسمح للمستخدمين بإضافة وإزالة المنتجات من قائمة المفضلة.

## ✨ الميزات المضافة

### 1. زر المفضلة التفاعلي
- **حالة ديناميكية**: يظهر الزر بحالة مختلفة حسب ما إذا كان المنتج في المفضلة أم لا
- **تأثيرات بصرية**: تأثيرات hover وتكبير عند الوقوف على الزر
- **حالة التحميل**: عرض spinner أثناء إضافة/إزالة المنتج من المفضلة
- **تعطيل الزر**: منع النقر المتكرر أثناء التحميل

### 2. إدارة الحالة
- **تحقق تلقائي**: فحص حالة المفضلة عند تحميل المنتج
- **تحديث فوري**: تحديث حالة الزر فور إضافة/إزالة المنتج
- **مزامنة مع تسجيل الدخول**: فحص المفضلة عند تسجيل الدخول

### 3. تجربة المستخدم
- **تسجيل الدخول مطلوب**: عرض نافذة تسجيل الدخول إذا لم يكن المستخدم مسجل
- **رسائل تأكيد**: رسائل في الكونسول تؤكد إضافة/إزالة المنتج
- **معالجة الأخطاء**: معالجة أخطاء API بشكل صحيح

## 🎨 التصميم

### الألوان
- **اللون الافتراضي**: رمادي (#6b7280)
- **اللون النشط**: أحمر (#ef4444)
- **الخلفية عند الوقوف**: أحمر شفاف (rgba(239, 68, 68, 0.1))

### التأثيرات
- **Hover Effect**: تغيير اللون والخلفية مع تكبير الزر
- **Active State**: لون أحمر وخلفية شفافة للمنتجات في المفضلة
- **Loading Animation**: دوران دائري أثناء التحميل
- **Scale Animation**: تكبير الزر عند الوقوف عليه

## 🔧 الكود المضافة

### المتغيرات
```typescript
// Wishlist data
const isInWishlist = ref(false)
const wishlistLoading = ref(false)
```

### الدوال
```typescript
// Check if product is in wishlist
async function checkWishlistStatus() {
  if (!auth?.user?.value || !product.value?.id) return
  
  try {
    const response = await $get('v1/customer/wish-list/')
    if (response && Array.isArray(response)) {
      isInWishlist.value = response.some((item: any) => item.product_id === product.value.id)
    }
  } catch (error) {
    console.error('Error checking wishlist status:', error)
  }
}

// Toggle wishlist status
async function toggleWishlist() {
  if (!auth?.user?.value) {
    showLoginModal.value = true
    return
  }
  
  if (!product.value?.id) return
  
  wishlistLoading.value = true
  
  try {
    if (isInWishlist.value) {
      // Remove from wishlist
      await $del('v1/customer/wish-list/remove', {
        params: { product_id: product.value.id }
      })
      isInWishlist.value = false
      console.log('تم إزالة المنتج من المفضلة')
    } else {
      // Add to wishlist
      await $post('v1/customer/wish-list/add', {
        product_id: product.value.id
      })
      isInWishlist.value = true
      console.log('تم إضافة المنتج إلى المفضلة')
    }
  } catch (error: any) {
    console.error('Error toggling wishlist:', error)
  } finally {
    wishlistLoading.value = false
  }
}
```

### HTML Template
```vue
<button 
  class="wishlist-btn" 
  :class="{ active: isInWishlist }"
  @click="toggleWishlist"
  :disabled="wishlistLoading"
>
  <svg v-if="wishlistLoading" width="20" height="20" viewBox="0 0 24 24" class="spinner">
    <!-- Loading spinner -->
  </svg>
  <svg v-else width="20" height="20" viewBox="0 0 24 24" :fill="isInWishlist ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2">
    <!-- Heart icon -->
  </svg>
</button>
```

### CSS
```css
.wishlist-btn{ 
  background:none; 
  border:none; 
  cursor:pointer; 
  color:#6b7280; 
  transition:all 0.3s ease;
  padding: 8px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
}
.wishlist-btn:hover{ 
  color:#ef4444; 
  background: rgba(239, 68, 68, 0.1);
  transform: scale(1.1);
}
.wishlist-btn.active {
  color: #ef4444;
  background: rgba(239, 68, 68, 0.1);
}
.wishlist-btn:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}
```

## 🚀 الاستخدام

### للمستخدمين المسجلين
1. **إضافة للمفضلة**: انقر على زر القلب لإضافة المنتج للمفضلة
2. **إزالة من المفضلة**: انقر على زر القلب مرة أخرى لإزالة المنتج
3. **تأكيد الحالة**: سيظهر الزر باللون الأحمر إذا كان المنتج في المفضلة

### للمستخدمين غير المسجلين
1. **النقر على الزر**: سيتم فتح نافذة تسجيل الدخول
2. **تسجيل الدخول**: بعد تسجيل الدخول، يمكن استخدام المفضلة
3. **تحديث تلقائي**: سيتم فحص حالة المفضلة تلقائياً

## 🔄 التكامل مع النظام

### API Endpoints
- **GET** `v1/customer/wish-list/` - جلب قائمة المفضلة
- **POST** `v1/customer/wish-list/add` - إضافة منتج للمفضلة
- **DELETE** `v1/customer/wish-list/remove` - إزالة منتج من المفضلة

### Authentication
- يتطلب تسجيل دخول المستخدم
- يستخدم `useAuth()` لإدارة حالة المستخدم
- يعرض نافذة تسجيل الدخول عند الحاجة

### State Management
- **isInWishlist**: حالة المنتج في المفضلة
- **wishlistLoading**: حالة التحميل
- **تحديث تلقائي**: عند تحميل المنتج وتسجيل الدخول

## ✅ الميزات المدعومة
- ✅ إضافة/إزالة المنتجات من المفضلة
- ✅ عرض حالة المفضلة بصرياً
- ✅ تأثيرات بصرية ناعمة
- ✅ معالجة حالات التحميل
- ✅ تكامل مع نظام المصادقة
- ✅ معالجة الأخطاء
- ✅ تحديث تلقائي للحالة
- ✅ تجربة مستخدم محسنة
