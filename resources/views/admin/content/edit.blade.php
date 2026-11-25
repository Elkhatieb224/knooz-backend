@extends('layouts.admin')
@section('title', 'تحرير: '.$pageLabel)
@section('breadcrumb', 'تحرير المحتوى')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="page-title mb-0">{{ $pageLabel }}</div>
    <a href="{{ route('admin.content.index') }}" class="btn btn-outline-secondary">رجوع</a>
</div>
<div class="panel">
    <div class="panel-body">
        <form method="POST" action="{{ route('admin.content.update', $page) }}">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label class="form-label">العنوان</label>
                <input type="text" name="title" class="form-control" value="{{ old('title', $content['title']) }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">المحتوى</label>
                <div id="content_body_editor" class="shop-rich-editor"></div>
                <textarea name="body" id="content_body" class="d-none" required>{{ old('body', $content['body']) }}</textarea>
            </div>
            <button class="btn btn-primary">حفظ</button>
        </form>
    </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
<style>
.shop-rich-editor { min-height: 260px; background: #fff; border-radius: 0 0 10px 10px; }
.shop-rich-editor .ql-editor { min-height: 240px; direction: rtl; text-align: right; font-family: Cairo, sans-serif; }
.ql-toolbar.ql-snow { border-radius: 10px 10px 0 0; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
<script>
(function () {
  var editorEl = document.getElementById('content_body_editor');
  var hidden = document.getElementById('content_body');
  if (!editorEl || !hidden || !window.Quill) return;
  var quill = new Quill(editorEl, {
    theme: 'snow',
    modules: { toolbar: [[{ header: [2, 3, false] }], ['bold', 'italic', 'underline'], [{ list: 'ordered' }, { list: 'bullet' }], ['link'], ['clean']] }
  });
  if (hidden.value.trim()) quill.root.innerHTML = hidden.value;
  editorEl.closest('form').addEventListener('submit', function () {
    hidden.value = quill.root.innerHTML;
  });
})();
</script>
@endpush
