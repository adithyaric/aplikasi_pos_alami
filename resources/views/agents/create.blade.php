@extends('layouts.master')

@section('title', 'Tambah Agen')

@section('container')
    <section class="content">
        <div class="row">
            <!-- left column -->
            <div class="col-md-12">
                <!-- general form elements -->
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">Tambah Agen</h3>
                    </div><!-- /.box-header -->
                    <!-- form start -->
                    <form action="{{ route('agents.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="box-body">
                            <div class="form-group">
                                <label for="">Nama</label>
                                <input type="text" class="form-control" name="name" value="{{ old('name') }}"
                                    placeholder="Masukkan Nama" required>
                                @error('name')
                                    <div class="invalid-feedback text-danger">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="">Deskripsi (Opsional)</label>
                                <input type="text" class="form-control" name="desc" value="{{ old('desc') }}"
                                    placeholder="Masukkan Deskripsi">
                                @error('desc')
                                    <div class="invalid-feedback text-danger">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                        </div><!-- /.box-body -->

                        <div class="box-footer">
                            <a href="{{ route('agents.index') }}" class="btn btn-default">Kembali</a>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </div><!-- /.box -->
            </div>
        </div>
    </section>
@endsection
