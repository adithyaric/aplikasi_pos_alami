@extends('layouts.master')
@section('title', 'Stocks')
@section('container')
    <section class="content-header">
        <h1>Data Stok</h1>
    </section>
    <section class="content">
        <div class="row">
            <div class="col-xs-12">
                <div class="box">
                    <div class="box-header">
                        <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                            <select id="filterKategori" class="form-control input-sm select2" style="width:auto; min-width:160px;">
                                <option value="">Semua Kategori</option>
                            </select>

                            <form method="GET" action="{{ route('laporan.stock') }}" style="display:flex; gap:6px; align-items:center; flex-wrap:wrap; margin:0 0 0 auto;">
                                <span class="text-muted">Periode export:</span>
                                <input type="date" name="date_from" class="form-control input-sm" value="{{ now()->startOfMonth()->toDateString() }}" required>
                                <span>s/d</span>
                                <input type="date" name="date_to" class="form-control input-sm" value="{{ now()->toDateString() }}" required>
                                <button type="submit" class="btn btn-sm btn-success">
                                    <i class="fa fa-file-excel-o"></i> Export Rekap Stok
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="box-body table-responsive">
                        <table id="example1" class="table table-bordered table-striped">
                            <thead>
                                    <tr>
                                        <td>No</td>
                                        <td>Code</td>
                                        <td>Product</td>
                                        <td>Stok</td>
                                        <td>Konversi</td>
                                        <td>Harga Beli</td>
                                        <td>Created</td>
                                        <td>Action</td>
                                        <td style="display:none;">Kategori</td>
                                    </tr>
                                </thead>
                            <tbody>
                                @foreach ($stocks as $stock)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $stock->product->code }}</td>
                                    <td>{{ $stock->product->name }}</td>
                                    <td>
                                        <span class="text-{{ $stock->total_qty <= 0 ? 'danger' : ($stock->product->isLowStock() ? 'warning' : 'success') }}">
                                            {{ $stock->product->stockSummaryDisplay($stock->total_qty) }}
                                        </span>
                                    </td>
                                    <td>
                                        {{-- Kolom Konversi --}}
                                        {{ $stock->product->konversi_string }}
                                        @if($stock->product->satuan_terbesar && $stock->product->konversi_qty_terbesar)
                                            <br><small class="text-muted">{{ $stock->product->konversi_terbesar_string }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        {{-- Kolom Harga Beli (Menampilkan harga terbaru/tertinggi dari batch) --}}
                                        <button type="button" class="btn btn-xs btn-info btn-price-history"
                                            data-toggle="modal" data-target="#priceHistoryModal"
                                            data-id="{{ $stock->product_id }}">
                                            @currency($stock->harga_beli)
                                        </button>
                                    </td>
                                    <td>
                                        {{-- Kolom Created diubah ke tanggal update/input batch terbaru --}}
                                        {{ $stock->latest_created_at ? \Carbon\Carbon::parse($stock->latest_created_at)->format('h:i a / d-M-Y') : '-' }}
                                    </td>
                                    <td>
                                        {{-- Mengubah data-id menjadi product_id --}}
                                        <button type="button" class="btn btn-xs btn-primary btn-stock-history"
                                            data-toggle="modal" data-target="#stockHistoryModal"
                                            data-id="{{ $stock->product_id }}"> {{-- Menggunakan product_id --}}
                                            <i class="fa fa-history"></i> History
                                        </button>
                                    </td>
                                    <td style="display:none;">{{ $stock->product->category?->name ?? '' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <!-- Price History Modal -->
                        <div class="modal fade" id="priceHistoryModal" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                                        <h4 class="modal-title">Price History (Harga Beli)</h4>
                                    </div>
                                    <div class="modal-body">
                                        <table class="table table-bordered table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>User</th>
                                                    <th>Change</th>
                                                </tr>
                                            </thead>
                                            <tbody id="priceHistoryBody">
                                                <tr>
                                                    <td colspan="3" class="text-center">Loading...</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Stock History Modal -->
                        <div class="modal fade" id="stockHistoryModal" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                                        <h4 class="modal-title">Stock History</h4>
                                    </div>
                                    <div class="modal-body">
                                        <h5><b>Activity Log</b></h5>
                                        <div class="table-responsive text-nowrap">
                                            <table id="example2" class="table table-bordered table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>User</th>
                                                        <th>Event</th>
                                                        <th>Changes</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="activityBody">
                                                    <tr>
                                                        <td colspan="4" class="text-center">Loading...</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        <h5><b>Stock Movements</b></h5>
                                        <div class="table-responsive text-nowrap">
                                            <table id="example3" class="table table-bordered table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>User</th>
                                                        <th>Type</th>
                                                        <th>In</th>
                                                        <th>Out</th>
                                                        <th>Balance</th>
                                                        <th>Notes</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="movementBody">
                                                    <tr>
                                                        <td colspan="7" class="text-center">Loading...</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div> {{-- box-body --}}
                </div>
            </div>
        </div>
    </section>
@endsection
@section('page-script')
    <script>
        $(document).ready(function() {
            if ($.fn.DataTable.isDataTable('#example1')) {
                $('#example1').DataTable().destroy();
            }
            if ($.fn.DataTable.isDataTable('#example2')) {
                $('#example2').DataTable().destroy();
            }
            if ($.fn.DataTable.isDataTable('#example3')) {
                $('#example3').DataTable().destroy();
            }

            var table = $('#example1').DataTable({
                columnDefs: [{ visible: false, targets: [8] }] // 7 → 8
            });

            // Populate Kategori dropdown (column 7)
            table.column(8).data().unique().sort().each(function(val) {
                if (val && String(val).trim() !== '') {
                    $('#filterKategori').append($('<option>', { value: val, text: val }));
                }
            });

            function escReg(val) {
                return val.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, '\\$&');
            }

            $('#filterKategori').on('change', function() {
                var val = $(this).val();
                table.column(8).search(val ? '^' + escReg(val) + '$' : '', true, false).draw();
            });

            $('#priceHistoryModal').on('show.bs.modal', function(event) {
                var button = $(event.relatedTarget);
                var id = button.data('id');
                var modal = $(this);
                modal.find('#priceHistoryBody').html(
                    '<tr><td colspan="3" class="text-center">Loading...</td></tr>');

                $.ajax({
                    url: '/product/' + id + '/price-history',
                    method: 'GET',
                    success: function(res) {
                        var rows = '';
                        if (res.data && res.data.length) {
                            res.data.forEach(function(item) {
                                var change = item.event === 'created' ?
                                    'Created → ' + Number(item.new).toLocaleString() :
                                    Number(item.old).toLocaleString() + ' → ' + Number(item.new).toLocaleString();
                                rows += '<tr><td>' + item.date + '</td><td>' + item.user + '</td><td>' + change + '</td></tr>';
                            });
                        } else {
                            rows = '<tr><td colspan="3" class="text-center">No changes found.</td></tr>';
                        }
                        modal.find('#priceHistoryBody').html(rows);
                    },
                    error: function() {
                        modal.find('#priceHistoryBody').html(
                            '<tr><td colspan="3" class="text-center text-danger">Error loading data.</td></tr>'
                        );
                    }
                });
            });

            $('#stockHistoryModal').on('show.bs.modal', function(event) {
                var button = $(event.relatedTarget);
                var id = button.data('id');

                $('#activityBody').html('<tr><td colspan="4" class="text-center">Loading...</td></tr>');
                $('#movementBody').html('<tr><td colspan="7" class="text-center">Loading...</td></tr>');

                if ($.fn.DataTable.isDataTable('#example2')) {
                    $('#example2').DataTable().destroy();
                }
                if ($.fn.DataTable.isDataTable('#example3')) {
                    $('#example3').DataTable().destroy();
                }

                $.get('/product/' + id + '/history', function(res) {

                    // Helper konversi qty — sama logic dengan Product::qtyDisplay() di PHP
                    function qtyDisplay(qty, product) {
                        if (!qty && qty !== 0) return '-';
                        qty = parseInt(qty) || 0;
                        var parts = [];

                        // Satuan dasar
                        parts.push(qty.toLocaleString('id-ID') + ' ' + (product.satuan || 'PCS'));

                        // Satuan besar
                        if (product.konversi_qty && product.satuan_besar) {
                            var satuanBesar = Math.floor(qty / product.konversi_qty);
                            if (satuanBesar > 0) {
                                parts.push(satuanBesar.toLocaleString('id-ID') + ' ' + product.satuan_besar);
                            }
                        }

                        // Satuan terbesar
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

                    var product = res.product || {};

                    var aRows = '';
                    if (res.activities.length) {
                        res.activities.forEach(function(item) {
                            var changes = '';
                            if (item.event === 'created') {
                                changes = 'Stock created';
                            } else {
                                var old = item.properties.old || {};
                                var attr = item.properties.attributes || {};
                                changes = Object.keys(attr).map(function(k) {
                                    return k + ': ' + (old[k] ?? '?') + ' → ' + attr[k];
                                }).join('<br>');
                            }
                            aRows += '<tr><td>' + item.date + '</td><td>' + item.user +
                                '</td><td>' + item.event + '</td><td>' + changes + '</td></tr>';
                        });
                    } else {
                        aRows = '<tr><td colspan="4" class="text-center">No activity found.</td></tr>';
                    }
                    $('#activityBody').html(aRows);

                    var mRows = '';
                    if (res.movements.length) {
                        res.movements.forEach(function(item) {
                            var qtyIn      = item.qty_in  ?? 0;
                            var qtyOut     = item.qty_out ?? 0;
                            var balance    = item.balance ?? 0;

                            var displayIn      = qtyIn > 0      ? qtyDisplay(qtyIn, product)   : '-';
                            var displayOut     = qtyOut > 0     ? qtyDisplay(qtyOut, product)  : '-';
                            var displayBalance = qtyDisplay(balance, product);

                            mRows += '<tr>'
                                + '<td>' + item.date + '</td>'
                                + '<td>' + item.user + '</td>'
                                + '<td>' + item.type + '</td>'
                                + '<td>' + displayIn + '</td>'
                                + '<td>' + displayOut + '</td>'
                                + '<td><strong>' + displayBalance + '</strong></td>'
                                + '<td><small>' + (item.notes ?? '-') + '</small></td>'
                                + '</tr>';
                        });
                    } else {
                        mRows = '<tr><td colspan="7" class="text-center">No movements found.</td></tr>';
                    }
                    $('#movementBody').html(mRows);

                    $('#example2').DataTable();
                    $('#example3').DataTable();

                }).fail(function() {
                    $('#activityBody').html('<tr><td colspan="4" class="text-center text-danger">Error loading data.</td></tr>');
                    $('#movementBody').html('<tr><td colspan="7" class="text-center text-danger">Error loading data.</td></tr>');
                });
            });
        });
    </script>
@endsection
