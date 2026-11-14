# قاعدة بيانات تطبيق المستخدم

## جداول أساسية مستخدمة

| جدول | الاستخدام |
|---|---|
| `users` | العملاء (`customer_individual` / `customer_company`) + حقول `city`, `gender`, `loyalty_points`, `notifications_enabled` |
| `personal_access_tokens` | Sanctum tokens للموبايل |
| `vehicles` | مركبات العميل/الشركة |
| `bookings` | حجوزات المراكز |
| `operations` | سجل التنفيذ |
| `centers` + `center_service` + `services` | الكتالوج |
| `wallets` + `wallet_transactions` + `payments` | المحفظة والشحن |
| `emergency_requests` + `service_request_metas` | طوارئ (tire/fuel/tow) |
| `offers` + `packages` + `subscriptions` | عروض وباقات |
| `shop_products` | متجر الزيوت/الإطارات |
| `notifications` + `device_tokens` | إشعارات وFCM tokens |
| `panel_api_tokens` | توافق قديم للإشعارات |

## فهارس مهمة
- `users`: email/phone unique, role/status
- `bookings`: user_id + status + scheduled_at (عبر الاستعلامات)
- `shop_products`: category+is_active+brand
- `notifications`: recipient composites (من تعزيز سابق)

## Migrations الجديدة
- `2024_01_01_000250_create_personal_access_tokens_table`
- `2024_01_01_000260_add_customer_app_fields_and_shop`
