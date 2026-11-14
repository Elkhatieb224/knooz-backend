# الأخطاء والإصلاحات — Center App

| # | الخطأ | السبب | الإصلاح | إعادة الاختبار |
|---|---|---|---|---|
| 1 | خلط Frames Figma (User vs Center) | Page 2 مختلطة | تصنيف عبر screenshots + IMPLEMENTATION_SCOPE | مراجعة يدوية للشاشات |
| 2 | `obscureText` على AppTextField | اسم المعامل `obscure` | تصحيح ResetPasswordScreen | analyze + test ✓ |
| 3 | بدء حجز من الرئيسية يفتح لوحة فقط | لم يُربط بالحجز | قبول الحجز + lookup باللوحة → تفاصيل | مراجعة كود home_screen |
| 4 | نسيت كلمة المرور بلا شاشة reset | ناقصة | ResetPasswordScreen + route | analyze ✓ |
| 5 | تحكم Admin بإظهار الأرباح غير ظاهر في الواجهة | الحقل موجود في API/DB فقط | مفتاح `show_revenue` في نموذج Admin للمراكز | مراجعة Blade |

## ملاحظات متبقية (غير حاجزة للإطلاق الداخلي)
- أيقونات Material بدل أصول SVG المخصصة من Figma (تحسين بصري لاحق).
- OTP شركات وFCM مؤجلان بقرار منتج.
- إنشاء شكوى يتطلب `user_id` رقمي من العملاء المرتبطين.
- Golden/visual regression لكل شاشة على جهاز حقيقي لم تُنفَّذ آلياً بالكامل.
