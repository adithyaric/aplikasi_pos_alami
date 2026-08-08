@extends('layouts.master')

@section('title', 'Detail Penjualan')

@section('container')
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
                                            {{ rtrim(rtrim(number_format((float) ($item->qty_input ?? $item->qty), 2, ',', '.'), '0'), ',') }}
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
                        @if ($penjualan->payment_status != 'paid' && ($penjualan->isWarehouseSale() || auth()->user()?->role === 'sales'))
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
                        @if ($penjualan->isBranchSale())
                        <a href="{{ route('laporan.penjualan.invoice', $penjualan) }}" class="btn btn-warning">
                            <i class="fa fa-print"></i> Print Nota
                        </a>
                        @elseif ($penjualan->buyer_type_label != 'Cabang')
                        <a href="{{ route('laporan.penjualan.invoice', $penjualan) }}" class="btn btn-warning">
                            <i class="fa fa-file-excel-o"></i> Invoice
                        </a>
                        @else
                        @if (! in_array(auth()->user()?->role, ['admin-cabang', 'sales'], true))
                        <a href="{{ route('laporan.penjualan.surat-jalan', $penjualan) }}" class="btn btn-info">
                            <i class="fa fa-file-excel-o"></i> Surat Jalan
                        </a>
                        @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
