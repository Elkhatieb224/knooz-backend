# مخطط تنفيذ Flutter — Knooz Center App

**لا يُنشأ مشروع Flutter في هذه المرحلة.** هذا مرجع للتنفيذ اللاحق بعد استكمال Backend P1.

---

## 1. Architecture
- **Clean Architecture عملي:** `presentation` / `domain` / `data`
- **Feature-first** داخل كل طبقة
- **State management:** Riverpod أو Bloc (اختر واحداً للمشروع وثبّته) — المفضل إن كان User App يستخدم نمطاً موجوداً: **حاذِه لتقليل تكلفة الفريق**
- اعتماديات للداخل: UI → UseCases → Repositories → API/Local
- بدون Mock في البناء الإنتاجي؛Mocks للاختبارات فقط

## 2. Package / Folder Structure (مقترح)
```
lib/
  app/                 # bootstrap, router, di
  core/                # network, errors, storage, theme, l10n, utils
  features/
    auth/
    home/
    operations/
    bookings/
    reports/
    customers/
    complaints/
    notifications/
    profile/
    settings/
  shared/widgets/
  shared/models/       # فقط إن لزم عبر الميزات
```

كل feature:
```
data/datasources|models|repositories
domain/entities|repositories|usecases
presentation/pages|widgets|providers|controllers
```

## 3. Navigation
- `go_router` (أو مكافئ)
- ShellRoute لـ Bottom Nav: Home / Operations / Reports / More
- Auth guard: لا توكن → Login
- Deep links: `center://bookings/{id}`, `center://operations/{id}`, `center://notifications/{id}`

## 4. Screens ↔ API mapping
| الشاشة | API |
|---|---|
| Login | POST auth/login |
| Home | GET dashboard |
| Bookings actions | PATCH bookings/{id} |
| Operations list | GET operations |
| Add method sheet | محلي |
| QR/NFC/Manual | POST operations/lookup |
| Operation details / start | POST operations + PATCH |
| Complete + photos | POST operations/{id}/images + PATCH |
| Reports | GET reports/summary |
| Customers | GET customers |
| Complaints | GET/POST complaints |
| Notifications | notifications* |
| Profile/Settings | me + profile |

## 5. Theme & UI
- استخرج الألوان من Figma (برتقالي العلامة ~`#F68B1F` مذكور في التحليل — ثبّت Tokens عند التنفيذ)
- خطوط عربية صريحة + EN
- RTL افتراضي؛ دعم LTR
- مكونات مشتركة: KpiCard, BookingCard, OperationCard, ScannerFrame, EmptyState, ErrorState, LoadingSkeleton
- لا تنسخ شاشات User App كما هي

## 6. Localization
- `flutter_localizations` + AR/EN arb
- كل النصوص من l10n — التصميم عربي أولاً

## 7. Auth & Storage
- Secure Storage للتوكن
- Interceptor يضيف Bearer
- عند 401 → تنظيف جلسة → Login
- Refresh غير موجود حالياً — إعادة Login

## 8. Operations device features
- `mobile_scanner` / camera permission — QR
- NFC plugin حسب المنصات المدعومة — مع fallback لـ Manual إن NFC غير متاح
- Image picker/camera لتوثيق before/after + ضغط الصور قبل الرفع
- التعامل مع رفض الصلاحيات بحالات UI واضحة

## 9. Error / Loading / Empty
طبقة Result/Either أو exceptions موحّدة → رسائل مستخدم؛ Retry على الشبكات.

## 10. Security على الجهاز
- لا logs للتوكن
- إخفاء الحقول الحساسة
- التحقق من صلاحيات السيرفر لإظهار تبويب التقارير المالية

## 11. Testing Strategy
| المستوى | أمثلة |
|---|---|
| Unit | Lookup parsing، status transitions |
| Widget | BookingCard actions، Empty states |
| Integration | Login → Dashboard goldens خفيفة |
| Golden (اختياري) | الشاشات الحرجة بعد ثبات التصميم |

## 12. مراحل تنفيذ Flutter (بعد Backend)
1. Skeleton + Theme + Router + Auth  
2. Home + Notifications  
3. Bookings actions  
4. Operations flow (lookup → create → status → images)  
5. Reports  
6. More: customers, complaints, settings  
7. Push (عند جاهزية FCM)  
8. Hardening / store release  

## 13. علاقة المشروعين Flutter
يُفضّل مستودع/حزمة منفصلة `flutter/center` عن `flutter/user` حتى لو شُاركت حزمة `knooz_design` لاحقاً — النطاق والصلاحيات مختلفان.
