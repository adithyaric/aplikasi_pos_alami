@extends('layouts.master')

@section('title', 'Tambah Customer PO')

@section('container')
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Tambah Customer PO</h3>
                </div>
                <form action="{{ route('customer-po.store') }}" method="POST">
                    @csrf
                    <div class="box-body">
                        <div class="form-group">
                            <label>Nama Customer PO</label>
                            <input type="text" class="form-control" name="name" value="{{ old('name') }}"
                                placeholder="Masukkan Nama Customer PO">
                            @error('name')<div class="invalid-feedback text-danger">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="box-footer">
                        <a href="{{ route('customer-po.index') }}" class="btn btn-default">Kembali</a>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
