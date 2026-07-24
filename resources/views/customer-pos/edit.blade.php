@extends('layouts.master')

@section('title', 'Edit Customer PO')

@section('container')
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Edit Customer PO</h3>
                </div>
                <form action="{{ route('customer-po.update', $customerPo) }}" method="POST">
                    @method('PUT')
                    @csrf
                    <div class="box-body">
                        <div class="form-group">
                            <label>Nama</label>
                            <input type="text" class="form-control" name="name"
                                value="{{ old('name', $customerPo->name) }}"
                                placeholder="Masukkan Nama">
                            @error('name')<div class="invalid-feedback text-danger">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label>Nama Perusahaan</label>
                            <input type="text" class="form-control" name="company_name"
                                value="{{ old('company_name', $customerPo->company_name) }}"
                                placeholder="Masukkan Nama Perusahaan">
                            @error('company_name')<div class="invalid-feedback text-danger">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label>Alamat</label>
                            <textarea class="form-control" name="address" rows="3"
                                placeholder="Masukkan Alamat">{{ old('address', $customerPo->address) }}</textarea>
                            @error('address')<div class="invalid-feedback text-danger">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" class="form-control" name="phone"
                                value="{{ old('phone', $customerPo->phone) }}"
                                placeholder="Masukkan Phone">
                            @error('phone')<div class="invalid-feedback text-danger">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" class="form-control" name="email"
                                value="{{ old('email', $customerPo->email) }}"
                                placeholder="Masukkan Email">
                            @error('email')<div class="invalid-feedback text-danger">{{ $message }}</div>@enderror
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
