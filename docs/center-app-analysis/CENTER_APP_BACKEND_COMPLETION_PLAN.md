# خطة استكمال Backend لتطبيق المراكز

**مرحلة التوثيق فقط — لا تنفيذ في هذا المستند.**

---

## Priority 1 — موانع تشغيل التطبيق

### P1.1 مصادقة موبايل للمركز
| | |
|---|---|
| الهدف | Login/Logout/Me لـ CenterUser عبر Bearer |
| المكونات | Controllers تحت `Api/Center/V1/Auth`, Middleware فحص `PanelApiToken` + panel=center + Active + center_id, Form Requests |
| اعتماديات | جدول `panel_api_tokens` موجود |
| اختبارات | login ناجح/فاشل، موقوف، بلا مركز، logout يبطل التوكن، عزل عن توكن عميل |

### P1.2 Dashboard API
| | |
|---|---|
| الهدف | JSON لـ KPIs + طلبات واردة + عمليات جارية |
| المكونات | توسيع `DashboardService`, `DashboardController` API Resource |
| اعتماديات | P1.1 |
| اختبارات | بيانات مركز A لا تظهر في B |

### P1.3 Bookings API (list/show/update)
| | |
|---|---|
| الهدف | قبول/رفض/بدء من الموبايل |
| المكونات | نقل قواعد Center\BookingController إلى API + سياسة انتقال حالات + NotificationHooks |
| اعتماديات | P1.1 |
| اختبارات | IDOR، انتقال غير قانوني، إشعار عميل/مركز |

### P1.4 Operations lookup + create + status + images
| | |
|---|---|
| الهدف | قلب التشغيل الميداني |
| المكونات | `OperationLookupService` (qr/nfc/plate)، `OperationCreateService` (خدمات، دفع، verification stub)، Controllers، Storage للصور، ربط `source` |
| اعتماديات | P1.1، wallets/subscriptions، center_service |
| DB | أعمدة اختيارية على operations؛ قرار verification |
| اختبارات | lookup، إنشاء، رصيد غير كافٍ، تكرار in_progress، رفع صورة، IDOR |

### P1.5 Notifications API للمركز
| | |
|---|---|
| الهدف | قائمة/قراءة/عداد/فتح deep link |
| المكونات | محاذاة مع نمط الإشعارات الحالي + destinations موبايل |
| اعتماديات | P1.1، نظام الإشعارات الحالي |
| اختبارات | عزل إشعارات المركز، open آمن |

---

## Priority 2 — اكتمال التجربة

### P2.1 Reports API
Summary + فلاتر تاريخ — إعادة استخدام منطق ReportController.

### P2.2 Customers API
Distinct users من عمليات/حجوزات المركز + تفاصيل تاريخ.

### P2.3 Profile/Settings API
تحديث بيانات CenterUser + تغيير كلمة المرور.

### P2.4 Center services read (+ availability toggle)
قراءة pivot؛ توغل `is_available` بقرار منتج.

### P2.5 Complaints للمركز
بعد تعديل DB أو جدول tickets — list/create.

### P2.6 Forgot/Reset password للمركز
قناة email أولاً.

---

## Priority 3 — تحسينات لاحقة

| مهمة | وصف |
|---|---|
| أدوار center_users | manager/staff + إخفاء الإيرادات |
| operation_services pivot | فاتورة متعددة الخدمات |
| PIN شركات كامل | توليد/إدارة/محاولات |
| Audit log | من غيّر حالة ماذا |
| Push FCM لـ CenterUser | أجهزة الموظفين |
| مزامنة employee_id مع center_users | بدل users فقط |
| تقارير تفصيلية/تصدير | CSV |

---

## ترتيب التنفيذ المقترح
1. Auth middleware + login  
2. Dashboard + Bookings  
3. Lookup + Create Operation + Status  
4. Images  
5. Notifications  
6. Reports + Profile  
7. Customers + Services  
8. Complaints + Password reset  
9. Roles + PIN + polish  

## ملفات/مسارات متوقع لمسها لاحقاً (عند التنفيذ فقط)
- `routes/api.php` أو `routes/api/center/v1/*.php` (جديد)
- `app/Http/Controllers/Api/Center/V1/*` (جديد)
- `app/Services/Center/*` (توسيع)
- `app/Http/Middleware/*` (توسيع/جديد لـ center API)
- Migrations اختيارية حسب قرارات DB
- Feature tests تحت `tests/Feature/CenterApi*`

## ما يجب عدم كسره
- Center Web session routes
- User App `/api/v1`
- Admin/Company panels
- Notification isolation الحالي

## تعريف الإنجاز لـ P1
تطبيق Flutter يستطيع: تسجيل دخول مركز → رؤية داشبورد حقيقي → قبول حجز → مسح/لوحة → إنشاء عملية → تحديث الحالة → رفع صورة → استلام إشعار — مع عزل بين مركزين.
