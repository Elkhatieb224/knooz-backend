@php $product = $product ?? null; @endphp
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
        <option value="tires" @selected(old('category', $product?->category) === 'tires')>إطارات</option>
        <option value="oils" @selected(old('category', $product?->category) === 'oils')>زيوت</option>
    </select>
</div>
<div class="col-md-4">
    <label class="form-label">الماركة</label>
    <input type="text" name="brand" class="form-control" value="{{ old('brand', $product?->brand) }}" placeholder="مثال: اختبار">
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
    <input type="text" name="slug" class="form-control" value="{{ old('slug', $product?->slug) }}" placeholder="يُولَّد تلقائياً إن تُرك فارغاً">
</div>
<div class="col-12">
    <label class="form-label">الوصف</label>
    <textarea name="description" class="form-control" rows="3">{{ old('description', $product?->description) }}</textarea>
</div>
<div class="col-md-8">
    <label class="form-label">مسار الصورة</label>
    <input type="text" name="image_path" class="form-control" value="{{ old('image_path', $product?->image_path) }}" placeholder="اختياري — مثال: shop/tire.png">
</div>
<div class="col-md-4 d-flex align-items-end">
    <div class="form-check mb-2">
        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $product?->is_active ?? true))>
        <label class="form-check-label" for="is_active">نشط (يظهر في التطبيق)</label>
    </div>
</div>
<div class="col-12">
    <label class="form-label">بيانات إضافية (JSON اختياري)</label>
    <textarea name="meta" class="form-control font-monospace" rows="3" placeholder='{"type":"نصف صناعي","badge":"جديد"}'>{{ old('meta', $product?->meta ? json_encode($product->meta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '') }}</textarea>
</div>
