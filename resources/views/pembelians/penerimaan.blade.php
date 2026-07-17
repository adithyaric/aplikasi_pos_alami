@extends('layouts.master')
@section('title', 'Penerimaan Barang')
@section('container')
    @php
        $isLocked = $pembelian->receipt_status === 'completed' || $pembelian->stocks->count() > 0;
    @endphp

    <section class="content-header">
        <h1>Penerimaan Barang <small>{{ $pembelian->code }}</small></h1>
        <ol class="breadcrumb">
            <li><a href="{{ route('penerimaan.index') }}">Penerimaan Barang</a></li>
            <li class="active">{{ $pembelian->code }}</li>
        </ol>
    </section>

    <section class="content">
        <form
            action="{{ $isLocked ? route('pembelian.update-penerimaan', $pembelian) : route('pembelian.store-penerimaan', $pembelian) }}"
            method="POST"
            enctype="multipart/form-data">
            @csrf

            <div class="row">
                <div class="col-md-12">
                    <div class="box {{ $isLocked ? 'box-success' : 'box-warning' }}">
                        <div class="box-header with-border">
                                    <h3 class="box-title"><i class="fa fa-file-text-o"></i> Info Purchase Order</h3>
                                    <div class="box-tools pull-right">
                                        @if($isLocked)
                                            <span class="label label-success">
                                                <i class="fa fa-lock"></i> Tersimpan
                                            </span>
                                        @else
                                            <span class="label label-warning">
                                                <i class="fa fa-pencil"></i> Belum disimpan
                                            </span>
                                        @endif
                                        <button type="button" class="btn btn-box-tool" data-widget="collapse">
                                            <i class="fa fa-minus"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="box-body">
                                    <table class="table table-condensed" style="margin:0">
                                        <tr><th width="130">Kode PO</th><td><strong>{{ $pembelian->code }}</strong></td></tr>
                                        <tr><th>Supplier</th><td>{{ $pembelian->supplier->name }}</td></tr>
                                        <tr><th>Total</th><td>Rp {{ number_format($pembelian->total, 0, ',', '.') }}</td></tr>
                                        <tr>
                                            <th>Status Bayar</th>
                                            <td>
                                                @php $ps = $pembelian->pembelianTransaction?->status ?? 'unpaid'; @endphp
                                                <span class="label label-{{ $ps === 'paid' ? 'success' : ($ps === 'partial' ? 'warning' : 'danger') }}">
                                                    {{ strtoupper($ps) }}
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                    <hr>
                                </div>
                        <div class="box-body table-responsive text-nowrap" style="padding:0">
                            <table class="table table-bordered table-striped" style="margin:0">
                                <thead>
                                    <tr>
                                        <th class="text-center">No</th>
                                        <th>Product</th>
                                        <th>Satuan</th>
                                        <th>Qty PO</th>
                                        <th>Qty Terima</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($pembelian->pembelianProducts as $item)
                                        @php
                                            $existingStock = $pembelian->stocks()
                                                ->where('product_id', $item->product_id)
                                                ->first();
                                        @endphp
                                        <tr>
                                            <td class="text-center text-muted">
                                                <small>{{ $loop->iteration }}</small>
                                            </td>
                                            <td>
                                                <strong>{{ $item->product->name }}</strong>
                                                <br><small class="text-muted">@currency($item->harga_beli)</small>

                                                @if(!$isLocked)
                                                    <input type="hidden"
                                                        name="items[{{ $loop->index }}][product_id]"
                                                        value="{{ $item->product_id }}">
                                                @endif
                                            </td>
                                            <td>{{ $item->product->satuan ?? '-' }}</td>
                                            <td>
                                                @php
                                                    $qtyPOSatuanBesar = $item->product->konversi_qty
                                                        ? (int)($item->qty / $item->product->konversi_qty)
                                                        : $item->qty;
                                                @endphp
                                                {{ $qtyPOSatuanBesar }} {{ $item->product->satuan_besar ?? $item->product->satuan }}
                                                <br><small class="text-muted">= {{ $item->qty }} {{ $item->product->satuan }}</small>
                                            </td>
                                            <td>
                                            @if($isLocked)
                                                @php
                                                    $qtyDiterimaSatuanBesar = $item->product->konversi_qty && $existingStock
                                                        ? (int)($existingStock->qty / $item->product->konversi_qty)
                                                        : ($existingStock->qty ?? 0);
                                                @endphp
                                                <span class="label label-success">{{ $qtyDiterimaSatuanBesar }} {{ $item->product->satuan_besar ?? $item->product->satuan }}</span>
                                                <br><small class="text-muted">= {{ $existingStock->qty ?? 0 }} {{ $item->product->satuan }}</small>
                                            @else
                                                @php
                                                    $defaultQtySatuanBesar = $item->product->konversi_qty
                                                        ? (int)(($existingStock->qty ?? $item->qty) / $item->product->konversi_qty)
                                                        : ($existingStock->qty ?? $item->qty);
                                                @endphp
                                                <input type="number"
                                                    name="items[{{ $loop->index }}][qty_diterima]"
                                                    class="form-control input-sm text-center"
                                                    min="1"
                                                    max="{{ $qtyPOSatuanBesar }}"
                                                    value="{{ old('items.' . $loop->index . '.qty_diterima', $defaultQtySatuanBesar) }}"
                                                    data-konversi-qty="{{ $item->product->konversi_qty ?? 1 }}"
                                                    required>
                                                <small class="text-muted qty-kecil-display">
                                                    = {{ ($defaultQtySatuanBesar) * ($item->product->konversi_qty ?? 1) }} {{ $item->product->satuan }}
                                                </small>
                                            @endif
                                        </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if($isLocked)
                            <div class="box-footer text-muted">
                                <i class="fa fa-lock"></i> Items sudah tersimpan dan tidak bisa diubah.
                                @if($pembelian->stocks->count())
                                    <a href="{{ route('laporan.penerimaan', [$pembelian->id, 'po']) }}" class="btn btn-info btn-xs pull-right">
                                        <i class="fa fa-file-excel-o"></i> Export Pembelian
                                    </a>
                                @endif
                            </div>
                        @else
                            {{--  <div class="box-footer text-muted">
                                <i class="fa fa-info-circle"></i> Isi SKU & qty lalu klik <strong>Simpan Penerimaan</strong>.
                            </div>  --}}
                        @endif
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="row">
                        <div class="col-md-12">
                            {{-- Detail Penerimaan --}}
                            <div class="box box-primary">
                                <div class="box-header with-border">
                                    <h3 class="box-title"> Detail Penerimaan</h3>
                                    @if($isLocked)
                                        <div class="box-tools pull-right">
                                            <span class="label label-success"><i class="fa fa-lock"></i> Items Terkunci</span>
                                        </div>
                                    @endif
                                </div>
                                <div class="box-body">
                                    <div class="form-group">
                                        <label>Nomor Penerimaan <span class="text-danger">*</span></label>
                                        @php
                                            $supplierCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', substr($pembelian->supplier?->name ?? 'SUP', 0, 6)));
                                            $poCode = preg_replace('/[^A-Za-z0-9]/', '', $pembelian->code);
                                            $autoGrCode = 'GR-' . now()->format('Ymd') . '-' . $supplierCode . '-' . $poCode;

                                            // Kalau sudah ada code_gr tersimpan → pakai yang lama
                                            // Kalau belum → generate otomatis
                                            $displayGrCode = $pembelian->code_gr ?? $autoGrCode;
                                        @endphp
                                        <input type="text"
                                            name="code_gr"
                                            class="form-control"
                                            value="{{ old('code_gr', $displayGrCode) }}"
                                            readonly
                                            style="background:#f5f5f5; cursor:not-allowed;"
                                            required />
                                        <p class="help-block">
                                            <i class="fa fa-info-circle"></i> Nomor penerimaan dibuat otomatis oleh sistem.
                                        </p>
                                    </div>
                                    <div class="form-group">
                                        <label>Tanggal Penerimaan <span class="text-danger">*</span></label>
                                        <input type="datetime-local" name="receipt_date" class="form-control"
                                            value="{{ old('receipt_date', $pembelian->receipt_date?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i')) }}"
                                            required>
                                    </div>
                                    <div class="form-group" style="display: none;">
                                        <label>PIC Penerima <span class="text-danger">*</span></label>
                                        <input type="text" name="receipt_pic" class="form-control"
                                            value="{{ old('receipt_pic', $pembelian->receipt_pic ?? auth()->user()->name) }}"
                                            required>
                                    </div>
                                    <div class="form-group" style="display: none;">
                                        <label>Status Penerimaan <span class="text-danger">*</span></label>
                                        <select name="receipt_status" class="form-control" required>
                                            {{--  <option value="draft"      {{ old('receipt_status', $pembelian->receipt_status) == 'draft'     ? 'selected' : '' }}>Draft</option>  --}}
                                            {{--  <option value="validated"  {{ old('receipt_status', $pembelian->receipt_status) == 'validated' ? 'selected' : '' }}>Validated</option>  --}}
                                            <option value="completed"  selected>Completed</option>
                                        </select>
                                        @if(!$isLocked)
                                            <span class="help-block">
                                                <i class="fa fa-info-circle"></i> Set ke <strong>Completed</strong> untuk publish stok ke gudang. Setelah itu items tidak bisa diubah.
                                            </span>
                                        @endif
                                    </div>
                                    {{--  <div class="form-group">
                                        <label>Bukti Foto <small class="text-muted">(opsional)</small></label>
                                        <input type="file" name="receipt_photo" class="form-control" accept="image/*">
                                        <small class="text-muted">JPG/PNG, max 2MB</small>
                                        @if($pembelian->receipt_photo)
                                            <div style="margin-top:6px">
                                                <a href="{{ asset('storage/' . $pembelian->receipt_photo) }}" target="_blank">
                                                    <img src="{{ asset('storage/' . $pembelian->receipt_photo) }}"
                                                        style="max-width:100%; max-height:120px; border-radius:4px; border:1px solid #ddd;">
                                                </a>
                                            </div>
                                        @endif
                                    </div>  --}}
                                </div>
                                <div class="box-footer">
                                    <a href="{{ route('penerimaan.index') }}" class="btn btn-default">
                                        <i class="fa fa-arrow-left"></i> Kembali
                                    </a>
                                    <button type="submit" class="btn btn-primary pull-right">
                                        <i class="fa fa-save"></i>
                                        {{ $isLocked ? 'Update Detail' : 'Simpan Penerimaan' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
@endsection
@section('page-script')
<script>
    $(document).on('input', 'input[data-konversi-qty]', function() {
        let konversiQty = parseInt($(this).data('konversi-qty')) || 1;
        let qtySatuanBesar = parseInt($(this).val()) || 0;
        let qtySatuanKecil = qtySatuanBesar * konversiQty;
        $(this).siblings('.qty-kecil-display').text('= ' + qtySatuanKecil.toLocaleString('id-ID') + ' {{ isset($item) ? $item->product->satuan : "PCS" }}');
    });
</script>
@endsection
