@php
    $selectedBuyerType = old('buyer_type', $penjualan?->buyer_type);
    $selectedAgentId = old('agent_id', $selectedBuyerType === 'agent' ? $penjualan?->buyer_id : '');
    $selectedCanvasId = old('canvas_id', $selectedBuyerType === 'canvas' ? $penjualan?->buyer_id : '');
    $selectedOutletId = old('outlet_target_id', $selectedBuyerType === 'outlet' ? $penjualan?->buyer_id : '');
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
                                    <label>Jenis Pembeli</label>
                                    <select class="form-control" name="buyer_type" id="buyer_type" required>
                                        <option value="">Pilih Jenis Pembeli</option>
                                        <option value="agent" {{ $selectedBuyerType === 'agent' ? 'selected' : '' }}>Agen</option>
                                        <option value="canvas" {{ $selectedBuyerType === 'canvas' ? 'selected' : '' }}>Canvas</option>
                                        <option value="outlet" {{ $selectedBuyerType === 'outlet' ? 'selected' : '' }}>Cabang</option>
                                    </select>
                                    @error('buyer_type')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
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
                                            <option value="{{ $outlet->id }}"
                                                data-termin-days="0"
                                                {{ (string) $selectedOutletId === (string) $outlet->id ? 'selected' : '' }}>
                                                {{ $outlet->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('outlet_target_id')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Tipe Pembayaran</label>
                                    <select class="form-control" id="payment_type" name="payment_type" required>
                                        <option value="cash" {{ old('payment_type', $penjualan?->payment_type ?? 'cash') === 'cash' ? 'selected' : '' }}>Cash</option>
                                        <option value="termin" {{ old('payment_type', $penjualan?->payment_type) === 'termin' ? 'selected' : '' }}>Termin</option>
                                    </select>
                                    @error('payment_type')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Status Pembayaran</label>
                                    <select class="form-control" id="payment_status" name="payment_status">
                                        <option value="paid" {{ old('payment_status', $penjualan?->payment_status ?? 'paid') === 'paid' ? 'selected' : '' }}>Paid</option>
                                        <option value="unpaid" {{ old('payment_status', $penjualan?->payment_status) === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                                        <option value="partial" {{ old('payment_status', $penjualan?->payment_status) === 'partial' ? 'selected' : '' }}>Partial</option>
                                    </select>
                                    @error('payment_status')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Jatuh Tempo</label>
                                    <input type="date" class="form-control" id="due_date" name="due_date"
                                        value="{{ old('due_date', optional($penjualan?->due_date)->format('Y-m-d')) }}">
                                    @error('due_date')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Catatan</label>
                            <textarea class="form-control" name="notes" rows="2" placeholder="Catatan tambahan (opsional)">{{ old('notes', $penjualan?->notes) }}</textarea>
                        </div>

                        <hr>

                        <div class="table-responsive">
                            <table class="table table-bordered" id="items-table">
                                <thead>
                                    <tr>
                                        <th style="width:28%">Produk</th>
                                        <th style="width:14%">Stok Tersedia</th>
                                        <th style="width:12%">Satuan</th>
                                        <th style="width:10%">Qty</th>
                                        <th style="width:16%">Harga</th>
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

                        <button type="button" class="btn btn-default" id="add-row">
                            <i class="fa fa-plus"></i> Tambah Produk
                        </button>

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
                </form>
            </div>
        </div>
    </div>
</section>
