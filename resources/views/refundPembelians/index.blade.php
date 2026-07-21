@extends('layouts.master')

@section('title', $isStaffOutlet ? 'Retur Cabang' : ($selectedType === 'outlet_ke_gudang' ? 'Retur Cabang' : 'Retur Gudang'))

@section('container')
    <section class="content-header">
        <h1>
            @if ($isStaffOutlet)
                Riwayat Retur Cabang ke Gudang
            @elseif ($selectedType === 'outlet_ke_gudang')
                Data Retur Cabang ke Gudang
            @else
                Data Retur Gudang ke Supplier
            @endif
        </h1>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-xs-12">
                <div class="box">
                    <div class="box-header">
                        <div class="row align-items-center">

                            <div class="col-md-4 col-sm-12 mb-2 mb-md-0">
                                @if ($isStaffOutlet)
                                    <a href="{{ route('refundPembelian.create', ['type' => 'outlet_ke_gudang']) }}" class="btn btn-sm bg-green">
                                        <i class="fa fa-plus"></i> Tambah Retur Cabang
                                    </a>
                                @else
                                    @if ($selectedType === 'outlet_ke_gudang')
                                        <a href="{{ route('refundPembelian.create', ['type' => 'outlet_ke_gudang']) }}" class="btn btn-sm bg-green">
                                            <i class="fa fa-plus"></i> Tambah Retur Cabang
                                        </a>
                                        <a href="{{ route('refundPembelian.index', ['type' => 'gudang_ke_supplier']) }}" class="btn btn-sm btn-default">
                                            <i class="fa fa-exchange"></i> Lihat Retur Gudang
                                        </a>
                                    @else
                                        <a href="{{ route('refundPembelian.create', ['type' => 'gudang_ke_supplier']) }}" class="btn btn-sm bg-green">
                                            <i class="fa fa-plus"></i> Tambah Retur Gudang
                                        </a>
                                        <a href="{{ route('refundPembelian.index', ['type' => 'outlet_ke_gudang']) }}" class="btn btn-sm btn-default">
                                            <i class="fa fa-exchange"></i> Lihat Retur Cabang
                                        </a>
                                    @endif
                                @endif
                            </div>

                            <div class="col-md-8 col-sm-12">
                                @if ($isStaffOutlet)
                                    {{-- Staff outlet: export only their outlet's retur by date range --}}
                                    <form method="GET" action="{{ route('laporan.retur-outlet') }}">
                                        <div class="row g-0">
                                            <div class="col-xs-4">
                                                <input type="date" name="tanggal_mulai" class="form-control input-sm" required>
                                            </div>
                                            <div class="col-xs-4">
                                                <input type="date" name="tanggal_selesai" class="form-control input-sm" required>
                                            </div>
                                            <div class="col-xs-4">
                                                <button type="submit" class="btn btn-success btn-sm w-100">
                                                    <i class="fa fa-file-excel-o"></i> Export
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                @else
                                    <form method="GET"
                                        action="{{ $selectedType === 'outlet_ke_gudang' ? route('laporan.retur-outlet') : route('laporan.retur-supplier') }}">
                                        <div class="row g-0" style="gap:4px 0">
                                            <div class="col-xs-4">
                                                <input type="date" name="tanggal_mulai" class="form-control input-sm" required>
                                            </div>
                                            <div class="col-xs-4">
                                                <input type="date" name="tanggal_selesai" class="form-control input-sm" required>
                                            </div>
                                            <div class="col-xs-4">
                                                <button type="submit" class="btn btn-success btn-sm w-100">
                                                    <i class="fa fa-file-excel-o"></i> Export
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                    @if ($selectedType === 'outlet_ke_gudang')
                                    <div class="row" style="margin-top:6px">
                                        <div class="col-xs-6">
                                            <select id="outlet-filter" class="form-control input-sm select2"
                                                style="width:100%" onchange="updateOutletFilter(this.value)">
                                                <option value="">-- Filter Cabang --</option>
                                                @foreach ($outlets as $outlet)
                                                    <option value="{{ $outlet->id }}"
                                                        {{ (string)($selectedOutletId ?? '') === (string)$outlet->id ? 'selected' : '' }}>
                                                        {{ $outlet->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="box-body table-responsive">
                        <table id="example1" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Kode Retur</th>
                                    @if (!$isStaffOutlet)
                                        <th>Jenis</th>
                                    @endif
                                    <th>{{ $isStaffOutlet ? 'Cabang' : 'Supplier / Cabang' }}</th>
                                    <th>Tanggal</th>
                                    @if (!$isStaffOutlet)
                                        <th>Total</th>
                                    @endif
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
                                        @if (!$isStaffOutlet)
                                            <td>
                                                @if ($value->type === 'gudang_ke_supplier')
                                                    <span class="label label-warning">Gudang → Supplier</span>
                                                @else
                                                    <span class="label label-info">Cabang → Gudang</span>
                                                @endif
                                            </td>
                                        @endif
                                        <td>
                                            {{ $value->type === 'gudang_ke_supplier' ? $value->supplier->name ?? '-' : $value->outlet->name ?? '-' }}
                                        </td>
                                        <td>{{ $value->tanggal->format('d M Y') }}</td>
                                        @if (!$isStaffOutlet)
                                            <td>@currency($value->total)</td>
                                        @endif
                                        <td>{{ $value->user->name ?? '-' }}</td>
                                        <td>
                                            @if ($value->status === 'retur')
                                                <span class="label label-danger">Retur</span>
                                            @else
                                                <span class="label label-success">Complete</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a class="btn btn-default btn-xs"
                                                href="{{ route('refundPembelian.show', $value->id) }}">
                                                <i class="fa fa-eye"></i> Show
                                            </a>

                                            @if (!$isStaffOutlet && $value->type === 'gudang_ke_supplier' && $value->status === 'retur')
                                                <a class="btn btn-success btn-xs"
                                                    href="{{ route('refundPembelian.terima.form', $value->id) }}">
                                                    <i class="fa fa-inbox"></i> Terima
                                                </a>
                                            @endif

                                            @if ($value->status !== 'complete')
                                                <form action="{{ route('refundPembelian.destroy', $value->id) }}"
                                                    method="post" style="display:inline">
                                                    @method('delete')
                                                    @csrf
                                                    <button class="btn btn-danger btn-xs"
                                                        onclick="return confirm('Hapus data ini?')">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endif

                                            @if ($value->type === 'gudang_ke_supplier')
                                                <a href="{{ route('laporan.retur-pembelian.single', $value->id) }}"
                                                    class="btn btn-success btn-xs" target="_blank">
                                                    <i class="fa fa-file-excel-o"></i> XLSX
                                                </a>
                                                <a href="{{ route('laporan.pdf.retur-pembelian-single', $value->id) }}"
                                                    class="btn btn-danger btn-xs" target="_blank">
                                                    <i class="fa fa-file-pdf-o"></i> PDF
                                                </a>
                                            @else
                                                <a href="{{ route('laporan.retur-outlet.single', $value->id) }}"
                                                    class="btn btn-success btn-xs" target="_blank">
                                                    <i class="fa fa-file-excel-o"></i> XLSX
                                                </a>
                                                <a href="{{ route('laporan.pdf.retur-outlet-single', $value->id) }}"
                                                    class="btn btn-danger btn-xs" target="_blank">
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

@section('page-script')
    <script>
        function updateOutletFilter(outletId) {
            var url = new URL(window.location.href);
            if (outletId) {
                url.searchParams.set('outlet_id', outletId);
            } else {
                url.searchParams.delete('outlet_id');
            }
            window.location.href = url.toString();
        }
    </script>
@endsection
