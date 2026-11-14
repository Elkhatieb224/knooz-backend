# هيكل صفحات تطبيق المراكز حسب Modules

## Modules

### M1 — Splash & Auth
| الشاشة | الوظيفة | ملاحظات |
|---|---|---|
| Splash (إن وُجدت) | إقلاع | غير مؤكدة Frame مستقل في Page 2 |
| Login | دخول مسؤول/موظف مركز | مؤكدة |
| Forgot Password | استعادة | جزئية |
| Logout | إنهاء الجلسة | من المزيد/الملف |

### M2 — Center Home
| الشاشة | الوظيفة |
|---|---|
| Service Center Dashboard | KPIs + طلبات واردة + عمليات حالية + إجراءات سريعة |
| Dashboard More Sheet/Menu | اختصارات: عملاء، تقارير مالية، شكاوى، إعدادات |

### M3 — Operations
| الشاشة | الوظيفة |
|---|---|
| Operations Tab / List | قائمة العمليات وحالاتها |
| Add Operation | اختيار QR / NFC / Manual |
| QR Scanner | مسح رمز العميل/المركبة |
| NFC Scanner | مسح لاسلكي |
| Manual Plate Entry | بحث بالمركبة عبر اللوحة |
| Operation Details | اختيار خدمات + دفع + بدء |
| Operation In Progress / Complete | إنهاء + توثيق |
| Identity / Customer Password | تحقق إضافي لمركبات الشركات (info.md + نصوص التصميم) |

### M4 — Incoming Requests (Bookings)
| الشاشة | الوظيفة |
|---|---|
| Incoming Requests | عرض الحجوزات الجديدة |
| Request Actions | قبول / رفض / بدء (على البطاقة) |
| Request Detail (إن وُجدت) | تفاصيل الموعد والعميل |

### M5 — Reports
| الشاشة | الوظيفة |
|---|---|
| Reports | إحصائيات وإيرادات وأداء |

### M6 — Customers
| الشاشة | الوظيفة |
|---|---|
| Customers Management | تتبع عمليات/تاريخ العملاء المرتبطين بالمركز |

### M7 — Complaints / Support
| الشاشة | الوظيفة |
|---|---|
| Complaints List/Form | شكاوى متعلقة بعمليات المركز أو دعم |

### M8 — Profile & Settings & Notifications
| الشاشة | الوظيفة |
|---|---|
| Profile | بيانات مسؤول المركز |
| Settings | إعدادات التطبيق/الإشعارات |
| Notifications | قائمة إشعارات المركز |

### M9 — Services (Center-scoped)
| الشاشة | الوظيفة | ثقة |
|---|---|---|
| Add/Manage Service | إضافة خدمة مخصصة / تفعيل خدمات | نصوص «إضافة خدمة» كثيرة — قد تكون ضمن تفاصيل العملية أو إدارة خدمات المركز |

## Bottom Navigation (مؤكد)
1. الرئيسية  
2. العمليات  
3. التقارير  
4. المزيد  

## علاقة مع أنظمة أخرى (ليست شاشات داخل التطبيق)
- Admin Dashboard: إدارة المركز وأسعاره وتعيين المدير
- Center Web Dashboard (`/center`): نسخة ويب جزئية لنفس المجال
- User App: مصدر الحجوزات والمركبات
