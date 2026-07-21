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
                <div class="col-md-6">
                    <div class="box">
                        <div class="box-header with-border">
                            <h3 class="box-title">Profil Perusahaan</h3>
                        </div>
                        <div class="box-body">
                            <div class="form-group">
                                <label for="name">Nama Perusahaan</label>
                                <input class="form-control" type="text" name="name" id="name" value="{{ old('name', $name) }}" required>
                                @error('name')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="address">Alamat Perusahaan</label>
                                <input class="form-control" type="text" name="address" id="address" value="{{ old('address', $address) }}" required>
                                @error('address')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="telp">No Telp</label>
                                <input class="form-control" type="text" name="telp" id="telp" value="{{ old('telp', $telp) }}" required>
                                @error('telp')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input class="form-control" type="email" name="email" id="email" value="{{ old('email', $email) }}" required>
                                @error('email')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="website">Website</label>
                                <input class="form-control" type="url" name="website" id="website" value="{{ old('website', $website) }}">
                                @error('website')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="logo">Logo Perusahaan</label>
                                <input class="form-control" type="file" name="logo" id="logo" accept="image/*">
                                @error('logo')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                                @if ($logo)
                                    <div style="margin-top:10px">
                                        <img src="{{ Storage::url($logo) }}" alt="Logo" style="max-height:80px;">
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="box">
                        <div class="box-header with-border">
                            <h3 class="box-title">Template PO</h3>
                        </div>
                        <div class="box-body">
                            <div class="alert alert-info">
                                Template aktif akan fallback ke file contoh bawaan repo jika belum upload custom.
                            </div>

                            <div class="form-group">
                                <label>Template DOCX Aktif</label>
                                <p class="form-control-static">
                                    {{ $poTemplateDocx['label'] }}
                                    <span class="label {{ $poTemplateDocx['source'] === 'custom' ? 'label-success' : 'label-default' }}">
                                        {{ $poTemplateDocx['source'] === 'custom' ? 'Custom Upload' : 'Default Example' }}
                                    </span>
                                </p>
                                <a href="{{ route('setting.po-template.download', 'docx') }}" class="btn btn-default btn-sm">
                                    <i class="fa fa-download"></i> Download DOCX Aktif
                                </a>
                            </div>

                            <div class="form-group">
                                <label for="po_template_docx">Upload Template DOCX Baru</label>
                                <input class="form-control" type="file" name="po_template_docx" id="po_template_docx" accept=".docx">
                                @error('po_template_docx')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                                <div class="checkbox" style="margin-top:8px">
                                    <label>
                                        <input type="checkbox" name="reset_po_template_docx" value="1">
                                        Kembalikan DOCX ke contoh default
                                    </label>
                                </div>
                            </div>

                            <hr>

                            <div class="form-group">
                                <label>Template Excel Aktif</label>
                                <p class="form-control-static">
                                    {{ $poTemplateXlsx['label'] }}
                                    <span class="label {{ $poTemplateXlsx['source'] === 'custom' ? 'label-success' : 'label-default' }}">
                                        {{ $poTemplateXlsx['source'] === 'custom' ? 'Custom Upload' : 'Default Example' }}
                                    </span>
                                </p>
                                <a href="{{ route('setting.po-template.download', 'xlsx') }}" class="btn btn-default btn-sm">
                                    <i class="fa fa-download"></i> Download Excel Aktif
                                </a>
                            </div>

                            <div class="form-group">
                                <label for="po_template_xlsx">Upload Template Excel Baru</label>
                                <input class="form-control" type="file" name="po_template_xlsx" id="po_template_xlsx" accept=".xlsx,.xls">
                                @error('po_template_xlsx')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                                <div class="checkbox" style="margin-top:8px">
                                    <label>
                                        <input type="checkbox" name="reset_po_template_xlsx" value="1">
                                        Kembalikan Excel ke contoh default
                                    </label>
                                </div>
                            </div>

                            <div class="text-muted small">
                                Default example saat ini:
                                <code>contoh-po-docs.docx</code> dan <code>contoh-po-excel.xlsx</code>.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Simpan</button>
        </form>
    </section>
@endsection
