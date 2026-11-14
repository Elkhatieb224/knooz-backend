# نطاق تنفيذ تطبيق المراكز — Knooz Center App

تاريخ: 2026-11-10  
المشروع: `flutter/center` + Backend `/api/center/v1`

---

## 1. النطاق المعتمد

تطبيق موبايل مستقل لمسؤولي/موظفي **المراكز** لإدارة التشغيل اليومي، منفصل تماماً عن:
- User App (`flutter/user`)
- Branch Employees App (مستقل — خارج النطاق)
- Admin / Company / Center Web dashboards

---

## 2. قرارات المنتج المحسومة

انظر `docs/center-app-analysis/CENTER_APP_MISSING_REQUIREMENTS.md`.

ملخص سريع:
| قرار | القيمة |
|---|---|
| D1 | تطبيقان منفصلان User ≠ Center |
| D2 | `center_users` + أدوار manager/staff؛ واجهات Staff منفصلة عن إدارة المركز |
| D3 | قبول→`in_progress` / رفض→`cancelled` |
| D4 | عدة operations لنفس الزيارة |
| D5 | OTP/FCM لاحقاً — غير مفعّل v1 |
| D6 | نعم — تفعيل/إيقاف خدمات المركز |
| D7 | توسيع `complaints` |
| D8 | Staff لا يرى الإيرادات |
| D9 | Forgot password عبر Email |
| D10 | NFC اختياري + Manual fallback |
| الإيرادات | صافي بعد عمولة التطبيق |
| Walk-in | مسموح دائماً |
| متعاقد vs مملوك | إخفاء الأرباح للمتعاقد + تحكم Admin عبر `show_revenue` |

---

## 3. الشاشات المنفّذة (Center App)

| الشاشة | القرار | مصدر Figma |
|---|---|---|
| Splash | تنفيذ | نمط التطبيق |
| Login | تنفيذ | نمط كنوز + Center API |
| Forgot Password | تنفيذ | Email reset |
| Home / Dashboard | تنفيذ | `228:731`, `228:558` |
| Incoming Bookings actions | تنفيذ | داخل Dashboard |
| Operations List / Tab | تنفيذ | Bottom Nav |
| Add Operation method sheet | تنفيذ | QR / NFC / Manual |
| QR Scanner | تنفيذ | `229:9586` |
| NFC Scanner | تنفيذ | `229:10623` (+ Manual fallback) |
| Manual Plate Entry | تنفيذ | نصوص اللوحة في Page 2 |
| Operation Details / Start | تنفيذ | `229:13843` |
| Operation Complete + photos | تنفيذ | تدفق العمليات |
| Reports | تنفيذ | Bottom Nav + تقارير (مخفية لـ Staff) |
| Customers | تنفيذ | من المزيد |
| Complaints | تنفيذ | من المزيد |
| Services toggle | تنفيذ | إعدادات/خدمات |
| Notifications | تنفيذ | أيقونة الجرس |
| Profile / Settings | تنفيذ | المزيد |
| More tab | تنفيذ | Bottom Nav |

Bottom Nav: الرئيسية · العمليات · التقارير · المزيد

---

## 4. الشاشات المستبعدة (Branch Employees / User App)

| الشاشة | التطبيق | القرار |
|---|---|---|
| مركباتي / إضافة مركبة / QR عضوية العميل | User App | استبعاد |
| شحن المحفظة / إيصال الحوالة | User App | استبعاد |
| متجر زيوت/إطارات للعميل | User App | استبعاد |
| سطحة / وقود / بنشر كرحلة عميل | User App | استبعاد |
| طوارئ ميدانية مستقلة لموظف الفرع | Branch Employees App | استبعاد |
| تسجيل عميل جديد / باقات أفراد | User App | استبعاد |
| لوحة الإدارة الويب | Admin | استبعاد |

**قاعدة:** لا Routes ولا Navigation لموظفي الفروع داخل `flutter/center`.

---

## 5. فصل Manager vs Staff داخل Center App

| القدرة | Manager | Staff |
|---|---|---|
| Dashboard تشغيلي | نعم | نعم (بدون إيرادات) |
| قبول/رفض حجوزات | نعم | نعم |
| عمليات QR/NFC/Manual | نعم | نعم (واجهة تشغيل أساسية) |
| التقارير المالية | نعم (إن `show_revenue`) | لا |
| العملاء / الشكاوى / الخدمات | نعم | قراءة/تشغيل محدود حسب API |
| الملف والإعدادات | نعم | نعم |

---

## 6. Backend

قاعدة: `/api/center/v1` — موثّق في `BACKEND_CHANGES_REPORT.md`.
