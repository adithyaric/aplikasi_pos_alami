@php
    $currentSupplier = $supplier ?? null;
    $initialPrefixText = old('po_builder_prefix_text', $poBuilderConfig['prefix_text'] ?? 'PO');
    $initialSeparator = old('po_builder_separator', $poBuilderConfig['separator'] ?? '-');
    $initialDateFormat = old('po_builder_date_format', $poBuilderConfig['date_format'] ?? 'yyyy_mm');
    $initialSequencePosition = old('po_builder_sequence_position', $poBuilderConfig['sequence_position'] ?? 'suffix');
    $initialSupplierCode = old('po_builder_include_supplier_code');
    $initialSupplierCode = null === $initialSupplierCode
        ? ($poBuilderConfig['include_supplier_code'] ?? true)
        : (bool) $initialSupplierCode;
    $showAdvanced = old('po_show_advanced');
    $showAdvanced = null === $showAdvanced
        ? ($poBuilderConfig['show_advanced'] ?? false)
        : (bool) $showAdvanced;
@endphp

<hr>
<div class="box box-default" style="border:1px solid #f4f4f4;">
    <div class="box-header with-border">
        <h3 class="box-title">Setting Nomor PO</h3>
    </div>
    <div class="box-body">
        <div class="alert alert-info" style="margin-bottom:15px;">
            Setting ini dibuat sederhana. Tinggal pilih prefix, format periode, dan posisi nomor urut.
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>Prefix Teks</label>
                    <input type="text" class="form-control" id="po_builder_prefix_text" name="po_builder_prefix_text"
                        value="{{ $initialPrefixText }}" placeholder="Contoh: PO">
                    <small class="text-muted">Biasanya cukup isi <code>PO</code>.</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Separator Antar Bagian</label>
                    <select class="form-control" id="po_builder_separator" name="po_builder_separator">
                        <option value="-" {{ '-' === $initialSeparator ? 'selected' : '' }}>Strip (-)</option>
                        <option value="/" {{ '/' === $initialSeparator ? 'selected' : '' }}>Slash (/)</option>
                        <option value="." {{ '.' === $initialSeparator ? 'selected' : '' }}>Titik (.)</option>
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Posisi Nomor Urut</label>
                    <select class="form-control" id="po_builder_sequence_position" name="po_builder_sequence_position">
                        <option value="suffix" {{ 'suffix' === $initialSequencePosition ? 'selected' : '' }}>Di Belakang</option>
                        <option value="prefix" {{ 'prefix' === $initialSequencePosition ? 'selected' : '' }}>Di Depan</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>Format Periode</label>
                    <select class="form-control" id="po_builder_date_format" name="po_builder_date_format">
                        <option value="yyyy_mm" {{ 'yyyy_mm' === $initialDateFormat ? 'selected' : '' }}>Tahun + Bulan Angka (202607)</option>
                        <option value="yyyy_roman" {{ 'yyyy_roman' === $initialDateFormat ? 'selected' : '' }}>Tahun + Bulan Romawi (2026VII)</option>
                        <option value="yy_mm" {{ 'yy_mm' === $initialDateFormat ? 'selected' : '' }}>Tahun 2 Digit + Bulan Angka (2607)</option>
                        <option value="yy_roman" {{ 'yy_roman' === $initialDateFormat ? 'selected' : '' }}>Tahun 2 Digit + Bulan Romawi (26VII)</option>
                        <option value="yyyy" {{ 'yyyy' === $initialDateFormat ? 'selected' : '' }}>Tahun 4 Digit (2026)</option>
                        <option value="yy" {{ 'yy' === $initialDateFormat ? 'selected' : '' }}>Tahun 2 Digit (26)</option>
                        <option value="mm" {{ 'mm' === $initialDateFormat ? 'selected' : '' }}>Bulan Angka (07)</option>
                        <option value="roman" {{ 'roman' === $initialDateFormat ? 'selected' : '' }}>Bulan Romawi (VII)</option>
                        <option value="none" {{ 'none' === $initialDateFormat ? 'selected' : '' }}>Tanpa Periode</option>
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Panjang Nomor Urut PO</label>
                    <input type="number" min="3" max="10" class="form-control" id="po_number_padding" name="po_number_padding"
                        value="{{ old('po_number_padding', optional($currentSupplier)->po_number_padding ?? 5) }}">
                    @error('po_number_padding')<div class="invalid-feedback text-danger">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group" style="margin-top:25px;">
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" id="po_builder_include_supplier_code" name="po_builder_include_supplier_code" value="1"
                                {{ $initialSupplierCode ? 'checked' : '' }}>
                            Tampilkan Kode Supplier
                        </label>
                    </div>
                    <small class="text-muted">Jika dicentang, sistem akan menaruh <code>{{ old('kode_supplier', optional($currentSupplier)->kode_supplier ?? $nextKode ?? 'S00001') }}</code>.</small>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label>Preview Nomor PO</label>
            <input type="text" class="form-control" id="po_number_preview" readonly>
            <small class="text-muted">Preview otomatis mengikuti kode supplier dan tanggal hari ini.</small>
        </div>

        <div class="checkbox" style="margin-bottom:10px;">
            <label>
                <input type="checkbox" id="po_show_advanced" name="po_show_advanced" value="1" {{ $showAdvanced ? 'checked' : '' }}>
                Tampilkan format advanced
            </label>
        </div>

        <div id="po_advanced_wrapper" style="{{ $showAdvanced ? '' : 'display:none;' }}">
            <div class="form-group">
                <label>Format Raw Nomor PO</label>
                <input type="text" class="form-control" id="po_number_prefix" name="po_number_prefix"
                    value="{{ old('po_number_prefix', $poBuilderConfig['custom_format'] ?? \App\Models\Supplier::DEFAULT_PO_NUMBER_FORMAT) }}"
                    placeholder="Contoh: PO-{SUPPLIER_CODE}-{YYYY}{ROMAN_MM}-{SEQ}">
                <small class="text-muted">
                    Token yang bisa dipakai:
                    <code>{SUPPLIER_CODE}</code>,
                    <code>{YYYY}</code>,
                    <code>{YY}</code>,
                    <code>{MM}</code>,
                    <code>{ROMAN_MM}</code>,
                    <code>{DD}</code>,
                    <code>{SEQ}</code>.
                    Jika <code>{SEQ}</code> tidak ditulis, sistem tetap menambahkan nomor urut di belakang untuk format lama.
                </small>
                @error('po_number_prefix')<div class="invalid-feedback text-danger">{{ $message }}</div>@enderror
            </div>
        </div>

        <hr>
        <h4>Template PO Supplier</h4>
        <p class="text-muted">
            Upload satu template khusus supplier ini dalam format XLSX atau DOCX. Saat PO dibuat untuk supplier ini,
            template tersebut dipakai lebih dulu. Jika kosong, sistem memakai template PO default.
        </p>
        @php $templatePath = data_get($currentSupplier, 'po_template'); @endphp
        <div class="well well-sm" style="min-height:130px; margin-bottom:10px;">
            <label>Template PO (XLSX atau DOCX)</label>
            <input class="form-control" type="file" name="po_template" accept=".xlsx,.docx">
            <small class="text-muted">Format file otomatis menentukan jenis export PO.</small>
            @error('po_template')
                <div class="invalid-feedback text-danger">{{ $message }}</div>
            @enderror
            @if ($templatePath)
                <p class="small text-success" style="margin:8px 0 4px;">
                    <i class="fa fa-check"></i> {{ basename($templatePath) }}
                    @if ($currentSupplier)
                        <a href="{{ route('supplier.po-template.download', $currentSupplier) }}" class="pull-right">
                            Download
                        </a>
                    @endif
                </p>
                <label class="checkbox-inline">
                    <input type="checkbox" name="reset_po_template" value="1">
                    Hapus template supplier
                </label>
            @endif
        </div>
    </div>
</div>
