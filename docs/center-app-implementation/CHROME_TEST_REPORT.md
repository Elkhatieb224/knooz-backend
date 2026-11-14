# تقرير اختبار Center App — Emulator / Auth

تاريخ: 2026-11-10  
الجهاز: Android Emulator `Pixel_4` (`emulator-5554`)  
API: `http://10.0.2.2:8000/api/center/v1`  
بيانات الدخول: `0500000010` / `password` (مكافئ `center@knooz.test`)

## 1) حالة التشغيل

| العنصر | الحالة |
|---|---|
| Emulator Pixel_4 | PASS — يعمل |
| Laravel `0.0.0.0:8000` | PASS |
| بناء APK وتثبيت التطبيق | PASS بعد إصلاحات Android |
| تشغيل التطبيق على المحاكي | PASS |

ملاحظات بناء Android التي أُصلحت:
- صورة نظام AVD كانت ناقصة (`kernel-ranchu`) ثم ثُبّتت.
- `compileSdk` رُفع إلى 36.
- تعطيل Kotlin incremental بسبب تعارض جذور `C:`/`D:`.
- تصحيح `nfc_manager` محلياً من `compileSdkVersion 31` → `36`.

اختبار Chrome السابق: Splash→Login نجح؛ تعبئة النموذج عبر أدوات الويب فشلت (قيود Flutter Web). أكمل الاختبار على Emulator.

## 2) نتائج الاختبارات

| # | الاختبار | النتيجة | دليل |
|---|---|---|---|
| 1 | Splash يظهر عند الإقلاع | PASS | لقطة `emu_fresh.png` / انتقال تلقائي |
| 2 | الانتقال التلقائي إلى Login بدون Token | PASS | `emu_screen.png` |
| 3 | لا تعليق على Splash / لا Redirect Loop | PASS | وصل Login ثم Home بدون حلقة |
| 4 | شاشة Login تعرض الحقول والأزرار | PASS | لقطات Login |
| 5 | تسجيل الدخول ببيانات المركز | PASS | `POST /auth/login` ثم `GET /dashboard` |
| 6 | الانتقال إلى Home بعد الدخول | PASS | `emu_final_login.png` — داشبورد مسؤول مركز الرياض |
| 7 | تبويب المزيد | PASS | `emu_more.png` |
| 8 | تسجيل الخروج (آخر اختبار) | PASS | `POST /auth/logout` + العودة لـ Login (`emu_logout.png`) |

## 3) أخطاء ظهرت أثناء الجلسة

| خطأ | السبب | الإصلاح |
|---|---|---|
| Emulator لا يقلع | صورة نظام ناقصة | تثبيت `system-images;android-34;google_apis_playstore;x86_64` |
| Gradle lock / Kotlin different roots | مشروع على D: وPub على C: | `kotlin.incremental=false` + تنظيف build |
| AAR metadata / compileSdk 31 | `nfc_manager` قديم | `compileSdk=36` + تعديل compileSdk للحزمة |
| فشل تعبئة Login على Chrome | Flutter Web لا يزامن Controllers مع DOM fill | الانتقال للاختبار على Emulator + adb input |

## 4) الإصلاحات المنفّذة (Flutter Android)

- `android/app/build.gradle.kts`: `compileSdk/targetSdk = 36`
- `android/gradle.properties`: تعطيل Kotlin incremental
- تصحيح محلي لـ `nfc_manager` compileSdk (Pub cache)

## 5) المشاكل المتبقية

- تعديل `nfc_manager` في Pub cache غير دائم عبر الأجهزة؛ يُفضّل ترقية الحزمة أو fork محلي لاحقاً.
- تحذير KGP من `mobile_scanner` / `nfc_manager` (مستقبلي، لا يمنع التشغيل الحالي).
- اختبار Chrome لنموذج Login ما زال غير موثوق آلياً بسبب Flutter Web.

## 6) الحكم النهائي

**التطبيق جاهز للانتقال إلى مرحلة الاختبار الوظيفي التالية على Emulator** (عمليات / حجوزات / تقارير)، بعد نجاح مسار:

`Splash → Login → Home → More → Logout → Login`
