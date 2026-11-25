@extends('layouts.admin')
@section('title', 'المحتوى')
@section('breadcrumb', 'المحتوى')
@section('content')
<div class="page-title">محتوى التطبيقات</div>
<p class="text-muted mb-3">إدارة الصفحات التعريفية التي تظهر في تطبيق المستخدم (عن التطبيق، الشروط، الخصوصية، الأسئلة الشائعة).</p>
<div class="row g-3">
    @foreach ($pages as $key => $label)
        @php $item = $contents[$key] ?? null; @endphp
        <div class="col-md-6">
            <div class="panel h-auto">
                <div class="panel-header d-flex justify-content-between align-items-center">
                    <strong>{{ $label }}</strong>
                    <a href="{{ route('admin.content.edit', $key) }}" class="btn btn-sm btn-primary">تحرير</a>
                </div>
                <div class="panel-body">
                    <div class="small text-muted mb-1">العنوان: {{ $item['title'] ?? $label }}</div>
                    <div class="small text-muted">آخر تحديث: {{ $item['updated_at'] ?? '—' }}</div>
                    <div class="mt-2 text-truncate" style="max-height:3.5rem; overflow:hidden;">
                        {!! \Illuminate\Support\Str::limit(strip_tags($item['body'] ?? ''), 140) ?: '<span class="text-muted">لا يوجد محتوى بعد</span>' !!}
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
