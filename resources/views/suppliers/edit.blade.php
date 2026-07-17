@extends('layouts.master')

@section('title', 'Edit Supplier')

@section('container')
<section class="content">
    <div class="row">
        <!-- left column -->
        <div class="col-md-12">
            <!-- general form elements -->
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Edit Supplier</h3>
                </div><!-- /.box-header -->
                <!-- form start -->
                <form action="{{ route('supplier.update', $supplier->id) }}" method="POST" enctype="multipart/form-data">
                    @method('PUT')
                    @csrf
                    <div class="box-body">
                        <div class="form-group">
                            <label>Kode</label>
                            <input type="text" class="form-control" name="kode_supplier"
                                value="{{ old('kode_supplier', $supplier->kode_supplier) }}" placeholder="Masukkan Kode Supplier">
                            @error('kode_supplier')<div class="invalid-feedback text-danger">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label>Nama Supplier <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name"
                                value="{{ old('name', $supplier->name) }}" placeholder="Masukkan Nama Supplier">
                            @error('name')<div class="invalid-feedback text-danger">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label>Alamat <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="alamat"
                                value="{{ old('alamat', $supplier->alamat) }}" placeholder="Masukkan Alamat">
                            @error('alamat')<div class="invalid-feedback text-danger">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label>Nomor Telp <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="no_telp"
                                value="{{ old('no_telp', $supplier->no_telp) }}" placeholder="Masukkan Nomor Telp">
                            @error('no_telp')<div class="invalid-feedback text-danger">{{ $message }}</div>@enderror
                        </div>
                    </div><!-- /.box-body -->

                    <div class="box-footer">
                        <a href="{{ route('supplier.index') }}" class="btn btn-default">Kembali</a>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div><!-- /.box -->
        </div>
    </div>
</section>
@endsection

@section('page-script')

@endsection
