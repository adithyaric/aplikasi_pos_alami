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
                                <label for="">Kode Agen</label>
                                <input type="text" class="form-control" name="code" value="{{ old('code') }}"
                                    placeholder="Masukkan Kode Agen">
                                @error('code')
                                    <div class="invalid-feedback text-danger">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
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
                            <div class="form-group">
                                <label for="">Alamat</label>
                                <textarea class="form-control" name="alamat" rows="2" placeholder="Masukkan Alamat">{{ old('alamat') }}</textarea>
                                @error('alamat')
                                    <div class="invalid-feedback text-danger">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="">Nomor Telp</label>
                                        <input type="text" class="form-control" name="no_telp" value="{{ old('no_telp') }}"
                                            placeholder="Masukkan Nomor Telp">
                                        @error('no_telp')
                                            <div class="invalid-feedback text-danger">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', 1) ? 'checked' : '' }}>
                                    Aktif
                                </label>
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
