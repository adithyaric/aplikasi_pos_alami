@extends('layouts.master')

@section('title', 'Edit Salesman')

@section('container')
    <section class="content">
        <div class="row">
            <!-- left column -->
            <div class="col-md-12">
                <!-- general form elements -->
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">Edit Salesman</h3>
                    </div><!-- /.box-header -->
                    <!-- form start -->
                    <form action="{{ route('salesman.update', $salesman->id) }}" method="POST" enctype="multipart/form-data">
                        @method('PUT')
                        @csrf
                        <div class="box-body">
                            <div class="form-group">
                                <label for="">Kode Salesman</label>
                                <input type="text" class="form-control" name="code"
                                    value="{{ old('code', $salesman->code) }}" placeholder="Masukkan Kode Salesman">
                                @error('code')
                                    <div class="invalid-feedback text-danger">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="">Nama Salesman</label>
                                <input type="text" class="form-control" name="name"
                                    value="{{ old('name', $salesman->name) }}" placeholder="Masukkan Nama Salesman">
                                @error('name')
                                    <div class="invalid-feedback text-danger">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="">Alamat</label>
                                <input type="text" class="form-control" name="alamat"
                                    value="{{ old('alamat', $salesman->alamat) }}" placeholder="Masukkan Alamat">
                                @error('alamat')
                                    <div class="invalid-feedback text-danger">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="">Nomor Telp</label>
                                <input type="number" class="form-control" name="no_telp"
                                    value="{{ old('no_telp', $salesman->no_telp) }}" placeholder="Masukkan Nomor Telp">
                                @error('no_telp')
                                    <div class="invalid-feedback text-danger">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="">Email Login</label>
                                <input type="email" class="form-control" name="email"
                                    value="{{ old('email', $salesman->user?->email) }}"
                                    placeholder="Masukkan email login (opsional, bisa login pakai no telp)">
                                @error('email')
                                    <div class="invalid-feedback text-danger">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="">Cabang</label>
                                <select class="form-control select2" name="outlet_id" data-placeholder="Pilih Cabang"
                                    style="width: 100%;">
                                    <option value="" selected disabled>Pilih Cabang</option>
                                    @foreach ($outlets as $outlet)
                                        <option value="{{ $outlet->id }}"
                                            {{ old('outlet_id', $salesman->outlet_id) == $outlet->id ? 'selected' : '' }}>
                                            {{ $outlet->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('outlet_id')
                                    <div class="invalid-feedback text-danger">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <hr>
                            <div class="form-group">
                                <label for="">Password Login</label>
                                <input type="password" class="form-control" name="password"
                                    placeholder="Kosongkan jika tidak ingin mengganti password">
                                @error('password')
                                    <div class="invalid-feedback text-danger">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="">Konfirmasi Password</label>
                                <input type="password" class="form-control" name="confirm-password"
                                    placeholder="Ulangi password baru">
                            </div>
                            <p class="text-muted">
                                @if ($salesman->user)
                                    Akun login sales sudah terhubung. Login memakai email atau nomor telp.
                                @else
                                    Salesman ini belum punya akun login. Isi password untuk membuat akun sales.
                                @endif
                            </p>
                        </div><!-- /.box-body -->

                        <div class="box-footer">
                            <a href="{{ route('salesman.index') }}" class="btn btn-default">Kembali</a>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </div><!-- /.box -->
            </div>
        </div>
    </section>
@endsection
