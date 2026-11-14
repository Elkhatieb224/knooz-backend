# خطة Backend لتطبيق المستخدم – KNOOZ

تاريخ: 2026-11-10

## 1. ملخص التحليل

تطبيق Flutter (`flutter/user`) واجهة مكتملة (~44 شاشة) مع **بيانات وهمية**. Laravel يملك نماذج تشغيلية جاهزة (`users`, `vehicles`, `bookings`, `operations`, `wallets`, `centers`, `services`, `emergency_requests`, `offers`, `packages`…) وAPI إشعارات Bearer فقط.

**القرار المعماري:** API تحت `/api/v1` مع مصادقة Bearer عبر `panel_api_tokens` (متوافق مع الإشعارات الحالية). العملاء على جدول `users` بأدوار `customer_individual` / `customer_company`.

## 2. خريطة الشاشات → الوحدات

| مجموعة Flutter | Module API |
|---|---|
| Splash/Onboarding/Login/Register/Forgot | Auth |
| Home / wallet card / offers / nearest center | Home + Catalog |
| Vehicles / Add / QR | Vehicles |
| Bookings list / Branch schedule-payment / Wash→branch | Bookings |
| Emergency / Tow / Fuel / Tire | Emergency (+ service meta) |
| Wallet recharge flow | Wallet |
| Notifications tab | Notifications |
| More / Profile / Loyalty / Settings | Profile |
| Shop catalog/detail | Shop |
| Centers services | Catalog |

## 3. Modules والتنفيذ

### Auth
- register, login (phone/email + password), logout, me, update profile, change password, forgot/reset
- رفض الحسابات غير النشطة / غير العملاء
- Rate limit على login/register

### Home & Catalog
- home aggregate (profile snippet, wallet, loyalty placeholder, offers, nearest centers)
- services, centers (geo filter), offers, packages

### Vehicles
- CRUD مملوك للمستخدم، توليد QR/NFC عند الإنشاء

### Bookings
- list/show/create/cancel
- center availability slots (توليد نوافذ زمنية بسيطة)
- إنشاء حجز مرتبط بخدمة ومركز ومركبة

### Emergency
- create/list/show لطلبات الطوارئ (كفرات/وقود/سطحة كـ meta type)
- إشعار للإدارة عند الإنشاء

### Wallet
- balance + transactions
- recharge bank transfer (pending payment + optional receipt)

### Notifications
- إعادة استخدام منطق الإشعارات تحت `/api/v1/notifications`

### Shop
- جدول `shop_products` خفيف للمنتجات المعروضة في التطبيق

### Profile
- avatar upload, preferences (theme/notifications flag في settings JSON أو حقل لاحق)

## 4. Response موحّد

```json
{ "success": true, "message": "...", "data": {}, "meta": {} }
```

خطأ:

```json
{ "success": false, "message": "...", "errors": {} }
```

## 5. متطلبات الأداء

- Eager load للعلاقات
- Pagination افتراضي 20
- Cache قصير للكتالوج (centers/services/offers)
- Queues للإشعارات
- Indexes على user_id/status/scheduled_at

## 6. اختبارات مطلوبة

- Feature: auth, vehicles ownership, booking create/cancel, wallet recharge, emergency, IDOR
- Flutter: repositories تستدعي API الحقيقي
- Load: يُوثَّق الحد الفعلي لاحقاً (لا ادعاء مليون متزامن دون قياس)

## 7. ترتيب التنفيذ

1. ApiResponse + Auth middleware + Auth endpoints  
2. Home/Catalog/Vehicles/Bookings  
3. Wallet/Emergency/Shop/Notifications v1  
4. ربط Flutter repositories + شاشات حرجة  
5. Tests + docs المتبقية  
