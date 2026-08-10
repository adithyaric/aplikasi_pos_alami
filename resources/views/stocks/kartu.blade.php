@extends('layouts.master')
@section('title', 'Kartu Stok')
@section('container')
    <section class="content-header">
        <h1>Kartu Stok</h1>
    </section>
    <section class="content">
        <div class="row">
            <div class="col-sm-12">
                <div class="box">
                    <div class="box-header">
                        <h3 class="box-title"><strong>KARTU STOK</strong></h3>
                    </div>
                    <div class="box-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Pilih Produk</label>
                                <select id="selectStock" class="form-control select2">
                                    <option value="">-- Pilih Produk --</option>
                                    @foreach ($stocks as $stock)
                                        <option value="{{ $stock['product_id'] }}"
                                            data-product="{{ $stock['product_name'] }}"
                                            data-code="{{ $stock['product_code'] }}"
                                            data-supplier="Global Stock">
                                            {{ $stock['product_name'] }} ({{ $stock['product_code'] }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button id="btnLoadKartu" class="btn btn-primary form-control" disabled>
                                    <i class="fa fa-search"></i> Tampilkan Kartu
                                </button>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <a id="btnExportKartu" href="#" class="btn btn-success form-control" style="pointer-events:none; opacity:0.6;">
                                    <i class="fa fa-file-excel-o"></i> Export Excel
                                </a>
                            </div>
                        </div>

                        <!-- Info Stock -->
                        <table class="table table-borderless" style="width: 60%">
                            <tr>
                                <td style="width: 30%">Nama Produk</td>
                                <td>: <span id="displayProduct">-</span></td>
                            </tr>
                            <tr>
                                <td>Barcode</td>
                                <td>: <span id="displayCode">-</span></td>
                            </tr>
                            <tr>
                                <td>Supplier</td>
                                <td>: <span id="displaySupplier">-</span></td>
                            </tr>
                        </table>

                        <!-- Transaction Table -->
                        <div class="table-responsive">
                            <table id="kartuTable" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Tanggal</th>
                                        <th>Stok Awal</th>
                                        <th>Masuk</th>
                                        <th>Keluar</th>
                                        <th>Stok Akhir</th>
                                        <th>Harga Satuan (Rp)</th>
                                        <th>Nilai Persediaan</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody id="tableBody">
                                    <tr>
                                        <td colspan="9" class="text-center">Pilih Produk untuk menampilkan data</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="7" class="text-right">TOTAL NILAI PERSEDIAAN</th>
                                        <th id="totalPersediaan">0</th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('page-script')
    <script>
        // Fungsi helper konversi qty — Menggunakan logika 3 tingkatan unit seperti view stock awal Anda
        function qtyDisplay(qty, product) {
            if (!qty && qty !== 0) return '-';
            qty = parseInt(qty) || 0;
            var parts = [];

            // 1. Satuan dasar
            parts.push(qty.toLocaleString('id-ID') + ' ' + (product.satuan || 'PCS'));

            // 2. Satuan besar
            if (product.konversi_qty && product.satuan_besar) {
                var satuanBesar = Math.floor(qty / product.konversi_qty);
                if (satuanBesar > 0) {
                    parts.push(satuanBesar.toLocaleString('id-ID') + ' ' + product.satuan_besar);
                }
            }

            // 3. Satuan terbesar
            if (product.konversi_qty && product.satuan_besar && product.konversi_qty_terbesar && product.satuan_terbesar) {
                var totalSatuanBesar = qty / product.konversi_qty;
                var satuanTerbesar = totalSatuanBesar / product.konversi_qty_terbesar;
                if (satuanTerbesar > 0) {
                    var formatted = (satuanTerbesar % 1 === 0)
                        ? satuanTerbesar.toLocaleString('id-ID')
                        : satuanTerbesar.toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 });
                    parts.push(formatted + ' ' + product.satuan_terbesar);
                }
            }

            return parts.join(' | ');
        }

        $(document).ready(function() {
            let currentData = [];
            let stockMeta = {};

            // Initialize select2
            $('#selectStock').select2({
                placeholder: '-- Pilih Produk --',
                width: '100%'
            });

            // Enable button when stock selected
            $('#selectStock').on('change', function() {
                $('#btnLoadKartu').prop('disabled', !$(this).val());
            });

            // Load kartu data
            $('#btnLoadKartu').on('click', function() {
                const productId = $('#selectStock').val();

                if (!productId) return;

                $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Loading...');

                $.ajax({
                    url: '{{ route('stock.kartu.data') }}',
                    method: 'GET',
                    data: {
                        product_id: productId
                    },
                    success: function(response) {
                        currentData = response;
                        stockMeta = response.stock;

                        $('#displayProduct').text(response.stock.product_name);
                        $('#displayCode').text(response.stock.product_code);
                        $('#displaySupplier').text(response.stock.supplier);

                        renderKartuTable(response.transactions, stockMeta);

                        $('#btnLoadKartu').prop('disabled', false).html('<i class="fa fa-search"></i> Tampilkan Kartu');

                        // Update link export
                        $('#btnExportKartu')
                            .attr('href', '{{ route('laporan.kartu-stok') }}/' + productId)
                            .css({'pointer-events': 'auto', 'opacity': '1'});

                    },
                    error: function() {
                        alert('Gagal memuat data kartu stok');
                        $('#btnLoadKartu').prop('disabled', false).html('<i class="fa fa-search"></i> Tampilkan Kartu');
                    }
                });
            });

            function renderKartuTable(transactions, meta) {
                meta = meta || {};
                const tbody = $('#tableBody');
                tbody.empty();

                if (transactions.length === 0) {
                    tbody.append('<tr><td colspan="9" class="text-center">Tidak ada transaksi untuk SKU ini</td></tr>');
                    $('#totalPersediaan').text('0');
                    return;
                }

                // Memakai fungsi qtyDisplay baru yang mendukung 3 tier unit
                function fmtQty(qty) {
                    var formattedText = qtyDisplay(qty, meta);
                    return `<span>${formattedText}</span>`;
                }

                let latestNilai = 0;

                transactions.forEach((item, index) => {
                    latestNilai = item.nilai;

                    tbody.append(`
                        <tr>
                            <td>${index + 1}</td>
                            <td>${item.tanggal}</td>
                            <td class="text-right">${fmtQty(item.stok_awal)}</td>
                            <td class="text-right">${fmtQty(item.masuk)}</td>
                            <td class="text-right">${fmtQty(item.keluar)}</td>
                            <td class="text-right"><strong>${fmtQty(item.stok_akhir)}</strong></td>
                            <td class="text-right">${formatRupiah(item.harga)}</td>
                            <td class="text-right"><strong>${formatRupiah(item.nilai)}</strong></td>
                            <td><small>${item.keterangan}</small></td>
                        </tr>
                    `);
                });

                $('#totalPersediaan').text(formatRupiah(latestNilai));
            }

            function formatRupiah(amount) {
                return new Intl.NumberFormat('id-ID').format(amount);
            }
        });
    </script>
@endsection
