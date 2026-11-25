@extends('layouts.center')
@section('title', 'منتجات المركز')
@section('breadcrumb', 'المنتجات')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="page-title mb-0">منتجات المركز</div>
    <a href="{{ route('center.products.create') }}" class="btn btn-primary">إضافة منتج</a>
</div>
<form class="row g-2 mb-3" method="GET">
    <div class="col-md-5"><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="بحث بالاسم أو الماركة"></div>
    <div class="col-md-4">
        <select name="category" class="form-select">
            <option value="">كل التصنيفات</option>
            @foreach ($categories as $value => $label)
                <option value="{{ $value }}" @selected(request('category')===$value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3"><button class="btn btn-outline-primary w-100">تصفية</button></div>
</form>
<div class="panel">
    <div class="table-responsive">
        <table class="table table-admin mb-0">
            <thead>
                <tr>
                    <th>المنتج</th>
                    <th>التصنيف</th>
                    <th>السعر</th>
                    <th>عمولة التطبيق</th>
                    <th>صافي المركز</th>
                    <th>المخزون</th>
                    <th>الحالة</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    @php $breakdown = $product->profitBreakdown(); @endphp
                    <tr>
                        <td>
                            <div class="fw-bold">{{ $product->name_ar }}</div>
                            @if ($product->brand)
                                <div class="small text-muted">{{ $product->brand }}@if($product->size) · {{ $product->size }}@endif</div>
                            @endif
                        </td>
                        <td>{{ \App\Enums\ShopProductCategory::tryLabel($product->category) }}</td>
                        <td class="dir-ltr">{{ number_format((float) $product->price, 2) }}</td>
                        <td>
                            {{ \App\Support\CenterServiceCommission::formatPivot($breakdown['app_profit'], $breakdown['app_profit_mode']) }}
                            <div class="small text-muted">
                                @if ($breakdown['has_custom_commission'])
                                    مخصصة
                                @elseif ($breakdown['commission_source'] === 'center_default')
                                    عامة للمركز
                                @else
                                    غير محددة
                                @endif
                                · <span class="dir-ltr">{{ number_format($breakdown['app_commission'], 2) }} ر.س</span>
                            </div>
                        </td>
                        <td class="dir-ltr">{{ number_format($breakdown['center_net'], 2) }}</td>
                        <td class="dir-ltr">{{ $product->stock }}</td>
                        <td>@include('components.admin.status-badge', ['status' => $product->is_active ? 'active' : 'inactive', 'label' => $product->is_active ? 'نشط' : 'متوقف'])</td>
                        <td class="col-actions">
                            <a href="{{ route('center.products.edit', $product) }}" class="btn btn-sm btn-outline-primary">تعديل</a>
                            <form method="POST" action="{{ route('center.products.toggle', $product) }}" class="d-inline">
                                @csrf
                                <button class="btn btn-sm btn-outline-warning">{{ $product->is_active ? 'إيقاف' : 'تفعيل' }}</button>
                            </form>
                            <form method="POST" action="{{ route('center.products.destroy', $product) }}" class="d-inline" onsubmit="return confirm('حذف المنتج؟')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">حذف</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8">@include('components.admin.empty-state', ['message' => 'لا توجد منتجات. أضف منتجات مركزك مع السعر وعمولة التطبيق.'])</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
{{ $products->links() }}
@endsection
