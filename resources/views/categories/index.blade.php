@extends('layouts.master')

@section('title', 'Halaman Kategori Produk')

@section('container')
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>
            Halaman Kategori
            <small>Kelola kategori produk</small>
        </h1>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-xs-12">
                <div class="box">
                    <div class="box-header">
                        <div class="row">
                            <div class="col-xs-12">
                                <a href="{{ route('category.product.create') }}" class="btn btn-primary btn-sm">
                                    <i class="fa fa-plus"></i> Tambah Kategori
                                </a>
                                <span class="text-muted" style="margin-left: 10px;">
                                    {{ $categories->count() }} kategori
                                </span>
                            </div>
                        </div>
                    </div><!-- /.box-header -->
                    <div class="box-body table-responsive">
                        <table id="example1" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Kategori</th>
                                    <th>Jumlah Produk</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($categories as $value)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $value->name }}</td>
                                        <td>
                                            <span class="label label-info">{{ $value->products_count }}</span>
                                        </td>
                                        <td>
                                            <a class="btn btn-info btn-sm" href="{{ route('product.index', ['category_id' => $value->id]) }}">
                                                <i class="fa fa-cubes"></i> Lihat Produk
                                            </a>
                                            <a class="btn btn-warning btn-sm" href="{{ $type == 'product' ? route('category.product.edit', $value->id) : route('category.pengeluaran.edit', $value->id) }}">
                                                <i class="fa fa-pencil"></i> Edit
                                            </a>
                                            @if ((int) $value->products_count === 0)
                                                <form action="{{ route('category.destroy', $value->id) }}" method="post" style="display: inline;">
                                                    @method('delete')
                                                    @csrf
                                                    <button class="border-0 btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus kategori ini?')">
                                                        <i class="fa fa-trash"></i> Hapus
                                                    </button>
                                                </form>
                                            @else
                                                <button class="btn btn-default btn-sm" disabled title="Kategori masih digunakan oleh produk">
                                                    <i class="fa fa-lock"></i> Hapus
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">Belum ada kategori produk.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div><!-- /.box-body -->
                </div><!-- /.box -->
            </div><!-- /.col -->
        </div><!-- /.row -->
    </section><!-- /.content -->
@endsection
