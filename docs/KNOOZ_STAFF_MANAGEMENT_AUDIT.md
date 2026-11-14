# تدقيق وتنفيذ نظام الموظفين — Knooz Staff Management

تاريخ: 2026-11-10

## 1. تحليل الوضع الحالي

موظفو الشركات في كنوز **ليسوا جدولاً منفصلاً**. هم صفوف في `company_users` مع عمود `role` ونظام صلاحيات ثابت بالمصفوفة `CompanyPermissions`.

| المكوّن | قبل الإصلاح | بعد الإصلاح |
|---|---|---|
| Migration `role` / `created_by` | موجود لكن معلّق | مُنفَّذ |
| `CompanyUser` (casts/can/isOwner) | ناقص | مكتمل |
| مسارات ويب الموظفين | Controllers/Views موجودة بلا Routes | مربوطة + `company.can` |
| API `/api/company/v1/staff` | Controller موجود بلا Routes | مربوط + حماية صلاحيات |
| حماية باقي مسارات الشركة | غير مفعّلة | middleware حسب المصفوفة |
| Sidebar | بدون موظفين/فلترة | موظفين + فلترة `can()` |
| Flutter | بدون دور/صلاحيات/شاشات موظفين | دور + permissions + Staff UI |
| Admin تفاصيل الشركة | بدون مستخدمي اللوحة | جدول `companyUsers` |
| بذور أدوار متعددة | مالك فقط | owner/manager/accountant/viewer |

**ملاحظة Figma:** الرابط `node-id=461-18` يشير إلى لوحة **Center App** (مراكز الخدمة)، وليس شاشات إدارة موظفي الشركات. متطلبات النص العربي (موظفون تابعون للشركات + لوحة الشركة) طُبّقت على منظومة `CompanyUser`. أدوار موظفي المراكز (`center_users.role` manager/staff) منفذة مسبقاً في Center App ولا تُخلط مع موظفي الشركات.

## 2. شاشات Figma (461:18)

Canvas الاسم: **Center App** — يشمل لوحات تشغيل المركز (حجوزات، عمليات، مسح، تقارير…).  
لا يحتوي على شاشات «قائمة موظفي الشركة / إضافة موظف / تعيين أدوار شركة».

واجهات إدارة موظفي الشركة المعتمدة في التنفيذ هي واجهات لوحة الويب + Flutter المبنية على RBAC الحالي، بما يتوافق مع التصميم الوظيفي المطلوب في نص المهمة.

## 3. الشاشات المنفّذة

### ويب الشركة
- قائمة الموظفين (بحث/تصفية دور/حالة)
- إضافة موظف
- تعديل موظف + الدور
- تفعيل/تعطيل
- Sidebar يخفي الروابط غير المصرح بها

### Flutter الشركة
- قائمة الموظفين / إضافة / تعديل / تعطيل (لمن يملك `staff.manage`)
- إخفاء عناصر الرئيسية والمزيد وشريط التنقل حسب الصلاحيات
- توجيه Router يرفض مسارات غير مصرح بها

### Admin
- عرض مستخدمي لوحة الشركة (دور/حالة/آخر دخول) في صفحة تفاصيل الشركة

## 4. الوظائف المربوطة

- إنشاء موظف مرتبط بـ `company_id` للمستخدم الحالي فقط
- تعديل البيانات والدور وكلمة المرور
- تعطيل يبطل توكنات `PanelApiToken` ويمنع الدخول
- منع المدير من منح دور `owner`
- منع إدارة مالك من غير مالك
- منع الوصول عبر الشركات (404 خارج المستأجر)
- منع المشاهد من الفواتير/المحفظة/التقارير/الموظفين على API والويب

## 5. جداول قاعدة البيانات والعلاقات

```text
companies 1───* company_users
company_users.role ∈ {owner, manager, accountant, viewer}
company_users.created_by → company_users.id (nullable)
panel_api_tokens (morph) → company_users  (جلسات الموبايل)
```

لا يوجد جدول `staff` منفصل. لا تكرار مع `users` لعملاء الشركات.

## 6. APIs المستخدمة

الأساس: `/api/company/v1`

| Method | Path | صلاحية |
|---|---|---|
| GET/POST | `/staff` | `staff.manage` |
| PUT | `/staff/{id}` | `staff.manage` |
| POST | `/staff/{id}/toggle-status` | `staff.manage` |
| GET | `/auth/me` | مصادق — يعيد `role` + `permissions[]` |
| GET | `/dashboard` | `dashboard.view` |
| GET | `/vehicles*` | `vehicles.view` |
| GET | `/operations` | `operations.view` |
| GET | `/invoices*` | `invoices.view` |
| GET | `/wallet` | `wallet.view` |
| GET | `/reports/summary` | `reports.view` |
| GET | `/subscriptions` | `subscription.view` |

ويب: `/company/staff*` بنفس صلاحية `staff.manage`.

## 7. الأدوار والصلاحيات

مصفوفة `App\Support\CompanyPermissions::MATRIX`:

| صلاحية | owner | manager | accountant | viewer |
|---|---|---|---|---|
| dashboard.view | ✓ | ✓ | ✓ | ✓ |
| vehicles.view | ✓ | ✓ | ✓ | ✓ |
| operations.view | ✓ | ✓ | ✓ | ✓ |
| invoices.view | ✓ | ✓ | ✓ | ✗ |
| subscription.view | ✓ | ✓ | ✗ | ✗ |
| wallet.view | ✓ | ✓ | ✓ | ✗ |
| reports.view | ✓ | ✓ | ✓ | ✗ |
| staff.manage | ✓ | ✓ | ✗ | ✗ |
| profile.edit | ✓ | ✓ | ✓ | ✓ |

التحقق: `CompanyUser::can()` + middleware `company.can` على الويب والـ API.

## 8. آلية التحقق

1. تسجيل الدخول عبر نفس `CompanyUser` (جلسة ويب أو Bearer API).
2. تحميل الدور من DB.
3. كل مسار حسّاس يمر بـ `company.can:{permission}`.
4. الواجهة تخفي العناصر، والـ Backend يرفض 403 عند التجاوز.
5. تعطيل الموظف يحذف التوكنات فوراً.

## 9. تكامل لوحة الشركة

مكتمل: إدارة الموظفين من `/company/staff` مع عزل المستأجر ومنع تجاوز صلاحيات المنصة.

## 10. تكامل Flutter

- نفس الحسابات والأدوار عبر `/api/company/v1`.
- `CompanyUserModel.permissions` من السيرفر.
- شاشات Staff + فلترة Nav/More/Home + redirects.

## 11. تكامل لوحة الإدارة

- تفاصيل الشركة تعرض مستخدمي اللوحة مع الدور والحالة.
- إدارة الشركة (تفعيل/إيقاف الشركة) ما زالت عبر Admin كما سابقاً وتؤثر على دخول كل موظفي الشركة.

## 12. نتائج الاختبارات

| الاختبار | النتيجة |
|---|---|
| `CompanyApiV1Test` | PASS |
| `CompanyStaffRbacTest` (7 حالات) | PASS |
| owner ينشئ موظف | PASS |
| viewer → staff/invoices 403 | PASS |
| accountant مالية بدون staff | PASS |
| manager لا يمنح owner | PASS |
| تعطيل يبطل التوكن ويمنع الدخول | PASS |
| عزل شركة أخرى | PASS |
| ويب viewer ممنوع من staff | PASS |
| `flutter analyze` | No issues |

حسابات تجريبية (بعد البذر):

| الحساب | الدور | كلمة المرور |
|---|---|---|
| company@knooz.test | owner | password |
| manager@knooz.test | manager | password |
| accountant@knooz.test | accountant | password |
| viewer@knooz.test | viewer | password |

## 13. أخطاء أُصلحت

- تفعيل migration الدور وتشغيله.
- إكمال نموذج `CompanyUser` ليطابق Resource.
- ربط Routes ويب/API التي كانت ميتة.
- تسجيل `company.can` وفرضه على المسارات.
- فلترة Sidebar + Flutter حسب الصلاحيات.
- بذر أدوار متعددة + عرضها في Admin.
- اختبارات RBAC آلية.

## 14. المشاكل المتبقية

| البند | السبب |
|---|---|
| صلاحيات مخصصة لكل مستخدم خارج الأدوار الأربعة | النظام Role-only؛ لا جدول permissions فردي — متعمد |
| دعوات موظفين بالبريد من Figma | غير موجودة في Backend/تصميم موظفي الشركات |
| مطابقة بصرية 100% لـ Figma 461:18 | الـ node هو Center App وليست شاشات موظفي الشركات |
| تعديل حالة الموظف من Admin مباشرة | Admin يعرض الحالة؛ التعطيل التشغيلي عبر لوحة الشركة (يمكن توسعته لاحقاً) |
| اختبار UI يدوي كامل لكل دور على المحاكي في هذه الجولة | مغطى باختبارات Feature API/Web؛ يُنصح بمرور يدوي سريع بالحسابات الأربعة |

## معيار الاكتمال

- إدارة موظفين من لوحة الشركة: **نعم**
- دخول الموظف بصلاحياته: **نعم**
- حماية Backend + إخفاء UI: **نعم**
- لا تداخل owner vs viewer: **نعم (مختبر)**
- Admin يرى المستخدمين: **نعم**
- بيانات موحّدة App/Web: **نعم (نفس CompanyUser)**
