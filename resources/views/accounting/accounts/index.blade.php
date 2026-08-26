@extends('layouts.master')

@section('title', 'Akun Perkiraan')

@section('container')
<section class="content-header">
    <h1>Akun Perkiraan <small>Chart of Accounts</small></h1>
</section>

<section class="content">
    @if($errors->any())
        <div class="alert alert-danger">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <ul style="margin:0;padding-left:18px">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-plus-circle"></i> Tambah Akun / Sub Akun</h3>
            <div class="box-tools pull-right">
                <a href="{{ route('accounting.index') }}" class="btn btn-xs btn-default">
                    <i class="fa fa-arrow-left"></i> Kembali ke Akuntansi
                </a>
            </div>
        </div>
        <form method="POST" action="{{ route('accounting.accounts.store') }}">
            @csrf
            <div class="box-body" style="padding-bottom:8px">
                <div class="row">
                    <div class="col-md-2 col-sm-6">
                        <label class="small text-muted">Kode Perkiraan</label>
                        <input name="code" class="form-control input-sm" placeholder="Contoh: 110106" value="{{ old('code') }}" required>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="small text-muted">Nama Akun</label>
                        <input name="name" class="form-control input-sm" placeholder="Nama akun" value="{{ old('name') }}" required>
                    </div>
                    <div class="col-md-2 col-sm-6">
                        <label class="small text-muted">Tipe Akun</label>
                        <input name="type_code" class="form-control input-sm" placeholder="BANK / REVE / EXPS" value="{{ old('type_code') }}" required>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="small text-muted">Akun Induk</label>
                        <select name="parent_id" class="form-control input-sm select2 account-select2"
                            data-placeholder="Pilih akun induk" style="width:100%">
                            <option value=""></option>
                            @foreach($parents as $parent)
                                <option value="{{ $parent->id }}" @selected((int) old('parent_id') === (int) $parent->id)>
                                    {{ $parent->code }} - {{ $parent->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-12">
                        <div style="margin-top:24px">
                            <label class="checkbox-inline" style="display:block;margin:0 0 8px;padding-left:20px">
                                <input type="checkbox" name="is_header" value="1" @checked(old('is_header'))>
                                Akun header
                            </label>
                            <button class="btn btn-primary btn-sm btn-block" type="submit">
                                <i class="fa fa-plus"></i> Tambah Akun
                            </button>
                        </div>
                    </div>
                </div>
                <p class="help-block" style="margin:7px 0 0">
                    <i class="fa fa-info-circle"></i> Akun header hanya sebagai induk dan tidak dapat dipakai langsung pada jurnal.
                </p>
            </div>
        </form>
    </div>

    <div class="box box-default">
        <div class="box-header with-border">
            <div class="row">
                <div class="col-sm-7">
                    <h3 class="box-title"><i class="fa fa-list"></i> Daftar Akun</h3>
                    @if($accounts->total() > 0)
                        <span class="text-muted small" style="margin-left:8px">
                            Menampilkan {{ $accounts->firstItem() }}–{{ $accounts->lastItem() }} dari {{ $accounts->total() }}
                        </span>
                    @endif
                </div>
                <div class="col-sm-5">
                    <form class="form-inline pull-right" style="margin-top:-5px">
                        <input class="form-control input-sm" name="search" placeholder="Cari kode/nama"
                            value="{{ request('search') }}" style="width:190px">
                        <select class="form-control input-sm select2 account-select2" name="type_code"
                            data-placeholder="Semua tipe" style="width:145px">
                            <option value=""></option>
                            @foreach($types as $type)
                                <option value="{{ $type }}" @selected(request('type_code') === $type)>{{ $type }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-sm btn-default" title="Filter"><i class="fa fa-search"></i></button>
                        @if(request()->hasAny(['search', 'type_code']))
                            <a href="{{ route('accounting.accounts.index') }}" class="btn btn-sm btn-link" title="Reset filter"><i class="fa fa-times"></i></a>
                        @endif
                    </form>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-condensed" style="margin-bottom:0">
                <thead>
                    <tr>
                        <th style="width:115px">Kode</th>
                        <th>Nama</th>
                        <th style="width:85px">Tipe</th>
                        <th style="width:205px">Akun Induk</th>
                        <th class="text-center" style="width:75px">Header</th>
                        <th class="text-center" style="width:85px">Status</th>
                        <th class="text-center" style="width:155px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($accounts as $account)
                    @php
                        $formId = 'account-update-'.$account->id;
                    @endphp
                    <tr>
                        <td>
                            <form id="{{ $formId }}" method="POST" action="{{ route('accounting.accounts.update', $account) }}">
                                @csrf @method('PUT')
                            </form>
                            <input form="{{ $formId }}" name="code" class="form-control input-sm" value="{{ $account->code }}" required>
                        </td>
                        <td><input form="{{ $formId }}" name="name" class="form-control input-sm" value="{{ $account->name }}" required></td>
                        <td><input form="{{ $formId }}" name="type_code" class="form-control input-sm" value="{{ $account->type_code }}" required></td>
                        <td>
                            <select form="{{ $formId }}" name="parent_id" class="form-control input-sm select2 account-select2"
                                data-placeholder="Tanpa induk" style="width:100%">
                                <option value=""></option>
                                @foreach($parents as $parent)
                                    <option value="{{ $parent->id }}" @selected((int) $account->parent_id === (int) $parent->id)>
                                        {{ $parent->code }} - {{ $parent->name }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td class="text-center"><input form="{{ $formId }}" type="checkbox" name="is_header" value="1" @checked($account->is_header)></td>
                        <td class="text-center"><label class="checkbox-inline"><input form="{{ $formId }}" type="checkbox" name="is_active" value="1" @checked($account->is_active)> Aktif</label></td>
                        <td class="text-center text-nowrap">
                            <button form="{{ $formId }}" type="submit" class="btn btn-xs btn-success" title="Simpan perubahan">
                                <i class="fa fa-save"></i> Simpan
                            </button>
                            <form method="POST" action="{{ route('accounting.accounts.destroy', $account) }}" style="display:inline" onsubmit="return confirm('Hapus/nonaktifkan akun ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-xs btn-danger" title="Hapus/nonaktifkan"><i class="fa fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted" style="padding:24px">Belum ada akun yang sesuai.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="box-footer clearfix">
            <div class="pull-right">{{ $accounts->onEachSide(1)->links() }}</div>
        </div>
    </div>
</section>
@endsection

@section('page-script')
<script>
$(function () {
    $('.account-select2').each(function () {
        if (!$(this).hasClass('select2-hidden-accessible')) {
            $(this).select2({
                width: '100%',
                allowClear: true,
                placeholder: $(this).data('placeholder') || 'Pilih'
            });
        }
    });
});
</script>
@endsection
