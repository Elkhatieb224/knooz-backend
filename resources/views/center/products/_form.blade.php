@php
    $product = $product ?? null;
    $center = auth('center')->user()?->center;
    $breakdown = $product
        ? $product->profitBreakdown(center: $center)
        : [
            'app_profit' => $center?->product_app_profit !== null ? (float) $center->product_app_profit : null,
            'app_profit_mode' => \App\Support\CenterServiceCommission::normalizeMode($center?->product_app_profit_mode),
            'app_commission' => 0,
            'center_net' => 0,
            'commission_source' => $center?->product_app_profit !== null ? 'center_default' : 'none',
            'has_custom_commission' => false,
        ];
@endphp
<div class="col-md-6">
    <label class="form-label">اسم المنتج (عربي)</label>
    <input type="text" name="name_ar" class="form-control" value="{{ old('name_ar', $product?->name_ar) }}" required>
</div>
<div class="col-md-6">
    <label class="form-label">اسم المنتج (إنجليزي)</label>
    <input type="text" name="name_en" class="form-control" value="{{ old('name_en', $product?->name_en) }}">
</div>
<div class="col-md-4">
    <label class="form-label">التصنيف</label>
    <select name="category" class="form-select" required>
        <option value="">اختر التصنيف</option>
        @foreach ($categories as $value => $label)
            <option value="{{ $value }}" @selected(old('category', $product?->category) === $value)>{{ $label }}</option>
        @endforeach
    </select>
</div>
<div class="col-md-4">
    <label class="form-label">الماركة</label>
    <input type="text" name="brand" class="form-control" value="{{ old('brand', $product?->brand) }}">
</div>
<div class="col-md-4">
    <label class="form-label">المقاس / الحجم</label>
    <input type="text" name="size" class="form-control" value="{{ old('size', $product?->size) }}">
</div>
<div class="col-md-4">
    <label class="form-label">سعر البيع (ر.س)</label>
    <input type="number" step="0.01" min="0" name="price" class="form-control" value="{{ old('price', $product?->price) }}" required data-product-price>
</div>
<div class="col-md-4">
    <label class="form-label">المخزون</label>
    <input type="number" min="0" name="stock" class="form-control" value="{{ old('stock', $product?->stock ?? 0) }}" required>
</div>
<div class="col-md-4 d-flex align-items-end">
    <div class="form-check mb-2">
        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $product?->is_active ?? true))>
        <label class="form-check-label" for="is_active">نشط (يظهر للعملاء)</label>
    </div>
</div>
<div class="col-12">
    <div class="border rounded p-3 bg-light">
        <div class="fw-bold mb-1">عمولة التطبيق (تحددها الإدارة)</div>
        <p class="small text-muted mb-2 mb-md-1">
            @if ($breakdown['has_custom_commission'])
                عمولة مخصصة لهذا المنتج من إدارة التطبيق.
            @elseif ($breakdown['commission_source'] === 'center_default')
                تُطبَّق العمولة العامة لهذا المركز.
            @else
                لم تُحدد الإدارة عمولة بعد — صافي المركز = سعر البيع كاملاً حتى يتم الضبط.
            @endif
        </p>
        <div class="row g-2 small">
            <div class="col-md-4">
                <span class="text-muted">القيمة:</span>
                <strong>{{ \App\Support\CenterServiceCommission::formatPivot($breakdown['app_profit'], $breakdown['app_profit_mode']) }}</strong>
            </div>
            <div class="col-md-8" data-commission-preview
                 data-mode="{{ $breakdown['app_profit_mode'] }}"
                 data-profit="{{ $breakdown['app_profit'] ?? '' }}">
                <span class="text-muted">معاينة عند السعر الحالي:</span>
                <strong class="dir-ltr" data-commission-text>
                    عمولة {{ number_format($breakdown['app_commission'], 2) }} ر.س · صافي {{ number_format($breakdown['center_net'], 2) }} ر.س
                </strong>
            </div>
        </div>
    </div>
</div>
<div class="col-12">
    <label class="form-label">الوصف</label>
    <textarea name="description" class="form-control" rows="3">{{ old('description', $product?->description) }}</textarea>
</div>
<div class="col-md-8">
    <label class="form-label">صورة المنتج</label>
    <input type="file" name="image" class="form-control" accept="image/*">
    @if ($product?->image_path)
        <div class="small text-muted mt-1">الحالية: {{ $product->image_path }}</div>
    @endif
</div>

@push('scripts')
<script>
(function () {
  var price = document.querySelector('[data-product-price]');
  var box = document.querySelector('[data-commission-preview]');
  if (!price || !box) return;
  var mode = box.getAttribute('data-mode') || 'fixed';
  var profitRaw = box.getAttribute('data-profit');
  var text = box.querySelector('[data-commission-text]');

  function sync() {
    var p = parseFloat(price.value || '0') || 0;
    if (profitRaw === '' || profitRaw === null) {
      text.textContent = 'عمولة 0.00 ر.س · صافي ' + p.toFixed(2) + ' ر.س';
      return;
    }
    var v = parseFloat(profitRaw) || 0;
    var commission = mode === 'percent'
      ? Math.round((p * Math.min(100, Math.max(0, v)) / 100) * 100) / 100
      : Math.round(v * 100) / 100;
    var net = Math.max(0, Math.round((p - commission) * 100) / 100);
    text.textContent = 'عمولة ' + commission.toFixed(2) + ' ر.س · صافي ' + net.toFixed(2) + ' ر.س';
  }

  price.addEventListener('input', sync);
  sync();
})();
</script>
@endpush
