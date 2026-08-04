@extends('layouts.master')

@section('title', 'Penjualan Cabang')

@section('container')
    <section class="content-header">
        <h1>Penjualan Cabang</h1>
    </section>

    <section class="content">
        <div class="row" style="margin-bottom:10px;">
            <div class="col-xs-12">
                <div class="box box-default" style="margin-bottom:0;">
                    <div class="box-body" style="padding:10px 15px;">
                        <form method="GET" action="{{ route('penjualan.branch-index') }}">
                            <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                                <label style="margin:0; white-space:nowrap;">Tanggal:</label>
                                <input type="hidden" name="period" value="daterange">
                                <input type="date" name="date_from" class="form-control input-sm" style="width:180px"
                                    value="{{ $dateFrom }}">
                                <span>s/d</span>
                                <input type="date" name="date_to" class="form-control input-sm" style="width:180px"
                                    value="{{ $dateTo }}">

                                <label style="margin:0 0 0 8px; white-space:nowrap;">Cabang:</label>
                                <select name="branch_id" class="form-control input-sm" style="width:220px">
                                    <option value="">Semua Cabang</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ (string) $selectedBranchId === (string) $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>

                                <label style="margin:0 0 0 8px; white-space:nowrap;">Sales:</label>
                                <select name="salesman_id" class="form-control input-sm" style="width:220px">
                                    <option value="">Semua Sales</option>
                                    @foreach ($salesmen as $salesman)
                                        <option value="{{ $salesman->id }}" {{ (string) $selectedSalesmanId === (string) $salesman->id ? 'selected' : '' }}>
                                            {{ $salesman->name }}
                                        </option>
                                    @endforeach
                                </select>

                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class="fa fa-filter"></i> Terapkan Filter
                                </button>

                                <a href="{{ route('penjualan.branch-index') }}" class="btn btn-sm btn-default">
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
                                    <span class="info-box-icon"><i class="fa fa-line-chart"></i></span>
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
                        <a href="{{ route('customer-penjualan.index', ['type' => 'toko']) }}" class="btn btn-sm btn-default">
                            <i class="fa fa-users"></i> Customer Penjualan
                        </a>
                    </div>
                    <div class="box-body table-responsive">
                        <table id="example1" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Invoice</th>
                                    <th>Tanggal</th>
                                    <th>Cabang</th>
                                    <th>Sales</th>
                                    <th>Customer/Toko</th>
                                    <th>Pembayaran</th>
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
                                        <td>{{ $penjualan->outlet?->name ?? '-' }}</td>
                                        <td>{{ $penjualan->salesman?->name ?? $penjualan->operator?->name ?? '-' }}</td>
                                        <td>{{ $penjualan->buyer_display_name }}</td>
                                        <td>
                                            {{ strtoupper($penjualan->payment_type ?? '-') }}
                                            <br>
                                            <span class="label {{ $penjualan->payment_status === 'paid' ? 'label-success' : ($penjualan->payment_status === 'partial' ? 'label-warning' : 'label-default') }}">
                                                {{ strtoupper($penjualan->payment_status ?? '-') }}
                                            </span>
                                        </td>
                                        <td>@currency($penjualan->total)</td>
                                        <td class="text-nowrap">
                                            <a class="btn btn-default btn-xs" href="{{ route('penjualan.show', $penjualan) }}">
                                                <i class="fa fa-eye"></i> Show
                                            </a>
                                            @if (auth()->user()?->role === 'sales' && $penjualan->payment_status != 'paid')
                                                <a class="btn btn-primary btn-xs" href="{{ route('penjualan.edit', $penjualan) }}">
                                                    <i class="fa fa-pencil"></i> Edit
                                                </a>
                                            @endif
                                            <a class="btn btn-warning btn-xs" href="{{ route('penjualan.print', $penjualan) }}" target="_blank">
                                                <i class="fa fa-print"></i> Invoice
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
