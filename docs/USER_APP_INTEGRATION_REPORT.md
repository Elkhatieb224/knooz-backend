# تقرير ربط Flutter ↔ Laravel

تاريخ: 2026-11-10

## Base URL
Flutter `ApiClient` الافتراضي: `http://127.0.0.1:8000/api/v1`  
(يمكن تجاوزه بـ `--dart-define=API_BASE_URL=...`)

## حالة الربط حسب الشاشات

| شاشة Flutter | API | الحالة |
|---|---|---|
| Login | POST `/auth/login` | مكتمل |
| Register | POST `/auth/register` | Backend جاهز / UI خطوة 2 ما زالت تنتقل بدون استدعاء كامل |
| Forgot password | POST `/auth/forgot-password` | Backend stub / UI غير مربوط بعد |
| Home | GET `/home` | Backend جاهز / UI ما زال mock جزئياً |
| Notifications | GET `/notifications` + mark read | مكتمل |
| Vehicles list/create | `/vehicles` | Backend جاهز / UI يحتاج استبدال القوائم الثابتة |
| Bookings list/create/cancel | `/bookings` | Backend جاهز / UI يحتاج استبدال mock |
| Branch slots/payment | `/centers/{id}/slots` + bookings | Backend جاهز / UI mock |
| Wallet recharge | `/wallet` + `/wallet/recharge` | Backend جاهز / UI mock |
| Emergency/Tow/Fuel/Tire | POST `/emergency` | Backend جاهز / UI flows محلية |
| Shop | `/shop/products` | Backend جاهز / UI يستخدم `shop_products.dart` محلي |
| Profile/More logout | `/auth/me` `/auth/logout` | Backend جاهز / UI جزئي |

## ما تم ربطه فعلياً في الكود
- `ApiClient` مع Bearer + SharedPreferences
- `AuthRepository` / `BookingsRepository` / `VehiclesRepository` / `NotificationsRepository` تستدعي API الحقيقي
- `LoginScreen` يستدعي login الفعلي
- `NotificationsScreen` تجلب وتعلّم المقروء من Backend

## قيود متبقية
- بقية الشاشات ما زالت تعرض بيانات UI/Figma ثابتة وتحتاج استبدال تدريجي بالمستودعات
- تسجيل حساب من Step2 يحتاج تمرير بيانات Step1 ثم استدعاء register
- لا FCM حقيقي بعد (device-tokens endpoint جاهز)
