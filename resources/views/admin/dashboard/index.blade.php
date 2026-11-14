@extends('layouts.admin')

@section('title', 'لوحة الرئيسية')
@section('breadcrumb', 'لوحة الرئيسية')

@section('content')
<div class="page-title">لوحة الرئيسية</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl">
        @include('components.admin.stat-card', ['label' => 'إجمالي المستخدمين', 'value' => $stats['users']['value'], 'change' => $stats['users']['change'], 'icon' => 'bi-people'])
    </div>
    <div class="col-6 col-xl">
        @include('components.admin.stat-card', ['label' => 'مراكز نشطة', 'value' => $stats['centers']['value'], 'change' => $stats['centers']['change'], 'icon' => 'bi-geo-alt'])
    </div>
    <div class="col-6 col-xl">
        @include('components.admin.stat-card', ['label' => 'شركات مسجلة', 'value' => $stats['companies']['value'], 'change' => $stats['companies']['change'], 'icon' => 'bi-building'])
    </div>
    @can('reports.financial')
    <div class="col-6 col-xl">
        @include('components.admin.stat-card', ['label' => 'إجمالي الإيرادات', 'value' => $stats['revenue']['value'], 'change' => $stats['revenue']['change'], 'icon' => 'bi-cash-coin', 'format' => 'money'])
    </div>
    @endcan
    <div class="col-6 col-xl">
        @include('components.admin.stat-card', ['label' => 'شكاوى مفتوحة', 'value' => $stats['complaints']['value'], 'change' => $stats['complaints']['change'], 'icon' => 'bi-exclamation-triangle'])
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-5">
        <div class="panel">
            <div class="panel-header">
                <strong>آخر العمليات</strong>
            </div>
            <div class="panel-body">
                @forelse ($recentOperations as $op)
                    <div class="ops-item">
                        <div class="ops-icon"><i class="bi bi-wrench"></i></div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-bold">{{ number_format((float) $op->amount, 0) }} ر.س</div>
                            <div class="small text-muted text-truncate">
                                {{ $op->vehicle?->plate_number ?? '—' }} · {{ $op->service?->name_ar ?? 'خدمة' }}
                            </div>
                        </div>
                        <div class="ops-meta">
                            @include('components.admin.status-badge', ['status' => $op->status])
                            <div class="small text-muted mt-1">{{ $op->created_at?->diffForHumans() }}</div>
                        </div>
                    </div>
                @empty
                    @include('components.admin.empty-state', ['message' => 'لا توجد عمليات حديثة'])
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="panel">
            <div class="panel-header">
                <strong>الإيرادات</strong>
                <div class="d-flex gap-1">
                    @foreach (['yearly' => 'سنوي', 'monthly' => 'شهري', 'weekly' => 'أسبوعي', 'daily' => 'يومي'] as $key => $label)
                        <button type="button" class="period-btn {{ $key === 'monthly' ? 'active' : '' }}" data-period="{{ $key }}">{{ $label }}</button>
                    @endforeach
                </div>
            </div>
            <div class="panel-body">
                <canvas id="revenueChart" height="140"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <div class="panel">
            <div class="panel-header"><strong>نشاط المستخدمين</strong></div>
            <div class="panel-body">
                <canvas id="activityChart" height="180"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="panel">
            <div class="panel-header"><strong>أسرع مراكز الخدمة</strong></div>
            <div class="panel-body">
                @forelse ($fastestCenters as $center)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span>{{ $center->name }}</span>
                        <span class="fw-bold text-primary">{{ $center->operations_count }}</span>
                    </div>
                @empty
                    @include('components.admin.empty-state', ['message' => 'لا توجد بيانات'])
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="panel">
            <div class="panel-header"><strong>توزيع الخدمات</strong></div>
            <div class="panel-body">
                <canvas id="servicesChart" height="180"></canvas>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const revenueData = @json($revenueChart);
const serviceData = @json($serviceDistribution);
const activityData = @json($userActivity);
const chartUrl = @json(route('admin.dashboard.revenue-chart'));

const primary = '#F97316';
const fill = getComputedStyle(document.documentElement).getPropertyValue('--chart-fill').trim() || 'rgba(249,115,22,.18)';

Chart.defaults.font.family = 'Cairo, sans-serif';
Chart.defaults.locale = 'ar';

const rtlChartOptions = {
    locale: 'ar',
    plugins: {
        legend: {
            rtl: true,
            textDirection: 'rtl',
            labels: { font: { family: 'Cairo, sans-serif' } }
        }
    }
};

const revenueChart = new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: {
        labels: revenueData.labels,
        datasets: [{
            data: revenueData.values,
            borderColor: primary,
            backgroundColor: fill,
            fill: true,
            tension: .35,
            pointRadius: 3,
        }]
    },
    options: {
        ...rtlChartOptions,
        plugins: { ...rtlChartOptions.plugins, legend: { display: false } },
        scales: {
            x: { grid: { display: false }, reverse: true, ticks: { font: { family: 'Cairo, sans-serif' } } },
            y: { beginAtZero: true, position: 'right', ticks: { font: { family: 'Cairo, sans-serif' } } }
        }
    }
});

new Chart(document.getElementById('servicesChart'), {
    type: 'doughnut',
    data: {
        labels: serviceData.labels,
        datasets: [{
            data: serviceData.values,
            backgroundColor: [primary, '#2563EB', '#16A34A', '#7C3AED', '#64748B']
        }]
    },
    options: {
        ...rtlChartOptions,
        plugins: {
            legend: { position: 'bottom', rtl: true, textDirection: 'rtl', labels: { font: { family: 'Cairo, sans-serif' } } }
        }
    }
});

new Chart(document.getElementById('activityChart'), {
    type: 'bar',
    data: {
        labels: activityData.labels,
        datasets: [{
            data: activityData.values,
            backgroundColor: primary,
            borderRadius: 8,
        }]
    },
    options: {
        ...rtlChartOptions,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false }, reverse: true, ticks: { font: { family: 'Cairo, sans-serif' } } },
            y: { beginAtZero: true, position: 'right', ticks: { stepSize: 1, font: { family: 'Cairo, sans-serif' } } }
        }
    }
});

document.querySelectorAll('[data-period]').forEach((btn) => {
    btn.addEventListener('click', async () => {
        document.querySelectorAll('[data-period]').forEach((b) => b.classList.remove('active'));
        btn.classList.add('active');
        const res = await fetch(chartUrl + '?period=' + btn.dataset.period, {
            headers: { Accept: 'application/json' }
        });
        const data = await res.json();
        revenueChart.data.labels = data.labels;
        revenueChart.data.datasets[0].data = data.values;
        revenueChart.update();
    });
});
</script>
@endpush
