@php
    $product = $product ?? null;
    $categories = \App\Enums\ShopProductCategory::options();
    $centers = $centers ?? collect();
    $meta = is_array($product?->meta) ? $product->meta : [];
    $metaNotes = old('meta_notes', $meta['notes'] ?? '');
    $profitMode = old('app_profit_mode', $product?->app_profit_mode ?? 'fixed');
@endphp
<div class="col-md-6">
    <label class="form-label">اسم المنتج (عربي)</label>
    <input type="text" name="name_ar" id="name_ar" class="form-control" value="{{ old('name_ar', $product?->name_ar) }}" required data-slug-source>
</div>
<div class="col-md-6">
    <label class="form-label">اسم المنتج (إنجليزي)</label>
    <input type="text" name="name_en" id="name_en" class="form-control" value="{{ old('name_en', $product?->name_en) }}" data-slug-source-en>
</div>
<div class="col-md-6">
    <label class="form-label">المركز المالك</label>
    <select name="center_id" class="form-select">
        <option value="">منتج المنصة (عام)</option>
        @foreach ($centers as $center)
            <option value="{{ $center->id }}" @selected((string) old('center_id', $product?->center_id) === (string) $center->id)>{{ $center->name }}</option>
        @endforeach
    </select>
    <div class="form-text">اتركه فارغاً للمنتجات العامة، أو اربطه بمركز يقدّم خدمة الفرع.</div>
</div>
<div class="col-12">
    @php $useCustom = (string) old('use_custom_app_profit', $product?->app_profit !== null ? '1' : '0') === '1'; @endphp
    <label class="form-label fw-bold">عمولة التطبيق على هذا المنتج</label>
    <p class="small text-muted mb-2">
        بدون تفعيل العمولة المخصصة يُستخدم <strong>إعداد العمولة العامة للمركز</strong> (من شاشة المركز).
        فعّل الخيار أدناه فقط إذا أردت عمولة مختلفة لهذا المنتج.
    </p>
    <div class="form-check form-switch mb-2">
        <input type="hidden" name="use_custom_app_profit" value="0">
        <input class="form-check-input" type="checkbox" role="switch" name="use_custom_app_profit" value="1" id="use_custom_app_profit" @checked($useCustom) data-custom-commission-toggle>
        <label class="form-check-label" for="use_custom_app_profit">عمولة مخصصة لهذا المنتج</label>
    </div>
    <div class="row g-2" data-custom-commission-fields style="{{ $useCustom ? '' : 'display:none' }}">
        <div class="col-md-4">
            <select name="app_profit_mode" class="form-select">
                @foreach (\App\Support\CenterServiceCommission::modeLabels() as $value => $label)
                    <option value="{{ $value }}" @selected($profitMode === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <input type="number" step="0.01" min="0" name="app_profit" class="form-control" value="{{ old('app_profit', $product?->app_profit) }}" placeholder="قيمة العمولة">
        </div>
    </div>
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
    <input type="text" name="brand" class="form-control" value="{{ old('brand', $product?->brand) }}" placeholder="مثال: Shell / Goodyear">
</div>
<div class="col-md-4">
    <label class="form-label">المقاس / الحجم</label>
    <input type="text" name="size" class="form-control" value="{{ old('size', $product?->size) }}" placeholder="مثال: 18 أو 4L">
</div>
<div class="col-md-4">
    <label class="form-label">السعر</label>
    <input type="number" step="0.01" min="0" name="price" class="form-control" value="{{ old('price', $product?->price) }}" required>
</div>
<div class="col-md-4">
    <label class="form-label">المخزون</label>
    <input type="number" min="0" name="stock" class="form-control" value="{{ old('stock', $product?->stock ?? 0) }}" required>
</div>
<div class="col-md-4">
    <label class="form-label">المعرّف (slug)</label>
    <input type="text" id="slug_preview" class="form-control dir-ltr" value="{{ old('slug', $product?->slug) }}" readonly tabindex="-1" placeholder="يُولَّد تلقائياً من الاسم">
    <div class="form-text">يُنشأ تلقائياً عند الحفظ ولا يمكن تعديله يدوياً.</div>
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
<div class="col-md-4 d-flex align-items-end">
    <div class="form-check mb-2">
        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $product?->is_active ?? true))>
        <label class="form-check-label" for="is_active">نشط (يظهر في التطبيق)</label>
    </div>
</div>
<div class="col-12">
    <label class="form-label">بيانات إضافية</label>
    <div class="form-text mb-2">نص حر منظم (عناوين، قوائم، فقرات) — يظهر كبيانات إضافية للمنتج.</div>
    <div id="meta_notes_editor" class="shop-rich-editor"></div>
    <textarea name="meta_notes" id="meta_notes" class="d-none">{{ $metaNotes }}</textarea>
</div>

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
<style>
.shop-rich-editor {
  min-height: 180px;
  background: var(--surface, #fff);
  border: 1px solid var(--border, #dee2e6);
  border-radius: 0 0 10px 10px;
}
.shop-rich-editor .ql-editor {
  min-height: 160px;
  font-family: Cairo, sans-serif;
  font-size: 0.95rem;
  line-height: 1.7;
  direction: rtl;
  text-align: right;
}
.ql-toolbar.ql-snow {
  border-radius: 10px 10px 0 0;
  border-color: var(--border, #dee2e6);
  background: var(--surface-2, #f8f9fa);
}
.ql-container.ql-snow {
  border-color: var(--border, #dee2e6);
  border-radius: 0 0 10px 10px;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
<script>
(function () {
  var editorEl = document.getElementById('meta_notes_editor');
  var hidden = document.getElementById('meta_notes');
  var slugPreview = document.getElementById('slug_preview');
  var nameAr = document.getElementById('name_ar');
  var nameEn = document.getElementById('name_en');
  var isEdit = {{ $product ? 'true' : 'false' }};

  if (editorEl && hidden && window.Quill) {
    var quill = new Quill(editorEl, {
      theme: 'snow',
      placeholder: 'اكتب التفاصيل الإضافية هنا…',
      modules: {
        toolbar: [
          [{ header: [2, 3, false] }],
          ['bold', 'italic', 'underline'],
          [{ list: 'ordered' }, { list: 'bullet' }],
          ['link'],
          ['clean']
        ]
      }
    });
    if (hidden.value.trim()) {
      quill.root.innerHTML = hidden.value;
    }
    var form = editorEl.closest('form');
    if (form) {
      form.addEventListener('submit', function () {
        var html = quill.root.innerHTML;
        var plain = quill.getText().trim();
        hidden.value = plain === '' ? '' : html;
      });
    }
  }

  function slugify(raw) {
    return String(raw || '')
      .trim()
      .toLowerCase()
      .replace(/[^\u0600-\u06FFa-z0-9\s\-]+/g, '')
      .replace(/\s+/g, '-')
      .replace(/-+/g, '-')
      .replace(/^-|-$/g, '');
  }

  function refreshSlugPreview() {
    if (!slugPreview || isEdit) return;
    var en = slugify(nameEn && nameEn.value);
    var ar = slugify(nameAr && nameAr.value);
    slugPreview.value = en || ar || '';
    slugPreview.placeholder = (en || ar) ? '' : 'يُولَّد تلقائياً من الاسم';
  }

  if (!isEdit) {
    if (nameAr) nameAr.addEventListener('input', refreshSlugPreview);
    if (nameEn) nameEn.addEventListener('input', refreshSlugPreview);
    refreshSlugPreview();
  }

  var customToggle = document.querySelector('[data-custom-commission-toggle]');
  var customFields = document.querySelector('[data-custom-commission-fields]');
  if (customToggle && customFields) {
    customToggle.addEventListener('change', function () {
      customFields.style.display = customToggle.checked ? '' : 'none';
    });
  }
})();
</script>
@endpush
