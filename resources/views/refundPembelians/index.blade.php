@extends('layouts.master')

@section('title', $selectedType === 'outlet_ke_gudang' ? 'Retur Cabang' : 'Retur Pembelian')

@section('container')
    <section class="content-header">
        <h1>
            @if ($selectedType === 'outlet_ke_gudang')
                Data Retur Cabang ke Gudang
            @else
                Data Retur Gudang ke Supplier
            @endif
        </h1>
    </section>

    <section class="content">
        <div class="row" style="margin-bottom:10px;">
            <div class="col-xs-12">
                <div class="box box-default" style="margin-bottom:0;">
                    <div class="box-body" style="padding:10px 15px;">
                        <form method="GET" action="{{ route('refundPembelian.index') }}">
                            <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                                @if (! $isStaffOutlet)
                                    <label style="margin:0; white-space:nowrap;">Jenis Retur:</label>
                                    <select name="type" class="form-control input-sm" style="width:220px">
                                        <option value="gudang_ke_supplier" {{ $selectedType === 'gudang_ke_supplier' ? 'selected' : '' }}>
                                            Gudang ke Supplier
                                        </option>
                                        <option value="outlet_ke_gudang" {{ $selectedType === 'outlet_ke_gudang' ? 'selected' : '' }}>
                                            Cabang ke Gudang
                                        </option>
                                    </select>
                                @else
                                    <input type="hidden" name="type" value="outlet_ke_gudang">
                                @endif

                                <label style="margin:0; white-space:nowrap;">Tanggal:</label>
                                <input type="hidden" name="period" value="daterange">
                                <input type="date" name="date_from" class="form-control input-sm" style="width:180px"
                                    value="{{ $dateFrom }}">
                                <span>s/d</span>
                                <input type="date" name="date_to" class="form-control input-sm" style="width:180px"
                                    value="{{ $dateTo }}">

                                @if ($selectedType === 'outlet_ke_gudang')
                                    <label style="margin:0 0 0 8px; white-space:nowrap;">Cabang:</label>
                                    <select name="outlet_id" class="form-control input-sm" style="width:220px">
                                        <option value="">Semua Cabang</option>
                                        @foreach ($outlets as $outlet)
                                            <option value="{{ $outlet->id }}" {{ (string) $selectedOutletId === (string) $outlet->id ? 'selected' : '' }}>
                                                {{ $outlet->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                @endif

                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class="fa fa-filter"></i> Terapkan Filter
                                </button>

                                <a href="{{ route('refundPembelian.index', ['type' => $selectedType]) }}" class="btn btn-sm btn-default">
                                    <i class="fa fa-times"></i> Reset
                                </a>
                            </div>
                        </form>

                        <br>

                        <div class="row">
                            <div class="col-xs-12 col-sm-6 col-md-3">
                                <div class="info-box bg-aqua">
                                    <span class="info-box-icon"><i class="fa fa-refresh"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Total Retur</span>
                                        <span class="info-box-number">Rp {{ number_format($summary['totalNominal'], 0, ',', '.') }}</span>
                                        <span class="progress-description">{{ $summary['totalCount'] }} transaksi retur</span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xs-12 col-sm-6 col-md-3">
                                <div class="info-box bg-green">
                                    <span class="info-box-icon"><i class="fa fa-check-circle"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Status Complete</span>
                                        <span class="info-box-number">Rp {{ number_format($summary['completeNominal'], 0, ',', '.') }}</span>
                                        <span class="progress-description">{{ $summary['completeCount'] }} retur selesai</span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xs-12 col-sm-6 col-md-3">
                                <div class="info-box bg-red">
                                    <span class="info-box-icon"><i class="fa fa-hourglass-half"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Status Retur</span>
                                        <span class="info-box-number">Rp {{ number_format($summary['returNominal'], 0, ',', '.') }}</span>
                                        <span class="progress-description">{{ $summary['returCount'] }} retur proses</span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xs-12 col-sm-6 col-md-3">
                                <div class="info-box bg-yellow">
                                    <span class="info-box-icon"><i class="fa fa-building"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Jenis Retur</span>
                                        <span class="info-box-number">{{ $summary['branchReturnCount'] }} / {{ $summary['supplierReturnCount'] }}</span>
                                        <span class="progress-description">Cabang / Supplier</span>
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
                        @if ($canCreateRetur)
                            <a href="{{ route('refundPembelian.create', ['type' => $selectedType]) }}" class="btn btn-sm bg-green">
                                <i class="fa fa-plus"></i>
                                {{ $selectedType === 'outlet_ke_gudang' ? 'Tambah Retur Cabang' : 'Tambah Retur Gudang' }}
                            </a>
                        @endif
                    </div>
                    <div class="box-body table-responsive">
                        <table id="example1" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Kode Retur</th>
                                    <th>Jenis</th>
                                    <th>{{ $selectedType === 'outlet_ke_gudang' ? 'Cabang' : 'Supplier / Cabang' }}</th>
                                    <th>Tanggal</th>
                                    <th>Total</th>
                                    <th>Operator</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($refundPembelians as $value)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $value->code }}</td>
                                        <td>
                                            @if ($value->type === 'gudang_ke_supplier')
                                                <span class="label label-warning">Gudang → Supplier</span>
                                            @else
                                                <span class="label label-info">Cabang → Gudang</span>
                                            @endif
                                        </td>
                                        <td>{{ $value->type === 'gudang_ke_supplier' ? $value->supplier->name ?? '-' : $value->outlet->name ?? '-' }}</td>
                                        <td>{{ $value->tanggal->format('d M Y') }}</td>
                                        <td>@currency($value->total)</td>
                                        <td>{{ $value->user->name ?? '-' }}</td>
                                        <td>
                                            @if ($value->status === 'retur')
                                                <span class="label label-danger">Retur</span>
                                            @else
                                                <span class="label label-success">Complete</span>
                                            @endif
                                        </td>
                                        <td class="text-nowrap">
                                            <a class="btn btn-default btn-xs" href="{{ route('refundPembelian.show', $value) }}">
                                                <i class="fa fa-eye"></i> Show
                                            </a>

                                            @if (! $isStaffOutlet && $value->type === 'gudang_ke_supplier' && $value->status === 'retur')
                                                <a class="btn btn-success btn-xs" href="{{ route('refundPembelian.terima.form', $value) }}">
                                                    <i class="fa fa-inbox"></i> Terima
                                                </a>
                                            @endif

                                            @if ($value->status !== 'complete')
                                                <form action="{{ route('refundPembelian.destroy', $value) }}" method="post" style="display:inline">
                                                    @method('delete')
                                                    @csrf
                                                    <button class="btn btn-danger btn-xs" onclick="return confirm('Hapus data ini?')">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endif

                                            @if ($value->type === 'gudang_ke_supplier')
                                                <a href="{{ route('laporan.retur-pembelian.single', $value) }}" class="btn btn-success btn-xs" target="_blank">
                                                    <i class="fa fa-file-excel-o"></i> XLSX
                                                </a>
                                                <a href="{{ route('laporan.pdf.retur-pembelian-single', $value) }}" class="btn btn-danger btn-xs" target="_blank">
                                                    <i class="fa fa-file-pdf-o"></i> PDF
                                                </a>
                                            @else
                                                <a href="{{ route('laporan.retur-outlet.single', $value) }}" class="btn btn-success btn-xs" target="_blank">
                                                    <i class="fa fa-file-excel-o"></i> XLSX
                                                </a>
                                                <a href="{{ route('laporan.pdf.retur-outlet-single', $value) }}" class="btn btn-danger btn-xs" target="_blank">
                                                    <i class="fa fa-file-pdf-o"></i> PDF
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
