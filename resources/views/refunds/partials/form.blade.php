@php
    $selectedReturnScope = $selectedReturnScope ?: '';
    $selectedBuyerType = $selectedBuyerType ?: '';
    $selectedBuyerId = (string) ($selectedBuyerId ?: '');
    $selectedSourceOutletId = (string) ($selectedSourceOutletId ?: '');
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

                    <input type="hidden" name="return_scope" id="return_scope" value="{{ $selectedReturnScope }}">
                    <input type="hidden" name="buyer_id" id="buyer_id" value="{{ $selectedBuyerId }}">

                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Kode Retur</label>
                                    <input type="text" class="form-control" name="code" value="{{ old('code', $code) }}" required>
                                    @error('code')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Tanggal</label>
                                    <input type="date" class="form-control" name="tanggal" value="{{ $tanggalValue }}" required>
                                    @error('tanggal')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                @if ($isBranchScoped)
                                    <div class="form-group">
                                        <label>Cabang Penerima Retur</label>
                                        <input type="hidden" name="buyer_type" id="buyer_type" value="toko">
                                        <input type="hidden" name="source_outlet_id" value="{{ $selectedSourceOutletId }}">
                                        <input type="text" class="form-control" value="{{ $branchName ?: '-' }}" readonly>
                                    </div>
                                @else
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
                                @endif
                            </div>
                        </div>

                        <div class="row">
                            @if ($isBranchScoped)
                                <div class="col-md-6 buyer-block buyer-toko">
                                    <div class="form-group">
                                        <label>Customer/Toko</label>
                                        <select class="form-control select2 buyer-select" id="shop_buyer_id" data-buyer-type="toko" style="width:100%">
                                            <option value="">Pilih Customer/Toko</option>
                                            @foreach ($shops as $shop)
                                                <option value="{{ $shop->id }}" {{ $selectedBuyerId === (string) $shop->id ? 'selected' : '' }}>
                                                    {{ $shop->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            @else
                                <div class="col-md-4 buyer-block buyer-agent" style="display:none">
                                    <div class="form-group">
                                        <label>Agen</label>
                                        <select class="form-control select2 buyer-select" id="agent_buyer_id" data-buyer-type="agent" style="width:100%">
                                            <option value="">Pilih Agen</option>
                                            @foreach ($agents as $agent)
                                                <option value="{{ $agent->id }}" {{ $selectedBuyerType === 'agent' && $selectedBuyerId === (string) $agent->id ? 'selected' : '' }}>
                                                    {{ $agent->code ? $agent->code.' - ' : '' }}{{ $agent->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4 buyer-block buyer-canvas" style="display:none">
                                    <div class="form-group">
                                        <label>Canvas</label>
                                        <select class="form-control select2 buyer-select" id="canvas_buyer_id" data-buyer-type="canvas" style="width:100%">
                                            <option value="">Pilih Canvas</option>
                                            @foreach ($canvases as $canvas)
                                                <option value="{{ $canvas->id }}" {{ $selectedBuyerType === 'canvas' && $selectedBuyerId === (string) $canvas->id ? 'selected' : '' }}>
                                                    {{ $canvas->code ? $canvas->code.' - ' : '' }}{{ $canvas->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4 buyer-block buyer-outlet" style="display:none">
                                    <div class="form-group">
                                        <label>Cabang</label>
                                        <select class="form-control select2 buyer-select" id="branch_buyer_id" data-buyer-type="outlet" style="width:100%">
                                            <option value="">Pilih Cabang</option>
                                            @foreach ($branches as $branch)
                                                <option value="{{ $branch->id }}" {{ $selectedBuyerType === 'outlet' && $selectedBuyerId === (string) $branch->id ? 'selected' : '' }}>
                                                    {{ $branch->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div id="invoice-preview" class="alert alert-warning" style="display:none"></div>

                        <div class="form-group">
                            <label>Catatan</label>
                            <textarea class="form-control" name="notes" rows="2" placeholder="Catatan retur">{{ old('notes', $refund?->notes) }}</textarea>
                        </div>

                        <hr>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th style="width:28%">Produk</th>
                                        <th style="width:12%">Satuan</th>
                                        <th style="width:10%">Qty Retur</th>
                                        <th style="width:16%">Harga Jual</th>
                                        <th style="width:14%">Subtotal</th>
                                        <th>Alasan</th>
                                        <th style="width:6%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="refund-items-body"></tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="4" class="text-right">Total Retur</th>
                                        <th id="return-total-display">0</th>
                                        <th colspan="2"></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        @error('product')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror

                        <button type="button" class="btn btn-default" id="add-row">
                            <i class="fa fa-plus"></i> Tambah Produk
                        </button>
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
