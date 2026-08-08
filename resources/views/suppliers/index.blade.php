@extends('layouts.master')

@section('title', 'Supplier')

@section('container')
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>
        Data Supplier
    </h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-header">
                    <a href="{{ route('supplier.create') }}" class="btn btn-sm bg-light-blue">Tambah</a>
                    <a href="{{ route('supplier.export') }}" class="btn btn-sm bg-green">
                        <i class="fa fa-download"></i> Export
                    </a>
                </div><!-- /.box-header -->
                <div class="box-body table-responsive">
                    <table id="example1" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kode</th>
                                <th>Nama</th>
                                <th>Alamat</th>
                                <th>Nomor Telp</th>
                                <th>Format PO</th>
                                <th>Template PO</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($suppliers as $value)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $value->kode_supplier }}</td>
                                <td>{{ $value->name }}</td>
                                <td>{{ $value->alamat }}</td>
                                <td>{{ $value->no_telp }}</td>
                                <td>
                                    <code>{{ $value->poNumberFormat() }}</code>
                                    <br>
                                    <small class="text-muted">Contoh: {{ $value->previewPoCode() }}</small>
                                    <br>
                                    <small class="text-muted">Digit: {{ $value->po_number_padding ?: 5 }}</small>
                                </td>
                                <td class="small">
                                    {{ $value->po_template ? basename($value->po_template) : 'Default' }}
                                </td>

                                <td>
                                    <a class="btn btn-warning btn-xs" href="{{ route('supplier.edit', $value->id) }}">Kelola PO</a>
                                    <form action="{{ route('supplier.destroy', $value->id) }}" method="post" style="display:inline">
                                        @method('delete')
                                        @csrf
                                        <button class="btn btn-danger btn-xs" onclick="return confirm('Hapus supplier ini?')">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div><!-- /.box-body -->
            </div><!-- /.box -->
        </div><!-- /.col -->
    </div><!-- /.row -->
</section><!-- /.content -->
@endsection
