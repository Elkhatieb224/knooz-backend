# مراجعة أمنية – API تطبيق المستخدم

## نتائج منفّذة (PASS)

| اختبار | النتيجة |
|---|---|
| تسجيل/دخول العملاء فقط | PASS |
| رفض أدوار غير العملاء عند login | PASS |
| رفض الطلبات بدون Bearer | PASS |
| IDOR مركبات | PASS |
| IDOR حجوزات | PASS |
| Rate limiting على auth/v1 | PASS (مضبوط) |
| كلمات المرور hashed | PASS |
| Tokens قابلة للإلغاء عند logout/change-password | PASS |
| الحساب غير النشط مرفوض | PASS |

## غير مكتمل / لاحق

| بند | الحالة |
|---|---|
| OTP حقيقي لنسيت كلمة المرور | BLOCKED (مزوّد SMS) |
| فحص اختراق XSS/SQLi شامل | NOT TESTED (Eloquent mitigates SQLi) |
| رفع ملفات خبيث متقدم | NOT TESTED (قيود image/max موجودة) |
| HTTPS إجبار في الإنتاج | توثيق تشغيلي |
| Brute force beyond throttle | جزئي (throttle) |

## توصيات
1. تفعيل HTTPS وSecure cookies للوحة الويب
2. ربط مزوّد OTP
3. مراقبة failed logins
4. مراجعة صلاحيات التخزين العام للإيصالات
