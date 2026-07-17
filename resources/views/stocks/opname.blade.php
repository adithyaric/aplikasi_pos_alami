@extends('layouts.master')
@section('title', 'Stock Opname')
@section('container')
    <section class="content-header">
        <h1>Stock Opname</h1>
    </section>
    <section class="content">
        <div class="row">
            <div class="col-sm-12">
                <div class="box">
                    <div class="box-header">
                        <h3 class="box-title"><strong>STOCK OPNAME</strong></h3>
                    </div>
                    <div class="box-body">
                        <table class="table table-borderless mb-2" style="width: 70%">
                            <tr>
                                <td style="white-space:nowrap">Tanggal Stock Opname</td>
                                <td>
                                    <input type="date" id="tglStockOpname" class="form-control" value="{{ date('Y-m-d') }}" />
                                </td>
                                <td style="white-space:nowrap;padding-left:16px">Lokasi</td>
                                <td>
                                    <select id="filterLokasi" class="form-control select2">
                                        <option value="">-- Semua Lokasi --</option>
                                        @foreach($lokasiOptions as $lok)
                                            <option value="{{ $lok }}">{{ $lok }}</option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                        </table>

                        <div class="table-responsive">
                            <table id="datatable" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Barcode</th>
                                        <th>Product</th>
                                        <th>Satuan</th>
                                        <th>Stock Fisik</th>
                                        <th>Stock di Kartu</th>
                                        <th>Selisih</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody id="tableBody">
                                    <tr>
                                        <td colspan="8" class="text-center">Loading...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3 d-flex justify-content-between">
                            <button id="tambahBaris" class="btn btn-primary">
                                <i class="fa fa-plus-circle"></i> Tambah Baris
                            </button>
                            <button class="btn btn-success" id="btnSaveOpname">
                                <i class="fa fa-save"></i> Save Stock Opname
                            </button>
                            <a id="btnExportTemplate" href="{{ route('stock.opname.export-template') }}"
                               class="btn btn-sm btn-default">
                                <i class="fa fa-file-excel-o"></i> Export Template
                            </a>
                            <form method="GET" action="{{ route('laporan.stock-opname') }}" style="display:inline;">
                                <input type="hidden" name="tanggal" id="exportTanggal" value="{{ date('Y-m-d') }}" />
                                <input type="hidden" name="lokasi" id="exportLokasi" value="" />
                                <button type="submit" class="btn btn-sm btn-success">
                                    <i class="fa fa-file-excel-o"></i> Export Laporan
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('page-script')
    <script>
        let allStockData = [];

        function qtyDisplay(qty, item) {
            if (!qty && qty !== 0) return '-';
            qty = parseInt(qty) || 0;
            var parts = [];

            parts.push(qty.toLocaleString('id-ID') + ' ' + (item.satuan || 'PCS'));

            if (item.konversi_qty && item.satuan_besar) {
                var satuanBesar = Math.floor(qty / item.konversi_qty);
                if (satuanBesar > 0) {
                    parts.push(satuanBesar.toLocaleString('id-ID') + ' ' + item.satuan_besar);
                }
            }

            if (item.konversi_qty && item.satuan_besar && item.konversi_qty_terbesar && item.satuan_terbesar) {
                var totalSatuanBesar = qty / item.konversi_qty;
                var satuanTerbesar   = totalSatuanBesar / item.konversi_qty_terbesar;
                if (satuanTerbesar > 0) {
                    var formatted = (satuanTerbesar % 1 === 0)
                        ? satuanTerbesar.toLocaleString('id-ID')
                        : satuanTerbesar.toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 });
                    parts.push(formatted + ' ' + item.satuan_terbesar);
                }
            }

            return parts.join(' | ');
        }

        $(document).ready(function() {
            loadStockData();

            function loadStockData() {
                const lokasi = $('#filterLokasi').val();
                $.get('{{ route('stock.opname.data') }}', { lokasi: lokasi }, function(data) {
                    allStockData = data.stocks;
                    renderInitialRows();
                }).fail(function() {
                    alert('Gagal memuat data stock');
                });
            }

            function renderInitialRows() {
                const tbody = $('#tableBody');
                tbody.empty();

                allStockData.forEach((item, index) => {
                    const newRow = `
                        <tr>
                            <td>${index + 1}</td>
                            <td><input type="text" class="form-control" value="${item.product_code}" disabled /></td>
                            <td><input type="text" class="form-control product-name" value="${item.product_name}"
                                data-stock-id="${item.id}"
                                data-product-id="${item.product_id}"
                                data-konversi-qty="${item.konversi_qty || 1}"
                                data-satuan-besar="${item.satuan_besar || ''}"
                                data-konversi-qty-terbesar="${item.konversi_qty_terbesar || ''}"
                                data-satuan-terbesar="${item.satuan_terbesar || ''}"
                                data-satuan="${item.satuan || 'PCS'}"
                                disabled /></td>
                            <td><input type="text" class="form-control satuan" value="${item.satuan}" disabled /></td>
                            <td>
                                <input type="number" step="1" class="form-control stock_fisik" value="${item.qty}" />
                                <small class="text-muted konversi-fisik">${qtyDisplay(item.qty, item)}</small>
                            </td>
                            <td>
                                <input type="number" class="form-control stock_dikartu" value="${item.qty}" disabled />
                                <small class="text-muted">${qtyDisplay(item.qty, item)}</small>
                            </td>
                            <td>
                                <input type="number" step="1" class="form-control selisih" value="0" disabled />
                                <small class="text-muted konversi-selisih"></small>
                            </td>
                            <td><input type="text" class="form-control keterangan" value="" /></td>
                        </tr>
                    `;
                    tbody.append(newRow);
                });

                attachEventListeners();
            }

            function attachEventListeners() {
                $('.stock_fisik').off('input').on('input', function() {
                    const row        = $(this).closest('tr');
                    const stockFisik = parseFloat($(this).val()) || 0;
                    const stockKartu = parseFloat(row.find('.stock_dikartu').val()) || 0;
                    const selisih    = stockFisik - stockKartu;
                    row.find('.selisih').val(selisih.toFixed(2));

                    // Update konversi display
                    const productInput = row.find('.product-name');
                    const itemMeta = {
                        satuan:                productInput.data('satuan') || 'PCS',
                        satuan_besar:          productInput.data('satuan-besar') || '',
                        konversi_qty:          productInput.data('konversi-qty') || 0,
                        satuan_terbesar:       productInput.data('satuan-terbesar') || '',
                        konversi_qty_terbesar: productInput.data('konversi-qty-terbesar') || 0,
                    };

                    row.find('.konversi-fisik').text(qtyDisplay(stockFisik, itemMeta));

                    // Konversi selisih — tampilkan tanda + atau -
                    if (selisih !== 0) {
                        var selisihDisplay = (selisih > 0 ? '+' : '') + qtyDisplay(Math.abs(selisih), itemMeta);
                        row.find('.konversi-selisih').text(selisihDisplay).css('color', selisih > 0 ? 'green' : 'red');
                    } else {
                        row.find('.konversi-selisih').text('');
                    }
                });
            }

            function updateNomorUrut() {
                $('#tableBody tr').each(function(index) {
                    $(this).find('td:first').text(index + 1);
                });
            }

            $('#tambahBaris').on('click', function(e) {
            e.preventDefault();
            const newRow = `
                <tr>
                    <td></td>
                    <td colspan="2">
                        <select class="form-control select-stock">
                            <option value="">-- Pilih Produk --</option>
                            ${allStockData.map(s => `
                                <option value="${s.id}"
                                        data-product-id="${s.product_id}"
                                        data-product-code="${s.product_code}"
                                        data-satuan="${s.satuan || 'PCS'}"
                                        data-satuan-besar="${s.satuan_besar || ''}"
                                        data-konversi-qty="${s.konversi_qty || 0}"
                                        data-satuan-terbesar="${s.satuan_terbesar || ''}"
                                        data-konversi-qty-terbesar="${s.konversi_qty_terbesar || 0}"
                                        data-qty="${s.qty}">
                                    ${s.product_code} - ${s.product_name}
                                </option>
                            `).join('')}
                        </select>
                    </td>
                    <td><input type="text" class="form-control satuan" disabled /></td>
                    <td>
                        <input type="number" step="1" class="form-control stock_fisik" value="0" />
                        <small class="text-muted konversi-fisik"></small>
                    </td>
                    <td>
                        <input type="number" class="form-control stock_dikartu" disabled />
                        <small class="text-muted konversi-kartu"></small>
                    </td>
                    <td>
                        <input type="number" step="1" class="form-control selisih" disabled />
                        <small class="text-muted konversi-selisih"></small>
                    </td>
                    <td><input type="text" class="form-control keterangan" /></td>
                </tr>
            `;
            $('#tableBody').append(newRow);
                updateNomorUrut();

                const lastRow = $('#tableBody tr:last');
                lastRow.find('.select-stock').on('change', function() {
                    const selected = $(this).find(':selected');
                    const row      = $(this).closest('tr');
                    const stockQty = parseFloat(selected.data('qty')) || 0;

                    const itemMeta = {
                        satuan:                selected.data('satuan') || 'PCS',
                        satuan_besar:          selected.data('satuan-besar') || '',
                        konversi_qty:          selected.data('konversi-qty') || 0,
                        satuan_terbesar:       selected.data('satuan-terbesar') || '',
                        konversi_qty_terbesar: selected.data('konversi-qty-terbesar') || 0,
                    };

                    row.find('.satuan').val(itemMeta.satuan);
                    row.find('.stock_dikartu').val(stockQty);
                    row.find('.stock_fisik').val(stockQty);
                    row.find('.selisih').val(0);
                    row.find('.konversi-fisik').text(qtyDisplay(stockQty, itemMeta));
                    row.find('.konversi-kartu').text(qtyDisplay(stockQty, itemMeta));
                    row.find('.konversi-selisih').text('');
                });

                lastRow.find('.stock_fisik').on('input', function() {
                    const row        = $(this).closest('tr');
                    const stockFisik = parseFloat($(this).val()) || 0;
                    const stockKartu = parseFloat(row.find('.stock_dikartu').val()) || 0;
                    const selisih    = stockFisik - stockKartu;
                    row.find('.selisih').val(selisih.toFixed(2));

                    const selected = row.find('.select-stock').find(':selected');
                    const itemMeta = {
                        satuan:                selected.data('satuan') || 'PCS',
                        satuan_besar:          selected.data('satuan-besar') || '',
                        konversi_qty:          selected.data('konversi-qty') || 0,
                        satuan_terbesar:       selected.data('satuan-terbesar') || '',
                        konversi_qty_terbesar: selected.data('konversi-qty-terbesar') || 0,
                    };

                    row.find('.konversi-fisik').text(qtyDisplay(stockFisik, itemMeta));

                    if (selisih !== 0) {
                        var selisihDisplay = (selisih > 0 ? '+' : '') + qtyDisplay(Math.abs(selisih), itemMeta);
                        row.find('.konversi-selisih').text(selisihDisplay).css('color', selisih > 0 ? 'green' : 'red');
                    } else {
                        row.find('.konversi-selisih').text('').css('color', '');
                    }
                });
            });

            $('#tglStockOpname').on('change', function() {
                $('#exportTanggal').val($(this).val());
            });

            $('#filterLokasi').on('change', function() {
                var lok  = $(this).val();
                var base = '{{ route('stock.opname.export-template') }}';
                $('#btnExportTemplate').attr('href', lok ? base + '?lokasi=' + encodeURIComponent(lok) : base);
                $('#exportLokasi').val(lok);
                loadStockData();
            });

            $('#btnSaveOpname').on('click', function() {
                const tglStockOpname = $('#tglStockOpname').val();
                if (!tglStockOpname) {
                    alert('Tanggal Stock Opname harus diisi!');
                    return;
                }

                const items = [];
                $('#tableBody tr').each(function() {
                    const row          = $(this);
                    const productInput = row.find('.product-name');
                    const selectStock  = row.find('.select-stock');

                    // Prioritas membaca product_id secara global
                    let productId = productInput.data('product-id');
                    if (!productId && selectStock.length) {
                        productId = selectStock.find(':selected').data('product-id');
                    }

                    const selisih     = parseFloat(row.find('.selisih').val()) || 0;
                    const keterangan  = row.find('.keterangan').val().trim();
                    const systemQty   = parseFloat(row.find('.stock_dikartu').val()) || 0;
                    const physicalQty = parseFloat(row.find('.stock_fisik').val()) || 0;

                    if (productId && selisih !== 0) {
                        items.push({
                            product_id:   productId,
                            selisih:      selisih,
                            system_qty:   systemQty,
                            physical_qty: physicalQty,
                            keterangan:   keterangan,
                        });
                    }
                });

                if (items.length === 0) {
                    alert('Tidak ada perubahan stock untuk disimpan!');
                    return;
                }

                if (!confirm(`Simpan ${items.length} penyesuaian stock?`)) {
                    return;
                }

                $.ajax({
                    url: '{{ route('stock.opname.save') }}',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        _token: '{{ csrf_token() }}',
                        adjustment_date: tglStockOpname,
                        items: items
                    }),
                    success: function(data) {
                        if (data.success) {
                            alert('Stock opname berhasil disimpan!');
                            location.reload();
                        } else {
                            alert('Gagal menyimpan: ' + data.message);
                        }
                    },
                    error: function(xhr) {
                        alert('Terjadi kesalahan saat menyimpan data');
                        console.error(xhr.responseText);
                    }
                });
            });
        });
    </script>
@endsection
