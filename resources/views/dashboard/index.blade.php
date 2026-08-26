@extends('layouts.master')

@section('title', 'Dashboard')

@section('container')
    <section class="content-header">
        <h1>Selamat Datang di Gudang, {{ ucfirst(auth()->user()->name) }}!</h1>
        <ol class="breadcrumb"><li class="active">Dashboard</li></ol>
    </section>

    <section class="content">
        @if($isStaffOutletDashboard ?? false)
            @if($showLegacyDistributionFlow)
                <div class="row">
                    <div class="col-md-6"><div class="small-box bg-aqua"><div class="inner"><h3>{{ number_format($outletRequestTotal ?? 0) }}</h3><p>Total Jumlah Data Cabang Minta Gudang</p></div><div class="icon"><i class="fa fa-list-alt"></i></div><a href="{{ route('request-orders.index') }}" class="small-box-footer">Lihat Detail <i class="fa fa-arrow-circle-right"></i></a></div></div>
                    <div class="col-md-6"><div class="small-box bg-yellow"><div class="inner"><h3>{{ number_format($outletRequestPending ?? 0) }}</h3><p>Total Cabang Minta Gudang Status Pending</p></div><div class="icon"><i class="fa fa-clock-o"></i></div><a href="{{ route('request-orders.index') }}" class="small-box-footer">Lihat Detail <i class="fa fa-arrow-circle-right"></i></a></div></div>
                </div>
            @else
                <div class="alert alert-info">Flow lama <strong>Request Cabang / Picking / Delivery</strong> sudah disembunyikan dari menu utama.</div>
            @endif
        @else
            @if($showLegacyDistributionFlow)
                <div class="row">
                    <div class="col-md-6"><div class="small-box bg-yellow"><div class="inner"><h3>{{ number_format($pendingOrdersCount) }}</h3><p>Pending Order</p></div><div class="icon"><i class="fa fa-clock-o"></i></div><a href="{{ route('request-orders.index') }}" class="small-box-footer">Lihat Detail <i class="fa fa-arrow-circle-right"></i></a></div></div>
                    <div class="col-md-6"><div class="small-box bg-green"><div class="inner"><h3>{{ number_format($deliveredCount) }}</h3><p>Order Terkirim</p></div><div class="icon"><i class="fa fa-truck"></i></div><a href="{{ route('delivery-orders.index') }}" class="small-box-footer">Lihat Detail <i class="fa fa-arrow-circle-right"></i></a></div></div>
                </div>
            @endif

            {{-- Cabang and Sales intentionally come before Agen and Canvas. --}}
            <div class="row">
                @foreach([
                    'branch' => ['label' => 'Cabang', 'color' => 'yellow', 'icon' => 'fa-building'],
                    'sales' => ['label' => 'Sales', 'color' => 'purple', 'icon' => 'fa-user'],
                    'agent' => ['label' => 'Agen', 'color' => 'green', 'icon' => 'fa-users'],
                    'canvas' => ['label' => 'Canvas', 'color' => 'aqua', 'icon' => 'fa-truck'],
                ] as $group => $meta)
                    @php
                        $card = $distributionDashboard['cards'][$group] ?? ['sales' => 0, 'receivable' => 0];
                    @endphp
                    <div class="col-md-3 col-sm-6">
                        <div class="small-box bg-{{ $meta['color'] }}">
                            <div class="inner">
                                <p>Total Penjualan Bulan Ini ({{ $meta['label'] }})</p>
                                <h3>Rp {{ number_format($card['sales'], 0, ',', '.') }}</h3>
                                <p>Total Piutang ({{ $meta['label'] }})</p>
                                <h4>Rp {{ number_format($card['receivable'], 0, ',', '.') }}</h4>
                            </div>
                            <div class="icon"><i class="fa {{ $meta['icon'] }}"></i></div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="row">
                @foreach([
                    'branch' => 'Cabang',
                    'sales' => 'Sales',
                    'agent' => 'Agen',
                    'canvas' => 'Canvas',
                ] as $group => $label)
                    <div class="col-md-6">
                        <div class="box box-default">
                            <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-bar-chart"></i> Penjualan &amp; Pembayaran Piutang {{ $label }}</h3></div>
                            <div class="box-body"><div id="chartDistribution{{ ucfirst($group) }}" style="min-height:240px"></div></div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="row">
                <div class="col-xs-12">
                    <div class="box box-primary">
                        <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-line-chart"></i> Trend Penjualan (Penjualan Terbanyak Semua Produk)</h3></div>
                        <div class="box-body"><div id="chartSalesTrend" style="min-height:300px"></div></div>
                    </div>
                </div>
            </div>

            @if($showLegacyDistributionFlow)
                <div class="row">
                    <div class="col-xs-12">
                        <div class="box box-default">
                            <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-list-alt"></i> Pesanan Terbaru</h3><div class="box-tools pull-right"><a href="{{ route('request-orders.index') }}" class="btn btn-xs btn-default">Lihat Semua</a></div></div>
                            <div class="box-body table-responsive no-padding">
                                <table class="table table-hover">
                                    <thead><tr><th>ID Pesanan</th><th>Cabang</th><th>Status</th><th>Tanggal</th></tr></thead>
                                    <tbody>
                                    @forelse($recentOrders as $order)
                                        @php
                                            $statusMap = [
                                                'pending' => ['label-warning', 'Pending'],
                                                'approved' => ['label-success', 'Disetujui'],
                                                'partial' => ['label-info', 'Sebagian'],
                                                'rejected' => ['label-danger', 'Ditolak'],
                                            ];
                                            [$cls, $lbl] = $statusMap[$order->status] ?? ['label-default', $order->status];
                                        @endphp
                                        <tr><td><strong>#{{ $order->code ?? $order->id }}</strong></td><td>{{ $order->owner?->name ?? '—' }}</td><td><span class="label {{ $cls }}">{{ $lbl }}</span></td><td>{{ $order->created_at->format('d M Y') }}</td></tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted">Belum ada pesanan</td></tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endif
    </section>
@endsection

@section('page-script')
    @if(!($isStaffOutletDashboard ?? false))
        <script src="https://cdnjs.cloudflare.com/ajax/libs/highcharts/10.3.3/highcharts.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/highcharts/10.3.3/modules/accessibility.min.js"></script>
        <script>
            Highcharts.chart('chartSalesTrend', {
                chart: { type: 'column', backgroundColor: 'transparent' },
                title: { text: null },
                credits: { enabled: false },
                xAxis: {
                    categories: {!! json_encode($salesTrendChart->map(fn($item) => $item->product?->name ?? '—')->values()->toArray()) !!},
                    labels: { style: { fontSize: '11px' } }
                },
                yAxis: { min: 0, title: { text: 'Jumlah Terjual' } },
                plotOptions: { column: { colorByPoint: true, dataLabels: { enabled: true } } },
                tooltip: { formatter: function () { return '<b>' + this.x + '</b><br/>Terjual: <b>' + Highcharts.numberFormat(this.y, 0) + '</b>'; } },
                series: [{
                    name: 'Penjualan',
                    data: {!! json_encode($salesTrendChart->map(fn($item) => (float) $item->total_qty)->values()->toArray()) !!}
                }],
                legend: { enabled: false }
            });

            const distributionDashboard = @json($distributionDashboard);
            Object.keys(distributionDashboard.charts || {}).forEach(function (group) {
                const chart = distributionDashboard.charts[group] || { sales: [], payments: [] };
                Highcharts.chart('chartDistribution' + group.charAt(0).toUpperCase() + group.slice(1), {
                    chart: { type: 'column', backgroundColor: 'transparent' },
                    title: { text: null },
                    credits: { enabled: false },
                    xAxis: { categories: distributionDashboard.labels || [] },
                    yAxis: { min: 0, title: { text: 'Nominal (Rp)' } },
                    tooltip: { shared: true, valuePrefix: 'Rp ', valueDecimals: 0 },
                    series: [
                        { name: 'Penjualan', data: chart.sales || [], color: '#00a65a' },
                        { name: 'Pembayaran Piutang', data: chart.payments || [], color: '#3c8dbc' }
                    ]
                });
            });
        </script>
    @endif
@endsection
