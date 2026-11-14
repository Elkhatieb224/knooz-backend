# تقرير تدقيق وتطوير نظام الإشعارات – KNOOZ

تاريخ التحديث: 2026-11-10

## 1. تحليل النظام قبل التعديل

- جدول `notifications` افتراضي من Laravel (`uuid`, `type`, `notifiable morphs`, `data`, `read_at`).
- واجهة Admin فقط: صفحة قائمة + تعليم مقروء / الكل، بدون قائمة منسدلة فعلية.
- Company/Center: لا مسارات ولا UI للإشعارات.
- لا توجد Notification Classes فعلية، ولا خدمة إرسال، ولا ربط وجهة آمن.
- البذر يخزّن `url` خام داخل `data` دون تحقق صلاحيات.
- لا API للموبايل، لا Device Tokens، لا Queue مخصص، لا أرشفة.

## 2. المشكلات التي تم اكتشافها

1. غياب الإرسال عند الأحداث التشغيلية (دفعات، حجوزات، عمليات، شكاوى…).
2. عدم عزل واجهات Company/Center.
3. الاعتماد على URL مخزّن بدون Destination Resolver.
4. عدّ غير المقروء في كل تحميل صفحة بدون كاش.
5. `markAllRead` عبر تحميل المجموعة في الذاكرة سابقاً.
6. غياب منع التكرار (`event_key`).
7. غياب فهارس مركّبة مناسبة للحجم.
8. لا تكامل Flutter/Push جاهز.
9. لا سياسة احتفاظ/أرشفة.

## 3. المعمارية النهائية

```
Event/Controller
    -> NotificationHooks
        -> NotificationService (dedupe + bulk insert + cache bust)
            -> jobs: SendBulkPanelNotificationsJob / DispatchPushNotificationJob
UI/API
    -> HandlesPanelNotifications / NotificationApiController
        -> DestinationResolver (panel + permissions + reference existence)
```

المكونات الأساسية:
- `App\Enums\NotificationType`
- `App\Models\DatabaseNotification`
- `App\Services\Notifications\NotificationService`
- `App\Services\Notifications\DestinationResolver`
- `App\Services\Notifications\NotificationHooks`
- Controllers موحّدة عبر Trait لكل لوحة
- API Bearer عبر `panel_api_tokens`

## 4. جداول قاعدة البيانات والفهارس

### `notifications` (محسّن)
- أعمدة جديدة: `notification_type`, `reference_type`, `reference_id`, `event_key` (unique), `archived_at`
- فهارس:
  - `notifications_recipient_read_idx`
  - `notifications_recipient_created_idx`
  - `notifications_recipient_type_idx`
  - `notifications_reference_idx`

### `device_tokens`
لـ FCM/APNs لكل مستلم polymorphic.

### `panel_api_tokens`
توكنات API للوحات/الموبايل (hash SHA-256).

## 5. أنواع الإشعارات والوجهات

| النوع | Admin | Company | Center |
|---|---|---|---|
| `payment.*` | `payments.show/index` | فواتير/ملخص | — |
| `booking.*` | `requests?tab=bookings` | operations إن مرتبط | `bookings.index` |
| `operation.status` | `requests?tab=operations` | `operations.index` | `operations.index` |
| `complaint.*` | `requests?tab=complaints` | — | — |
| `emergency.*` | `requests?tab=emergency` | — | حسب المركز |
| `company.created` | `companies.show/index` | — | — |
| `center.created` | `centers.show/index` | — | — |
| `invoice.issued` | — | `invoices.show/index` | — |
| `subscription.expiring` | — | `subscription.index` | — |
| `system` | صفحة الإشعارات | صفحة الإشعارات | صفحة الإشعارات |

الانتقال يتم عبر `/{panel}/notifications/{id}/open` بعد التحقق.

## 6. آلية العزل والصلاحيات

- كل إشعار مربوط بـ `notifiable_type/id` للمستلم فقط.
- Open/Read/Delete تستخدم ` $user->notifications()->whereKey($id)`.
- DestinationResolver يتحقق من صلاحية Admin عبر Permissions، ومن ملكية Company/Center عبر Tenant.
- IDOR: فتح إشعار مستخدم آخر يعيد 404 (مختبر).

## 7. آلية الإرسال والتحديث الفوري

- الإرسال غير المتزامن عبر Queue `notifications` للمجموعات.
- منع التكرار بـ `event_key`.
- تحديث العدد في الواجهة: polling كل 45 ثانية لـ unread-count فقط.
- القائمة المنسدلة تحمّل آخر 8 عند الفتح فقط.
- Push: Job جاهز؛ الإرسال الفعلي ينتظر ضبط `FCM_SERVER_KEY` / credentials.
- Broadcasting/WebSockets: غير مفعّل حالياً (تكلفة/تعقيد). التوصية لاحقاً: Redis + Reverb عند الحاجة الحقيقية.

## 8. تكامل Flutter وAPI

المسارات تحت `/api` مع Bearer token:

- `GET /api/notifications`
- `GET /api/notifications/unread-count`
- `POST /api/notifications/{id}/read`
- `POST /api/notifications/read-all`
- `GET /api/notifications/{id}/open` → payload + deep_link
- `POST /api/device-tokens`

Payload يعتمد على: `notification_id`, `notification_type`, `reference_type`, `reference_id`, `deep_link`.

## 9. التحسينات التي تم تنفيذها

- قائمة منسدلة موحّدة Admin/Company/Center
- صفحة كاملة مع بحث/تصفية/pagination/حذف
- Destination Resolver آمن
- Bulk insert + dedupe + unread cache 30s
- Hooks على الدفعات/الحجوزات/العمليات/الشكاوى/الطوارئ/إنشاء شركة/مركز
- جداول tokens + prune job يومي
- اختبارات Feature أمنية ووظيفية

## 10. الاختبارات الوظيفية والأمنية

انظر `docs/NOTIFICATIONS_TEST_CASES.md`.

ملخص تنفيذي: 7/7 اختبارات نظام الإشعارات PASS + اختبارات عزل اللوحات PASS.

## 11. نتائج اختبارات الأداء

| السيناريو | النتيجة |
|---|---|
| Feature tests (PHPUnit/SQLite) | PASS |
| k6 / JMeter تحميل إنتاجي | NOT TESTED في هذه الجولة (لا بيئة staging مطابقة) |
| EXPLAIN ANALYZE على MySQL إنتاج | NOT TESTED |
| مليون مستخدم متزامن | NOT CLAIMED |

أهداف أداء مقترحة للمرحلة التالية على Staging:
- Preview P95 < 200ms
- Unread count P95 < 100ms
- Mark all (حتى 10k غير مقروء) < 500ms عبر UPDATE واحد

## 12. أقصى حمل تم اختباره فعلياً

- اختبارات آلية محلية + smoke HTTP للوحات.
- **لم يُختبر** حمل آلاف المستخدمين المتزامنين في هذه الجولة.

## 13. المشكلات التي تم إصلاحها

- نقل منطق الإشعارات من Admin-only إلى نظام موحّد معزول.
- إزالة الاعتماد على URL خام غير موثوق.
- إضافة منع التكرار والفهارس والأرشفة المجدولة.
- تجهيز API/Push دون انتظار تطبيق Flutter.

## 14. المهام المتبقية

1. ربط Firebase Admin SDK فعلياً عند توفر المفاتيح.
2. اختبارات تحميل k6 على Staging.
3. تفعيل Laravel Reverb/Broadcasting إذا لزم التحديث الفوري بدل polling.
4. صفحات تفاصيل مستقلة لبعض الكيانات (حجز/عملية) بدل تبويبات Hub عند الحاجة.
5. Sanctum اختياري بدل `panel_api_tokens` إذا اعتُمد معيار موحّد للموبايل.

## 15. توصيات التشغيل والإنتاج والتوسع

- شغّل worker: `php artisan queue:work --queue=notifications,default,maintenance`
- Redis للكاش والطابور في الإنتاج.
- مراقبة `failed_jobs` وLatency الطابور.
- الاحتفاظ: أرشفة بعد 90 يوم، حذف المقروء بعد 180 يوم (Job مجدول).
- لا تعلن جاهزية لمليون مستخدم قبل اختبارات Staging موثّقة.
