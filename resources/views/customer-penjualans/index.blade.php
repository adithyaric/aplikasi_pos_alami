@extends('layouts.master')

@section('title', 'Customer Penjualan')

@section('container')
    <section class="content-header">
        <h1>Customer Penjualan</h1>
    </section>

    <section class="content">
        <div class="box box-default">
            <div class="box-body">
                <form method="GET" action="{{ route('customer-penjualan.index') }}" class="form-inline">
                    <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Cari customer">
                    <select name="type" class="form-control">
                        <option value="">Semua Tipe</option>
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}" {{ $selectedType === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-primary"><i class="fa fa-filter"></i> Filter</button>
                    <a href="{{ route('customer-penjualan.create') }}" class="btn bg-green"><i class="fa fa-plus"></i> Tambah Customer Penjualan</a>
                </form>
            </div>
        </div>

        <div class="box">
            <div class="box-body table-responsive">
                <table id="example1" class="table table-bordered table-striped">
                    <thead><tr><th>No</th><th>Tipe</th><th>Kode</th><th>Nama</th><th>Alamat</th><th>Telepon</th><th>Status</th><th>Aksi</th></tr></thead>
                    <tbody>
                    @foreach ($customers as $customer)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><span class="label label-info">{{ $customer['type_label'] }}</span></td>
                            <td>{{ $customer['code'] ?: '-' }}</td>
                            <td>{{ $customer['name'] }}</td>
                            <td>{{ $customer['alamat'] ?: '-' }}</td>
                            <td>{{ $customer['no_telp'] ?: '-' }}</td>
                            <td><span class="label label-{{ $customer['is_active'] ? 'success' : 'default' }}">{{ $customer['is_active'] ? 'Aktif' : 'Nonaktif' }}</span></td>
                            <td class="text-nowrap">
                                <a class="btn btn-warning btn-xs" href="{{ route('customer-penjualan.edit', [$customer['type'], $customer['id']]) }}">Edit</a>
                                <form action="{{ route('customer-penjualan.destroy', [$customer['type'], $customer['id']]) }}" method="POST" style="display:inline">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger btn-xs" onclick="return confirm('Hapus customer ini?')">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
