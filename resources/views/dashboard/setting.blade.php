@extends('layouts.master')

@section('title', 'Setting')

@section('container')
    <section class="content-header">
        <h1>Dashboard Setting</h1>
    </section>

    <section class="content">
        <form id="form" method="POST" action="{{ route('setting.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="row">
                <div class="col-md-8 col-md-offset-2">
                    <div class="box">
                        <div class="box-header with-border">
                            <h3 class="box-title">Profil Perusahaan</h3>
                        </div>
                        <div class="box-body">
                            <div class="form-group">
                                <label for="name">Nama Perusahaan</label>
                                <input class="form-control" type="text" name="name" id="name" value="{{ old('name', $name) }}" required>
                                @error('name') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label for="address">Alamat Perusahaan</label>
                                <input class="form-control" type="text" name="address" id="address" value="{{ old('address', $address) }}" required>
                                @error('address') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label for="telp">No Telp</label>
                                <input class="form-control" type="text" name="telp" id="telp" value="{{ old('telp', $telp) }}" required>
                                @error('telp') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input class="form-control" type="email" name="email" id="email" value="{{ old('email', $email) }}" required>
                                @error('email') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label for="website">Website</label>
                                <input class="form-control" type="url" name="website" id="website" value="{{ old('website', $website) }}">
                                @error('website') <div class="text-danger">{{ $message }}</div> @enderror
                            </div>
                            <div class="form-group">
                                <label for="logo">Logo Perusahaan</label>
                                <input class="form-control" type="file" name="logo" id="logo" accept="image/*">
                                @error('logo') <div class="text-danger">{{ $message }}</div> @enderror
                                @if ($logo)
                                    <div style="margin-top:10px">
                                        <img src="{{ Storage::url($logo) }}" alt="Logo" style="max-height:80px;">
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="box-footer">
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
@endsection
