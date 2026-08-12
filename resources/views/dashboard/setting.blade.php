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
                                <p class="help-block">Gunakan variabel <code>&#123;&#123;company.logo&#125;&#125;</code> pada template untuk menampilkan logo.</p>
                                @error('logo') <div class="text-danger">{{ $message }}</div> @enderror
                                @if ($logo)
                                    <div style="margin-top:10px">
                                        <img src="{{ route('setting.media', 'logo') }}" alt="Logo" style="max-height:80px;">
                                    </div>
                                @endif
                            </div>
                            <div class="form-group">
                                <label for="head_office_signature">TTD Head Office</label>
                                <input class="form-control" type="file" name="head_office_signature" id="head_office_signature" accept="image/*">
                                <p class="help-block">Gunakan variabel <code>&#123;&#123;company.ttd&#125;&#125;</code> pada template dokumen untuk menempatkan gambar ini.</p>
                                @error('head_office_signature') <div class="text-danger">{{ $message }}</div> @enderror
                                @if ($headOfficeSignature)
                                    <div id="head_office_signature_current" style="margin-top:10px">
                                        <div><strong>TTD saat ini:</strong></div>
                                        <img src="{{ route('setting.media', 'signature') }}" alt="TTD Head Office" style="max-height:100px; max-width:260px;">
                                    </div>
                                @endif
                                <div id="head_office_signature_preview_wrapper" style="display:none; margin-top:10px">
                                    <div><strong>Preview TTD baru:</strong></div>
                                    <img id="head_office_signature_preview" src="" alt="Preview TTD Head Office" style="max-height:100px; max-width:260px;">
                                </div>
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

@section('page-script')
<script>
    (function () {
        var input = document.getElementById('head_office_signature');
        var preview = document.getElementById('head_office_signature_preview');
        var wrapper = document.getElementById('head_office_signature_preview_wrapper');
        var current = document.getElementById('head_office_signature_current');

        if (!input || !preview || !wrapper) {
            return;
        }

        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            if (!file) {
                wrapper.style.display = 'none';
                return;
            }

            var reader = new FileReader();
            reader.onload = function (event) {
                preview.src = event.target.result;
                wrapper.style.display = 'block';
                if (current) {
                    current.style.opacity = '0.45';
                }
            };
            reader.readAsDataURL(file);
        });
    }());
</script>
@endsection
