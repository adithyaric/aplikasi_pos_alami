@php
    $selectedPenjualanId = (string) ($selectedPenjualanId ?? '');
    $tanggalValue = old('tanggal', $refund?->tanggal?->format('Y-m-d') ?? now()->format('Y-m-d'));
    $totalValue = old('total', isset($refund) ? number_format((int) $refund->total, 0, ',', '.') : '');
@endphp

<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ $boxTitle }}</h3>
                </div>

                <form action="{{ $formAction }}" method="POST" id="refund-form">
                    @csrf
                    @if ($formMethod !== 'POST')
                        @method($formMethod)
                    @endif

                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Kode Refund</label>
                                    <input type="text" class="form-control" name="code"
                                        value="{{ old('code', $refund?->code) }}" placeholder="Masukkan kode refund" required>
                                    @error('code')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Tanggal</label>
                                    <input type="date" class="form-control" name="tanggal"
                                        value="{{ $tanggalValue }}" required>
                                    @error('tanggal')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Penjualan</label>
                            <select id="penjualan_id" class="form-control select2" name="penjualan_id" style="width:100%" required>
                                <option value="">Pilih Penjualan</option>
                                @foreach ($penjualans as $penjualan)
                                    <option value="{{ $penjualan['id'] }}" {{ $selectedPenjualanId === (string) $penjualan['id'] ? 'selected' : '' }}>
                                        {{ $penjualan['code'] }} - {{ $penjualan['buyer_type_label'] }} {{ $penjualan['buyer_display_name'] }}
                                    </option>
                                @endforeach
                            </select>
                            @error('penjualan_id')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="alert alert-info">
                            Retur penjualan akan mengikuti pembeli pada invoice yang dipilih. Jadi bisa untuk
                            <strong>Agen</strong>, <strong>Canvas</strong>, atau <strong>Cabang</strong>.
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Channel Penjualan</label>
                                    <input type="text" class="form-control" id="sale_channel_info" readonly value="-">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Jenis Pembeli</label>
                                    <input type="text" class="form-control" id="buyer_type_info" readonly value="-">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Nama Pembeli</label>
                                    <input type="text" class="form-control" id="buyer_name_info" readonly value="-">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Total Refund</label>
                            <input type="text" class="form-control numeral-mask" name="total"
                                value="{{ $totalValue }}" placeholder="Masukkan total refund" required>
                            @error('total')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr>

                        <div class="clearfix" style="margin-bottom:10px;">
                            <button type="button" class="btn btn-default pull-right" id="reload-sale-items">
                                <i class="fa fa-refresh"></i> Muat Ulang Item Penjualan
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Produk</th>
                                        <th>Qty Terjual</th>
                                        <th>Maks Retur</th>
                                        <th>Qty Retur</th>
                                        <th>Alasan</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="refund-items-body"></tbody>
                            </table>
                        </div>

                        @error('product')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="box-footer">
                        <a href="{{ route('refund.index') }}" class="btn btn-default">Kembali</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> {{ $submitLabel }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
