@extends('layouts.master')

@section('title', 'Refund/Return Penjualan')

@section('container')
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>
            Data Refund/Return Penjualan
        </h1>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-xs-12">
                <div class="box">
                    <div class="box-header">
                        <a href="{{ route('refund.create') }}" class="btn btn-md bg-green">Tambah</a>
                    </div><!-- /.box-header -->
                    <div class="box-body table-responsive">
                        <table id="example1" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <td>No</td>
                                    <td>Kode</td>
                                    <td>Invoice</td>
                                    <td>Jenis Pembeli</td>
                                    <td>Pembeli</td>
                                    <td>Nama Operator</td>
                                    <td>Total</td>
                                    <td>Aksi</td>
                                </tr>
                            </thead>
                            @foreach ($refunds as $value)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $value->code }}</td>
                                    <td>{{ $value->penjualan?->code ?? '-' }}</td>
                                    <td>{{ $value->buyer_type_label }}</td>
                                    <td>{{ $value->buyer_display_name }}</td>
                                    <td>{{ $value->user->name }}</td>
                                    <td>@currency($value->total)</td>
                                    <td>
                                        <a class="btn btn-info" href="{{ route('refund.show', $value->id) }}">Show</a>
                                        <a class="btn btn-warning" href="{{ route('refund.edit', $value->id) }}">Edit</a>
                                        <form action="{{ route('refund.destroy', $value->id) }}" method="post"
                                            style="display: inline;">
                                            @method('delete')
                                            @csrf
                                            <button class="border-0 btn btn-danger"
                                                onclick="return confirm('Are you sure?')">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    </div><!-- /.box-body -->
                </div><!-- /.box -->
            </div><!-- /.col -->
        </div><!-- /.row -->
    </section><!-- /.content -->
@endsection
