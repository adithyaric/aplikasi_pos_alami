@extends('layouts.master')

@section('title', 'Penjualan Gudang')

@section('container')
    <section class="content-header">
        <h1>Penjualan Gudang</h1>
    </section>

    <section class="content">
        <div class="row" style="margin-bottom:10px;">
            <div class="col-xs-12">
                <div class="box box-default" style="margin-bottom:0;">
                    <div class="box-body" style="padding:10px 15px;">
                        <form method="GET" action="{{ route('penjualan.index') }}">
                            <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                                <label style="margin:0; white-space:nowrap;">Tanggal:</label>
                                <input type="hidden" name="period" value="daterange">
                                <input type="date" name="date_from" class="form-control input-sm" style="width:180px"
                                    value="{{ $dateFrom }}">
                                <span>s/d</span>
                                <input type="date" name="date_to" class="form-control input-sm" style="width:180px"
                                    value="{{ $dateTo }}">

                                <label style="margin:0 0 0 8px; white-space:nowrap;">Jenis Pembeli:</label>
                                <select name="buyer_type" class="form-control input-sm" style="width:180px">
                                    <option value="">Semua Jenis</option>
                                    @foreach ($buyerTypeOptions as $value => $label)
                                        <option value="{{ $value }}" {{ $selectedBuyerType === $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>

                                <select name="buyer_id" id="buyer_id_filter" class="form-control input-sm" style="width:220px">
                                    <option value="">Semua Pembeli</option>
                                    @foreach (($buyerOptionsByType[$selectedBuyerType] ?? []) as $buyer)
                                        <option value="{{ $buyer['id'] }}" {{ (string) $selectedBuyerId === (string) $buyer['id'] ? 'selected' : '' }}>
                                            {{ !empty($buyer['code']) ? $buyer['code'].' - ' : '' }}{{ $buyer['name'] }}
                                        </option>
                                    @endforeach
                                </select>

                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class="fa fa-filter"></i> Terapkan Filter
                                </button>

                                <a href="{{ route('penjualan.index') }}" class="btn btn-sm btn-default">
                                    <i class="fa fa-times"></i> Reset
                                </a>
                            </div>
                        </form>

                        <br>

                        <div class="row">
                            <div class="col-xs-12 col-sm-6 col-md-3">
                                <div class="info-box bg-red">
                                    <span class="info-box-icon"><i class="fa fa-warning"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Total Piutang</span>
                                        <span class="info-box-number">Rp {{ number_format($summary['totalPiutang'], 0, ',', '.') }}</span>
                                        <span class="progress-description">{{ $summary['countPiutang'] }} invoice belum lunas</span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xs-12 col-sm-6 col-md-3">
                                <div class="info-box bg-green">
                                    <span class="info-box-icon"><i class="fa fa-check-circle"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Total Lunas</span>
                                        <span class="info-box-number">Rp {{ number_format($summary['totalLunas'], 0, ',', '.') }}</span>
                                        <span class="progress-description">{{ $summary['countLunas'] }} invoice lunas</span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xs-12 col-sm-6 col-md-3">
                                <div class="info-box bg-aqua">
                                    <span class="info-box-icon"><i class="fa fa-tags"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Total Transaksi</span>
                                        <span class="info-box-number">{{ $summary['totalTransaksi'] }} invoice</span>
                                        <span class="progress-description">Rp {{ number_format($summary['totalTransaksiNominal'], 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xs-12 col-sm-6 col-md-3">
                                <div class="info-box bg-yellow">
                                    <span class="info-box-icon"><i class="fa fa-undo"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Potongan Retur</span>
                                        <span class="info-box-number">Rp {{ number_format($summary['totalPotonganRetur'], 0, ',', '.') }}</span>
                                        <span class="progress-description">{{ $summary['countPotonganRetur'] }} invoice terpotong</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xs-12">
                <div class="box">
                    <div class="box-header">
                        @if ($canCreatePenjualan)
                            <a href="{{ route('penjualan.create') }}" class="btn btn-sm bg-green">
                                <i class="fa fa-plus"></i> Tambah Penjualan
                            </a>
                        @endif
                    </div>
                    <div class="box-body table-responsive">
                        <table id="example1" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Kode</th>
                                    <th>Tanggal</th>
                                    <th>Jenis</th>
                                    <th>Pembeli</th>
                                    <th>Pembayaran</th>
                                    <th>Operator</th>
                                    <th>Total</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($penjualans as $penjualan)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $penjualan->code }}</td>
                                        <td>{{ optional($penjualan->sale_date ?? $penjualan->created_at)->format('d M Y') }}</td>
                                        <td>{{ $penjualan->buyer_type_label }}</td>
                                        <td>{{ $penjualan->buyer_display_name }}</td>
                                        <td>
                                            {{ strtoupper($penjualan->payment_type ?? '-') }}
                                            <br>
                                            <span class="label {{ $penjualan->payment_status === 'paid' ? 'label-success' : ($penjualan->payment_status === 'partial' ? 'label-warning' : 'label-default') }}">
                                                {{ strtoupper($penjualan->payment_status ?? '-') }}
                                            </span>
                                            @if (($penjualan->paymentTransaction?->amount ?? 0) > 0)
                                                <br><small>Dibayar: @currency($penjualan->paymentTransaction->amount)</small>
                                            @endif
                                        </td>
                                        <td>{{ $penjualan->operator?->name ?? '-' }}</td>
                                        <td>@currency($penjualan->total)</td>
                                        <td class="text-nowrap">
                                            <a class="btn btn-default btn-xs" href="{{ route('penjualan.show', $penjualan) }}">
                                                <i class="fa fa-eye"></i> Show
                                            </a>
                                            @if ($penjualan->payment_status != 'paid')
                                                <a class="btn btn-primary btn-xs" href="{{ route('penjualan.edit', $penjualan) }}">
                                                    <i class="fa fa-pencil"></i> Edit
                                                </a>
                                            @endif
                                            <a class="btn btn-success btn-xs" href="{{ route('penjualan.pembayaran.edit', $penjualan) }}">
                                                <i class="fa fa-credit-card"></i> Pembayaran
                                            </a>
                                            @if (in_array($penjualan->buyer_type, ['agent', 'canvas'], true))
                                                <a class="btn btn-warning btn-xs" href="{{ route('laporan.penjualan.invoice', $penjualan) }}">
                                                    <i class="fa fa-file-excel-o"></i> Print Invoice
                                                </a>
                                            @elseif ($penjualan->buyer_type === 'outlet')
                                                <a class="btn btn-info btn-xs" href="{{ route('laporan.penjualan.surat-jalan', $penjualan) }}">
                                                    <i class="fa fa-file-excel-o"></i> Print Surat Jalan
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('page-script')
<script>
    (function () {
        var options = @json($buyerOptionsByType);
        var $type = $('select[name="buyer_type"]');
        var $buyer = $('#buyer_id_filter');
        $type.on('change', function () {
            $buyer.empty().append(new Option('Semua Pembeli', ''));
            (options[$type.val()] || []).forEach(function (item) {
                $buyer.append(new Option((item.code ? item.code + ' - ' : '') + item.name, item.id));
            });
        });
    }());
</script>
@endsection
