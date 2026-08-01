@extends('layouts.master')

@section('title', 'Retur Penjualan')

@section('container')
    <section class="content-header">
        <h1>Data Retur Penjualan</h1>
    </section>

    <section class="content">
        <div class="row" style="margin-bottom:10px;">
            <div class="col-xs-12">
                <div class="box box-default" style="margin-bottom:0;">
                    <div class="box-body" style="padding:10px 15px;">
                        <form method="GET" action="{{ route('refund.index') }}">
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

                                <label style="margin:0 0 0 8px; white-space:nowrap;">Flow Retur:</label>
                                <select name="return_scope" class="form-control input-sm" style="width:210px">
                                    <option value="">Semua Flow</option>
                                    @foreach ($returnScopeOptions as $value => $label)
                                        <option value="{{ $value }}" {{ $selectedReturnScope === $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>

                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class="fa fa-filter"></i> Terapkan Filter
                                </button>

                                <a href="{{ route('refund.index') }}" class="btn btn-sm btn-default">
                                    <i class="fa fa-times"></i> Reset
                                </a>
                            </div>
                        </form>

                        <br>

                        <div class="row">
                            <div class="col-xs-12 col-sm-6 col-md-3">
                                <div class="info-box bg-aqua">
                                    <span class="info-box-icon"><i class="fa fa-undo"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Total Retur</span>
                                        <span class="info-box-number">Rp {{ number_format($summary['totalNominal'], 0, ',', '.') }}</span>
                                        <span class="progress-description">{{ $summary['totalCount'] }} transaksi retur</span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xs-12 col-sm-6 col-md-3">
                                <div class="info-box bg-yellow">
                                    <span class="info-box-icon"><i class="fa fa-users"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Agen / Canvas</span>
                                        <span class="info-box-number">Rp {{ number_format($summary['affiliateNominal'], 0, ',', '.') }}</span>
                                        <span class="progress-description">{{ $summary['affiliateCount'] }} retur affiliate</span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xs-12 col-sm-6 col-md-3">
                                <div class="info-box bg-green">
                                    <span class="info-box-icon"><i class="fa fa-building"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Cabang ke Gudang</span>
                                        <span class="info-box-number">Rp {{ number_format($summary['branchWarehouseNominal'], 0, ',', '.') }}</span>
                                        <span class="progress-description">{{ $summary['branchWarehouseCount'] }} retur cabang</span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xs-12 col-sm-6 col-md-3">
                                <div class="info-box bg-red">
                                    <span class="info-box-icon"><i class="fa fa-shopping-bag"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Toko ke Cabang</span>
                                        <span class="info-box-number">Rp {{ number_format($summary['branchCustomerNominal'], 0, ',', '.') }}</span>
                                        <span class="progress-description">{{ $summary['branchCustomerCount'] }} retur customer</span>
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
                        <a href="{{ route('refund.create', array_filter(['return_scope' => $selectedReturnScope])) }}" class="btn btn-sm bg-green">
                            <i class="fa fa-plus"></i> Tambah Retur Penjualan
                        </a>
                    </div>
                    <div class="box-body table-responsive">
                        <table id="example1" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Kode</th>
                                    <th>Invoice</th>
                                    <th>Jenis Pembeli</th>
                                    <th>Pembeli</th>
                                    <th>Tanggal</th>
                                    <th>Operator</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($refunds as $value)
                                    @php
                                        $isPendingBranchWarehouseReturn = $value->return_scope === \App\Services\SalesReturnManager::SCOPE_WAREHOUSE_BRANCH
                                            && $value->isPendingApproval();
                                        $canManage = auth()->user()->role !== 'admin-cabang'
                                            || $value->return_scope === \App\Services\SalesReturnManager::SCOPE_BRANCH_CUSTOMER
                                            || $isPendingBranchWarehouseReturn;
                                        $statusClass = $value->isPendingApproval()
                                            ? 'label-warning'
                                            : ($value->isRejected() ? 'label-danger' : 'label-success');
                                    @endphp
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $value->code }}</td>
                                        <td>{{ $value->appliedPenjualan?->code ?? $value->penjualan?->code ?? '-' }}</td>
                                        <td>{{ $value->buyer_type_label }}</td>
                                        <td>{{ $value->buyer_display_name }}</td>
                                        <td>{{ optional($value->tanggal)->format('d M Y') }}</td>
                                        <td>{{ $value->user?->name ?? '-' }}</td>
                                        <td>@currency($value->total)</td>
                                        <td><span class="label {{ $statusClass }}">{{ $value->status_label }}</span></td>
                                        <td class="text-nowrap">
                                            <a class="btn btn-default btn-xs" href="{{ route('refund.show', $value) }}">
                                                <i class="fa fa-eye"></i> Show
                                            </a>
                                            @if ($canManage)
                                                <a class="btn btn-warning btn-xs" href="{{ route('refund.edit', $value) }}">
                                                    <i class="fa fa-pencil"></i> Edit
                                                </a>
                                                <form action="{{ route('refund.destroy', $value) }}" method="post" style="display:inline;">
                                                    @method('delete')
                                                    @csrf
                                                    <button class="btn btn-danger btn-xs" onclick="return confirm('Hapus data retur ini?')">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                            @if ($canApproveBranchWarehouseReturn && $isPendingBranchWarehouseReturn)
                                                <form action="{{ route('refund.approve', $value) }}" method="post" style="display:inline;">
                                                    @csrf
                                                    <button class="btn btn-success btn-xs" onclick="return confirm('Konfirmasi retur cabang ini?')">
                                                        <i class="fa fa-check"></i> Approve
                                                    </button>
                                                </form>
                                                <form action="{{ route('refund.reject', $value) }}" method="post" style="display:inline;">
                                                    @csrf
                                                    <button class="btn btn-danger btn-xs" onclick="return confirm('Tolak retur cabang ini?')">
                                                        <i class="fa fa-times"></i> Reject
                                                    </button>
                                                </form>
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
