<section class="content-header"><h1>{{ $customer ? 'Edit' : 'Tambah' }} Customer Penjualan</h1></section>
<section class="content"><div class="row"><div class="col-md-12"><div class="box box-primary">
    <div class="box-header with-border"><h3 class="box-title">{{ $customer ? 'Edit' : 'Tambah' }} Customer Penjualan</h3></div>
    <form action="{{ $formAction }}" method="POST">
        @csrf @if ($formMethod !== 'POST') @method($formMethod) @endif
        <div class="box-body">
            <div class="form-group"><label>Jenis Customer</label><select name="type" class="form-control" {{ $customer ? 'readonly' : '' }} required>
                @foreach ($types as $value => $label)<option value="{{ $value }}" {{ $selectedType === $value ? 'selected' : '' }}>{{ $label }}</option>@endforeach
            </select>@error('type')<div class="text-danger">{{ $message }}</div>@enderror</div>
            <div class="form-group"><label>Nama</label><input name="name" class="form-control" value="{{ old('name', $customer?->name) }}" required>@error('name')<div class="text-danger">{{ $message }}</div>@enderror</div>
            <div class="form-group"><label>Kode</label><input name="code" class="form-control" value="{{ old('code', $customer?->code) }}"></div>
            <div class="form-group"><label>Alamat</label><textarea name="alamat" class="form-control">{{ old('alamat', $customer?->alamat) }}</textarea></div>
            <div class="form-group"><label>Telepon</label><input name="no_telp" class="form-control" value="{{ old('no_telp', $customer?->no_telp) }}"></div>
            <div class="form-group"><label>Deskripsi</label><textarea name="desc" class="form-control">{{ old('desc', $customer?->desc) }}</textarea></div>
            <div class="checkbox"><label><input type="checkbox" name="is_active" value="1" {{ old('is_active', $customer?->is_active ?? true) ? 'checked' : '' }}> Aktif</label></div>
        </div>
        <div class="box-footer"><a href="{{ route('customer-penjualan.index') }}" class="btn btn-default">Kembali</a> <button class="btn btn-primary">Simpan</button></div>
    </form>
</div></div></div></section>
