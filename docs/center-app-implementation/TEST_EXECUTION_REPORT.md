# تقرير تنفيذ الاختبارات — Center App

تاريخ التشغيل: 2026-11-10

## 1. Backend

### الأمر
```bash
cd backend
php artisan test --filter=CenterApi
```

### النتيجة
**12 passed** (47 assertions) — Duration ~2.3s

| Suite | Result |
|---|---|
| CenterApiAuthTest | 5 passed |
| CenterApiBookingOperationTest | 3 passed |
| CenterApiIsolationTest | 1 passed |
| CenterApiRevenueRulesTest | 3 passed |

## 2. Flutter

### الأوامر
```bash
cd flutter/center
flutter analyze
flutter test
```

### النتيجة
| فحص | النتيجة |
|---|---|
| `flutter analyze` | No issues found |
| `flutter test` | **17/17 passed** |

تغطية الاختبارات:
- BookingStatusMapper
- RevenueVisibility
- Router exclusions (Branch Employees)
- Login validators + AppTextField widget

## 3. تكامل يدوي موصى به (جهاز/محاكي)

```bash
# Terminal 1
cd backend && php artisan serve

# Terminal 2
cd flutter/center
flutter run --dart-define=API_BASE_URL=http://127.0.0.1:8000/api/center/v1
```

سيناريو ذهب: Login → Dashboard KPIs → قبول حجز → بدء (lookup) → اختيار خدمة → إنشاء عملية → إكمال + صورة.

## 4. أخطاء أصلحت أثناء التنفيذ
انظر `BUGS_AND_FIXES.md`.
