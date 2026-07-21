@extends('layouts.master')

@section('title', 'Pembayaran Penjualan')

@section('container')
    @php
        $paidAmount = (float) ($penjualan->paymentTransaction?->amount ?? 0);
        $remainingAmount = max(0, (float) $penjualan->total - $paidAmount);
        $paymentStatus = $penjualan->paymentTransaction?->status ?? $penjualan->payment_status ?? 'unpaid';
        $defaultReference = 'PAY-' . $penjualan->code . '-' . now()->format('YmdHis');
    @endphp

    <section class="content">
        <div class="row">
            <div class="col-md-8">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">{{ $penjualan->code }}</h3>
                        <div class="box-tools pull-right">
                            @if ($paymentStatus === 'paid')
                                <span class="label label-success" style="font-size:13px;">LUNAS</span>
                            @elseif ($paymentStatus === 'partial')
                                <span class="label label-warning" style="font-size:13px;">PARTIAL</span>
                            @else
                                <span class="label label-danger" style="font-size:13px;">BELUM LUNAS</span>
                            @endif
                        </div>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="col-sm-6">
                                <p class="text-muted" style="margin-bottom:2px;">Tanggal Penjualan</p>
                                <strong>{{ optional($penjualan->sale_date ?? $penjualan->created_at)->format('d/m/Y') }}</strong>
                            </div>
                            <div class="col-sm-6">
                                <p class="text-muted" style="margin-bottom:2px;">Pembeli</p>
                                <strong>{{ $penjualan->buyer_display_name }}</strong>
                            </div>
                        </div>

                        <hr>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Produk</th>
                                        <th>Qty Input</th>
                                        <th class="text-right">Harga</th>
                                        <th class="text-right">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($penjualan->items as $item)
                                        <tr>
                                            <td>{{ $item->product?->name ?? '-' }}</td>
                                            <td>
                                                {{ rtrim(rtrim(number_format((float) ($item->qty_input ?? $item->qty), 2, ',', '.'), '0'), ',') }}
                                                {{ $item->unit ?? $item->product?->satuan ?? '' }}
                                            </td>
                                            <td class="text-right">@currency($item->price)</td>
                                            <td class="text-right">@currency($item->subtotal)</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3" class="text-right">Diskon</th>
                                        <th class="text-right">@currency($penjualan->discount)</th>
                                    </tr>
                                    <tr>
                                        <th colspan="3" class="text-right">Total</th>
                                        <th class="text-right">@currency($penjualan->total)</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="box-footer">
                        <div class="row">
                            <div class="col-sm-4">
                                <p class="text-muted" style="margin-bottom:2px;">Sudah Dibayar</p>
                                <h4 class="text-green" style="margin:0;">Rp {{ number_format($paidAmount, 0, ',', '.') }}</h4>
                            </div>
                            <div class="col-sm-4">
                                <p class="text-muted" style="margin-bottom:2px;">Sisa Piutang</p>
                                <h4 class="text-red" style="margin:0;">Rp {{ number_format($remainingAmount, 0, ',', '.') }}</h4>
                            </div>
                            <div class="col-sm-4 text-right">
                                <a href="{{ route('penjualan.show', $penjualan) }}" class="btn btn-default" style="margin-top:18px;">Kembali ke Detail</a>
                            </div>
                        </div>
                    </div>
                </div>

                @if (!empty($paymentHistory))
                    <div class="box box-default">
                        <div class="box-header with-border">
                            <h3 class="box-title">Riwayat Pembayaran</h3>
                        </div>
                        <div class="box-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th class="text-right">Jumlah</th>
                                            <th>Metode</th>
                                            <th>Referensi</th>
                                            <th>Catatan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($paymentHistory as $history)
                                            <tr>
                                                <td>{{ \Carbon\Carbon::parse($history['payment_date'])->format('d/m/Y H:i') }}</td>
                                                <td class="text-right">Rp {{ number_format($history['amount'], 0, ',', '.') }}</td>
                                                <td>{{ strtoupper(str_replace('_', ' ', $history['payment_method'])) }}</td>
                                                <td>{{ $history['payment_reference'] ?? '-' }}</td>
                                                <td>{{ $history['notes'] ?? '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="col-md-4">
                <div class="box box-success">
                    <div class="box-header with-border">
                        <h3 class="box-title">Input Pembayaran</h3>
                    </div>

                    <form action="{{ route('penjualan.pembayaran.update', $penjualan) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="box-body">
                            @if ($remainingAmount <= 0)
                                <div class="alert alert-success">
                                    <i class="fa fa-check-circle"></i> Penjualan ini sudah lunas.
                                </div>
                            @endif

                            <div class="form-group">
                                <label>Tanggal Bayar</label>
                                <input type="datetime-local" name="payment_date" class="form-control"
                                    value="{{ now()->format('Y-m-d\TH:i') }}"
                                    @if ($remainingAmount <= 0) disabled @endif
                                    required>
                            </div>

                            <div class="form-group">
                                <label>Metode Pembayaran</label>
                                <select name="payment_method" class="form-control"
                                    @if ($remainingAmount <= 0) disabled @endif
                                    required>
                                    <option value="">Pilih Metode Pembayaran</option>
                                    <option value="cash">Cash</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="giro_cek">Giro/Cek</option>
                                    <option value="lainnya">Lainnya</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>No. Bukti / Referensi</label>
                                <input type="text" name="payment_reference" class="form-control"
                                    value="{{ $defaultReference }}"
                                    @if ($remainingAmount <= 0) disabled @endif>
                            </div>

                            <div class="form-group">
                                <label>Jumlah Pembayaran</label>
                                <input type="text" id="amountDisplay" class="form-control numeral-mask"
                                    placeholder="Masukkan jumlah dibayar"
                                    @if ($remainingAmount <= 0) disabled @endif
                                    required>
                                <input type="hidden" name="amount" id="amountInput">
                                <p class="help-block">Maksimal: Rp {{ number_format($remainingAmount, 0, ',', '.') }}</p>
                            </div>

                            <div class="form-group">
                                <label>Catatan</label>
                                <textarea name="notes" rows="3" class="form-control"
                                    placeholder="Catatan pembayaran (opsional)"
                                    @if ($remainingAmount <= 0) disabled @endif></textarea>
                            </div>
                        </div>

                        @if ($remainingAmount > 0)
                            <div class="box-footer">
                                <button type="submit" class="btn btn-success btn-block">
                                    <i class="fa fa-save"></i> Simpan Pembayaran
                                </button>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('page-script')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script>
        $(function() {
            var maxAmount = {{ (int) $remainingAmount }};
            $('#amountDisplay').mask('#,##0', { reverse: true });

            $('#amountDisplay').on('input change', function() {
                var raw = parseInt($(this).cleanVal() || '0', 10) || 0;
                if (raw > maxAmount) {
                    raw = maxAmount;
                    $(this).val(raw.toLocaleString('id-ID'));
                }

                $('#amountInput').val(raw);
            });

            $('form').on('submit', function() {
                var raw = parseInt($('#amountDisplay').cleanVal() || '0', 10) || 0;
                $('#amountInput').val(raw);
            });
        });
    </script>
@endsection
