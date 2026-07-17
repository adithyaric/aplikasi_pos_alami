@extends('layouts.master')

@section('title', 'Bayar Pembelian')

@section('container')
    <section class="content">
        <div class="row">
            <div class="col-md-8">

                {{-- ====================== INFO PEMBELIAN ====================== --}}
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">{{ $pembelian->code }}</h3>
                        <div class="box-tools pull-right">
                            @if ($pembelian->pembelianTransaction?->status == 'unpaid')
                                <span class="label label-danger" style="font-size: 13px;">BELUM LUNAS</span>
                            @elseif($pembelian->pembelianTransaction?->status == 'partial')
                                <span class="label label-warning" style="font-size: 13px;">PARTIAL</span>
                            @elseif($pembelian->pembelianTransaction?->status == 'paid')
                                <span class="label label-success" style="font-size: 13px;">LUNAS</span>
                            @else
                                <span class="label label-default" style="font-size: 13px;">No Payment</span>
                            @endif
                        </div>
                    </div>

                    <div class="box-body">
                        <div class="row">
                            <div class="col-sm-6">
                                <p class="text-muted mb-0" style="margin-bottom: 2px;">Tanggal Terima</p>
                                <strong>{{ $pembelian->receipt_date?->format('d/m/Y') ?? '-' }}</strong>
                            </div>
                            <div class="col-sm-6">
                                <p class="text-muted mb-0" style="margin-bottom: 2px;">Pemasok</p>
                                <strong>{{ $pembelian->supplier?->name ?? '-' }}</strong>
                            </div>
                        </div>

                        <hr>

                        <div class="table-responsive">
                            <table id="example1" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Produk</th>
                                        <th>Satuan</th>
                                        <th class="text-right">Harga Beli</th>
                                        <th class="text-right">Qty</th>
                                        <th class="text-right">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($pembelian->pembelianProducts as $item)
                                        <tr>
                                            <td>{{ $item->product?->name }}</td>
                                            <td>{{ $item->product?->satuan }}</td>
                                            <td class="text-right">Rp {{ number_format($item->harga_beli, 0, ',', '.') }}</td>
                                            <td class="text-right">
                                                {{ $item->qty }}
                                                @php $k = $item->product?->konversiDisplay($item->qty); @endphp
                                                @if($k && $k !== '-')
                                                    <span class="label label-info">{{ $k }}</span>
                                                @endif
                                            </td>
                                            <td class="text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="4" class="text-right">Grand Total</th>
                                        <th class="text-right">Rp {{ number_format($pembelian->total, 0, ',', '.') }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <div class="box-footer">
                        <div class="row">
                            <div class="col-sm-4">
                                <p class="text-muted mb-0" style="margin-bottom: 2px;">Paid Amount</p>
                                <h4 class="text-green" style="margin: 0;">
                                    Rp {{ number_format($pembelian->pembelianTransaction?->amount ?? 0, 0, ',', '.') }}
                                </h4>
                            </div>
                            <div class="col-sm-4">
                                <p class="text-muted mb-0" style="margin-bottom: 2px;">Outstanding Balance</p>
                                <h4 class="text-red" style="margin: 0;">
                                    Rp {{ number_format($pembelian->total - ($pembelian->pembelianTransaction?->amount ?? 0), 0, ',', '.') }}
                                </h4>
                            </div>
                            <div class="col-sm-4 text-right">
                                <a href="{{ route('laporan.pdf.faktur-pembelian', $pembelian->id) }}" class="btn btn-danger"
                                    title="Faktur PDF" target="_blank" style="margin-top: 18px;">
                                    <i class="fa fa-file-pdf-o"></i> Faktur
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ====================== RIWAYAT PEMBAYARAN ====================== --}}
                @if ($pembelian->pembelianTransaction && !empty($paymentHistory))
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
                                            {{--  <th class="text-center">Bukti</th>  --}}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($paymentHistory as $history)
                                            <tr>
                                                <td>{{ \Carbon\Carbon::parse($history['payment_date'])->format('d/m/Y H:i') }}</td>
                                                <td class="text-right">Rp {{ number_format($history['amount'], 0, ',', '.') }}</td>
                                                <td>{{ strtoupper(ucfirst(str_replace('_', ' ', $history['payment_method']))) }}</td>
                                                <td>{{ $history['payment_reference'] ?? '-' }}</td>
                                                {{--  <td class="text-center">
                                                    @if (!empty($history['bukti_transfer']))
                                                        <a href="{{ Storage::disk('public')->url($history['bukti_transfer']) }}"
                                                            target="_blank">
                                                            <i class="fa fa-file"></i> Lihat
                                                        </a>
                                                    @else
                                                        -
                                                    @endif
                                                </td>  --}}
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="1">Total Dibayar</th>
                                            <th class="text-right" colspan="4">
                                                Rp {{ number_format($pembelian->pembelianTransaction->amount, 0, ',', '.') }}
                                            </th>
                                        </tr>
                                        <tr class="text-red">
                                            <th colspan="1">Sisa</th>
                                            <th class="text-right" colspan="4">
                                                Rp {{ number_format($pembelian->total - $pembelian->pembelianTransaction->amount, 0, ',', '.') }}
                                            </th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- ====================== FORM PEMBAYARAN ====================== --}}
            <div class="col-md-4">
                <div class="box box-success">
                    <div class="box-header with-border">
                        <h3 class="box-title">Pembayaran</h3>
                    </div>

                    <form id="pembayaranForm" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="box-body">

                            @if ($pembelian->pembelianTransaction?->status === 'paid')
                                <div class="alert alert-success">
                                    <i class="fa fa-check-circle"></i> Pembelian ini sudah dibayar lunas.
                                </div>
                            @endif

                            <div class="form-group">
                                <label class="control-label">Tanggal Bayar</label>
                                @php
                                    $currentStatus = $pembelian->pembelianTransaction?->status ?? 'unpaid';
                                    $defaultDate = $currentStatus === 'paid'
                                        ? $pembelian->pembelianTransaction?->payment_date?->format('Y-m-d\TH:i')
                                        : now()->format('Y-m-d\TH:i');
                                @endphp
                                <input type="datetime-local"
                                    name="payment_date"
                                    class="form-control"
                                    value="{{ $defaultDate }}"
                                    required
                                    @if ($currentStatus === 'paid') disabled @endif />
                            </div>

                            <div class="form-group">
                                <label class="control-label">Metode Pembayaran</label>
                                <select name="payment_method" class="form-control" id="paymentMethod" required
                                    @if ($pembelian->pembelianTransaction?->status === 'paid') disabled @endif>
                                    <option value="">Pilih Metode Pembayaran</option>
                                    <option value="cash"
                                        {{ $pembelian->pembelianTransaction?->payment_method == 'cash' ? 'selected' : '' }}>
                                        Cash</option>
                                    <option value="bank_transfer"
                                        {{ $pembelian->pembelianTransaction?->payment_method == 'bank_transfer' ? 'selected' : '' }}>
                                        Bank Transfer</option>
                                    <option value="giro_cek"
                                        {{ $pembelian->pembelianTransaction?->payment_method == 'giro_cek' ? 'selected' : '' }}>
                                        Giro/Cek</option>
                                    <option value="lainnya"
                                        {{ $pembelian->pembelianTransaction?->payment_method == 'lainnya' ? 'selected' : '' }}>
                                        Lainnya</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="control-label">No. Bukti / Referensi</label>
                                @php
                                    $supplierCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', substr($pembelian->supplier?->name ?? 'SUP', 0, 5)));
                                    $autoRef = 'PAY-' . now()->format('Ymd') . '-' . $supplierCode . '-' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
                                    $currentStatus = $pembelian->pembelianTransaction?->status ?? 'unpaid';

                                    // Kalau paid → tampilkan referensi terakhir yang tersimpan
                                    // Kalau partial/unpaid → selalu generate baru untuk pembayaran berikutnya
                                    $displayRef = $currentStatus === 'paid'
                                        ? ($pembelian->pembelianTransaction?->payment_reference ?? $autoRef)
                                        : $autoRef;
                                @endphp
                                <input type="text"
                                    name="payment_reference"
                                    class="form-control"
                                    id="paymentReference"
                                    value="{{ $displayRef }}"
                                    readonly
                                    style="background:#f5f5f5; cursor:not-allowed;" />
                                @if ($pembelian->supplier?->bank_name && $pembelian->supplier?->bank_account)
                                    <p class="help-block">
                                        Rekening: {{ $pembelian->supplier->bank_name }} -
                                        {{ $pembelian->supplier->bank_account }}
                                    </p>
                                @endif
                            </div>

                            <div class="form-group">
                                <label class="control-label">Jumlah Pembayaran</label>
                                <input type="text"
                                    class="form-control numeral-mask"
                                    id="amountDisplay"
                                    placeholder="Masukkan jumlah yang dibayar"
                                    value="{{ old('amount') ? number_format((int) preg_replace('/[^\d]/', '', old('amount')), 0, '.', ',') : '' }}" required
                                    @if ($pembelian->pembelianTransaction?->status === 'paid') disabled @endif />

                                {{-- Hidden input yang dikirim ke backend --}}
                                <input type="hidden" name="amount" id="amountInput">

                                @if ($pembelian->pembelianTransaction && $pembelian->pembelianTransaction->status === 'partial')
                                    <p class="help-block">
                                        Sudah dibayar: Rp {{ number_format($pembelian->pembelianTransaction->amount, 0, ',', '.') }}<br>
                                        Sisa (max input): Rp {{ number_format($pembelian->total - $pembelian->pembelianTransaction->amount, 0, ',', '.') }}
                                    </p>
                                @endif
                            </div>

                            {{--  <div class="form-group">
                                <label class="control-label">Bukti Transfer</label>
                                <input type="file" name="bukti_transfer" class="form-control" accept="image/*,.pdf"
                                    @if ($pembelian->pembelianTransaction?->status === 'paid') disabled @endif />
                                @if ($pembelian->pembelianTransaction?->bukti_transfer)
                                    <p class="help-block">
                                        <a href="{{ Storage::disk('public')->url($pembelian->pembelianTransaction->bukti_transfer) }}"
                                            target="_blank">
                                            <i class="fa fa-file"></i> Lihat bukti saat ini
                                        </a>
                                    </p>
                                @endif
                            </div>  --}}

                            <div class="form-group">
                                <label class="control-label">Status</label>
                                <select name="status" class="form-control" id="paymentStatus" required
                                    @if ($pembelian->pembelianTransaction?->status === 'paid') disabled @endif>
                                    <option value="">Pilih Status Pembayaran</option>
                                    <option value="unpaid"
                                        {{ $pembelian->pembelianTransaction?->status == 'unpaid' ? 'selected' : '' }}>
                                        Unpaid</option>
                                    <option value="partial"
                                        {{ $pembelian->pembelianTransaction?->status == 'partial' ? 'selected' : '' }}>
                                        Partial</option>
                                    <option value="paid"
                                        {{ $pembelian->pembelianTransaction?->status == 'paid' ? 'selected' : '' }}>
                                        Paid</option>
                                </select>
                            </div>

                            {{--  <div class="form-group">
                                <label class="control-label">Catatan</label>
                                <textarea name="notes" class="form-control" rows="3" placeholder="Catatan tambahan (opsional)"
                                    @if ($pembelian->pembelianTransaction?->status === 'paid') disabled @endif>{{ $pembelian->pembelianTransaction?->notes }}</textarea>
                            </div>  --}}
                        </div>

                        @if ($pembelian->pembelianTransaction?->status !== 'paid')
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
        var maxAmount = {{ $pembelian->total - ($pembelian->pembelianTransaction?->amount ?? 0) }};
        var currentPaid = {{ $pembelian->pembelianTransaction?->amount ?? 0 }};
        var grandTotal = {{ $pembelian->total }};

        // Init mask pada display input
        $('#amountDisplay').mask('#,##0', { reverse: true });

        // Auto-generate referensi saat pilih bank transfer
        //$('#paymentMethod').change(function() {
        //    const method = $(this).val();
        //    const bankAccount = '{{ $pembelian->supplier?->bank_account }}';
        //    if (method === 'bank_transfer' && bankAccount) {
        //        const date = new Date();
        //        const ref = 'TRF-' +
        //            date.getFullYear().toString().substr(-2) +
        //            ('0' + (date.getMonth() + 1)).slice(-2) + '-' +
        //            Math.floor(Math.random() * 100000).toString().padStart(5, '0');
        //        $('#paymentReference').val(ref);
        //    }
        //});

        // Validasi max & update status saat nilai berubah
        $('#amountDisplay').on('input change', function() {
            var raw = parseInt($(this).cleanVal()) || 0;

            // Paksa max
            if (raw > maxAmount) {
                $(this).val(window.formatNumberWithCommas(maxAmount));
                raw = maxAmount;
            }

            // Sync ke hidden input
            $('#amountInput').val(raw);

            // Auto-set status pembayaran
            var totalPaid = currentPaid + raw;
            if (totalPaid <= 0) {
                $('#paymentStatus').val('unpaid');
            } else if (totalPaid >= grandTotal) {
                $('#paymentStatus').val('paid');
            } else {
                $('#paymentStatus').val('partial');
            }
        });

        // Submit form via AJAX
        $('#pembayaranForm').submit(function(e) {
            e.preventDefault();

            // Pastikan hidden input terisi sebelum submit
            var raw = parseInt($('#amountDisplay').cleanVal()) || 0;
            $('#amountInput').val(raw);

            const formData = new FormData(this);
            const $submitBtn = $(this).find('button[type="submit"]');
            const originalBtnText = $submitBtn.html();

            $submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');

            $.ajax({
                url: '{{ route('pembelian.pembayaran.update', $pembelian) }}',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.message);
                        window.location.href = '{{ route('pembelian.index') }}';
                    }
                },
                error: function(xhr) {
                    const errors = xhr.responseJSON?.message || 'Terjadi kesalahan';
                    alert(errors);
                    $submitBtn.prop('disabled', false).html(originalBtnText);
                }
            });
        });
    });
</script>
@endsection
