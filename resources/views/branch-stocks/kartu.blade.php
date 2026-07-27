@extends('layouts.master')

@section('title', 'Kartu Stock Cabang')

@section('container')
    <section class="content-header">
        <h1>Kartu Stock Cabang</h1>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-xs-12">
                <div class="box">
                    <div class="box-header">
                        <form method="GET" action="{{ route('branch-stock.kartu') }}" class="form-inline">
                            <div class="form-group">
                                <label>Cabang</label>
                                <select name="owner_id" id="owner_id" class="form-control select2" style="min-width:220px" {{ $isBranchScoped ? 'disabled' : '' }}>
                                    <option value="">Pilih Cabang</option>
                                    @foreach ($outlets as $outlet)
                                        <option value="{{ $outlet->id }}" {{ (string) $selectedOwnerId === (string) $outlet->id ? 'selected' : '' }}>
                                            {{ $outlet->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @if (! $isBranchScoped)
                                <button class="btn btn-primary" type="submit">Tampilkan</button>
                            @endif
                        </form>
                    </div>

                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-8">
                                <label>Produk</label>
                                <select id="product_id" class="form-control select2" style="width:100%">
                                    <option value="">Pilih Produk</option>
                                    @foreach ($products as $product)
                                        <option value="{{ $product['product_id'] }}">
                                            {{ $product['product_code'] }} - {{ $product['product_name'] }} ({{ $product['total_qty'] }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label>&nbsp;</label>
                                <button id="btnLoadKartu" class="btn btn-primary btn-block" type="button">
                                    <i class="fa fa-search"></i> Tampilkan Kartu
                                </button>
                            </div>
                        </div>

                        <hr>

                        <table class="table table-condensed" style="width:60%">
                            <tr>
                                <td>Cabang</td>
                                <td>: <span id="displayOwner">-</span></td>
                            </tr>
                            <tr>
                                <td>Produk</td>
                                <td>: <span id="displayProduct">-</span></td>
                            </tr>
                            <tr>
                                <td>Kode</td>
                                <td>: <span id="displayCode">-</span></td>
                            </tr>
                        </table>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Tanggal</th>
                                        <th>User</th>
                                        <th>Tipe</th>
                                        <th>Stok Awal</th>
                                        <th>Masuk</th>
                                        <th>Keluar</th>
                                        <th>Stok Akhir</th>
                                        <th>Nilai</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody id="kartuBody">
                                    <tr>
                                        <td colspan="10" class="text-center text-muted">Pilih cabang dan produk.</td>
                                    </tr>
                                </tbody>
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
        function formatNumber(value) {
            return Number(value || 0).toLocaleString('id-ID');
        }

        $('#btnLoadKartu').on('click', function() {
            var ownerId = $('#owner_id').val() || @json($selectedOwnerId);
            var productId = $('#product_id').val();

            if (!ownerId || !productId) {
                alert('Cabang dan produk wajib dipilih.');
                return;
            }

            $('#kartuBody').html('<tr><td colspan="10" class="text-center">Loading...</td></tr>');

            $.get('{{ route('branch-stock.kartu.data') }}', {
                owner_id: ownerId,
                product_id: productId
            }).done(function(response) {
                $('#displayOwner').text(response.stock.owner_name || '-');
                $('#displayProduct').text(response.stock.product_name || '-');
                $('#displayCode').text(response.stock.product_code || '-');

                if (!response.transactions.length) {
                    $('#kartuBody').html('<tr><td colspan="10" class="text-center text-muted">Belum ada mutasi.</td></tr>');
                    return;
                }

                var rows = '';
                response.transactions.forEach(function(item, index) {
                    rows += '<tr>' +
                        '<td>' + (index + 1) + '</td>' +
                        '<td>' + item.tanggal + '</td>' +
                        '<td>' + (item.user || '-') + '</td>' +
                        '<td>' + item.type + '</td>' +
                        '<td class="text-right">' + formatNumber(item.stok_awal) + '</td>' +
                        '<td class="text-right">' + formatNumber(item.masuk) + '</td>' +
                        '<td class="text-right">' + formatNumber(item.keluar) + '</td>' +
                        '<td class="text-right"><strong>' + formatNumber(item.stok_akhir) + '</strong></td>' +
                        '<td class="text-right">' + formatNumber(item.nilai) + '</td>' +
                        '<td>' + (item.keterangan || '-') + '</td>' +
                    '</tr>';
                });

                $('#kartuBody').html(rows);
            }).fail(function(xhr) {
                $('#kartuBody').html('<tr><td colspan="10" class="text-center text-danger">Gagal memuat data.</td></tr>');
                alert(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Gagal memuat kartu stock cabang.');
            });
        });
    </script>
@endsection
