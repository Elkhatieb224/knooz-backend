# خريطة تنقل تطبيق المراكز (Center App)

## تدفق الإقلاع
```
App Launch
  → Splash (اختياري)
  → Auth Check
       ├─ Token صالح → Home (Dashboard)
       └─ غير مسجّل → Login
            ├─ نجاح → Home
            └─ نسيت كلمة المرور → Forgot Password → Login
```

## التنقل السفلي
```
┌────────────┬────────────┬────────────┬────────────┐
│  الرئيسية  │  العمليات  │  التقارير  │   المزيد   │
└────────────┴────────────┴────────────┴────────────┘
```

## من الرئيسية (Dashboard)
```
Home
  ├─ بطاقة طلب وارد → قبول / رفض / بدء خدمة
  ├─ بطاقة عملية جارية → تفاصيل العملية
  ├─ إجراء سريع: مسح QR → QR Scanner → Operation Details
  ├─ إجراء سريع: NFC → NFC Scanner → Operation Details
  ├─ إجراء سريع: إدخال يدوي → Manual Plate → Operation Details
  ├─ أيقونة إشعارات → Notifications
  └─ المزيد (قائمة/Sheet)
        ├─ إدارة العملاء → Customers
        ├─ التقارير المالية → Reports (أو تفاصيل مالية)
        ├─ الشكاوى → Complaints
        └─ الإعدادات → Settings / Profile
```

## تدفق العملية (Operations)
```
Operations Tab
  ├─ قائمة عمليات → Operation Details / In Progress
  └─ زر إضافة (+)
        → اختيار طريقة
              ├─ QR → Scanner → نتيجة مركبة/عميل → Operation Details
              ├─ NFC → Scanner → ...
              └─ Manual → إدخال لوحة → بحث → Operation Details
                    → اختيار خدمات
                    → (إن مركبة شركة) Identity / Password
                    → تأكيد الدفع/الخصم
                    → بدء الخدمة → In Progress
                    → إنهاء → Complete → العودة للقائمة/الرئيسية
```

## طلبات الحجوزات
```
Incoming Request Card
  ├─ قبول → تحديث الحالة → تظهر ضمن العمليات/الجارية
  ├─ رفض → تحديث الحالة + سبب (إن وُجد)
  └─ بدء الخدمة → نفس مسار Operation In Progress
```

## Auth محمي
كل الشاشات ما عدا Splash/Login/Forgot تتطلب جلسة مركز (CenterUser) صالحة.
