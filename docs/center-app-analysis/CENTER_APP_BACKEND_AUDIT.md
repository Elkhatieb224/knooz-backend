# مراجعة Backend مقابل تطبيق المراكز (Center App)

تاريخ المراجعة: 2026-11-10  
نطاق المراجعة: Laravel الحالي — بدون تعديل كود في هذه المرحلة.

---

## 1. الخلاصة التنفيذية

| المحور | الحالة |
|---|---|
| نماذج البيانات الأساسية (centers, center_users, bookings, operations, operation_images, center_service) | موجودة |
| لوحة ويب المركز `/center` (جلسة `auth:center`) | موجودة وجزئية |
| API موبايل مخصص لتطبيق المراكز | **غير موجود** |
| مسح QR/NFC/لوحة وإنشاء عملية من التطبيق | **غير موجود كـ API** |
| قبول/رفض حجوزات من الموبايل | منطق ويب فقط (`PATCH` عبر session) |
| عملاء المركز / شكاوى المركز | **غير موجود للمراكز** |
| عزل بيانات المركز في الويب | مطبّق عبر `Tenant::centerId()` + `forCenter` + `abort_unless` |
| إشعارات المركز (ويب) | موجودة ومعزولة |

**حكم الجاهزية:** Backend جاهز كنواة Domain + Center Web، لكنه **غير جاهز** لتشغيل Flutter Center App دون طبقة API جديدة ومنطق تشغيل (scan → create operation → document).

---

## 2. ما هو موجود فعلياً (مؤكد من الكود)

### 2.1 المصادقة والصلاحيات (ويب)
| العنصر | التفاصيل |
|---|---|
| Guard | `center` → provider `center_users` → `CenterUser` |
| جدول | `center_users` (`center_id`, email, phone, password, status, theme, avatar…) |
| Middleware | `auth:center` + `EnsureCenter` (يتحقق من `center_id` والحساب Active) |
| Login | `POST /center/login` — جلسة فقط، ليس Bearer |

### 2.2 مسارات Center Web (`routes/center.php`)
| Method | Path | الوظيفة | JSON؟ |
|---|---|---|---|
| GET/POST | `/center/login` | دخول | لا |
| POST | `/center/logout` | خروج | لا |
| GET | `/center/` | Dashboard | View |
| GET | `/center/bookings` | قائمة حجوزات + فلترة | View |
| PATCH | `/center/bookings/{booking}` | تحديث status/notes | Redirect |
| GET | `/center/operations` | قائمة عمليات + فلترة | View |
| PATCH | `/center/operations/{operation}` | تحديث status/notes + timestamps | Redirect |
| GET | `/center/reports` | إحصائيات شهرية | View |
| GET/PUT | `/center/profile` | ملف شخصي | View / JSON جزئي للثيم |
| GET/POST/DELETE | `/center/notifications/*` | إشعارات | مختلط |

### 2.3 خدمات ومنطق قابل لإعادة الاستخدام
| المكوّن | الفائدة لتطبيق المراكز |
|---|---|
| `DashboardService::stats` | KPIs للداشبورد |
| `BelongsToCenter` / `forCenter` | عزل الاستعلامات |
| `NotificationHooks` للحجوزات/العمليات | إشعار المركز عند التحديث |
| `DestinationResolver` panel=`center` | فتح آمن للإشعارات (ويب حالياً) |
| `PanelApiToken` (morph) | يمكن إصدار توكن لـ `CenterUser` دون Sanctum على الجدول |
| جداول `vehicles.qr_code` / `nfc_code` | مصدر بيانات المسح |
| `operation_images` | توثيق قبل/بعد موجود كنموذج |
| `center_service` pivot | خدمات المركز وأسعارها |

### 2.4 API v1 الحالي (`/api/v1`)
مخصّص **للعميل النهائي** (Sanctum على `User`) فقط:
- auth, home, centers catalog, vehicles, bookings, wallet, emergency, shop, notifications

**لا يوجد** تحت `/api/v1` أي مسار لـ CenterUser أو تشغيل مركز.

---

## 3. مطابقة شاشات Figma ↔ Backend

| شاشة / عملية Center App | موجود؟ | أين؟ | فجوة التطبيق |
|---|---|---|---|
| Login | جزئي | Session web | يحتاج API Bearer لـ CenterUser |
| Forgot password | لا | — | غير موجود للمركز |
| Dashboard KPIs | جزئي | `DashboardService` + View | يحتاج JSON aggregate |
| Incoming bookings + قبول/رفض | جزئي | Web PATCH status | لا يوجد API؛ الحالات: new/in_progress/completed/cancelled (لا accept صريح) |
| Operations list | جزئي | Web index | يحتاج API + pagination JSON |
| Add operation QR/NFC/Manual | لا | بيانات QR على المركبة فقط (API عميل) | يحتاج resolve + create operation |
| Operation details / start / complete | جزئي | تحديث status فقط | لا إنشاء عملية، لا اختيار خدمات متعددة، لا خصم محفظة من طرف المركز |
| Company vehicle verification code | لا | — | غير موجود في DB/API |
| Photo documentation before/after | نموذج فقط | `OperationImage` + seeder | لا upload API للمركز |
| Reports | جزئي | Web aggregations | يحتاج JSON + فلاتر تاريخ |
| Customers management | لا | — | لا endpoint يجمع عملاء المركز |
| Complaints | جزئي للإدارة | `complaints` بلا `center_id` | غير مناسب لتطبيق المركز كما هو |
| Settings / Profile | جزئي | Web profile | يحتاج API |
| Notifications | جزئي | Web + hooks | يحتاج API panel center (يوجد نمط panel tokens للإشعارات سابقاً) |
| Center services toggle/add | جزئي | Admin يدير `center_service` | لا إدارة من Center App API |

---

## 4. تصنيف الجاهزية لكل قدرة

| القدرة | التصنيف |
|---|---|
| قراءة بيانات المركز للجلسة الحالية | موجودة ولكن تحتاج استكمال (API) |
| عزل center_id في استعلامات الويب | مكتملة وجاهزة كنمط |
| قائمة/تحديث حجوزات المركز | موجودة ولكن لا تدعم التطبيق بالشكل المطلوب (Views فقط + حالات محدودة) |
| قائمة/تحديث عمليات | موجودة ولكن تحتاج استكمال (لا create/scan/images) |
| تقارير أساسية | موجودة ولكن تحتاج استكمال (JSON/فترات) |
| Auth موبايل للمركز | غير موجودة وتحتاج تطوير |
| Scan QR/NFC/Plate → هوية مركبة | غير موجودة وتحتاج تطوير |
| إنشاء عملية تشغيلية كاملة | غير موجودة وتحتاج تطوير |
| تحقق كود شركات | غير موجودة — تحتاج قرار + تطوير |
| عملاء المركز | غير موجودة وتحتاج تطوير |
| شكاوى المركز | موجودة لكن بها مشكلة تصميم بيانات (لا center_id) |
| صلاحيات موظف فرع vs مدير | موجودة كأدوار على `users` القديمة؛ `center_users` بلا أدوار فرعية — تحتاج قرار |

---

## 5. تعارضات معمارية مهمة

### 5.1 info.md يفصل تطبيقين
- **تطبيق مسؤولي المراكز**
- **تطبيق موظفي الفروع (مستقل)** مع QR/NFC/توثيق

تصميم Figma Page 2 **يدمج** التدفقين في تطبيق واحد بـ Bottom Nav.  
→ قرار مطلوب: تطبيق موحّد بصلاحيات، أم تطبيقان؟

### 5.2 هوية التشغيل
- الويب الحالي يعتمد `CenterUser`.
- أدوار `branch_employee` / `center_manager` ما زالت على جدول `users`.
- `operations.employee_id` يشير إلى `users` وليس `center_users`.

### 5.3 حالات الحجز
التصميم يتحدث عن قبول/رفض؛ الـ enum: `new | in_progress | completed | cancelled`.  
→ يُقترح تعيين: قبول=`in_progress` (أو حالة `accepted` جديدة)، رفض=`cancelled` + notes.

### 5.4 شكاوى بلا مركز
`complaints.user_id` فقط — لا ربط بعملية أو مركز.

---

## 6. ما يمكن إعادة استخدامه دون إعادة اختراع

1. استخراج منطق Dashboard/Reports إلى Services تُرجع arrays (موجود جزئياً).
2. نفس قواعد `abort_unless($model->center_id === Tenant::centerId(), 404)`.
3. `PanelApiToken::issue($centerUser, 'center')` لمصادقة الموبايل.
4. جداول operations/bookings/vehicles/operation_images كما هي مع إضافات محدودة.
5. NotificationHooks بعد إنشاء/تحديث العمليات من API الجديد.

---

## 7. حكم المطابقة النهائي

| السؤال | الجواب |
|---|---|
| هل يوجد Domain كافٍ؟ | نعم كنواة |
| هل يمكن تشغيل Center App اليوم؟ | **لا** |
| أكبر مانع؟ | غياب `/api/...` للمركز + غياب تدفق scan/create/document |
| هل Center Web يغني عن الموبايل؟ | لا — واجهة مختلفة ونطاق تشغيلي ميداني في التصميم |
