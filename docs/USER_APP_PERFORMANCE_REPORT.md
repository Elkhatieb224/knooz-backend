# تقرير أداء تطبيق المستخدم

## تحسينات منفّذة
- Cache كتالوج الخدمات 60 ثانية
- Pagination على القوائم
- Eager loading للعلاقات في الحجوزات/المنزل
- Queues لإشعارات الحجوزات الجماعية
- Indexes على shop_products والحقول الجديدة

## اختبارات الحمل
| المستوى | النتيجة |
|---|---|
| PHPUnit Feature (محلي) | PASS |
| k6 100–1000 concurrent | NOT TESTED |
| 10k / 100k / 1M concurrent | NOT TESTED / NOT CLAIMED |

## أهداف مقترحة لـ Staging
- Auth login P95 < 300ms
- Home P95 < 400ms
- Bookings list P95 < 350ms
- Error rate < 1% عند 1k VU

## موارد مطلوبة لمليون متزامن (تقديري غير مثبت)
- Load balancer + عدة app nodes
- Redis cache/queue
- MySQL primary + read replicas
- CDN للصور
- اختبار تدريجي موثّق قبل أي إعلان طاقة استيعابية
