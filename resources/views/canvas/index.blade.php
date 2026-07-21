@extends('layouts.master')

@section('title', 'Canvas')

@section('container')
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>
            Data Canvas
        </h1>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-xs-12">
                <div class="box">
                    <div class="box-header">
                        <a href="{{ route('canvases.create') }}" class="btn btn-md bg-green">Tambah</a>
                    </div><!-- /.box-header -->
                    <div class="box-body table-responsive">
                        <table id="example1" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <td>No</td>
                                    <td>Kode</td>
                                    <td>Nama</td>
                                    <td>Telepon</td>
                                    <td>Termin</td>
                                    <td>Limit Piutang</td>
                                    <td>Status</td>
                                    <td>Aksi</td>
                                </tr>
                            </thead>
                            @foreach ($canvases as $value)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $value->code ?? '-' }}</td>
                                    <td>{{ $value->name }}</td>
                                    <td>{{ $value->no_telp ?? '-' }}</td>
                                    <td>{{ $value->termin_days ?? 0 }} hari</td>
                                    <td>@currency($value->credit_limit ?? 0)</td>
                                    <td>
                                        <span class="label label-{{ ($value->is_active ?? true) ? 'success' : 'default' }}">
                                            {{ ($value->is_active ?? true) ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td>
                                        <a class="btn btn-warning" href="{{ route('canvases.edit', $value->id) }}">Edit</a>
                                        <form action="{{ route('canvases.destroy', $value->id) }}" method="post"
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
