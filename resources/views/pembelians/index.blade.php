@extends('layouts.master')

@section('title', 'Purchase Order')

@section('container')
    <section class="content-header">
        <h1>Purchase Order <small>Gudang → Supplier</small></h1>
    </section>

    <section class="content">
        {{-- Filter Periode --}}
        <div class="row" style="margin-bottom:10px;">
            <div class="col-xs-12">
                <div class="box box-default" style="margin-bottom:0;">
                    <div class="box-body" style="padding:10px 15px;">
                        <form method="GET" action="{{ route('pembelian.index') }}" id="filterForm">
                            <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                                <label style="margin:0; white-space:nowrap;">Periode:</label>

                                {{-- Daterange picker --}}
                                <div class="input-group" style="width:260px;">
                                    <input type="text" id="daterangepicker" class="form-control input-sm"
                                        placeholder="Pilih rentang tanggal"
                                        value="{{ $dateFrom && $dateTo ? $dateFrom . ' - ' . $dateTo : '' }}"
                                        readonly style="background:#fff; cursor:pointer;">
                                    <span class="input-group-addon" style="cursor:pointer;" onclick="$('#daterangepicker').trigger('click')">
                                        <i class="fa fa-calendar"></i>
                                    </span>
                                </div>

                                <input type="hidden" name="period" id="hiddenPeriod" value="{{ $filterPeriod }}">
                                <input type="hidden" name="date_from" id="inputDateFrom" value="{{ $dateFrom }}">
                                <input type="hidden" name="date_to" id="inputDateTo" value="{{ $dateTo }}">

                                <label style="margin:0 0 0 8px; white-space:nowrap;">Supplier:</label>
                                <select name="supplier_id" class="form-control input-sm" style="width:220px">
                                    <option value="">Semua Supplier</option>
                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" {{ (string) $supplierId === (string) $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                                    @endforeach
                                </select>

                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class="fa fa-filter"></i> Terapkan Filter
                                </button>

                                <a href="{{ route('pembelian.index') }}" class="btn btn-sm btn-default">
                                    <i class="fa fa-times"></i> Reset
                                </a>

                                {{-- Label periode aktif --}}
                                @if($filterPeriod !== 'all')
                                    <span class="label label-info" style="font-size:12px; padding:5px 8px;">
                                        @if($filterPeriod === 'hari') Hari Ini
                                        @elseif($filterPeriod === 'minggu') Minggu Ini
                                        @elseif($filterPeriod === 'bulan') Bulan Ini
                                        @elseif($filterPeriod === 'daterange') {{ $dateFrom }} s/d {{ $dateTo }}
                                        @endif
                                    </span>
                                @endif
                            </div>
                        </form>
                        <br>
                        {{-- Summary Cards --}}
                        <div class="row">
                            {{-- Total Piutang --}}
                            <div class="col-xs-12 col-sm-6 col-md-3">
                                <div class="info-box bg-red">
                                    <span class="info-box-icon"><i class="fa fa-warning"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Total Piutang</span>
                                        <span class="info-box-number">
                                            Rp {{ number_format($totalPiutang, 0, ',', '.') }}
                                        </span>
                                        <span class="progress-description">
                                            {{ $countPiutang }} PO belum lunas
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {{-- Total Lunas --}}
                            <div class="col-xs-12 col-sm-6 col-md-3">
                                <div class="info-box bg-green">
                                    <span class="info-box-icon"><i class="fa fa-check-circle"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Total Lunas</span>
                                        <span class="info-box-number">
                                            Rp {{ number_format($totalLunas, 0, ',', '.') }}
                                        </span>
                                        <span class="progress-description">
                                            {{ $countLunas }} PO lunas
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {{-- Jumlah Transaksi --}}
                            <div class="col-xs-12 col-sm-6 col-md-3">
                                <div class="info-box bg-aqua">
                                    <span class="info-box-icon"><i class="fa fa-shopping-cart"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Total Transaksi</span>
                                        <span class="info-box-number">
                                            {{ $totalTransaksi }} PO
                                        </span>
                                        <span class="progress-description">
                                            Rp {{ number_format($totalTransaksiNominal, 0, ',', '.') }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {{-- Jumlah Produk di-PO --}}
                            <div class="col-xs-12 col-sm-6 col-md-3">
                                <div class="info-box bg-yellow">
                                    <span class="info-box-icon"><i class="fa fa-cubes"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Produk di-PO</span>
                                        <span class="info-box-number">
                                            {{ $totalQtyItems }} jenis
                                        </span>
                                        <span class="progress-description">
                                            {{ $totalQtyLines }} baris PO
                                        </span>
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
                        @if (auth()->user()->role !== 'owner')
                            <a href="{{ route('pembelian.create') }}" class="btn btn-md bg-green">
                                <i class="fa fa-plus"></i> Buat PO Baru
                            </a>
                        @endif
                        {{-- <a href="{{ route('refundPembelian.index') }}" class="btn btn-md bg-green"> --}}
                            {{-- <i class="fa fa-refresh"></i> Refund PO --}}
                        {{-- </a> --}}
                    </div>
                    <div class="box-body table-responsive">
                        <table id="example1" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th width="40">No</th>
                                    <th>Kode PO</th>
                                    <th>Customer PO</th>
                                    <th>Supplier</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th width="120">Status PO</th>
                                    <th width="120">Status Bayar</th>
                                    <th width="260">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pembelians as $value)
                                    @php
                                        $payStatus = $value->pembelianTransaction?->status ?? 'unpaid';
                                        $payBadge = match ($payStatus) {
                                            'paid' => 'success',
                                            'partial' => 'warning',
                                            default => 'danger',
                                        };
                                    @endphp
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td><strong>{{ $value->code }}</strong></td>
                                        <td>{{ $value->customer_po ?: '-' }}</td>
                                        <td>{{ $value->supplier?->name }}</td>
                                        <td>
                                            @php $totalItems = $value->pembelianProducts->count(); @endphp
                                            <ul class="list-unstyled" style="margin:0">
                                                @foreach ($value->pembelianProducts as $index => $item)
                                                    <li class="item-pembelian-{{ $value->id }} @if($index >= 3) extra-item-{{ $value->id }} @endif"
                                                        @if($index >= 3) style="display:none" @endif>
                                                        <small>
                                                            {{ $item->product?->name }} |
                                                            @php $k = $item->product?->konversiDisplay($item->qty); @endphp
                                                            @if($k && $k !== '-')
                                                                <span class="label label-info">{{ $k }}</span>
                                                            @endif
                                                        </small>
                                                    </li>
                                                @endforeach
                                            </ul>

                                            @if($totalItems > 3)
                                                <a href="javascript:void(0)"
                                                   class="btn-toggle-items"
                                                   data-target="{{ $value->id }}"
                                                   data-state="closed"
                                                   style="display:inline-block;margin-top:4px;">
                                                    <span class="label label-default">
                                                        Selengkapnya ({{ $totalItems - 3 }})
                                                    </span>
                                                </a>
                                            @endif
                                        </td>
                                        <td>@currency($value->total)</td>
                                        <td>
                                            @if ($value->is_published)
                                                <span class="label label-success">PUBLISHED</span>
                                            @else
                                                <span class="label label-default">DRAFT</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="label label-{{ $payBadge }}">
                                                {{ match($payStatus) {
                                                    'paid'    => 'LUNAS',
                                                    'unpaid'  => 'BELUM LUNAS',
                                                    'partial' => 'PARTIAL',
                                                    default   => strtoupper($payStatus)
                                                } }}
                                            </span>
                                            @if ($value->pembelianTransaction?->amount > 0)
                                                <br><small>Dibayar: @currency($value->pembelianTransaction->amount)</small>
                                            @endif
                                            @if ($payStatus === 'partial')
                                                @php $sisa = $value->total - $value->pembelianTransaction->amount; @endphp
                                                <br><small class="text-danger">Sisa: @currency($sisa)</small>
                                            @endif
                                        </td>
                                        <td>
                                            {{-- Bayar --}}
                                            <a href="{{ route('pembelian.pembayaran.edit', $value->id) }}"
                                                class="btn btn-xs btn-default"
                                                title="Pembayaran">
                                                <i class="fa fa-credit-card"></i> Pembayaran
                                            </a>

                                            {{-- @if ($value->canBeEditedBy(auth()->user())) --}}
                                                <a href="{{ route('pembelian.edit', $value->id) }}"
                                                    class="btn btn-xs btn-warning" title="Edit">
                                                    <i class="fa fa-pencil"></i> Edit
                                                </a>
                                            {{-- @endif --}}

                                            {{-- @if ($value->canBeEditedBy(auth()->user())) --}}
                                                @if (auth()->user()->role !== 'owner')
                                                    <form action="{{ route('pembelian.destroy', $value->id) }}" method="post"
                                                        style="display:inline">
                                                        @method('delete')
                                                        @csrf
                                                        <button class="btn btn-xs btn-danger"
                                                            onclick="return confirm('Hapus PO {{ $value->code }}?')"
                                                            title="Hapus">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            {{-- @endif --}}

                                            {{-- Export PO --}}
                                            <a href="{{ route('laporan.pembelian', $value->id) }}"
                                                class="btn btn-xs btn-success" title="Export XLSX PO">
                                                <i class="fa fa-file-excel-o"></i> XLSX
                                            </a>
                                            <a href="{{ route('laporan.pembelian.docx', $value->id) }}"
                                                class="btn btn-xs btn-primary" title="Export DOCX PO">
                                                <i class="fa fa-file-word-o"></i> DOCX
                                            </a>
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
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
<script>
    $(document).ready(function() {

        // Init daterange picker
        var startDate = '{{ $dateFrom }}';
        var endDate   = '{{ $dateTo }}';

        $('#daterangepicker').daterangepicker({
            autoUpdateInput: false,
            locale: {
                cancelLabel: 'Batal',
                applyLabel: 'Pilih',
                fromLabel: 'Dari',
                toLabel: 'Sampai',
                format: 'YYYY-MM-DD',
                separator: ' - ',
                daysOfWeek: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
                monthNames: ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'],
                firstDay: 1
            },
            ranges: {
                'Hari Ini':   [moment(), moment()],
                'Kemarin':    [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Minggu Ini': [moment().startOf('isoWeek'), moment().endOf('isoWeek')],
                'Bulan Ini':  [moment().startOf('month'), moment().endOf('month')],
                'Bulan Lalu': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                '7 Hari Terakhir':  [moment().subtract(6, 'days'), moment()],
                '30 Hari Terakhir': [moment().subtract(29, 'days'), moment()],
            },
            startDate: startDate ? moment(startDate) : moment().startOf('month'),
            endDate:   endDate   ? moment(endDate)   : moment(),
        });

        // Ketika user pilih range
        $('#daterangepicker').on('apply.daterangepicker', function(ev, picker) {
            var from = picker.startDate.format('YYYY-MM-DD');
            var to   = picker.endDate.format('YYYY-MM-DD');

            $(this).val(picker.startDate.format('DD MMM YYYY') + ' - ' + picker.endDate.format('DD MMM YYYY'));
            $('#inputDateFrom').val(from);
            $('#inputDateTo').val(to);
            $('#hiddenPeriod').val('daterange');
        });

        $('#daterangepicker').on('cancel.daterangepicker', function() {
            $(this).val('');
            $('#inputDateFrom').val('');
            $('#inputDateTo').val('');
            $('#hiddenPeriod').val('all');
        });

        // Set tampilan awal kalau sudah ada filter aktif
        @if($dateFrom && $dateTo)
            $('#daterangepicker').val(
                moment('{{ $dateFrom }}').format('DD MMM YYYY') + ' - ' +
                moment('{{ $dateTo }}').format('DD MMM YYYY')
            );
        @endif

        // Toggle items
        $(document).on('click', '.btn-toggle-items', function () {
            var id    = $(this).data('target');
            var state = $(this).data('state');
            var $extra = $('.extra-item-' + id);
            var $badge = $(this).find('.label');

            if (state === 'closed') {
                $extra.show();
                $badge.text('Tutup');
                $(this).data('state', 'open');
            } else {
                $extra.hide();
                $badge.text('Selengkapnya (' + $extra.length + ')');
                $(this).data('state', 'closed');
            }
        });
    });
</script>
<script>
    $(document).on('click', '.btn-toggle-items', function () {
        var id = $(this).data('target');
        var state = $(this).data('state');
        var $extra = $('.extra-item-' + id);
        var $badge = $(this).find('.label');

        if (state === 'closed') {
            $extra.show();
            $badge.text('Tutup');
            $(this).data('state', 'open');
        } else {
            $extra.hide();
            $badge.text('Selengkapnya (' + $extra.length + ')');
            $(this).data('state', 'closed');
        }
    });
</script>
@endsection
