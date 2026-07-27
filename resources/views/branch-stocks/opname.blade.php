@extends('layouts.master')

@section('title', 'Stock Opname Cabang')

@section('container')
    <section class="content-header">
        <h1>Stock Opname Cabang</h1>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-xs-12">
                <div class="box">
                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-3">
                                <label>Tanggal Opname</label>
                                <input type="date" id="adjustment_date" class="form-control" value="{{ date('Y-m-d') }}">
                            </div>
                            <div class="col-md-5">
                                <label>Cabang</label>
                                <select id="owner_id" class="form-control select2" style="width:100%" {{ $isBranchScoped ? 'disabled' : '' }}>
                                    <option value="">Pilih Cabang</option>
                                    @foreach ($outlets as $outlet)
                                        <option value="{{ $outlet->id }}" {{ (string) $selectedOwnerId === (string) $outlet->id ? 'selected' : '' }}>
                                            {{ $outlet->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>&nbsp;</label>
                                <button type="button" id="btnLoad" class="btn btn-primary btn-block">Load</button>
                            </div>
                            <div class="col-md-2">
                                <label>&nbsp;</label>
                                <button type="button" id="btnSave" class="btn btn-success btn-block">Simpan</button>
                            </div>
                        </div>

                        <hr>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Kode</th>
                                        <th>Produk</th>
                                        <th>Satuan</th>
                                        <th>Stock Fisik</th>
                                        <th>Stock Sistem</th>
                                        <th>Selisih</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody id="opnameBody">
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">Pilih cabang lalu klik Load.</td>
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
        var stockRows = [];

        function ownerId() {
            return $('#owner_id').val() || @json($selectedOwnerId);
        }

        function renderRows() {
            var rows = '';

            if (!stockRows.length) {
                $('#opnameBody').html('<tr><td colspan="8" class="text-center text-muted">Tidak ada stock cabang.</td></tr>');
                return;
            }

            stockRows.forEach(function(item, index) {
                rows += '<tr data-index="' + index + '">' +
                    '<td>' + (index + 1) + '</td>' +
                    '<td>' + (item.product_code || '-') + '</td>' +
                    '<td>' + (item.product_name || '-') + '</td>' +
                    '<td>' + (item.satuan || 'PCS') + '</td>' +
                    '<td><input type="number" class="form-control physical-qty" min="0" step="1" value="' + item.qty + '"></td>' +
                    '<td><input type="number" class="form-control system-qty" value="' + item.qty + '" disabled></td>' +
                    '<td><input type="number" class="form-control diff-qty" value="0" disabled></td>' +
                    '<td><input type="text" class="form-control notes" placeholder="Keterangan"></td>' +
                '</tr>';
            });

            $('#opnameBody').html(rows);
        }

        $('#btnLoad').on('click', function() {
            if (!ownerId()) {
                alert('Cabang wajib dipilih.');
                return;
            }

            $('#opnameBody').html('<tr><td colspan="8" class="text-center">Loading...</td></tr>');

            $.get('{{ route('branch-stock.opname.data') }}', { owner_id: ownerId() })
                .done(function(response) {
                    stockRows = response.stocks || [];
                    renderRows();
                })
                .fail(function() {
                    $('#opnameBody').html('<tr><td colspan="8" class="text-center text-danger">Gagal memuat stock cabang.</td></tr>');
                });
        });

        $(document).on('input', '.physical-qty', function() {
            var row = $(this).closest('tr');
            var physical = parseFloat($(this).val()) || 0;
            var system = parseFloat(row.find('.system-qty').val()) || 0;
            row.find('.diff-qty').val(physical - system);
        });

        $('#btnSave').on('click', function() {
            if (!ownerId()) {
                alert('Cabang wajib dipilih.');
                return;
            }

            var items = [];

            $('#opnameBody tr[data-index]').each(function() {
                var index = $(this).data('index');
                var item = stockRows[index];
                var systemQty = parseFloat($(this).find('.system-qty').val()) || 0;
                var physicalQty = parseFloat($(this).find('.physical-qty').val()) || 0;
                var diffQty = physicalQty - systemQty;

                if (diffQty === 0) {
                    return;
                }

                items.push({
                    product_id: item.product_id,
                    system_qty: systemQty,
                    physical_qty: physicalQty,
                    selisih: diffQty,
                    keterangan: $(this).find('.notes').val()
                });
            });

            if (!items.length) {
                alert('Tidak ada selisih untuk disimpan.');
                return;
            }

            $.post('{{ route('branch-stock.opname.save') }}', {
                _token: '{{ csrf_token() }}',
                owner_id: ownerId(),
                adjustment_date: $('#adjustment_date').val(),
                items: items
            }).done(function(response) {
                alert(response.message || 'Stock opname cabang berhasil disimpan.');
                $('#btnLoad').trigger('click');
            }).fail(function(xhr) {
                alert(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Gagal menyimpan opname.');
            });
        });

        $(function() {
            if (ownerId()) {
                $('#btnLoad').trigger('click');
            }
        });
    </script>
@endsection
