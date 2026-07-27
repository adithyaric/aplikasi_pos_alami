@php
    $saleMode = $saleMode ?? 'warehouse';
    $isBranchSaleMode = $saleMode === 'branch';
    $selectedBuyerType = old('buyer_type', $isBranchSaleMode ? 'toko' : $penjualan?->buyer_type);
    $selectedAgentId = old('agent_id', $selectedBuyerType === 'agent' ? $penjualan?->buyer_id : '');
    $selectedCanvasId = old('canvas_id', $selectedBuyerType === 'canvas' ? $penjualan?->buyer_id : '');
    $selectedOutletId = old('outlet_target_id', in_array($selectedBuyerType, ['outlet', 'toko'], true) ? $penjualan?->buyer_id : '');
    $selectedPaymentType = old('payment_type', $penjualan?->payment_type ?? 'termin');
    $selectedPaymentStatus = old('payment_status', $penjualan?->payment_status ?? ($selectedPaymentType === 'cash' ? 'paid' : 'unpaid'));
@endphp

<section class="content-header">
    <h1>{{ $pageHeading }}</h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ $boxTitle }}</h3>
                </div>

                <form action="{{ $formAction }}" method="POST" id="warehouse-sale-form">
                    @csrf
                    @if ($formMethod !== 'POST')
                        @method($formMethod)
                    @endif
                    <input type="hidden" id="payment_type" name="payment_type" value="{{ $selectedPaymentType }}">
                    <input type="hidden" id="payment_status" name="payment_status" value="{{ $selectedPaymentStatus }}">

                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>No. Penjualan</label>
                                    <input type="text" class="form-control" value="{{ $code }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Tanggal</label>
                                    <input type="date" class="form-control" name="sale_date"
                                        value="{{ old('sale_date', $saleDate) }}" required>
                                    @error('sale_date')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    @if ($isBranchSaleMode)
                                        <label>Cabang Penanggung Jawab</label>
                                        <input type="hidden" name="buyer_type" id="buyer_type" value="toko">
                                        <input type="text" class="form-control" value="{{ $branchName ?: '-' }}" readonly>
                                    @else
                                        <label>Jenis Pembeli</label>
                                        <select class="form-control" name="buyer_type" id="buyer_type" required>
                                            <option value="">Pilih Jenis Pembeli</option>
                                            <option value="agent" {{ $selectedBuyerType === 'agent' ? 'selected' : '' }}>Agen</option>
                                            <option value="canvas" {{ $selectedBuyerType === 'canvas' ? 'selected' : '' }}>Canvas</option>
                                            <option value="outlet" {{ $selectedBuyerType === 'outlet' ? 'selected' : '' }}>Cabang</option>
                                        </select>
                                    @endif
                                    @error('buyer_type')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            @if ($isBranchSaleMode)
                            <div class="col-md-4 buyer-select buyer-toko" style="display:none">
                                <div class="form-group">
                                    <label>Customer/Toko</label>
                                    <select class="form-control select2" id="outlet_target_id" name="outlet_target_id" style="width:100%">
                                        <option value="">Pilih Customer/Toko</option>
                                        @foreach ($outlets as $outlet)
                                            <option value="{{ $outlet->id }}" {{ (string) $selectedOutletId === (string) $outlet->id ? 'selected' : '' }}>
                                                {{ $outlet->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('outlet_target_id')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            @else
                            <div class="col-md-4 buyer-select buyer-agent" style="display:none">
                                <div class="form-group">
                                    <label>Agen</label>
                                    <select class="form-control select2" id="agent_id" name="agent_id" style="width:100%">
                                        <option value="">Pilih Agen</option>
                                        @foreach ($agents as $agent)
                                            <option value="{{ $agent->id }}"
                                                data-termin-days="{{ $agent->termin_days ?? 0 }}"
                                                {{ (string) $selectedAgentId === (string) $agent->id ? 'selected' : '' }}>
                                                {{ $agent->code ? $agent->code.' - ' : '' }}{{ $agent->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('agent_id')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4 buyer-select buyer-canvas" style="display:none">
                                <div class="form-group">
                                    <label>Canvas</label>
                                    <select class="form-control select2" id="canvas_id" name="canvas_id" style="width:100%">
                                        <option value="">Pilih Canvas</option>
                                        @foreach ($canvases as $canvas)
                                            <option value="{{ $canvas->id }}"
                                                data-termin-days="{{ $canvas->termin_days ?? 0 }}"
                                                {{ (string) $selectedCanvasId === (string) $canvas->id ? 'selected' : '' }}>
                                                {{ $canvas->code ? $canvas->code.' - ' : '' }}{{ $canvas->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('canvas_id')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4 buyer-select buyer-outlet" style="display:none">
                                <div class="form-group">
                                    <label>Cabang</label>
                                    <select class="form-control select2" id="outlet_target_id" name="outlet_target_id" style="width:100%">
                                        <option value="">Pilih Cabang</option>
                                        @foreach ($outlets as $outlet)
                                            <option value="{{ $outlet->id }}" {{ (string) $selectedOutletId === (string) $outlet->id ? 'selected' : '' }}>
                                                {{ $outlet->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('outlet_target_id')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            @endif

                        </div>

                        <hr>

                        <div class="alert alert-info">
                            @if ($isBranchSaleMode)
                                Penjualan cabang mengambil stock dari cabang login, dan pembelinya adalah Customer/Toko.
                            @else
                                Harga diisi per satuan dasar produk. Jika qty dimasukkan dalam Slop atau Ball, sistem akan
                                mengonversinya ke satuan dasar terlebih dahulu sebelum menghitung subtotal.
                            @endif
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered" id="items-table">
                                <thead>
                                    <tr>
                                        <th style="width:28%">Produk</th>
                                        <th style="width:14%">Stok Tersedia</th>
                                        <th style="width:12%">Satuan</th>
                                        <th style="width:10%">Qty</th>
                                        <th style="width:16%">Harga / Satuan Dasar</th>
                                        <th style="width:14%">Subtotal</th>
                                        <th style="width:6%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="items-body"></tbody>
                            </table>
                        </div>

                        @error('items')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror

                        <div class="btn-group">
                            <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#modalCekBarangPenjualan">
                                <i class="fa fa-search"></i> Cek Barang
                            </button>
                            <button type="button" class="btn btn-default" id="add-row">
                                <i class="fa fa-plus"></i> Tambah Produk
                            </button>
                        </div>

                        <div class="row" style="margin-top:20px">
                            <div class="col-md-4 col-md-offset-8">
                                <div class="form-group">
                                    <label>Diskon</label>
                                    <input type="text" class="form-control numeral-mask" name="discount" id="discount"
                                        value="{{ old('discount', $penjualan?->discount ?? 0) }}">
                                    @error('discount')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label>Subtotal</label>
                                    <input type="text" class="form-control" id="subtotal_display" value="0" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Total Akhir</label>
                                    <input type="text" class="form-control" id="grand_total_display" value="0" readonly>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="box-footer">
                        <a href="{{ route('penjualan.index') }}" class="btn btn-default">Kembali</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> {{ $submitLabel }}
                        </button>
                    </div>

                    <div class="modal fade" id="modalCekBarangPenjualan" tabindex="-1" role="dialog" aria-labelledby="modalCekBarangPenjualanLabel">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                    <h4 class="modal-title" id="modalCekBarangPenjualanLabel">
                                        <i class="fa fa-search"></i> Pilih Produk Penjualan
                                    </h4>
                                </div>
                                <div class="modal-body">
                                    <p class="text-muted">
                                        Checklist produk yang ingin ditambahkan. Produk yang sudah ada di tabel akan otomatis dikunci.
                                    </p>
                                    <table id="tableCekBarangPenjualan" class="table table-bordered table-striped table-hover" style="width:100%">
                                        <thead>
                                            <tr>
                                                <th width="30"><input type="checkbox" id="checkAllPenjualan"></th>
                                                <th>Kode</th>
                                                <th>Nama Produk</th>
                                                <th>Stok Tersedia</th>
                                                <th>Satuan Input</th>
                                                <th>Harga / Satuan Dasar</th>
                                                <th>Status</th>
                                                <th width="90">Qty</th>
                                            </tr>
                                        </thead>
                                        <tbody id="cekBarangPenjualanBody"></tbody>
                                    </table>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
                                    <button type="button" class="btn btn-primary" id="btnTambahkanPenjualan">
                                        <i class="fa fa-check"></i> Tambahkan ke Penjualan
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
