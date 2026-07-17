@extends('layouts.master')

@section('title', 'Edit Product')

@section('container')
<section class="content">
    <div class="row">
        <!-- left column -->
        <div class="col-md-12">
            <!-- general form elements -->
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Edit Product</h3>
                </div><!-- /.box-header -->
                <!-- form start -->
                <form action="{{ route('product.update', $product->id) }}" method="POST" enctype="multipart/form-data">
                    @method('PUT')
                    @csrf
                    <div class="row box-body">
                        <div class="col-md-12 form-group">
                            <label for="">Nama</label>
                            <input type="text" class="form-control" name="name"
                                value="{{ old('name', $product->name) }}" placeholder="Masukkan Nama">
                            @error('name')
                            <div class="invalid-feedback text-danger">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>
                        <div class="col-md-12 form-group">
                            <label for="">Barcode</label>
                            <input type="text" class="form-control" name="code"
                                value="{{ old('code', $product->code) }}" placeholder="Masukkan Barcode">
                            @error('code')
                            <div class="invalid-feedback text-danger">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>
                        <!-- Satuan Besar (Konversi) -->
                        <div class="col-md-12 form-group">
                            <label for="">Satuan Terbesar <small class="text-muted">(Opsional)</small></label>
                            <input type="text" class="form-control" name="satuan_terbesar"
                                value="{{ old('satuan_terbesar', $product->satuan_terbesar ?? '') }}" placeholder="karton / box / lusin">
                            @error('satuan_terbesar')
                            <div class="invalid-feedback text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-12 form-group">
                            <label for="">Isi Konversi Terbesar <small class="text-danger">(Contoh: 1 karton = 12 pcs)</small></label>
                            <input type="text" class="form-control integer-only-input" name="konversi_qty_terbesar"
                                value="{{ old('konversi_qty_terbesar', $product->konversi_qty_terbesar ?? '') }}" inputmode="numeric"
                                pattern="[0-9]*" placeholder="12" autocomplete="off">
                            @error('konversi_qty_terbesar')
                            <div class="invalid-feedback text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <!-- Satuan Besar (Konversi) -->
                        <div class="col-md-12 form-group">
                            <label for="">Satuan Besar <small class="text-muted">(Opsional)</small></label>
                            <input type="text" class="form-control" name="satuan_besar"
                                value="{{ old('satuan_besar', $product->satuan_besar ?? '') }}" placeholder="karton / box / lusin">
                            @error('satuan_besar')
                            <div class="invalid-feedback text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <!-- Satuan -->
                        <div class="col-md-12 form-group">
                            <label for="">Satuan</label>
                            <input type="text" class="form-control" name="satuan"
                                value="{{ old('satuan', $product->satuan ?? '') }}" placeholder="Contoh: Pcs, Box, Kg">
                            @error('satuan')
                            <div class="invalid-feedback text-danger">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>
                        <div class="col-md-12 form-group">
                            <label for="">Isi Konversi <small class="text-danger">(Contoh: 1 karton = 12 pcs)</small></label>
                            <input type="text" class="form-control integer-only-input" name="konversi_qty"
                                value="{{ old('konversi_qty', $product->konversi_qty ?? '') }}" inputmode="numeric"
                                pattern="[0-9]*" placeholder="12" autocomplete="off">
                            @error('konversi_qty')
                            <div class="invalid-feedback text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-12 form-group">
                            <label for="">Harga Beli</label>
                            <input type="text" class="form-control" name="harga_beli"
                                value="{{ old('harga_beli', $product->harga_beli) }}" placeholder="Masukkan Harga Beli">
                            @error('harga_beli')
                            <div class="invalid-feedback text-danger">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>
                        <div class="col-md-12 form-group">
                            <label>Category</label>
                            <select class="form-control select2" name="category_id" data-placeholder="Pilih Category"
                                style="width: 100%;">
                                <option value="" selected disabled>Pilih Category</option>
                                @foreach ($categories as $category)
                                <option value="{{ $category->id }}"
                                    {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('category_id')
                            <div class="invalid-feedback text-danger">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>
                        <!-- Multiple Select Supplier -->
                        <div class="col-md-12 form-group">
                            <label>Supplier</label>
                            <select class="form-control select2" name="supplier_ids[]" multiple
                                data-placeholder="Pilih Supplier" style="width: 100%;">
                                @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}"
                                    {{ in_array($supplier->id, old('supplier_ids', $selectedSuppliers ?? [])) ? 'selected' : '' }}>
                                    {{ $supplier->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('supplier_ids')
                            <div class="invalid-feedback text-danger">
                                {{ $message }}
                            </div>
                            @enderror
                            @error('supplier_ids.*')
                            <div class="invalid-feedback text-danger">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>
                    </div><!-- /.box-body -->

                    <div class="box-footer">
                        <a href="{{ route('product.index') }}" class="btn btn-default">Kembali</a>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div><!-- /.box -->
        </div>
    </div>
</section>
@endsection
@section('page-script')
<script>
    $(document).on('keydown', '.integer-only-input', function(e) {
        if ([190, 188, 69, 110].includes(e.keyCode)) {
            e.preventDefault();
        }
    });

    $(document).on('input', '.integer-only-input', function() {
        let cleaned = $(this).val().replace(/[^0-9]/g, '');
        if ($(this).val() !== cleaned) {
            $(this).val(cleaned);
        }
    });
</script>
<script>
    $(document).ready(function() {
        // Initialize select2 for multiple select
        $('.select2').select2({
            placeholder: "Pilih Supplier",
            allowClear: true
        });
        var statusProdukModal = $('#statusProdukModal');

        function toggleStatusNote(forceOpen) {
            var isTambahanDiskon = $('#status-produk-select').val() === 'tambahan_diskon';
            $('#status-note-group').toggle(isTambahanDiskon);

            if (!isTambahanDiskon) {
                $('#status-note-hidden').val('');
                $('#status-note-display').val('');
                $('#status-note-input-modal').val('');
                return;
            }

            if (forceOpen || !$('#status-note-hidden').val()) {
                statusProdukModal.modal('show');
            }
        }

        $('#status-produk-select').on('change', function() {
            toggleStatusNote(true);
        });

        $('#btn-save-status-note').on('click', function() {
            var note = $('#status-note-input-modal').val().trim();
            $('#status-note-hidden').val(note);
            $('#status-note-display').val(note);
            statusProdukModal.modal('hide');
        });

        toggleStatusNote(false);
    });
</script>
@endsection
