@extends('layouts.master')

@section('title', 'Akun Perkiraan')

@section('container')
<section class="content-header"><h1>Akun Perkiraan <small>Chart of Accounts</small></h1></section>
<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">Tambah Akun / Sub Akun</h3></div>
        <form method="POST" action="{{ route('accounting.accounts.store') }}">
            @csrf
            <div class="box-body"><div class="row">
                <div class="col-md-2"><input name="code" class="form-control" placeholder="Kode" value="{{ old('code') }}" required></div>
                <div class="col-md-3"><input name="name" class="form-control" placeholder="Nama akun" value="{{ old('name') }}" required></div>
                <div class="col-md-2"><input name="type_code" class="form-control" placeholder="Tipe (BANK/REVE)" value="{{ old('type_code') }}" required></div>
                <div class="col-md-3"><select name="parent_id" class="form-control"><option value="">Akun induk (opsional)</option>@foreach($parents as $parent)<option value="{{ $parent->id }}">{{ $parent->code }} - {{ $parent->name }}</option>@endforeach</select></div>
                <div class="col-md-1"><label class="checkbox-inline"><input type="checkbox" name="is_header" value="1"> Header</label></div>
                <div class="col-md-1"><button class="btn btn-primary btn-block"><i class="fa fa-plus"></i></button></div>
            </div></div>
        </form>
    </div>

    <div class="box box-default">
        <div class="box-header with-border">
            <form class="form-inline"><input class="form-control" name="search" placeholder="Cari kode/nama" value="{{ request('search') }}"> <select class="form-control" name="type_code"><option value="">Semua tipe</option>@foreach($types as $type)<option value="{{ $type }}" @selected(request('type_code') === $type)>{{ $type }}</option>@endforeach</select> <button class="btn btn-default">Filter</button></form>
        </div>
        <div class="table-responsive"><table class="table table-bordered table-hover">
            <thead><tr><th>Kode</th><th>Nama</th><th>Tipe</th><th>Akun Induk</th><th>Header</th><th>Status</th><th width="230">Aksi</th></tr></thead>
            <tbody>
            @forelse($accounts as $account)
                <tr>
                    <form method="POST" action="{{ route('accounting.accounts.update', $account) }}">
                        @csrf @method('PUT')
                        <td><input name="code" class="form-control input-sm" value="{{ $account->code }}" required></td>
                        <td><input name="name" class="form-control input-sm" value="{{ $account->name }}" required></td>
                        <td><input name="type_code" class="form-control input-sm" value="{{ $account->type_code }}" required></td>
                        <td><select name="parent_id" class="form-control input-sm"><option value="">-</option>@foreach($parents as $parent)<option value="{{ $parent->id }}" @selected($account->parent_id === $parent->id)>{{ $parent->code }}</option>@endforeach</select></td>
                        <td class="text-center"><input type="checkbox" name="is_header" value="1" @checked($account->is_header)></td>
                        <td><label class="checkbox-inline"><input type="checkbox" name="is_active" value="1" @checked($account->is_active)> Aktif</label></td>
                        <td><button class="btn btn-xs btn-success"><i class="fa fa-save"></i> Simpan</button></form>
                            <form method="POST" action="{{ route('accounting.accounts.destroy', $account) }}" style="display:inline" onsubmit="return confirm('Hapus/nonaktifkan akun ini?')">@csrf @method('DELETE')<button class="btn btn-xs btn-danger"><i class="fa fa-trash"></i></button></form>
                        </td>
                </tr>
            @empty <tr><td colspan="7" class="text-center">Belum ada akun.</td></tr>@endforelse
            </tbody>
        </table></div>
    </div>
</section>
@endsection
