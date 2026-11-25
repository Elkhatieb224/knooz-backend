@php
    $center = $center ?? null;
    $selected = $center?->services?->keyBy('id') ?? collect();
    $servicesByChannel = $servicesByChannel ?? collect();
    $channelLabels = $channelLabels ?? [];
    $offersCenter = filter_var(old('offers_center_service', $center?->offers_center_service ?? true), FILTER_VALIDATE_BOOLEAN);
    $offersHome = filter_var(old('offers_home_service', $center?->offers_home_service ?? true), FILTER_VALIDATE_BOOLEAN);
    $offersEmergency = filter_var(old('offers_emergency_service', $center?->offers_emergency_service ?? true), FILTER_VALIDATE_BOOLEAN);
@endphp
<div class="col-md-6">
    <label class="form-label">اسم المركز</label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $center?->name) }}" required>
</div>
<div class="col-md-3">
    <label class="form-label">النوع</label>
    <select name="type" class="form-select" required data-center-type>
        @foreach ($types as $value => $label)
            <option value="{{ $value }}" @selected(old('type', $center?->type?->value)===$value)>{{ $label }}</option>
        @endforeach
    </select>
</div>
<div class="col-md-3">
    <label class="form-label">الحالة</label>
    <select name="status" class="form-select" required>
        @foreach ($statuses as $value => $label)
            <option value="{{ $value }}" @selected(old('status', $center?->status?->value)===$value)>{{ $label }}</option>
        @endforeach
    </select>
</div>
<div class="col-12">
    <label class="form-label fw-bold">قنوات التنفيذ</label>
    <p class="small text-muted mb-2">حدد أين يعمل هذا المركز. الطلبات تصل فقط للمراكز التي تفعّل القناة المناسبة وتقدّم الخدمة.</p>
    <div class="row g-2">
        <div class="col-md-4">
            <div class="border rounded p-3 h-100">
                <input type="hidden" name="offers_center_service" value="0">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" name="offers_center_service" value="1" id="offers_center_service" data-channel-toggle="center" @checked($offersCenter)>
                    <label class="form-check-label fw-bold" for="offers_center_service">خدمة في المركز</label>
                </div>
                <div class="small text-muted mt-1">حجز موعد داخل الفرع (غسيل، زيوت، إطارات…)</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="border rounded p-3 h-100">
                <input type="hidden" name="offers_home_service" value="0">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" name="offers_home_service" value="1" id="offers_home_service" data-channel-toggle="home" @checked($offersHome)>
                    <label class="form-check-label fw-bold" for="offers_home_service">خدمة عند المنزل</label>
                </div>
                <div class="small text-muted mt-1">تنفيذ عند موقع العميل (منزل / مكتب)</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="border rounded p-3 h-100">
                <input type="hidden" name="offers_emergency_service" value="0">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" name="offers_emergency_service" value="1" id="offers_emergency_service" data-channel-toggle="emergency" @checked($offersEmergency)>
                    <label class="form-check-label fw-bold" for="offers_emergency_service">طوارئ الطريق</label>
                </div>
                <div class="small text-muted mt-1">بنشر / سطحة / طوارئ على الطريق</div>
            </div>
        </div>
    </div>
</div>
<div class="col-md-4">
    <label class="form-label">الهاتف</label>
    <input type="text" name="phone" class="form-control" value="{{ old('phone', $center?->phone) }}">
</div>
<div class="col-md-4">
    <label class="form-label">البريد</label>
    <input type="email" name="email" class="form-control" value="{{ old('email', $center?->email) }}">
</div>
<div class="col-md-4">
    <label class="form-label">المدينة</label>
    <input type="text" name="city" class="form-control" value="{{ old('city', $center?->city) }}">
</div>
<div class="col-12">
    <label class="form-label">موقع المركز على الخريطة <span class="text-muted fw-normal">(اختياري)</span></label>
    <p class="small text-muted mb-2">اختياري — يمكنك الحفظ بدون تحديد موقع، أو تحديده لاحقاً بالنقر/البحث على الخريطة.</p>
    @include('components.admin.location-map-picker', [
        'lat' => old('lat', $center?->lat),
        'lng' => old('lng', $center?->lng),
        'address' => old('address', $center?->address),
    ])
</div>
<div class="col-md-8">
    <label class="form-label">العنوان / اسم الموقع</label>
    <input type="text" name="address" class="form-control" value="{{ old('address', $center?->address) }}" placeholder="يُملأ تلقائياً من الخريطة ويمكن تعديله">
</div>
<div class="col-md-2">
    <label class="form-label">خط العرض <span class="text-muted fw-normal">(اختياري)</span></label>
    <input type="text" name="lat" class="form-control dir-ltr" value="{{ old('lat', $center?->lat) }}" readonly placeholder="—" autocomplete="off">
    @error('lat')<div class="text-danger small">{{ $message }}</div>@enderror
</div>
<div class="col-md-2">
    <label class="form-label">خط الطول <span class="text-muted fw-normal">(اختياري)</span></label>
    <input type="text" name="lng" class="form-control dir-ltr" value="{{ old('lng', $center?->lng) }}" readonly placeholder="—" autocomplete="off">
    @error('lng')<div class="text-danger small">{{ $message }}</div>@enderror
</div>
@if ($center)
<div class="col-md-6">
    <label class="form-label">مسؤول المركز</label>
    <select name="manager_center_user_id" class="form-select">
        <option value="">—</option>
        @foreach ($managers as $manager)
            <option value="{{ $manager->id }}" @selected(old('manager_center_user_id', $center?->manager_center_user_id)==$manager->id)>
                {{ $manager->name }} — {{ $manager->email }}
            </option>
        @endforeach
    </select>
    <div class="small text-muted mt-1">يمكن ربط مسؤول موجود بهذا المركز. لإنشاء مسؤول جديد استخدم شاشة موظفي المركز أو أنشئ المركز من جديد بحساب مسؤول.</div>
</div>
@else
<div class="col-12">
    <div class="border rounded p-3">
        <div class="fw-bold mb-1">حساب مسؤول المركز</div>
        <p class="small text-muted mb-3">يُنشأ حساب مدير لتسجيل الدخول إلى تطبيق ولوحة المركز.</p>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">اسم المسؤول</label>
                <input type="text" name="manager_name" class="form-control" value="{{ old('manager_name') }}" required>
                @error('manager_name')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">بريد المسؤول</label>
                <input type="email" name="manager_email" class="form-control" value="{{ old('manager_email') }}" required autocomplete="off">
                @error('manager_email')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">هاتف المسؤول <span class="text-muted fw-normal">(اختياري)</span></label>
                <input type="text" name="manager_phone" class="form-control" value="{{ old('manager_phone') }}" autocomplete="off">
                @error('manager_phone')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">كلمة المرور</label>
                <input type="password" name="manager_password" class="form-control" required autocomplete="new-password">
                @error('manager_password')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">تأكيد كلمة المرور</label>
                <input type="password" name="manager_password_confirmation" class="form-control" required autocomplete="new-password">
            </div>
        </div>
    </div>
</div>
@endif
<div class="col-12">
    <label class="form-label">إظهار الأرباح في تطبيق المركز</label>
    <div class="form-check form-switch mt-1">
        <input type="hidden" name="show_revenue" value="0">
        <input
            type="checkbox"
            class="form-check-input"
            name="show_revenue"
            value="1"
            id="show_revenue"
            data-revenue-toggle
            @checked((string) old('show_revenue', $center?->show_revenue ?? ($center?->type?->value === 'contracted' ? '1' : '0')) === '1')
        >
        <label class="form-check-label" for="show_revenue">
            يظهر لمدير المركز صافي الإيرادات بعد عمولة التطبيق. الافتراضي: مفعّل للمتعاقد وموقوف للمملوك. الموظفون لا يرون الأرباح.
        </label>
    </div>
</div>
<div class="col-12" data-product-commission-defaults>
    <label class="form-label fw-bold">عمولة التطبيق على منتجات المركز</label>
    <p class="small text-muted mb-2">
        عمولة عامة لهذا المركز تُطبَّق على كل منتجاته ما لم تُحدَّد عمولة مخصصة للمنتج من إدارة المتجر.
        الإدارة فقط هي من تضبط العمولات — المركز يحدد أسعار البيع فقط.
    </p>
    <div class="row g-2">
        <div class="col-md-4">
            <label class="form-label">نوع العمولة العامة</label>
            <select name="product_app_profit_mode" class="form-select" id="product_app_profit_mode">
                @foreach (\App\Support\CenterServiceCommission::modeLabels() as $value => $label)
                    <option value="{{ $value }}" @selected(old('product_app_profit_mode', $center?->product_app_profit_mode ?? 'fixed') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">قيمة العمولة العامة</label>
            <input type="number" step="0.01" min="0" name="product_app_profit" id="product_app_profit" class="form-control" value="{{ old('product_app_profit', $center?->product_app_profit) }}" placeholder="اتركه فارغاً = بدون عمولة عامة">
        </div>
        <div class="col-md-4 d-flex align-items-end">
            <div class="form-text" id="product_commission_hint">تُستخدم عند غياب عمولة مخصصة على المنتج.</div>
        </div>
    </div>
</div>
<div class="col-12" data-services-by-channel>
    <label class="form-label fw-bold">الخدمات حسب نوع التنفيذ</label>
    <p class="small text-muted mb-2">
        فعّل الخدمات التي يقدّمها المركز ضمن كل قناة. عمولة التطبيق تُحسب إما كمبلغ ثابت (ر.س) أو كنسبة مئوية من مبلغ العملية، ويُخصم الناتج من إيراد المركز.
    </p>
    @foreach ($channelLabels as $channelKey => $channelLabel)
        <div class="panel mb-2" data-channel-panel="{{ $channelKey }}" style="height:auto">
            <div class="panel-header py-2"><strong>{{ $channelLabel }}</strong></div>
            <div class="table-responsive">
                <table class="table table-admin mb-0">
                    <thead>
                        <tr>
                            <th>تفعيل</th>
                            <th>الخدمة</th>
                            <th>سعر الاتفاق</th>
                            <th style="min-width:220px">عمولة التطبيق</th>
                            <th>متاحة للطلب</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse (($servicesByChannel[$channelKey] ?? collect()) as $service)
                        @php
                            $pivot = $selected->get($service->id)?->pivot;
                            $profitMode = old("services.{$service->id}.app_profit_mode", $pivot->app_profit_mode ?? 'fixed');
                            $contractVal = old("services.{$service->id}.contract_price", $pivot->contract_price ?? $service->base_price);
                            $profitVal = old("services.{$service->id}.app_profit", $pivot->app_profit ?? 0);
                        @endphp
                        <tr data-commission-row>
                            <td><input type="checkbox" class="js-service-enabled" name="services[{{ $service->id }}][enabled]" value="1" data-service-channel="{{ $channelKey }}" @checked(old("services.{$service->id}.enabled", (bool)$pivot))></td>
                            <td>
                                <div class="fw-bold">{{ $service->name_ar }}</div>
                                @if ($service->kind_key)
                                    <div class="small text-muted">{{ $service->kind_key }}@if($service->flow_hint) · {{ $service->flow_hint }}@endif</div>
                                @endif
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" class="form-control form-control-sm js-contract-price" name="services[{{ $service->id }}][contract_price]" value="{{ $contractVal }}">
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1 align-items-center">
                                    <select class="form-select form-select-sm js-profit-mode" name="services[{{ $service->id }}][app_profit_mode]" style="max-width:118px">
                                        <option value="fixed" @selected($profitMode === 'fixed')>مبلغ ثابت</option>
                                        <option value="percent" @selected($profitMode === 'percent')>نسبة %</option>
                                    </select>
                                    <div class="input-group input-group-sm" style="max-width:130px">
                                        <input type="number" step="0.01" min="0" class="form-control js-profit-value" name="services[{{ $service->id }}][app_profit]" value="{{ $profitVal }}">
                                        <span class="input-group-text js-profit-suffix">{{ $profitMode === 'percent' ? '%' : 'ر.س' }}</span>
                                    </div>
                                </div>
                                <div class="small text-muted mt-1 js-profit-hint"></div>
                            </td>
                            <td><input type="checkbox" name="services[{{ $service->id }}][is_available]" value="1" @checked(old("services.{$service->id}.is_available", $pivot->is_available ?? true))></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted small">لا توجد خدمات معرفة لهذه القناة في الإعدادات.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
</div>

@push('scripts')
<script>
(function () {
  function syncPanels() {
    var anyOn = false;
    document.querySelectorAll('[data-channel-toggle]').forEach(function (toggle) {
      var channel = toggle.getAttribute('data-channel-toggle');
      var panel = document.querySelector('[data-channel-panel="' + channel + '"]');
      if (!panel) return;
      var on = toggle.checked;
      if (on) anyOn = true;
      panel.hidden = !on;
      panel.style.display = on ? '' : 'none';
      panel.querySelectorAll('input, select, button').forEach(function (el) {
        if (el === toggle) return;
        el.disabled = !on;
      });
    });
    var servicesBlock = document.querySelector('[data-services-by-channel]');
    if (servicesBlock) {
      servicesBlock.hidden = !anyOn;
      servicesBlock.style.display = anyOn ? '' : 'none';
    }
  }

  function syncCommissionRow(row) {
    var mode = row.querySelector('.js-profit-mode');
    var value = row.querySelector('.js-profit-value');
    var contract = row.querySelector('.js-contract-price');
    var suffix = row.querySelector('.js-profit-suffix');
    var hint = row.querySelector('.js-profit-hint');
    if (!mode || !value || !suffix || !hint) return;

    var isPercent = mode.value === 'percent';
    suffix.textContent = isPercent ? '%' : 'ر.س';
    value.max = isPercent ? '100' : '';

    var price = parseFloat(contract && contract.value ? contract.value : '0') || 0;
    var profit = parseFloat(value.value || '0') || 0;
    if (isPercent) {
      profit = Math.min(100, Math.max(0, profit));
      var amount = Math.round((price * profit / 100) * 100) / 100;
      var net = Math.max(0, Math.round((price - amount) * 100) / 100);
      hint.textContent = price > 0
        ? ('عمولة ≈ ' + amount.toFixed(2) + ' ر.س · صافي المركز ≈ ' + net.toFixed(2) + ' ر.س')
        : 'أدخل سعر الاتفاق لمعاينة العمولة';
    } else {
      var netFixed = Math.max(0, Math.round((price - profit) * 100) / 100);
      hint.textContent = 'عمولة ثابتة ' + profit.toFixed(2) + ' ر.س · صافي المركز ≈ ' + netFixed.toFixed(2) + ' ر.س';
    }
  }

  function bindCommissionRows() {
    document.querySelectorAll('[data-commission-row]').forEach(function (row) {
      syncCommissionRow(row);
      row.querySelectorAll('.js-profit-mode, .js-profit-value, .js-contract-price').forEach(function (el) {
        el.addEventListener('input', function () { syncCommissionRow(row); });
        el.addEventListener('change', function () { syncCommissionRow(row); });
      });
    });
  }

  document.querySelectorAll('[data-channel-toggle]').forEach(function (el) {
    el.addEventListener('change', syncPanels);
  });
  syncPanels();
  bindCommissionRows();

  // Default revenue visibility: contracted ON, owned OFF (admin can override).
  var typeSelect = document.querySelector('[data-center-type]');
  var revenueToggle = document.querySelector('[data-revenue-toggle]');
  var isEdit = {{ $center ? 'true' : 'false' }};
  if (typeSelect && revenueToggle && !isEdit) {
    function syncRevenueDefault() {
      revenueToggle.checked = typeSelect.value === 'contracted';
    }
    typeSelect.addEventListener('change', syncRevenueDefault);
  }
})();
</script>
@endpush
