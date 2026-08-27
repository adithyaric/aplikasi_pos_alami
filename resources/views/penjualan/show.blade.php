@extends('layouts.master')

@section('title', 'Detail Penjualan')

@section('container')
    @php
        $hasSalesLocation = $penjualan->isBranchSale()
            && is_numeric($penjualan->latitude)
            && is_numeric($penjualan->longitude);
        $salesLatitude = $hasSalesLocation ? (float) $penjualan->latitude : null;
        $salesLongitude = $hasSalesLocation ? (float) $penjualan->longitude : null;
        $salesMapPadding = 0.005;
        $salesOsmEmbedUrl = $hasSalesLocation
            ? 'https://www.openstreetmap.org/export/embed.html?bbox='
                .rawurlencode(implode(',', [
                    $salesLongitude - $salesMapPadding,
                    $salesLatitude - $salesMapPadding,
                    $salesLongitude + $salesMapPadding,
                    $salesLatitude + $salesMapPadding,
                ]))
                .'&layer=mapnik&marker='.rawurlencode($salesLatitude.','.$salesLongitude)
            : null;
        $salesOsmUrl = $hasSalesLocation
            ? 'https://www.openstreetmap.org/?mlat='.rawurlencode((string) $salesLatitude)
                .'&mlon='.rawurlencode((string) $salesLongitude)
                .'#map=17/'.rawurlencode((string) $salesLatitude).'/'.rawurlencode((string) $salesLongitude)
            : null;
    @endphp
    <section class="content-header">
        <h1>Detail Penjualan</h1>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-md-4">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">Informasi Penjualan</h3>
                    </div>
                    <div class="box-body">
                        <table class="table table-bordered">
                            <tr>
                                <th>Kode</th>
                                <td>{{ $penjualan->code }}</td>
                            </tr>
                            <tr>
                                <th>Tanggal</th>
                                <td>{{ optional($penjualan->sale_date ?? $penjualan->created_at)->format('d M Y') }}</td>
                            </tr>
                            <tr>
                                <th>Jenis Pembeli</th>
                                <td>{{ $penjualan->buyer_type_label }}</td>
                            </tr>
                            <tr>
                                <th>Pembeli</th>
                                <td>{{ $penjualan->buyer_display_name }}</td>
                            </tr>
                            <tr>
                                <th>Alamat</th>
                                <td>{{ $penjualan->buyer_address ?: '-' }}</td>
                            </tr>
                            <tr>
                                <th>No. Telp</th>
                                <td>{{ $penjualan->buyer_phone ?: '-' }}</td>
                            </tr>
                            <tr>
                                <th>Pembayaran</th>
                                <td>
                                    {{ strtoupper($penjualan->payment_type ?? '-') }}
                                    /
                                    {{ strtoupper($penjualan->payment_status ?? '-') }}
                                    @if (($penjualan->paymentTransaction?->amount ?? 0) > 0)
                                        <br><small>Dibayar: @currency($penjualan->paymentTransaction->amount)</small>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Operator</th>
                                <td>{{ $penjualan->operator?->name ?? '-' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                @if ($penjualan->isBranchSale() && ($penjualan->photos->isNotEmpty() || $hasSalesLocation))
                    <div class="box box-info">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="fa fa-camera"></i> Dokumentasi Penjualan</h3>
                        </div>
                        <div class="box-body">
                            @if ($penjualan->photos->isNotEmpty())
                                <div class="sales-photo-preview">
                                    @foreach ($penjualan->photos as $photo)
                                        <a href="{{ asset('storage/'.$photo->path) }}" target="_blank" rel="noopener" class="sales-photo-thumb">
                                            <img src="{{ asset('storage/'.$photo->path) }}" alt="{{ $photo->original_name ?: 'Foto penjualan' }}">
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-muted">Tidak ada foto penjualan.</p>
                            @endif

                            @if ($hasSalesLocation)
                                <hr>
                                <p>
                                    <i class="fa fa-map-marker"></i>
                                    Lokasi: <strong>{{ number_format($salesLatitude, 7, '.', '') }}, {{ number_format($salesLongitude, 7, '.', '') }}</strong>
                                    @if ($penjualan->location_accuracy)
                                        <small class="text-muted">(akurasi ±{{ number_format((float) $penjualan->location_accuracy, 0, ',', '.') }} m)</small>
                                    @endif
                                </p>
                                <iframe title="Peta OpenStreetMap lokasi penjualan" loading="lazy" src="{{ $salesOsmEmbedUrl }}"
                                    style="width:100%;height:220px;border:1px solid #ddd;border-radius:4px;"></iframe>
                                <span class="small text-muted">© OpenStreetMap contributors · </span>
                                <a href="{{ $salesOsmUrl }}" target="_blank" rel="noopener" class="small">Buka di OpenStreetMap</a>
                                @if ($penjualan->location_captured_at)
                                    <p class="text-muted small" style="margin:6px 0 0;">Diambil {{ $penjualan->location_captured_at->format('d/m/Y H:i') }}</p>
                                @endif
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <div class="col-md-8">
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">Item Penjualan</h3>
                    </div>
                    <div class="box-body table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Produk</th>
                                    <th>Qty Input</th>
                                    <th>Qty Database</th>
                                    <th>Diskon / Item</th>
                                    <th>Harga</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $subtotal = 0;
                                    $itemDiscountTotal = 0;
                                    $returnAdjustment = $penjualan->totalAdjustments->sum('amount');
                                @endphp
                                @foreach ($penjualan->items as $item)
                                    @php
                                        $subtotal += (int) $item->subtotal + (int) ($item->discount ?? 0);
                                        $itemDiscountTotal += (int) ($item->discount ?? 0);
                                    @endphp
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $item->product?->name ?? '-' }}</td>
                                        <td>
                                            {{ number_format((float) ($item->qty_input ?? $item->qty), 0, ',', '.') }}
                                            {{ $item->unit ?? $item->product?->satuan ?? '' }}
                                        </td>
                                        <td>{{ $item->product?->qtyDisplay((int) $item->qty) ?? $item->qty }}</td>
                                        <td>@currency($item->discount ?? 0)</td>
                                        <td>
                                            @currency($item->price)
                                            <small class="text-muted">/ {{ $item->product?->satuan ?? 'unit' }}</small>
                                        </td>
                                        <td>@currency($item->subtotal)</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="6" class="text-right">Subtotal Sebelum Diskon</th>
                                    <th>@currency($subtotal)</th>
                                </tr>
                                <tr>
                                    <th colspan="6" class="text-right">Total Diskon Item</th>
                                    <th>@currency($itemDiscountTotal + (int) ($penjualan->discount ?? 0))</th>
                                </tr>
                                @if ($returnAdjustment > 0)
                                    <tr>
                                        <th colspan="6" class="text-right">Potongan Retur</th>
                                        <th class="text-danger">- @currency($returnAdjustment)</th>
                                    </tr>
                                @endif
                                <tr>
                                    <th colspan="6" class="text-right">Total</th>
                                    <th>@currency($penjualan->total)</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="box-footer">
                        <a href="{{ $backRoute ?? route('penjualan.index') }}" class="btn btn-default">Kembali</a>
                        @if ($penjualan->payment_status != 'paid' && ($penjualan->isWarehouseSale() || in_array(auth()->user()?->role, ['admin-cabang', 'leader-cabang', 'sales'], true)))
                        <a href="{{ route('penjualan.edit', $penjualan) }}" class="btn btn-primary">
                            <i class="fa fa-pencil"></i> Edit
                        </a>
                        @endif
                        @if (($penjualan->isWarehouseSale() && ! in_array(auth()->user()?->role, ['admin-cabang', 'sales'], true)) || $penjualan->isBranchSale())
                        <a href="{{ route('penjualan.pembayaran.edit', $penjualan) }}" class="btn btn-success">
                            <i class="fa fa-credit-card"></i> Pembayaran
                        </a>
                        @endif
                        {{-- <a href="{{ route('refund.create', ['penjualan_id' => $penjualan->id]) }}" class="btn btn-danger"> --}}
                            {{-- <i class="fa fa-undo"></i> Retur --}}
                        {{-- </a> --}}
                        @if (in_array($penjualan->buyer_type, ['agent', 'canvas'], true))
                        <a href="{{ route('laporan.penjualan.invoice', $penjualan) }}" class="btn btn-warning">
                            <i class="fa fa-file-excel-o"></i> Print Invoice
                        </a>
                        @elseif ($penjualan->buyer_type === 'outlet')
                        <a href="{{ route('laporan.penjualan.surat-jalan', $penjualan) }}" class="btn btn-info">
                            <i class="fa fa-file-excel-o"></i> Print Surat Jalan
                        </a>
                        @elseif ($penjualan->isBranchSale() && $penjualan->buyer_type === 'toko')
                        <a href="{{ route('laporan.penjualan.nota', $penjualan) }}" class="btn btn-default">
                            <i class="fa fa-print"></i> Print Nota
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
