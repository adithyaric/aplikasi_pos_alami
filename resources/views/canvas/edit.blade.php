@extends('layouts.master')

@section('title', 'Edit Canvas')

@section('container')
    <section class="content">
        <div class="row">
            <!-- left column -->
            <div class="col-md-12">
                <!-- general form elements -->
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">Edit Canvas</h3>
                    </div><!-- /.box-header -->
                    <!-- form start -->
                    <form action="{{ route('canvases.update', $canvases) }}" method="POST" enctype="multipart/form-data">
                        @method('PUT')
                        @csrf
                        <div class="box-body">
                            <div class="form-group">
                                <label for="">Kode Canvas</label>
                                <input type="text" class="form-control" name="code" value="{{ old('code', $canvases->code) }}"
                                    placeholder="Masukkan Kode Canvas">
                                @error('code')
                                    <div class="invalid-feedback text-danger">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="">Nama</label>
                                <input type="text" class="form-control" name="name" value="{{ old('name', $canvases->name) }}"
                                    placeholder="Masukkan Nama">
                                @error('name')
                                    <div class="invalid-feedback text-danger">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="">Deskripsi (Opsional)</label>
                                <input type="text" class="form-control" name="desc" value="{{ old('desc', $canvases->desc) }}"
                                    placeholder="Masukkan Deskripsi">
                                @error('desc')
                                    <div class="invalid-feedback text-danger">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="">Alamat</label>
                                <textarea class="form-control" name="alamat" rows="2" placeholder="Masukkan Alamat">{{ old('alamat', $canvases->alamat) }}</textarea>
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
                                        <input type="text" class="form-control" name="no_telp" value="{{ old('no_telp', $canvases->no_telp) }}"
                                            placeholder="Masukkan Nomor Telp">
                                        @error('no_telp')
                                            <div class="invalid-feedback text-danger">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="">Termin (Hari)</label>
                                        <input type="number" class="form-control" name="termin_days" value="{{ old('termin_days', $canvases->termin_days) }}"
                                            min="0" placeholder="0">
                                        @error('termin_days')
                                            <div class="invalid-feedback text-danger">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="">Limit Piutang</label>
                                        <input type="number" class="form-control" name="credit_limit" value="{{ old('credit_limit', $canvases->credit_limit) }}"
                                            min="0" step="0.01" placeholder="0">
                                        @error('credit_limit')
                                            <div class="invalid-feedback text-danger">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $canvases->is_active ?? true) ? 'checked' : '' }}>
                                    Aktif
                                </label>
                            </div>
                        </div><!-- /.box-body -->

                        <div class="box-footer">
                            <a href="{{ route('canvases.index') }}" class="btn btn-default">Kembali</a>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </div><!-- /.box -->
            </div>
        </div>
    </section>
@endsection
