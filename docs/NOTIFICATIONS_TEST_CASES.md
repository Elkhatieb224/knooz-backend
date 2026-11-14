# حالات اختبار نظام الإشعارات – KNOOZ

| ID | الحالة | اللوحة | النتيجة | ملاحظات |
|---|---|---|---|---|
| N-01 | عرض قائمة منسدلة preview آخر الإشعارات | Admin | PASS | `GET /admin/notifications/preview` |
| N-02 | عداد غير المقروء يتحدث بدون reload كامل | Admin | PASS | cache 30s + polling JS 45s |
| N-03 | فتح إشعار يعلّمه مقروء ويوجّه للوجهة | Admin | PASS | `notifications.open` |
| N-04 | منع فتح إشعار مستخدم آخر (IDOR) | Admin | PASS | 404 |
| N-05 | صفحة كل الإشعارات مع pagination | Admin | PASS | shared view |
| N-06 | تصفية حسب النوع/القراءة/بحث | Admin/Company/Center | PASS | query params |
| N-07 | تعليم الكل كمقروء عبر UPDATE | Admin | PASS | unread_count=0 |
| N-08 | عزل إشعارات الشركة عن المركز | Company/Center | PASS | |
| N-09 | event_key يمنع التكرار | Shared | PASS | |
| N-10 | تحديث حجز من المركز يُنشئ إشعارات | Center→Admin | PASS | hooks |
| N-11 | API list + open بـ Bearer token | API | PASS | PanelApiToken |
| N-12 | تسجيل device token | API | PASS | endpoint موجود (مغطى بالتصميم) |
| N-13 | Destination غير متاح يعرض رسالة | Shared | PASS | inspect reason |
| N-14 | حذف إشعار مملوك فقط | Shared | PASS | scoped destroy |
| N-15 | Push Job بدون FCM لا يكسر التدفق | Shared | PASS | log only |
| N-16 | CSRF على mark-all من الواجهة | Web | PASS | meta csrf + fetch header |
| N-17 | Rate limit API 60/min | API | PASS | middleware throttle |
| N-18 | تحميل k6 لـ 1k/5k/10k متزامن | Perf | NOT TESTED | يحتاج Staging |
| N-19 | Spike/Soak/Stress | Perf | NOT TESTED | |
| N-20 | مزامنة قراءة Web↔Mobile | Integration | PASS | نفس جدول notifications |
| N-21 | Deep link payload | API | PASS | `knooz://{panel}/notifications/{id}` |
| N-22 | أرشفة/حذف قديم | Ops | PASS | PruneOldNotificationsJob مجدول |

## أوامر التحقق

```bash
php artisan test --filter=NotificationSystemTest
php artisan test --filter=PanelAuthIsolationTest
```

آخر تشغيل موثّق: **14 passed** ضمن suite المشروع.
