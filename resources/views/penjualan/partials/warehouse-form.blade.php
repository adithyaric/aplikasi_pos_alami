@php
    $saleMode = $saleMode ?? 'warehouse';
    $isBranchSaleMode = $saleMode === 'branch';
    $selectedBuyerType = old('buyer_type', $isBranchSaleMode ? 'toko' : $penjualan?->buyer_type);
    $selectedAgentId = old('agent_id', $selectedBuyerType === 'agent' ? $penjualan?->buyer_id : '');
    $selectedCanvasId = old('canvas_id', $selectedBuyerType === 'canvas' ? $penjualan?->buyer_id : '');
    $selectedOutletId = old('outlet_target_id', in_array($selectedBuyerType, ['outlet', 'toko'], true) ? $penjualan?->buyer_id : '');
    $selectedShopId = old('toko_id', $selectedBuyerType === 'toko' && ! $isBranchSaleMode ? $penjualan?->buyer_id : '');
    $selectedPaymentType = old('payment_type', $penjualan?->payment_type ?? 'termin');
    $selectedPaymentStatus = old('payment_status', $penjualan?->payment_status ?? ($selectedPaymentType === 'cash' ? 'paid' : 'unpaid'));
    $oldDebtOverride = old('old_debt_override', $penjualan?->old_debt_override);
    $shippingCost = old('shipping_cost', $penjualan?->shipping_cost ?? 0);
    $paidAmount = (float) ($penjualan?->paymentTransaction?->amount ?? 0);
    $oldDebtPreview = $oldDebtOverride === null || $oldDebtOverride === ''
        ? (float) ($calculatedOldDebt ?? 0)
        : (float) $oldDebtOverride;
    $newDebtPreview = max(0, $oldDebtPreview + (float) $shippingCost + (float) ($penjualan?->total ?? 0) - $paidAmount);
    $salesLatitude = old('latitude', $penjualan?->latitude);
    $salesLongitude = old('longitude', $penjualan?->longitude);
    $salesLocationAccuracy = old('location_accuracy', $penjualan?->location_accuracy);
    $salesLocationCapturedAt = old('location_captured_at', $penjualan?->location_captured_at?->toIso8601String());
    $existingSalesPhotos = $penjualan?->photos?->map(fn ($photo) => [
        'url' => asset('storage/'.$photo->path),
        'name' => $photo->original_name,
    ])->values() ?? collect();
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

                <form action="{{ $formAction }}" method="POST" enctype="multipart/form-data" id="warehouse-sale-form"
                    data-penjualan-id="{{ $penjualan?->id ?? '' }}"
                    data-branch-sale="{{ $isBranchSaleMode ? 'true' : 'false' }}"
                    @if ($penjualan)
                        data-offline-queue="penjualan-update"
                        data-offline-title="Perubahan Penjualan"
                        data-offline-redirect="{{ $isBranchSaleMode ? route('penjualan.branch-index') : route('penjualan.index') }}"
                    @else
                        data-offline-queue="penjualan-create"
                        data-offline-title="Penjualan"
                        data-offline-redirect="{{ $isBranchSaleMode ? route('penjualan.branch-index') : route('penjualan.index') }}"
                    @endif>
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
                                    <label>{{ $isBranchSaleMode ? 'No. Invoice Cabang' : 'No. Penjualan' }}</label>
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
                                            <option value="toko" {{ $selectedBuyerType === 'toko' ? 'selected' : '' }}>Toko</option>
                                        </select>
                                        <div style="margin-top:6px"><a href="{{ route('customer-penjualan.create') }}" target="_blank" class="btn btn-default btn-xs"><i class="fa fa-user-plus"></i> Tambah Customer Penjualan</a> <a href="{{ route('customer-penjualan.index') }}" target="_blank" class="btn btn-link btn-xs">Kelola Customer</a></div>
                                    @endif
                                    @error('buyer_type')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            @if ($isBranchSaleMode)
                            <div class="col-md-4 buyer-select buyer-toko"
                                @unless ($isBranchSaleMode) style="display:none" @endunless>
                                <div class="form-group">
                                    <label>Customer/Toko</label>
                                    <select class="form-control select2" id="outlet_target_id" name="outlet_target_id"
                                        style="width:100%" @if ($isBranchSaleMode) required @endif>
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

                            <div class="col-md-4 buyer-select buyer-toko" style="display:none">
                                <div class="form-group">
                                    <label>Toko</label>
                                    <select class="form-control select2" id="toko_id" name="toko_id" style="width:100%">
                                        <option value="">Pilih Toko</option>
                                        @foreach ($shops as $shop)
                                            <option value="{{ $shop->id }}" {{ (string) $selectedShopId === (string) $shop->id ? 'selected' : '' }}>{{ $shop->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('toko_id')<div class="text-danger">{{ $message }}</div>@enderror
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

                        @if ($isBranchSaleMode)
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Foto Penjualan <small class="text-muted">(opsional)</small></label>
                                        <input type="file" class="form-control" id="sales_photos" name="sales_photos[]"
                                            accept="image/jpeg,image/png,image/webp" capture="environment" multiple>
                                        <p class="help-block">
                                            <i class="fa fa-info-circle"></i> Bisa memilih lebih dari satu foto. JPG, PNG, atau WEBP, maksimal 10 foto per penyimpanan dan 5MB per foto.
                                        </p>
                                        <div id="sales-photo-preview" class="sales-photo-preview" aria-live="polite"></div>
                                        @error('sales_photos')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                        @error('sales_photos.*')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Lokasi Penjualan <small class="text-muted">(opsional)</small></label>
                                        <div>
                                            <button type="button" class="btn btn-info btn-sm" id="capture-sales-location">
                                                <i class="fa fa-map-marker"></i> Ambil Lokasi Saat Ini
                                            </button>
                                            <button type="button" class="btn btn-default btn-sm" id="clear-sales-location" style="display:none;">
                                                Hapus Lokasi
                                            </button>
                                        </div>
                                        <p id="sales-location-status" class="help-block" aria-live="polite">
                                            Lokasi hanya disimpan jika Anda mengizinkan akses lokasi perangkat.
                                        </p>
                                        <input type="hidden" name="latitude" id="latitude" value="{{ $salesLatitude }}">
                                        <input type="hidden" name="longitude" id="longitude" value="{{ $salesLongitude }}">
                                        <input type="hidden" name="location_accuracy" id="location_accuracy" value="{{ $salesLocationAccuracy }}">
                                        <input type="hidden" name="location_captured_at" id="location_captured_at" value="{{ $salesLocationCapturedAt }}">
                                        <div id="sales-osm-map-preview" class="sales-osm-map-preview" style="display:none;"></div>
                                        @error('latitude')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                        @error('longitude')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="table-responsive">
                            <table class="table table-bordered" id="items-table">
                                <thead>
                                    <tr>
                                        <th style="width:25%">Produk</th>
                                        <th style="width:12%">Stok Tersedia</th>
                                        <th style="width:11%">Satuan</th>
                                        <th style="width:8%">Qty</th>
                                        <th style="width:13%">Diskon / Item (Rp)</th>
                                        <th style="width:15%">Harga / Satuan Dasar</th>
                                        <th style="width:11%">Subtotal</th>
                                        <th style="width:5%">Aksi</th>
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
                                    <label>Subtotal Sebelum Diskon</label>
                                    <input type="text" class="form-control" id="subtotal_display" value="0" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Total Diskon Item</label>
                                    <input type="text" class="form-control" id="discount_display" value="0" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Total Akhir</label>
                                    <input type="text" class="form-control" id="grand_total_display" value="0" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Tunggakan Lama (Rp)</label>
                                    <div class="well well-sm" id="old-debt-auto-display" style="margin-bottom:8px;">
                                        Tunggakan pelanggan sebelumnya:
                                        <strong>Rp {{ number_format((float) ($calculatedOldDebt ?? 0), 0, ',', '.') }}</strong>
                                    </div>
                                    <input type="text" class="form-control numeral-mask" name="old_debt_override"
                                        id="old_debt_override"
                                        value="{{ $oldDebtOverride === null || $oldDebtOverride === '' ? '' : number_format((float) $oldDebtOverride, 0, ',', '.') }}"
                                        data-auto-value="{{ number_format((float) ($calculatedOldDebt ?? 0), 0, ',', '.') }}"
                                        placeholder="Override (kosongkan untuk nilai otomatis)">
                                    <div class="text-muted small">Nilai di atas dihitung dari invoice pelanggan sebelumnya yang belum lunas. Isi kolom bila perlu menimpa nilai otomatis.</div>
                                    @error('old_debt_override')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label>Ongkos Kirim (Rp)</label>
                                    <input type="text" class="form-control numeral-mask" name="shipping_cost"
                                        id="shipping_cost" value="{{ number_format((float) $shippingCost, 0, ',', '.') }}">
                                    @error('shipping_cost')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label>Pembayaran Saat Ini (Rp)</label>
                                    <input type="text" class="form-control" id="payment_display"
                                        value="{{ number_format($paidAmount, 0, ',', '.') }}" readonly>
                                    <div class="text-muted small">Diubah melalui menu pembayaran setelah invoice dibuat.</div>
                                </div>
                                <div class="form-group">
                                    <label>Tunggakan Baru (Rp)</label>
                                    <input type="text" class="form-control" id="new_debt_display"
                                        value="{{ number_format($newDebtPreview, 0, ',', '.') }}" readonly>
                                    <div class="text-muted small">Otomatis: Tunggakan Lama + Ongkos Kirim + Total - Pembayaran.</div>
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

                    <div class="modal fade product-picker-modal" id="modalCekBarangPenjualan" tabindex="-1" role="dialog" aria-labelledby="modalCekBarangPenjualanLabel">
                        <div class="modal-dialog modal-lg product-picker-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                    <h4 class="modal-title" id="modalCekBarangPenjualanLabel">
                                        <i class="fa fa-search"></i> Pilih Produk Penjualan
                                    </h4>
                                </div>
                                <div class="modal-body product-picker-modal-body">
                                    <p class="text-muted">
                                        Checklist produk yang ingin ditambahkan. Produk yang sudah ada di tabel akan otomatis dikunci.
                                    </p>
                                    <p class="product-picker-scroll-hint text-muted visible-xs">
                                        Geser tabel ke samping untuk melihat kolom lainnya.
                                    </p>
                                    <div class="product-picker-table-wrap">
                                        <table id="tableCekBarangPenjualan" class="table table-bordered table-striped table-hover product-picker-table" style="width:100%">
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
