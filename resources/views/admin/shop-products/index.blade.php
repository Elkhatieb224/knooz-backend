@extends('layouts.admin')
@section('title', 'منتجات المتجر')
@section('breadcrumb', 'المتجر')
@section('search_action', route('admin.shop-products.index'))
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="page-title mb-0">منتجات المتجر</div>
    <a href="{{ route('admin.shop-products.create') }}" class="btn btn-primary">إضافة منتج</a>
</div>
<form class="filters-bar row g-2" method="GET">
    <div class="col-md-4"><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="بحث بالاسم أو الماركة"></div>
    <div class="col-md-3">
        <select name="category" class="form-select">
            <option value="">كل التصنيفات</option>
            @foreach (\App\Enums\ShopProductCategory::options() as $value => $label)
                <option value="{{ $value }}" @selected(request('category')===$value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <select name="active" class="form-select">
            <option value="">كل الحالات</option>
            <option value="1" @selected(request('active')==='1')>نشط</option>
            <option value="0" @selected(request('active')==='0')>متوقف</option>
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-outline-knooz w-100">تصفية</button></div>
</form>
<div class="panel">
    <div class="table-responsive">
        <table class="table table-admin">
            <thead>
                <tr>
                    <th>المنتج</th>
                    <th>المركز</th>
                    <th>التصنيف</th>
                    <th>الماركة</th>
                    <th>السعر</th>
                    <th>عمولة التطبيق</th>
                    <th>المخزون</th>
                    <th>الحالة</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse ($products as $product)
                <tr>
                    <td class="fw-bold">{{ $product->name_ar }}</td>
                    <td>{{ $product->center?->name ?: 'المنصة' }}</td>
                    <td>{{ \App\Enums\ShopProductCategory::tryLabel($product->category) }}</td>
                    <td>{{ $product->brand ?: '—' }}</td>
                    <td class="dir-ltr">{{ number_format((float) $product->price, 2) }}</td>
                    <td>
                        @php $b = $product->profitBreakdown(); @endphp
                        {{ \App\Support\CenterServiceCommission::formatPivot($b['app_profit'], $b['app_profit_mode']) }}
                        <div class="small text-muted">
                            @if ($b['has_custom_commission']) مخصصة
                            @elseif ($b['commission_source'] === 'center_default') عامة للمركز
                            @else —
                            @endif
                        </div>
                    </td>
                    <td class="dir-ltr">{{ $product->stock }}</td>
                    <td>@include('components.admin.status-badge', ['status' => $product->is_active ? 'active' : 'inactive', 'label' => $product->is_active ? 'نشط' : 'متوقف'])</td>
                    <td class="col-actions">
                        <a href="{{ route('admin.shop-products.show', $product) }}" class="btn btn-sm btn-outline-secondary">عرض</a>
                        <a href="{{ route('admin.shop-products.edit', $product) }}" class="btn btn-sm btn-outline-primary">تعديل</a>
                        <form method="POST" action="{{ route('admin.shop-products.toggle', $product) }}" class="d-inline">
                            @csrf
                            <button class="btn btn-sm btn-outline-warning">{{ $product->is_active ? 'إيقاف' : 'تفعيل' }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9">@include('components.admin.empty-state', ['message' => 'لا توجد منتجات'])</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($products->hasPages())<div class="panel-body">{{ $products->links() }}</div>@endif
</div>
@endsection
