@extends('layouts.master')

@section('title', 'Penjualan')

@section('container')
    <section class="content-header">
        <h1>Data Penjualan</h1>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-xs-12">
                <div class="box">
                    <div class="box-header">
                        <a href="{{ route('penjualan.create') }}" class="btn btn-sm bg-green">
                            <i class="fa fa-plus"></i> Tambah Penjualan
                        </a>
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
                                            <a class="btn btn-primary btn-xs" href="{{ route('penjualan.edit', $penjualan) }}">
                                                <i class="fa fa-pencil"></i> Edit
                                            </a>
                                            @if (! in_array(auth()->user()?->role, ['admin-cabang', 'sales'], true))
                                            <a class="btn btn-success btn-xs" href="{{ route('penjualan.pembayaran.edit', $penjualan) }}">
                                                <i class="fa fa-credit-card"></i> Pembayaran
                                            </a>
                                            @endif
                                            <a class="btn btn-danger btn-xs" href="{{ route('refund.create', ['penjualan_id' => $penjualan->id]) }}">
                                                <i class="fa fa-undo"></i> Retur
                                            </a>
                                            <a class="btn btn-warning btn-xs" href="{{ route('penjualan.print', $penjualan) }}" target="_blank">
                                                <i class="fa fa-print"></i> Invoice
                                            </a>
                                            @if (! in_array(auth()->user()?->role, ['admin-cabang', 'sales'], true))
                                            <a class="btn btn-info btn-xs" href="{{ route('penjualan.surat-jalan', $penjualan) }}" target="_blank">
                                                <i class="fa fa-truck"></i> Surat Jalan
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
