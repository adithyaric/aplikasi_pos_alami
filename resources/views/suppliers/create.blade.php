@extends('layouts.master')

@section('title', 'Tambah Supplier')

@section('container')
<section class="content">
    <div class="row">
        <!-- left column -->
        <div class="col-md-12">
            <!-- general form elements -->
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Tambah Supplier</h3>
                </div><!-- /.box-header -->
                <!-- form start -->
                <form action="{{ route('supplier.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="box-body">
                        <div class="form-group">
                            <label>Kode</label>
                            <input type="text" class="form-control" name="kode_supplier"
                                value="{{ old('kode_supplier', $nextKode) }}">
                            @error('kode_supplier')<div class="invalid-feedback text-danger">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label>Nama Supplier <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" value="{{ old('name') }}"
                                placeholder="Masukkan Nama Supplier">
                            @error('name')<div class="invalid-feedback text-danger">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label>Alamat <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="alamat" value="{{ old('alamat') }}"
                                placeholder="Masukkan Alamat">
                            @error('alamat')<div class="invalid-feedback text-danger">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label>Nomor Telp <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="no_telp" value="{{ old('no_telp') }}"
                                placeholder="Masukkan Nomor Telp">
                            @error('no_telp')<div class="invalid-feedback text-danger">{{ $message }}</div>@enderror
                        </div>
                        <hr>
                        <div class="form-group">
                            <label>Format Prefix Nomor PO</label>
                            <input type="text" class="form-control" name="po_number_prefix"
                                value="{{ old('po_number_prefix', 'PO-{SUPPLIER_CODE}-{YYYY}{MM}-') }}"
                                placeholder="Contoh: PO-{SUPPLIER_CODE}-{YYYY}{MM}-">
                            <small class="text-muted">
                                Token yang bisa dipakai: <code>{SUPPLIER_CODE}</code>, <code>{YYYY}</code>, <code>{YY}</code>, <code>{MM}</code>, <code>{DD}</code>.
                            </small>
                            @error('po_number_prefix')<div class="invalid-feedback text-danger">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label>Panjang Nomor Urut PO</label>
                            <input type="number" min="3" max="10" class="form-control" name="po_number_padding"
                                value="{{ old('po_number_padding', 5) }}">
                            @error('po_number_padding')<div class="invalid-feedback text-danger">{{ $message }}</div>@enderror
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
