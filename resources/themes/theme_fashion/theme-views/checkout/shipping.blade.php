@extends('theme-views.layouts.app')

@section('title', translate('shopping_details').' | '.$web_config['company_name'].' '.translate('ecommerce'))

@section('content')

<section class="breadcrumb-section pt-20px">
    <div class="container">
        <div class="section-title mb-4">
            <div
                class="d-flex flex-wrap justify-content-between row-gap-3 column-gap-2 align-items-center search-page-title">
                <ul class="breadcrumb">
                    <li>
                        <a href="{{route('home')}}">{{translate('home')}}</a>
                    </li>
                    <li>
                        <a href="{{route('shop-cart')}}">{{translate('cart')}}</a>
                    </li>
                    <li>
                        <a href="{{url()->current()}}" class="text-base custom-text-link">{{translate('checkout')}}</a>
                    </li>
                </ul>
                <div class="ms-auto ms-md-0">
                    <a href="{{route('shop-cart')}}" class="text-base custom-text-link">{{ translate('check_All_CartList') }}</a>
                </div>
            </div>
        </div>
    </div>
</section>


<section class="checkout-section pt-4 section-gap">
    <div class="container">
        <h3 class="mb-3 mb-lg-4 d-flex justify-content-center justify-content-sm-start">{{translate('checkout')}}</h3>
        <div class="row g-4">
            <div class="col-md-8 col-sm-12">
                <ul class="checkout-flow">
                    <li class="checkout-flow-item active">
                        <a href="javascript:">
                            <span class="serial">{{ translate('1') }}</span>
                            <span class="icon">
                                <i class="bi bi-check"></i>
                            </span>
                            <span class="text thisIsALinkElement" data-linkpath="{{route('shop-cart')}}">{{translate('cart')}}</span>
                        </a>
                    </li>
                    <li class="line"></li>
                    <li class="checkout-flow-item active current">
                        <a href="javascript:">
                            <span class="serial">{{ translate('2') }}</span>
                            <span class="icon">
                                <i class="bi bi-check"></i>
                            </span>
                            <span class="text text-capitalize">{{translate('shipping_details')}}</span>
                        </a>
                    </li>
                    <li class="line"></li>
                    <li class="checkout-flow-item">
                        <a href="javascript:">
                            <span class="serial">{{ translate('3') }}</span>
                            <span class="icon">
                                <i class="bi bi-check"></i>
                            </span>
                            <span class="text">{{translate('payment')}}</span>
                        </a>
                    </li>
                </ul>
                <input type="hidden" id="physical_product" name="physical_product" value="{{ $physical_product_view ? 'yes':'no'}}">
                <input type="hidden" id="billing_input_enable" name="billing_input_enable" value="{{ $billing_input_by_customer }}">
                <div class="delivery-information">
                    <h4 class="font-bold letter-spacing-0 title text-capitalize mb-20px">
                        {{ translate('delivery_information_details') }}
                    </h4>
                </div>


                    <!-- نموذج مخفي لجمع بيانات الشحن -->
                    <form method="post" id="address-form" style="display: none;">
                        <input type="hidden" name="contact_person_name" id="hidden_contact_person_name">
                        <input type="hidden" name="phone" id="hidden_phone">
                        <input type="hidden" name="email" id="hidden_email">
                        <input type="hidden" name="country" id="hidden_country">
                        <input type="hidden" name="city" id="hidden_city">
                        <input type="hidden" name="zip" id="hidden_zip">
                        <input type="hidden" name="address_type" id="hidden_address_type">
                        <input type="hidden" name="address" id="hidden_address">
                        <input type="hidden" name="shipping_method_id" id="hidden_shipping_method_id" value="0">
                        <input type="hidden" name="save_address" id="hidden_save_address">
                        <input type="hidden" name="latitude" id="hidden_latitude">
                        <input type="hidden" name="longitude" id="hidden_longitude">
                    </form>

                    <!-- النموذج الأساسي والظاهر للفوترة -->
                    <form method="post" id="billing-address-form">
                        @if(!Auth::guard('customer')->check() && $web_config['guest_checkout_status'])
                            <!-- قسم إنشاء حساب للضيوف -->
                            <div class="card __card mt-3">
                                <div class="card-body">
                                    <div class="d-flex align-items-center flex-wrap justify-content-between gap-3">
                                        <div class="min-h-45 d-flex gap-2 align-items-center cursor-pointer user-select-none">
                                            <input type="checkbox" id="is_check_create_account" name="is_check_create_account" class="w-auto">
                                            <label class="form-check-label fw-bold fs-13 mb-0" for="is_check_create_account">
                                                {{ translate('Create_an_account_with_the_above_info') }}
                                            </label>
                                        </div>
                                        <div class="is_check_create_account_password_group d--none">
                                            <div class="d-flex gap-3 flex-wrap flex-sm-nowrap">
                                                <div class="position-relative">
                                                    <input type="password" class="form-control" name="customer_password" id="customer_password" placeholder="{{translate('password')}}" required="">
                                                    <div class="js-password-toggle"><i class="bi bi-eye-slash-fill"></i></div>
                                                </div>
                                                <div class="position-relative">
                                                    <input type="password" class="form-control" name="customer_confirm_password" id="customer_confirm_password" placeholder="{{translate('confirm_password')}}" required="">
                                                    <div class="js-password-toggle"><i class="bi bi-eye-slash-fill"></i></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if($billing_input_by_customer)
                            <!-- قسم عنوان الفوترة -->
                            <div class="delivery-information mt-32px {{ $billing_input_by_customer ? '':'d-none' }}">
                                <div id="billing-address" class="mt-20px">
                                    <div class="delivery-information mt-32px" id="hide_billing_address">
                                        <div class="d-flex flex-wrap row-gap-3 column-gap-4 mb-20px align-items-end">
                                            <div class="font-bold letter-spacing-0 title m-0 text-capitalize">{{translate('Billing_Address')}}</div>
                                            @if(auth('customer')->check())
                                                <div class="ms-auto text-base" type="button" data-bs-target="#billing_addresses" data-bs-toggle="modal">
                                                    {{translate('select_from_saved')}}
                                                </div>
                                            @endif
                                            @if(getWebConfig('map_api_status') ==1 )
                                                <div class="text-base" type="button" data-bs-target="#set_billing_addresses" data-bs-toggle="modal">
                                                    {{translate('set_from_map')}} <i class="bi bi-geo-alt-fill"></i>
                                                </div>
                                                <div class="modal fade" id="set_billing_addresses">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title text-capitalize">{{translate('set_delivery_address')}}</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="modal-body">
                                                                <div class="product-quickview">
                                                                    <input id="pac-input-billing" class="controls rounded __map-input mt-1" title="{{translate('search_your_location_here')}}" type="text" placeholder="{{translate('search_here')}}"/>
                                                                    <div class="dark-support rounded w-100 __h-14rem" id="billing_location_map_canvas"></div>
                                                                    <input type="hidden" id="billing_latitude" name="billing_latitude" class="form-control d-inline"
                                                                        value="{{$default_location?$default_location['lat']:0}}" required readonly>
                                                                    <input type="hidden" name="billing_longitude" class="form-control"
                                                                        id="billing_longitude" value="{{$default_location?$default_location['lng']:0}}" required >
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer p-3">
                                                            <button type="button" class="btn btn-base secondary-color"
                                                                data-bs-dismiss="modal">{{translate('close')}}</button>
                                                            <button type="button" data-bs-dismiss="modal" class="btn rounded btn-base">{{translate('Update')}}</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            @endif
                                        </div>
                                        <div class="row g-4">
                                            <div class="col-12 col-sm-@if(auth('customer')->check()) '6' @else '12' @endif">
                                                <label for="billing_contact_person_name" class="form-label">{{translate('contact_Person_Name')}}</label>
                                                <input type="text" placeholder="{{translate('contact_Person_Name')}}" id="billing_contact_person_name" name="billing_contact_person_name" class="form-control" {{$shipping_addresses->count()==0?'required':''}}>
                                            </div>
                                            @if(!auth('customer')->check())
                                                <div class="col-sm-6 col-12">
                                                    <label for="billing_contact_email" class="form-label">{{ translate('email') }}</label>
                                                    <input type="text" name="billing_contact_email" id="billing_contact_email" class="form-control" placeholder="{{ translate('email') }}" required>
                                                </div>
                                            @endif
                                            <div class="col-sm-6 col-12" style="display: none;">
                                                <!-- Hidden billing country with Saudi Arabia display -->
                                                <input type="hidden" name="billing_country" id="billing_country" value="Saudi Arabia">
                                            </div>
                                            @if(auth('customer')->check())
                                                <!-- Use logged-in user's phone number -->
                                                <input type="hidden" name="billing_phone" id="billing_phone_hidden" value="{{ auth('customer')->user()->phone }}">
                                            @else
                                                <!-- For guests, set empty phone (optional) -->
                                                <input type="hidden" name="billing_phone" id="billing_phone_hidden" value="">
                                            @endif
                                            <div class="col-sm-6 col-md-3 col-12">
                                                <label for="billing_city" class="form-label">{{translate('city')}}</label>
                                                <div class="city-select-wrapper position-relative">
                                                    <input type="text" 
                                                           id="billing_city_search" 
                                                           class="form-control" 
                                                           placeholder="ابحث عن المدينة..." 
                                                           autocomplete="off">
                                                    <div class="city-dropdown" id="city_dropdown" style="display: none;">
                                                        <!-- المدن الرئيسية -->
                                                        <div class="city-option" data-value="الرياض">الرياض</div>
                                                        <div class="city-option" data-value="جدة">جدة</div>
                                                        <div class="city-option" data-value="مكة المكرمة">مكة المكرمة</div>
                                                        <div class="city-option" data-value="المدينة المنورة">المدينة المنورة</div>
                                                        <div class="city-option" data-value="الدمام">الدمام</div>
                                                        <div class="city-option" data-value="الخبر">الخبر</div>
                                                        <div class="city-option" data-value="الظهران">الظهران</div>
                                                        <div class="city-option" data-value="تبوك">تبوك</div>
                                                        <div class="city-option" data-value="بريدة">بريدة</div>
                                                        <div class="city-option" data-value="خميس مشيط">خميس مشيط</div>
                                                        <div class="city-option" data-value="الهفوف">الهفوف</div>
                                                        <div class="city-option" data-value="حفر الباطن">حفر الباطن</div>
                                                        <div class="city-option" data-value="الطائف">الطائف</div>
                                                        <div class="city-option" data-value="نجران">نجران</div>
                                                        <div class="city-option" data-value="جازان">جازان</div>
                                                        <div class="city-option" data-value="ينبع">ينبع</div>
                                                        <div class="city-option" data-value="الجبيل">الجبيل</div>
                                                        <div class="city-option" data-value="أبها">أبها</div>
                                                        <div class="city-option" data-value="عرعر">عرعر</div>
                                                        <div class="city-option" data-value="سكاكا">سكاكا</div>
                                                        <div class="city-option" data-value="القريات">القريات</div>
                                                        <div class="city-option" data-value="الباحة">الباحة</div>
                                                        <div class="city-option" data-value="القطيف">القطيف</div>
                                                        <div class="city-option" data-value="رفحاء">رفحاء</div>
                                                        <div class="city-option" data-value="وادي الدواسر">وادي الدواسر</div>
                                                        
                                                        <!-- مدن منطقة الرياض -->
                                                        <div class="city-option" data-value="الخرج">الخرج</div>
                                                        <div class="city-option" data-value="الدلم">الدلم</div>
                                                        <div class="city-option" data-value="المزاحمية">المزاحمية</div>
                                                        <div class="city-option" data-value="رماح">رماح</div>
                                                        <div class="city-option" data-value="ثادق">ثادق</div>
                                                        <div class="city-option" data-value="شقراء">شقراء</div>
                                                        <div class="city-option" data-value="عفيف">عفيف</div>
                                                        <div class="city-option" data-value="القويعية">القويعية</div>
                                                        <div class="city-option" data-value="السليل">السليل</div>
                                                        <div class="city-option" data-value="ديرية">ديرية</div>
                                                        <div class="city-option" data-value="حوطة بني تميم">حوطة بني تميم</div>
                                                        <div class="city-option" data-value="الأفلاج">الأفلاج</div>
                                                        <div class="city-option" data-value="الزلفي">الزلفي</div>
                                                        <div class="city-option" data-value="المجمعة">المجمعة</div>
                                                        <div class="city-option" data-value="الغاط">الغاط</div>
                                                        <div class="city-option" data-value="حريملاء">حريملاء</div>
                                                        
                                                        <!-- مدن المنطقة الشرقية -->
                                                        <div class="city-option" data-value="الأحساء">الأحساء</div>
                                                        <div class="city-option" data-value="بقيق">بقيق</div>
                                                        <div class="city-option" data-value="رأس تنورة">رأس تنورة</div>
                                                        <div class="city-option" data-value="النعيرية">النعيرية</div>
                                                        <div class="city-option" data-value="العديد">العديد</div>
                                                        <div class="city-option" data-value="تاروت">تاروت</div>
                                                        <div class="city-option" data-value="صفوى">صفوى</div>
                                                        <div class="city-option" data-value="سيهات">سيهات</div>
                                                        <div class="city-option" data-value="الخفجي">الخفجي</div>
                                                        
                                                        <!-- مدن منطقة مكة المكرمة -->
                                                        <div class="city-option" data-value="رابغ">رابغ</div>
                                                        <div class="city-option" data-value="خليص">خليص</div>
                                                        <div class="city-option" data-value="الكامل">الكامل</div>
                                                        <div class="city-option" data-value="الليث">الليث</div>
                                                        <div class="city-option" data-value="القنفذة">القنفذة</div>
                                                        <div class="city-option" data-value="أضم">أضم</div>
                                                        <div class="city-option" data-value="المويه">المويه</div>
                                                        <div class="city-option" data-value="تربة">تربة</div>
                                                        <div class="city-option" data-value="رنية">رنية</div>
                                                        <div class="city-option" data-value="الخرمة">الخرمة</div>
                                                        
                                                        <!-- مدن منطقة المدينة المنورة -->
                                                        <div class="city-option" data-value="العلا">العلا</div>
                                                        <div class="city-option" data-value="بدر">بدر</div>
                                                        <div class="city-option" data-value="خيبر">خيبر</div>
                                                        <div class="city-option" data-value="المهد">المهد</div>
                                                        <div class="city-option" data-value="وادي الفرع">وادي الفرع</div>
                                                        
                                                        <!-- مدن منطقة القصيم -->
                                                        <div class="city-option" data-value="عنيزة">عنيزة</div>
                                                        <div class="city-option" data-value="الرس">الرس</div>
                                                        <div class="city-option" data-value="المذنب">المذنب</div>
                                                        <div class="city-option" data-value="البكيرية">البكيرية</div>
                                                        <div class="city-option" data-value="الأسياح">الأسياح</div>
                                                        <div class="city-option" data-value="النبهانية">النبهانية</div>
                                                        <div class="city-option" data-value="عيون الجواء">عيون الجواء</div>
                                                        <div class="city-option" data-value="الشماسية">الشماسية</div>
                                                        <div class="city-option" data-value="رياض الخبراء">رياض الخبراء</div>
                                                        <div class="city-option" data-value="عقلة الصقور">عقلة الصقور</div>
                                                        <div class="city-option" data-value="البدائع">البدائع</div>
                                                        <div class="city-option" data-value="ضرية">ضرية</div>
                                                        
                                                        <!-- مدن منطقة عسير -->
                                                        <div class="city-option" data-value="بيشة">بيشة</div>
                                                        <div class="city-option" data-value="النماص">النماص</div>
                                                        <div class="city-option" data-value="ظهران الجنوب">ظهران الجنوب</div>
                                                        <div class="city-option" data-value="سراة عبيدة">سراة عبيدة</div>
                                                        <div class="city-option" data-value="أحد رفيدة">أحد رفيدة</div>
                                                        <div class="city-option" data-value="رجال ألمع">رجال ألمع</div>
                                                        <div class="city-option" data-value="محايل عسير">محايل عسير</div>
                                                        <div class="city-option" data-value="تنومة">تنومة</div>
                                                        <div class="city-option" data-value="طريب">طريب</div>
                                                        <div class="city-option" data-value="المجاردة">المجاردة</div>
                                                        <div class="city-option" data-value="بارق">بارق</div>
                                                        <div class="city-option" data-value="بلقرن">بلقرن</div>
                                                        <div class="city-option" data-value="تثليث">تثليث</div>
                                                        
                                                        <!-- مدن منطقة تبوك -->
                                                        <div class="city-option" data-value="الوجه">الوجه</div>
                                                        <div class="city-option" data-value="ضباء">ضباء</div>
                                                        <div class="city-option" data-value="تيماء">تيماء</div>
                                                        <div class="city-option" data-value="أملج">أملج</div>
                                                        <div class="city-option" data-value="حقل">حقل</div>
                                                        <div class="city-option" data-value="البدع">البدع</div>
                                                        
                                                        <!-- مدن منطقة حائل -->
                                                        <div class="city-option" data-value="حائل">حائل</div>
                                                        <div class="city-option" data-value="بقعاء">بقعاء</div>
                                                        <div class="city-option" data-value="الغزالة">الغزالة</div>
                                                        <div class="city-option" data-value="الشنان">الشنان</div>
                                                        <div class="city-option" data-value="موقق">موقق</div>
                                                        <div class="city-option" data-value="الحائط">الحائط</div>
                                                        <div class="city-option" data-value="سميراء">سميراء</div>
                                                        
                                                        <!-- مدن الحدود الشمالية -->
                                                        <div class="city-option" data-value="طريف">طريف</div>
                                                        
                                                        <!-- مدن منطقة الجوف -->
                                                        <div class="city-option" data-value="دومة الجندل">دومة الجندل</div>
                                                        <div class="city-option" data-value="طبرجل">طبرجل</div>
                                                        
                                                        <!-- مدن منطقة نجران -->
                                                        <div class="city-option" data-value="شرورة">شرورة</div>
                                                        <div class="city-option" data-value="حبونا">حبونا</div>
                                                        <div class="city-option" data-value="بدر الجنوب">بدر الجنوب</div>
                                                        <div class="city-option" data-value="ثار">ثار</div>
                                                        <div class="city-option" data-value="خباش">خباش</div>
                                                        <div class="city-option" data-value="يدمة">يدمة</div>
                                                        
                                                        <!-- مدن منطقة جازان -->
                                                        <div class="city-option" data-value="صبيا">صبيا</div>
                                                        <div class="city-option" data-value="أبو عريش">أبو عريش</div>
                                                        <div class="city-option" data-value="صامطة">صامطة</div>
                                                        <div class="city-option" data-value="بيش">بيش</div>
                                                        <div class="city-option" data-value="فرسان">فرسان</div>
                                                        <div class="city-option" data-value="الدائر">الدائر</div>
                                                        <div class="city-option" data-value="العيدابي">العيدابي</div>
                                                        <div class="city-option" data-value="الطوال">الطوال</div>
                                                        <div class="city-option" data-value="الحرث">الحرث</div>
                                                        <div class="city-option" data-value="ضمد">ضمد</div>
                                                        <div class="city-option" data-value="الريث">الريث</div>
                                                        <div class="city-option" data-value="أحد المسارحة">أحد المسارحة</div>
                                                        <div class="city-option" data-value="العارضة">العارضة</div>
                                                        <div class="city-option" data-value="الدرب">الدرب</div>
                                                        <div class="city-option" data-value="هروب">هروب</div>
                                                        
                                                        <!-- مدن الباحة -->
                                                        <div class="city-option" data-value="بلجرشي">بلجرشي</div>
                                                        <div class="city-option" data-value="المندق">المندق</div>
                                                        <div class="city-option" data-value="العقيق">العقيق</div>
                                                        <div class="city-option" data-value="قلوة">قلوة</div>
                                                        <div class="city-option" data-value="المخواة">المخواة</div>
                                                        <div class="city-option" data-value="غامد الزناد">غامد الزناد</div>
                                                        <div class="city-option" data-value="بني حسن">بني حسن</div>
                                                        <div class="city-option" data-value="القرى">القرى</div>
                                                        <div class="city-option" data-value="الحجرة">الحجرة</div>
                                                    </div>
                                                    <input type="hidden" name="billing_city" id="billing_city" value="جدة" {{$shipping_addresses->count()==0?'required':''}}>
                                                </div>
                                            </div>
                                            <div class="col-sm-6 col-md-3 col-12" style="display: none;">
                                                <!-- Hidden billing zip with permanent value -->
                                                <input type="hidden" name="billing_zip" id="billing_zip" value="permanent">
                                            </div>
                                            <!-- Hidden billing address type field with permanent value -->
                                            <input type="hidden" name="billing_address_type" id="billing_address_type" value="permanent">
                                            <div class="col-sm-12 col-12">
                                                <label for="billing_address" class="form-label">{{ translate('Address') }}</label>
                                                <div class="form-control p-0 rounded d-flex align-items-center force-border-input">
                                                    <input type="text" id="billing_address" name="billing_address" placeholder="{{ translate('Address') }}" class="border-0 bg-transparent p-3 outline-custom-remove form-control" autocomplete="off">
                                                    <div class="border-start p-3" data-bs-toggle="modal" data-bs-target="#set_billing_addresses">
                                                        <i class="bi bi-compass-fill cursor-pointer"></i>
                                                    </div>
                                                </div>
                                            </div>
                                            <input type="hidden" name="billing_method_id" id="billing_method_id" value="0">
                                            @if(auth('customer')->check())
                                                <div class="col-sm-12 col-12" >
                                                    <label class="form-check m-0" id="save-billing-address-label">
                                                        <input type="checkbox" name="save_address_billing" id="save_address_billing" class="form-check-input dark-form-check-input">
                                                        <span class="form-check-label">{{translate('save_this_Address')}}</span>
                                                    </label>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                            @if(!Auth::guard('customer')->check() && $web_config['guest_checkout_status'] && !$physical_product_view)
                                <div class="card __card mt-3">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center flex-wrap justify-content-between gap-3">
                                            <div class="min-h-45 d-flex gap-2 align-items-center cursor-pointer user-select-none">
                                                <input type="checkbox" id="is_check_create_account" name="is_check_create_account" class="w-auto">
                                                <label class="form-check-label fw-bold fs-13 mb-0" for="is_check_create_account">
                                                    {{ translate('Create_an_account_with_the_above_info') }}
                                                </label>
                                            </div>
                                            <div class="is_check_create_account_password_group d--none">
                                                <div class="d-flex gap-3 flex-wrap flex-sm-nowrap">
                                                    <div class="position-relative">
                                                        <input type="password" class="form-control" name="customer_password" id="customer_password" placeholder="{{translate('password')}}" required="">
                                                        <div class="js-password-toggle"><i class="bi bi-eye-slash-fill"></i></div>
                                                    </div>
                                                    <div class="position-relative">
                                                        <input type="password" class="form-control" name="customer_confirm_password" id="customer_confirm_password" placeholder="{{translate('confirm_password')}}" required="">
                                                        <div class="js-password-toggle"><i class="bi bi-eye-slash-fill"></i></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                        @endif
                    </form>

            </div>
            <div class="col-md-4 shipping col-sm-12">
                @include('theme-views.partials._order-summery')
            </div>

        </div>
    </div>
</section>

<div class="modal fade" id="shipping_addresses">
    <div class="modal-dialog modal-dialog-centered ">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ translate('Saved_Addresses') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mapouter">
                    <div class="row ">
                        @if (auth('customer')->check() && $shipping_addresses->count()>0)
                            @foreach($shipping_addresses as $key=>$address)
                                <div class="col-md-12">
                                    <div class="address-card mb-20px ">
                                        <div class="address-card-header bg-transparent d-flex justify-content-between align-items-center">
                                            <label class="d-flex align-items-start gap-3 cursor-pointer mb-0 w-100">
                                                <input class="s-16px form-check-input mt-1" type="radio" name="shipping_method_id" value="{{$address['id']}}" {{$key==0?'checked':''}}>
                                                <div class="w-0 flex-grow-1">
                                                    <div class="d-flex justify-content-between align-items-center column-gap-4">
                                                        <h6 class="text-capitalize">{{$address['address_type']}}</h6>
                                                        <a href="{{route('address-edit',$address->id)}}" >
                                                            <img loading="lazy" src="{{ theme_asset('assets/img/icons/edit.png') }}" alt="{{ translate('edit') }}">
                                                        </a>
                                                    </div>
                                                    <div class="address-card-body pb-0 px-0 text-start">
                                                        <ul>
                                                            <li>
                                                                <span class="form--label w-70px">{{ translate('name') }}</span>
                                                                <span class="info ps-2 shipping-contact-person">{{$address['contact_person_name']}}</span>
                                                            </li>
                                                            <li>
                                                                <span class="form--label w-70px">{{ translate('phone') }}</span>
                                                                <span class="info ps-2 shipping-contact-phone">{{$address['phone']}}</span>
                                                            </li>
                                                            <li>
                                                                <span class="form--label w-70px">{{ translate('address') }}</span>
                                                                <span class="info ps-2 shipping-contact-address">{{$address['address']}}</span>
                                                            </li>
                                                            <!-- Hidden data spans -->
                                                            <span class="shipping-contact-address d-none">{{ $address['address'] }}</span>
                                                            <span class="shipping-contact-city d-none">{{ $address['city'] }}</span>
                                                            <span class="shipping-contact-zip d-none">{{ $address['zip'] }}</span>
                                                            <span class="shipping-contact-country d-none">{{ $address['country'] }}</span>
                                                            <span class="shipping-contact-address_type d-none">{{ $address['address_type'] }}</span>
                                                            <!-- Data attributes for JavaScript -->
                                                            <input type="hidden" class="selected_{{$address['id']}}" value="{{json_encode([
                                                                'contact_person_name' => $address['contact_person_name'],
                                                                'phone' => $address['phone'],
                                                                'address' => $address['address'],
                                                                'city' => $address['city'],
                                                                'zip' => $address['zip'],
                                                                'country' => $address['country'],
                                                                'address_type' => $address['address_type']
                                                            ])}}">
                                                        </ul>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center">
                                <img loading="lazy" src="{{ theme_asset('assets/img/icons/address.svg') }}" alt="{{ translate('address') }}" class="w-25">
                                <h5 class="my-3 pt-1 text-muted">
                                    {{ translate('no_address_is_saved') }}!
                                </h5>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            @if (auth('customer')->check() && $shipping_addresses->count() > 0)
                <div class="modal-footer p-3">
                    <button type="button" class="btn rounded btn-reset text-title"
                        data-bs-dismiss="modal">{{translate('close')}}</button>
                    <button type="button" data-bs-dismiss="modal" class="btn rounded btn-base" id="select-shipping-address">
                        {{translate('select')}}
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>


<div class="modal fade" id="billing_addresses">
    <div class="modal-dialog modal-dialog-centered ">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-capitalize">{{translate('saved_addresses')}}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mapouter">
                    <div class="row ">
                        @if (auth('customer')->check() && $billing_addresses->count()>0)
                         @foreach($billing_addresses as $key=>$address)
                            <div class="col-md-12">
                                <div class="address-card mb-20px ">
                                    <div class="address-card-header bg-transparent d-flex justify-content-between align-items-center">
                                        <label class="d-flex align-items-start gap-3 cursor-pointer mb-0 w-100">
                                            <input class="s-16px form-check-input mt-1" type="radio" name="billing_method_id" {{$key==0?'checked':''}} value="{{$address['id']}}" >
                                            <div class="w-0 flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-center column-gap-4">
                                                    <h6 class="text-capitalize">{{$address['address_type']}}</h6>
                                                    <a href="{{route('address-edit',$address->id)}}" >
                                                        <img loading="lazy" src="{{ theme_asset('assets/img/icons/edit.png') }}" alt="{{ translate('edit') }}">
                                                    </a>
                                                </div>
                                                <div class="address-card-body pb-0 px-0 text-start">
                                                    <ul>
                                                        <li>
                                                            <span class="form--label w-70px">{{ translate('name') }}</span>
                                                            <span class="info ps-2 billing-contact-name">{{$address['contact_person_name']}}</span>
                                                        </li>
                                                        <li>
                                                            <span class="form--label w-70px">{{ translate('phone') }}</span>
                                                            <span class="info ps-2 billing-contact-phone">{{$address['phone']}}</span>
                                                        </li>
                                                        <li>
                                                            <span class="form--label w-70px">{{ translate('address') }}</span>
                                                            <span class="info ps-2 billing-contact-address">{{$address['address']}}</span>
                                                        </li>
                                                        <!-- Hidden data spans -->
                                                        <span class="billing-contact-city d-none">{{ $address['city'] }}</span>
                                                        <span class="billing-contact-zip d-none">{{ $address['zip'] }}</span>
                                                        <span class="billing-contact-country d-none">{{ $address['country'] }}</span>
                                                        <span class="billing-contact-address_type d-none">{{ $address['address_type'] }}</span>
                                                        <!-- Data attributes for JavaScript -->
                                                        <input type="hidden" class="selected_{{$address['id']}}" value="{{json_encode([
                                                            'contact_person_name' => $address['contact_person_name'],
                                                            'phone' => $address['phone'],
                                                            'address' => $address['address'],
                                                            'city' => $address['city'],
                                                            'zip' => $address['zip'],
                                                            'country' => $address['country'],
                                                            'address_type' => $address['address_type']
                                                        ])}}">
                                                    </ul>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        @else
                            <div class="text-center">
                                <img loading="lazy" src="{{ theme_asset('assets/img/icons/address.svg') }}" alt="{{ translate('address') }}" class="w-25">
                                <h5 class="my-3 pt-1 text-muted">
                                        {{translate('no_address_is_saved')}}!
                                </h5>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            @if (auth('customer')->check() && $billing_addresses->count()>0)
                <div class="modal-footer p-3">
                    <button type="button" class="btn rounded btn-reset text-title"
                        data-bs-dismiss="modal">{{translate('close')}}</button>
                    <button type="button" data-bs-dismiss="modal" class="btn rounded btn-base" id="select-billing-address">
                        {{ translate('select') }}
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>


<span id="shippingaddress-storage"
    data-latitude="{{ $default_location ? ($default_location['lat'] ?? 0) : '-33.8688' }}"
    data-longitude="{{ $default_location ? ($default_location['lng'] ?? 0) : '151.2195' }}">
</span>

@endsection

@push('script')
    {{-- Route data for JavaScript --}}
    <span id="route-customer-choose-shipping-address-other" data-url="{{ route('customer.choose-shipping-address-other') }}"></span>
    
    @if(getWebConfig('map_api_status') ==1 )
        <script
            src="https://maps.googleapis.com/maps/api/js?key={{getWebConfig('map_api_key')}}&callback=mapsShopping&loading=async&libraries=places&v=3.56"
            defer>
        </script>
    @endif
    <script src="{{ theme_asset('assets/js/shipping-page.js') }}"></script>
    <script src="{{ asset('public/assets/front-end/js/shipping.js') }}"></script>
    <script src="{{ asset('billing_phone_override.js') }}"></script>
    @if(config('app.debug'))
        <script src="{{ asset('assets/js/debug-shipping.js') }}"></script>
    @endif
    <script>
// Add CSRF token debugging and session checking
document.addEventListener('DOMContentLoaded', function() {
    // Check CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken || !csrfToken.getAttribute('content')) {
        console.error('CSRF token not found in meta tag');
        // Try to reload page once
        if (!sessionStorage.getItem('csrf_reload_attempted')) {
            sessionStorage.setItem('csrf_reload_attempted', 'true');
            console.log('Attempting to reload page for CSRF token...');
            location.reload();
        } else {
            console.error('CSRF token still missing after reload');
            alert('خطأ في الأمان. يرجى تحديث الصفحة يدوياً.');
        }
    } else {
        console.log('✅ CSRF token found:', csrfToken.getAttribute('content').substring(0, 10) + '...');
        // Clear reload attempt flag
        sessionStorage.removeItem('csrf_reload_attempted');
    }
    
    // Check session status using a simple request
    const checkSessionStatus = () => {
        $.ajax({
            url: '{{ route("home") }}',
            method: 'HEAD',
            timeout: 5000,
            headers: {
                'X-CSRF-TOKEN': csrfToken ? csrfToken.getAttribute('content') : '',
            },
            success: function() {
                console.log('✅ Session status check completed');
            },
            error: function(xhr) {
                if (xhr.status === 419) {
                    console.warn('Session expired (419)');
                    if (confirm('انتهت صلاحية الجلسة. هل تريد تحديث الصفحة؟')) {
                        location.reload();
                    }
                } else {
                    console.log('Session check completed with status:', xhr.status);
                }
            }
        });
    };
    
    // Run session check
    setTimeout(checkSessionStatus, 1000);
    
    // Auto-refresh CSRF token every 10 minutes
    setInterval(function() {
        $.get('{{ route("home") }}', function(data) {
            const newToken = $(data).find('meta[name="csrf-token"]').attr('content');
            if (newToken) {
                $('meta[name="csrf-token"]').attr('content', newToken);
                console.log('✅ CSRF token refreshed');
            }
        }).fail(function() {
            console.warn('Failed to refresh CSRF token');
        });
    }, 600000); // 10 minutes
    
    // Initialize city search functionality
    initializeCitySearch();
    
    // Initialize saved address selection handlers
    initializeSavedAddressHandlers();
    
    // Auto-fill billing fields to prevent validation errors
    function autoFillBillingFields() {
        // Set default values for hidden fields
        document.getElementById('billing_country').value = 'Saudi Arabia';
        document.getElementById('billing_zip').value = 'permanent';
        
        // Set default city to Jeddah if not selected
        const billingCity = document.getElementById('billing_city');
        const billingSeerach = document.getElementById('billing_city_search');
        if (billingCity && !billingCity.value) {
            billingCity.value = 'جدة';
            billingSeerach.value = 'جدة';
        }
        
        // Set default address if empty
        const billingAddress = document.getElementById('billing_address');
        if (billingAddress && !billingAddress.value) {
            billingAddress.placeholder = 'العنوان سيتم ملؤه تلقائياً';
        }
        
        console.log('✅ Billing fields auto-filled successfully (phone from user account, default city: Jeddah)');
    }
    
    // Initialize saved address selection handlers
    function initializeSavedAddressHandlers() {
        // Handle billing address selection from modal
        const selectBillingBtn = document.getElementById('select-billing-address');
        if (selectBillingBtn) {
            selectBillingBtn.addEventListener('click', function() {
                const selectedRadio = document.querySelector('input[name="billing_method_id"]:checked');
                if (selectedRadio) {
                    // Trigger the change event to load the address data
                    $(selectedRadio).trigger('change');
                }
            });
        }
        
        // Handle shipping address selection from modal
        const selectShippingBtn = document.getElementById('select-shipping-address');
        if (selectShippingBtn) {
            selectShippingBtn.addEventListener('click', function() {
                const selectedRadio = document.querySelector('input[name="shipping_method_id"]:checked');
                if (selectedRadio) {
                    // Trigger the change event to load the address data
                    $(selectedRadio).trigger('change');
                }
            });
        }
    }
    
    // Initialize city search functionality
    function initializeCitySearch() {
        const searchInput = document.getElementById('billing_city_search');
        const dropdown = document.getElementById('city_dropdown');
        const hiddenInput = document.getElementById('billing_city');
        const cityOptions = dropdown.querySelectorAll('.city-option');
        
        // Set default city to Jeddah
        searchInput.value = 'جدة';
        hiddenInput.value = 'جدة';
        
        // Show dropdown on focus
        searchInput.addEventListener('focus', function() {
            dropdown.style.display = 'block';
            filterCities('');
        });
        
        // Hide dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.city-select-wrapper')) {
                dropdown.style.display = 'none';
            }
        });
        
        // Filter cities on input
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value;
            filterCities(searchTerm);
        });
        
        // Handle city selection
        cityOptions.forEach(option => {
            option.addEventListener('click', function() {
                const selectedCity = this.getAttribute('data-value');
                searchInput.value = selectedCity;
                hiddenInput.value = selectedCity;
                dropdown.style.display = 'none';
                
                // Remove selected class from all options
                cityOptions.forEach(opt => opt.classList.remove('selected'));
                // Add selected class to clicked option
                this.classList.add('selected');
                
                // Trigger change event for form sync
                hiddenInput.dispatchEvent(new Event('change'));
            });
        });
        
        function filterCities(searchTerm) {
            cityOptions.forEach(option => {
                const cityName = option.getAttribute('data-value');
                if (cityName.includes(searchTerm)) {
                    option.style.display = 'block';
                } else {
                    option.style.display = 'none';
                }
            });
        }
    }
    
    // Run auto-fill immediately
    autoFillBillingFields();
    
    // Also run when form is about to be submitted
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            autoFillBillingFields();
        });
    });
    
    // Handle billing form validation
    const billingForm = document.getElementById('billing-address-form');
    if (billingForm) {
        billingForm.addEventListener('submit', function(e) {
            // Copy billing data to shipping form (hidden)
            syncBillingDataToShippingForm();
            
            // Ensure hidden fields have values
            const requiredHiddenFields = [
                {id: 'billing_country', value: 'Saudi Arabia'},
                {id: 'billing_zip', value: 'permanent'},
                {id: 'billing_address_type', value: 'permanent'}
            ];
            
            requiredHiddenFields.forEach(field => {
                const element = document.getElementById(field.id);
                if (element) {
                    element.value = field.value;
                }
            });
            
            // Set default city if not selected
            const billingCity = document.getElementById('billing_city');
            if (billingCity && !billingCity.value) {
                billingCity.value = 'جدة';
            }
            
            const billingAddress = document.getElementById('billing_address');
            if (billingAddress && !billingAddress.value.trim()) {
                billingAddress.value = 'Saudi Arabia';
            }
        });
    }
    
    // Function to sync billing data to hidden shipping form
    function syncBillingDataToShippingForm() {
        const mappings = [
            {billing: 'billing_contact_person_name', shipping: 'hidden_contact_person_name'},
            {billing: 'billing_phone_hidden', shipping: 'hidden_phone'},
            {billing: 'billing_contact_email', shipping: 'hidden_email'},
            {billing: 'billing_country', shipping: 'hidden_country'},
            {billing: 'billing_city', shipping: 'hidden_city'},
            {billing: 'billing_zip', shipping: 'hidden_zip'},
            {billing: 'billing_address_type', shipping: 'hidden_address_type'},
            {billing: 'billing_address', shipping: 'hidden_address'},
            {billing: 'billing_method_id', shipping: 'hidden_shipping_method_id'},
            {billing: 'billing_latitude', shipping: 'hidden_latitude'},
            {billing: 'billing_longitude', shipping: 'hidden_longitude'}
        ];
        
        mappings.forEach(mapping => {
            const billingElement = document.getElementById(mapping.billing);
            const shippingElement = document.getElementById(mapping.shipping);
            
            if (billingElement && shippingElement) {
                shippingElement.value = billingElement.value || '';
            }
        });
        
        // Handle save address checkbox
        const saveBillingCheckbox = document.getElementById('save_address_billing');
        const hiddenSaveAddress = document.getElementById('hidden_save_address');
        if (saveBillingCheckbox && hiddenSaveAddress) {
            hiddenSaveAddress.value = saveBillingCheckbox.checked ? 'on' : '';
        }
        
        console.log('✅ Billing data synced to hidden shipping form');
    }
    
    // Auto-sync data when billing form fields change
    document.addEventListener('change', function(e) {
        if (e.target.closest('#billing-address-form')) {
            syncBillingDataToShippingForm();
        }
    });
    
    // Also sync on input events for real-time updates
    document.addEventListener('input', function(e) {
        if (e.target.closest('#billing-address-form')) {
            syncBillingDataToShippingForm();
        }
    });
});
</script>
@endpush


<style>
    
    .total-cost-wrapper {
        top: 0 !important
    }
    @media screen and (max-width: 767px) {
        
        .total-cost-wrapper .mb-4 ,.total-cost-wrapper .overflow-y-auto , .total-cost-wrapper .d-block {
            display: none !important;
        }
        .shipping {
            position: relative;
            top: 0 !important;
            width: 100%;
        }
        
    }
    @media screen and (max-width: 1199.98px) {
        #mobile_app_bar {
            display: none !important;
        }
    }
    
    /* تحسين مظهر radio buttons خاصة billing_method_id */
    input[type="radio"][name="billing_method_id"] {
        accent-color: #28a745 !important;
        width: 18px !important;
        height: 18px !important;
        border: 2px solid #28a745 !important;
        background-color: white !important;
    }
    
    input[type="radio"][name="billing_method_id"]:checked {
        background-color: #28a745 !important;
        border-color: #28a745 !important;
    }
    
    input[type="radio"][name="billing_method_id"]:checked::before {
        content: '';
        display: block;
        width: 8px;
        height: 8px;
        background-color: white;
        border-radius: 50%;
        margin: 3px auto;
    }
    
    /* تحسين مظهر جميع radio buttons */
    input[type="radio"] {
        accent-color: #0d6efd;
        transform: scale(1.2);
    }
    
    input[type="radio"]:focus {
        box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.25);
    }
    
    /* تنسيق Labels للنموذج */
    .form-label {
        font-weight: 600;
        color: #333;
        margin-bottom: 8px;
        font-size: 14px;
        display: block;
    }
    
    /* تحسين مظهر select للمدن السعودية */
    .city-select-wrapper {
        position: relative;
    }
    
    #billing_city_search {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m1 6 7 7 7-7'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 12px center;
        background-size: 16px 12px;
        padding-right: 40px;
    }
    
    #billing_city_search:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    }
    
    .city-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #ddd;
        border-radius: 4px;
        max-height: 200px;
        overflow-y: auto;
        z-index: 1000;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .city-option {
        padding: 10px 15px;
        cursor: pointer;
        border-bottom: 1px solid #f0f0f0;
        transition: background-color 0.2s;
    }
    
    .city-option:hover {
        background-color: #f8f9fa;
    }
    
    .city-option.selected {
        background-color: #0d6efd;
        color: white;
    }
    
    .city-option:last-child {
        border-bottom: none;
    }
    
    /* تحسين تنسيق النموذج */
    .delivery-information .row.g-4 .col-12,
    .delivery-information .row.g-4 .col-sm-6,
    .delivery-information .row.g-4 .col-md-3 {
        margin-bottom: 1rem;
    }
    
    #mobile_app_bar {
        z-index: 0 !important;
    }
</style>