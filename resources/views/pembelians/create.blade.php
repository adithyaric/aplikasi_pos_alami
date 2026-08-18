@extends('layouts.master')

@section('title', 'Tambah PO')

@section('container')
    <section class="content">
        <div class="row">
            <!-- left column -->
            <div class="col-md-12">
                <!-- general form elements -->
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">Tambah PO</h3>
                    </div><!-- /.box-header -->
                    <!-- form start -->
                    <form action="{{ route('pembelian.store') }}" method="POST" enctype="multipart/form-data"
                        data-offline-queue="pembelian-create"
                        data-offline-title="Pembelian"
                        data-offline-redirect="{{ route('pembelian.index') }}">
                        @csrf
                        <div class="box-body">
                            <div class="form-group">
                                <label for="">Kode PO</label>
                                <input type="text" class="form-control" name="code" value="{{ old('code', $code) }}"
                                    placeholder="Pilih supplier untuk membuat nomor PO" readonly>
                                <small class="text-muted">Nomor PO otomatis mengikuti format dan urutan supplier yang dipilih.</small>
                                @error('code')
                                    <div class="invalid-feedback text-danger">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            @php
                                $selectedCustomerPo = old('customer_po', '');
                            @endphp
                            <div class="form-group">
                                <label for="">Customer PO</label>
                                <div class="clearfix" style="margin-bottom:6px;">
                                    <button type="button" class="btn btn-success btn-xs pull-right" data-toggle="modal" data-target="#modalCustomerPo" style="margin-left:6px;">
                                        <i class="fa fa-plus"></i> Tambah Customer PO
                                    </button>
                                    <a href="{{ route('customer-po.index') }}" class="btn btn-default btn-xs pull-right">
                                        Manage Customer PO
                                    </a>
                                </div>
                                <select class="form-control customer-po-select"
                                    name="customer_po"
                                    data-placeholder="Pilih Customer PO"
                                    data-options-url="{{ route('pembelian.customer-po-options') }}"
                                    data-selected-customer-po="{{ $selectedCustomerPo }}"
                                    style="width:100%;">
                                    <option value=""></option>
                                </select>
                                @error('customer_po')
                                    <div class="invalid-feedback text-danger">
                                        {{ $message }}
                                    </div>
                                @enderror
                                <small class="text-muted">Pilih dari master Customer PO. Klik Tambah Customer PO untuk membuat data baru tanpa meninggalkan halaman.</small>
                                <div class="well well-sm customer-po-detail-card" style="display:none; margin-top:10px; margin-bottom:0;">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>Nama</strong>
                                            <div id="customer_po_detail_name">-</div>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Nama Perusahaan</strong>
                                            <div id="customer_po_detail_company">-</div>
                                        </div>
                                    </div>
                                    <div class="row" style="margin-top:10px;">
                                        <div class="col-md-6">
                                            <strong>Phone</strong>
                                            <div id="customer_po_detail_phone">-</div>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Email</strong>
                                            <div id="customer_po_detail_email">-</div>
                                        </div>
                                    </div>
                                    <div style="margin-top:10px;">
                                        <strong>Alamat</strong>
                                        <div id="customer_po_detail_address">-</div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Supplier</label>
                                <select class="form-control select2" name="supplier_id" data-placeholder="Pilih Supplier"
                                    style="width: 100%;">
                                    <option value="" selected disabled>Pilih Supplier</option>
                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}"
                                            {{ old('supplier_id', request('supplier_id')) == $supplier->id ? 'selected' : '' }}>
                                            {{ $supplier->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('supplier_id')
                                    <div class="invalid-feedback text-danger">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <hr>
                            <table class="table table-bordered table-striped" id="example">
                                <tr>
                                    <td>Nama Product</td>
                                    <td>Qty (Satuan Besar)</td>
                                    <td>Harga Beli (per satuan kecil)</td>
                                    <td>Sub Total</td>
                                    <td>Aksi</td>
                                </tr>
                                <tbody id="product-repeater">
                                    <tr>
                                        <td>
                                            <select class="form-control select2 product" data-placeholder="Pilih Product"
                                                name="product[0][product_id]" required style="width:100%">
                                                <option value="" disabled selected>Pilih Produk</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control qty" name="product[0][qty]" required
                                                value="1" min="1">
                                            <span class="konversi-display"></span>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control harga_beli numeral-mask"
                                                name="product[0][harga_beli]" required>
                                        </td>
                                        <td>
                                            <input class="form-control subtotal" name="product[0][subtotal]" required
                                                readonly>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-danger" onclick="removeBahanBaku(this)"
                                                type="button">Remove</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <div class="d-flex gap-2 mb-2">
                                <button class="btn btn-sm btn-warning" type="button" data-toggle="modal" data-target="#modalCekBarang">
                                    <i class="fa fa-search"></i> Cek Barang
                                </button>
                                <button class="btn btn-sm btn-primary" onclick="addBahanBaku()" type="button">
                                    <i class="fa fa-plus"></i> Add Row
                                </button>
                            </div>
                            <hr>
                            <div class="form-group">
                                <label>Total</label>
                                <input type="text" required class="form-control" name="total" id="total" readonly>
                            </div>
                        </div><!-- /.box-body -->

                        <div class="box-footer">
                            <a href="{{ route('pembelian.index') }}" class="btn btn-default">Kembali</a>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>

                        <!-- Modal Cek Barang -->
                        <div class="modal fade" id="modalCekBarang" tabindex="-1" role="dialog" aria-labelledby="modalCekBarangLabel">
                            <div class="modal-dialog modal-lg" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                        <h4 class="modal-title" id="modalCekBarangLabel">
                                            <i class="fa fa-search"></i> Pilih Produk untuk PO
                                            <small class="text-warning">— diurutkan dari stok paling kritis</small>
                                        </h4>
                                    </div>
                                    <div class="modal-body">
                                        <table id="tableCekBarang" class="table table-bordered table-striped table-hover" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th width="30"><input type="checkbox" id="checkAll"></th>
                                                    <th>Kode</th>
                                                    <th>Nama Produk</th>
                                                    <th>Stok Saat Ini</th>
                                                    <th>Min Stok</th>
                                                    <th>Konversi</th>
                                                    <th>Status</th>
                                                    <th width="90">Qty Order</th>
                                                </tr>
                                            </thead>
                                            <tbody id="cekBarangBody"></tbody>
                                        </table>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
                                        <button type="button" class="btn btn-primary" id="btnTambahkanPO">
                                            <i class="fa fa-check"></i> Tambahkan ke PO
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Modal Customer PO -->
                    <div class="modal fade" id="modalCustomerPo" tabindex="-1" role="dialog" aria-labelledby="modalCustomerPoLabel">
                        <div class="modal-dialog" role="document">
                            <form id="customerPoModalForm" action="{{ route('customer-po.store') }}" method="POST">
                                @csrf
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                        <h4 class="modal-title" id="modalCustomerPoLabel">
                                            <i class="fa fa-plus"></i> Tambah Customer PO
                                        </h4>
                                    </div>
                                    <div class="modal-body">
                                        <div class="form-group">
                                            <label for="customer_po_name">Nama</label>
                                            <input type="text" class="form-control" id="customer_po_name" name="name" placeholder="Masukkan Nama">
                                            <span class="help-block text-danger customer-po-error" data-field="name" style="display:none;"></span>
                                        </div>
                                        <div class="form-group">
                                            <label for="customer_po_company_name">Nama Perusahaan</label>
                                            <input type="text" class="form-control" id="customer_po_company_name" name="company_name" placeholder="Masukkan Nama Perusahaan">
                                            <span class="help-block text-danger customer-po-error" data-field="company_name" style="display:none;"></span>
                                        </div>
                                        <div class="form-group">
                                            <label for="customer_po_address">Alamat</label>
                                            <textarea class="form-control" id="customer_po_address" name="address" rows="3" placeholder="Masukkan Alamat"></textarea>
                                            <span class="help-block text-danger customer-po-error" data-field="address" style="display:none;"></span>
                                        </div>
                                        <div class="form-group">
                                            <label for="customer_po_phone">Phone</label>
                                            <input type="text" class="form-control" id="customer_po_phone" name="phone" placeholder="Masukkan Phone">
                                            <span class="help-block text-danger customer-po-error" data-field="phone" style="display:none;"></span>
                                        </div>
                                        <div class="form-group" style="margin-bottom:0;">
                                            <label for="customer_po_email">Email</label>
                                            <input type="email" class="form-control" id="customer_po_email" name="email" placeholder="Masukkan Email">
                                            <span class="help-block text-danger customer-po-error" data-field="email" style="display:none;"></span>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-primary" id="btnSaveCustomerPo">
                                            <i class="fa fa-save"></i> Simpan
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div><!-- /.box -->
            </div>
        </div>
    </section>
@endsection
@section('page-script')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script>
        let currentProducts = null;
        let productIndex = 0;
        let supplierRequest = null;
        let selectedSupplierId = $('[name="supplier_id"]').val() || null;
        let poCodeRequest = null;
        let poCodeManuallyEdited = {{ old('code') ? 'true' : 'false' }};
        let currentSuggestedPoCode = $('[name="code"]').val() || '';

        var customerPoDirectory = {};

        function normalizeCustomerPoName(name) {
            return $.trim(name || '');
        }

        function normalizeCustomerPoField(value) {
            return $.trim(value || '');
        }

        function customerPoOptionKey(name) {
            return normalizeCustomerPoName(name).toLowerCase();
        }

        function extractCustomerPoItems(response) {
            if ($.isArray(response)) {
                return response;
            }

            if (response && $.isArray(response.results)) {
                return response.results;
            }

            if (response && $.isArray(response.data)) {
                return response.data;
            }

            return [];
        }

        function customerPoItemDetails(item) {
            if (typeof item === 'string') {
                return {
                    name: normalizeCustomerPoName(item),
                    company_name: '',
                    address: '',
                    phone: '',
                    email: ''
                };
            }

            return {
                name: normalizeCustomerPoName(item && (item.name || item.text || item.value || item.id)),
                company_name: normalizeCustomerPoField(item && item.company_name),
                address: normalizeCustomerPoField(item && item.address),
                phone: normalizeCustomerPoField(item && item.phone),
                email: normalizeCustomerPoField(item && item.email)
            };
        }

        function customerPoItemLabel(item) {
            var details = customerPoItemDetails(item);

            return details.company_name
                ? details.name + ' - ' + details.company_name
                : details.name;
        }

        function rememberCustomerPoItem(item) {
            var details = customerPoItemDetails(item);
            var key = customerPoOptionKey(details.name);

            if (key === '') {
                return;
            }

            if (!customerPoDirectory[key]) {
                customerPoDirectory[key] = details;
                return;
            }

            ['company_name', 'address', 'phone', 'email'].forEach(function(field) {
                if (!customerPoDirectory[key][field] && details[field]) {
                    customerPoDirectory[key][field] = details[field];
                }
            });
        }

        function appendCustomerPoOption($select, item) {
            var details = customerPoItemDetails(item);

            if (details.name === '') {
                return;
            }

            rememberCustomerPoItem(details);

            $select.append($('<option>', {
                value: details.name,
                text: customerPoItemLabel(details)
            }));
        }

        function appendAndSelectCustomerPo($select, name) {
            var normalized = normalizeCustomerPoName(name);

            if (normalized === '') {
                $select.val('').trigger('change');
                return;
            }

            var matchingValue = null;
            $select.find('option').each(function() {
                if (normalizeCustomerPoName(this.value).toLowerCase() === normalized.toLowerCase()) {
                    matchingValue = this.value;
                    return false;
                }
            });

            if (matchingValue === null) {
                appendCustomerPoOption($select, {
                    name: normalized
                });
                matchingValue = normalized;
            }

            $select.val(matchingValue).trigger('change');
        }

        function renderCustomerPoDetails(name) {
            var normalized = normalizeCustomerPoName(name);
            var details = customerPoDirectory[customerPoOptionKey(normalized)] || customerPoItemDetails({
                name: normalized
            });

            if (normalized === '') {
                $('.customer-po-detail-card').hide();
                return;
            }

            $('#customer_po_detail_name').text(details.name || '-');
            $('#customer_po_detail_company').text(details.company_name || '-');
            $('#customer_po_detail_address').text(details.address || '-');
            $('#customer_po_detail_phone').text(details.phone || '-');
            $('#customer_po_detail_email').text(details.email || '-');
            $('.customer-po-detail-card').show();
        }

        function notifyCustomerPo(icon, message) {
            if (window.Swal && typeof window.Swal.fire === 'function') {
                window.Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: icon,
                    title: message,
                    showConfirmButton: false,
                    timer: 2200,
                    timerProgressBar: true
                });
                return;
            }

            alert(message);
        }

        function initializeCustomerPoSelectWidget($select) {
            $select.each(function() {
                var $currentSelect = $(this);

                if ($currentSelect.data('select2')) {
                    $currentSelect.select2('destroy');
                }

                $currentSelect.nextAll('.select2-container').remove();

                $currentSelect.select2({
                    width: '100%',
                    allowClear: true,
                    placeholder: $currentSelect.data('placeholder') || 'Pilih Customer PO'
                });
            });
        }

        function loadCustomerPoOptions(selectedName) {
            var $select = $('.customer-po-select');
            var selected = normalizeCustomerPoName(selectedName || $select.val());
            var optionsUrl = $select.data('options-url');

            if (!optionsUrl) {
                appendAndSelectCustomerPo($select, selected);
                renderCustomerPoDetails(selected);
                return $.Deferred().resolve().promise();
            }

            return $.getJSON(optionsUrl, { q: '' })
                .done(function(response) {
                    var seen = {};
                    customerPoDirectory = {};

                    $select.find('option:not([value=""])').remove();

                    $.each(extractCustomerPoItems(response), function(_, item) {
                        var details = customerPoItemDetails(item);
                        var key = customerPoOptionKey(details.name);

                        if (details.name === '' || seen[key]) {
                            return;
                        }

                        seen[key] = true;
                        appendCustomerPoOption($select, details);
                    });

                    initializeCustomerPoSelectWidget($select);
                    appendAndSelectCustomerPo($select, selected);
                })
                .fail(function() {
                    if (!$select.data('select2')) {
                        initializeCustomerPoSelectWidget($select);
                    }

                    appendAndSelectCustomerPo($select, selected);
                    renderCustomerPoDetails(selected);
                    notifyCustomerPo('error', 'Gagal memuat daftar Customer PO.');
                });
        }

        function initializeCustomerPoSelect() {
            var $select = $('.customer-po-select');

            if (!$select.length) {
                return;
            }

            $select.on('change', function() {
                renderCustomerPoDetails($(this).val());
            });

            loadCustomerPoOptions($select.data('selected-customer-po'));
        }

        function resetCustomerPoModal() {
            $('#customerPoModalForm')[0].reset();
            $('.customer-po-error').hide().text('');
        }

        function closeCustomerPoModal() {
            var $modal = $('#modalCustomerPo');

            $modal.modal('hide');

            setTimeout(function() {
                if ($modal.is(':visible')) {
                    $modal.removeClass('in').hide().attr('aria-hidden', 'true');
                    $('body').removeClass('modal-open');
                    $('.modal-backdrop').remove();
                }
            }, 200);
        }

        initializeCustomerPoSelect();

        $('#modalCustomerPo').on('shown.bs.modal', function() {
            $('#customer_po_name').focus();
        });

        $('#modalCustomerPo').on('hidden.bs.modal', function() {
            resetCustomerPoModal();
        });

        $('#customerPoModalForm').on('submit', function(event) {
            event.preventDefault();

            var $form = $(this);
            var $button = $('#btnSaveCustomerPo');
            var firstMessage = '';

            $('.customer-po-error').hide().text('');

            $form.find('[name="name"]').val(normalizeCustomerPoName($form.find('[name="name"]').val()));
            $form.find('[name="company_name"]').val(normalizeCustomerPoField($form.find('[name="company_name"]').val()));
            $form.find('[name="address"]').val(normalizeCustomerPoField($form.find('[name="address"]').val()));
            $form.find('[name="phone"]').val(normalizeCustomerPoField($form.find('[name="phone"]').val()));
            $form.find('[name="email"]').val(normalizeCustomerPoField($form.find('[name="email"]').val()));

            if ($form.find('[name="name"]').val() === '') {
                $('[data-field="name"]').text('Nama Customer PO wajib diisi.').show();
                return;
            }

            if (!$button.data('original-text')) {
                $button.data('original-text', $button.html());
            }

            $button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan');

            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                dataType: 'json',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                data: $form.serialize() + '&_ajax=1'
            })
                .done(function(response) {
                    var savedName = response && response.data && response.data.name
                        ? response.data.name
                        : $form.find('[name="name"]').val();

                    if (response && response.data) {
                        rememberCustomerPoItem(response.data);
                    }

                    closeCustomerPoModal();
                    loadCustomerPoOptions(savedName);
                    notifyCustomerPo('success', 'Customer PO berhasil disimpan.');
                })
                .fail(function(xhr) {
                    var message = 'Gagal menyimpan Customer PO.';

                    if (xhr.status >= 200 && xhr.status < 300) {
                        closeCustomerPoModal();
                        loadCustomerPoOptions($form.find('[name="name"]').val());
                        notifyCustomerPo('success', 'Customer PO berhasil disimpan.');
                        return;
                    }

                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        $.each(xhr.responseJSON.errors, function(field, messages) {
                            var fieldMessage = messages && messages[0] ? messages[0] : '';

                            if (fieldMessage !== '') {
                                $('.customer-po-error[data-field="' + field + '"]').text(fieldMessage).show();

                                if (firstMessage === '') {
                                    firstMessage = fieldMessage;
                                }
                            }
                        });
                    }

                    if (firstMessage === '' && xhr.responseJSON && xhr.responseJSON.message) {
                        firstMessage = xhr.responseJSON.message;
                    }

                    message = firstMessage || message;
                    notifyCustomerPo('error', message);
                })
                .always(function() {
                    $button.prop('disabled', false).html($button.data('original-text'));
                });
        });
    </script>
    <script>

        //TODO use product's konversiDisplay instead
        function konversiDisplay(qty, konversiQty, satuanBesar, satuan) {
            satuan = satuan || 'PCS';
            qty = parseInt(qty) || 0;
            if (!konversiQty || !satuanBesar) return null;
            var boxes = Math.floor(qty / konversiQty);
            var rem = qty % konversiQty;
            if (rem === 0) return boxes + ' ' + satuanBesar;
            if (boxes > 0) return boxes + ' ' + satuanBesar + ' ' + rem + ' ' + satuan;
            return qty + ' ' + satuan;
        }
        function fmtQtyK(qty, p) {
            if (!p) return qty;
            var k = konversiDisplay(qty, p.konversi_qty, p.satuan_besar, p.satuan);
            return qty + (k ? ' <span class="label label-info">' + k + '</span>' : '');
        }

        function fmtKonversiRatio(p) {
            if (!p || !p.konversi_qty || !p.satuan_besar) {
                return '<span class="text-muted">-</span>';
            }
            var satuanKecil = p.satuan || 'PCS';
            return '1 ' + p.satuan_besar + ' = ' + p.konversi_qty + ' ' + satuanKecil;
        }

        function buildProductRow(index) {
            return `
                <tr>
                    <td>
                        <select required class="form-control select2 product" name="product[${index}][product_id]" data-placeholder="Pilih Product" style="width:100%;">
                            <option value="" disabled selected>Pilih Produk</option>
                        </select>
                    </td>
                    <td>
                        <div class="input-group">
                            <input type="number" required value="1" min="1" class="form-control qty" name="product[${index}][qty]">
                            <span class="input-group-addon satuan-besar-label" style="white-space:nowrap;">-</span>
                        </div>
                        <span class="konversi-display text-muted" style="font-size:11px;"></span>
                    </td>
                    <td><input required type="text" class="form-control harga_beli numeral-mask" name="product[${index}][harga_beli]"></td>
                    <td><input type="text" required class="form-control subtotal" name="product[${index}][subtotal]" readonly></td>
                    <td><button class="btn btn-sm btn-danger" onclick="removeBahanBaku(this)" type="button">Remove</button></td>
                </tr>`;
        }

        function initializeProductRow($row) {
            $row.find('.numeral-mask').mask("#,##0", { reverse: true });
            $row.find('.select2').select2();

            if (currentProducts) {
                populateProductSelects(currentProducts, $row.find('.product'));
            }

            updateSubtotalAndTotal();
        }

        function resetCekBarangModal() {
            $('#checkAll').prop('checked', false);
            if (cekBarangTable) {
                cekBarangTable.destroy();
                cekBarangTable = null;
            }
            $('#cekBarangBody').empty();
        }

        function resetProductRowsForSupplierChange() {
            productIndex = 0;
            $('#product-repeater').html(buildProductRow(0));
            initializeProductRow($('#product-repeater tr:first'));
        }


        function populateProductSelects(products, target = '.product') {
            $(target).each(function() {
                let $select = $(this);
                let currentVal = $select.val(); // preserve selected value if still valid

                $select.empty().append('<option value="" disabled selected>Pilih Produk</option>');
                $.each(products, function(i, product) {
                    // Include stock count if your API returns it; otherwise omit.
                    let stockText = product.stock_count ? ' [' + product.stock_count + ']' : '';
                    $select.append($('<option>', {
                        value: product.id,
                        text: product.code + ' ' + product.name + stockText,
                        'data-serialized': product.is_serialized ? 1 : 0
                    }));
                });

                // Try to reselect previous value if it exists in new options
                if (currentVal && products.some(p => p.id == currentVal)) {
                    $select.val(currentVal);
                }

                // Refresh Select2
                $select.trigger('change.select2');
            });
        }

        // Helper: format number with comma thousands separators for rupiah inputs
        function formatRupiah(angka) {
            return window.formatNumberWithCommas(angka) || '0';
        }

        function addBahanBaku() {
            productIndex++;
            $('#product-repeater').append(buildProductRow(productIndex));
            initializeProductRow($('#product-repeater tr:last'));
        }

        $(document).on('change', '.qty, .harga_beli', function() {
            updateSubtotalAndTotal();
        });

        // Handle serial number input changes
        $(document).on('input', '.serial-numbers', function() {
            let serialText = $(this).val();
            let serialLines = serialText.split('\n').filter(line => line.trim() !== '');
            let qtyInput = $(this).closest('tr').find('.qty');
            let isProductSerialized = $(this).closest('tr').find('.product option:selected').data('serialized');

            if (isProductSerialized) {
                qtyInput.val(serialLines.length);
                updateSubtotalAndTotal();
            }
        });

        function updateSubtotalAndTotal() {
            let total = 0;
            $('#product-repeater tr').each(function() {
                let $row = $(this);
                let qtySatuanBesar = parseInt($row.find('.qty').val()) || 0;
                let konversiQty = parseInt($row.find('.qty').data('konversi-qty')) || 1;
                let qtySatuanKecil = qtySatuanBesar * konversiQty;

                let $hargaInput = $row.find('.harga_beli');
                let harga_beli = ($hargaInput.data('mask') !== undefined)
                    ? ($hargaInput.cleanVal() || 0)
                    : (parseFloat($hargaInput.val()) || 0);

                let subtotal = qtySatuanKecil * harga_beli;
                $row.find('.subtotal').val(formatRupiah(subtotal));
                total += subtotal;
            });
            $('#total').val(formatRupiah(total));
        }

        $('.numeral-mask').mask("#,##0", {
            reverse: true
        });
        updateSubtotalAndTotal();

        function removeBahanBaku(button) {
            if ($('#example tbody tr').length > 1) {
                $(button).closest('tr').remove();
                updateSubtotalAndTotal();
            }
        }

        function updateKonversiDisplay($row) {
            if (!currentProducts) return;
            let productId = $row.find('.product').val();
            let qtySatuanBesar = parseInt($row.find('.qty').val()) || 0;
            let prod = currentProducts.find(function(p) { return p.id == productId; });

            if (!prod) return;

            let konversiQty = prod.konversi_qty || 1;
            let satuanBesar = prod.satuan_besar || prod.satuan || 'PCS';
            let satuan = prod.satuan || 'PCS';
            let qtySatuanKecil = qtySatuanBesar * konversiQty;

            // Update label satuan besar di input group
            $row.find('.satuan-besar-label').text(satuanBesar);

            // Simpan konversi_qty ke data attribute input qty
            $row.find('.qty').data('konversi-qty', konversiQty);

            // Tampilkan info konversi
            $row.find('.konversi-display').html(
                '= ' + qtySatuanKecil.toLocaleString('id-ID') + ' ' + satuan
            );
        }

        $(document).on('change', '.product', function() {
            let $row = $(this).closest('tr');
            let harga_beli = $row.find('.harga_beli');
            let product_id = $(this).val();

            if (!product_id) return;

            let prod = currentProducts ? currentProducts.find(p => p.id == product_id) : null;
            let konversiQty = prod?.konversi_qty || 1;
            let satuanBesar = prod?.satuan_besar || prod?.satuan || 'PCS';

            $row.find('.qty').data('konversi-qty', konversiQty);
            $row.find('.satuan-besar-label').text(satuanBesar);

            updateKonversiDisplay($row);

            $.get('/product/' + product_id, function(data) {
                harga_beli.val(formatRupiah(data.harga_beli)).trigger('input');
                updateSubtotalAndTotal();
            });
        });

        $(document).on('change input', '.qty', function() {
            updateKonversiDisplay($(this).closest('tr'));
        });

        function loadProductsForSupplier(supplierId) {
            currentProducts = [];
            resetCekBarangModal();
            populateProductSelects([]);

            if (!supplierId) {
                return;
            }

            if (supplierRequest) {
                supplierRequest.abort();
                supplierRequest = null;
            }

            supplierRequest = $.get('{{ route("pembelian.all-products") }}', { supplier_id: supplierId })
                .done(function(products) {
                    if (String($('[name="supplier_id"]').val() || '') !== String(supplierId)) {
                        return;
                    }

                    currentProducts = products;
                    populateProductSelects(products);

                    $('.product').each(function() {
                        $(this).trigger('change');
                    });
                })
                .fail(function() {
                    alert('Gagal memuat daftar produk supplier. Silakan refresh halaman.');
                })
                .always(function() {
                    supplierRequest = null;
                });
        }

        function loadPoCodeForSupplier(supplierId, forceApply = false) {
            if (!supplierId) {
                return;
            }

            if (poCodeRequest) {
                poCodeRequest.abort();
                poCodeRequest = null;
            }

            poCodeRequest = $.get('{{ url('/pembelian/next-code') }}/' + supplierId)
                .done(function(response) {
                    if (String($('[name="supplier_id"]').val() || '') !== String(supplierId)) {
                        return;
                    }

                    currentSuggestedPoCode = response.code || '';

                    if (forceApply || !poCodeManuallyEdited || !$('[name="code"]').val()) {
                        $('[name="code"]').val(currentSuggestedPoCode);
                        poCodeManuallyEdited = false;
                    }
                })
                .always(function() {
                    poCodeRequest = null;
                });
        }

        // Handle product change on page load for existing rows
        $(document).ready(function() {
            loadProductsForSupplier(selectedSupplierId);
            if (selectedSupplierId && !poCodeManuallyEdited) {
                loadPoCodeForSupplier(selectedSupplierId, true);
            }

            $('.harga_beli').each(function() {
                $(this).trigger('input');
            });
        });

        $('[name="code"]').on('input', function() {
            poCodeManuallyEdited = $(this).val() !== currentSuggestedPoCode;
        });

        $('[name="supplier_id"]').on('change', function() {
            var nextSupplierId = $(this).val() || null;

            if (String(selectedSupplierId || '') !== String(nextSupplierId || '')) {
                currentProducts = [];
                resetProductRowsForSupplierChange();
            }

            selectedSupplierId = nextSupplierId;
            loadProductsForSupplier(selectedSupplierId);
            loadPoCodeForSupplier(selectedSupplierId, true);
        });

        $('#kas').prop('disabled', true);
        $('#outlet').on('change', function() {
            let outlet_id = $(this).val();
            $.get('/outlet/' + outlet_id + '/kas', function(data) {
                $('#kas').find('option').remove();
                let defaultOption = $('<option>').val('').text('Pilih Kas').prop('disabled', true).prop(
                    'selected', true);
                $('#kas').append(defaultOption);
                data.forEach(function(kas) {
                    let option = $('<option>').val(kas.id).text(kas.name);
                    $('#kas').append(option);
                });
                $('#kas').trigger('change.select2');
            });
            $('#kas').prop('disabled', false);
        });

        // ---- Cek Barang Modal ----
        let cekBarangTable = null;

        $('#modalCekBarang').on('show.bs.modal', function (e) {
            if (!$('[name="supplier_id"]').val()) {
                e.preventDefault();
                alert('Pilih supplier terlebih dahulu.');
                return;
            }

            if (!currentProducts || currentProducts.length === 0) {
                e.preventDefault();
                alert('Produk supplier belum tersedia. Coba pilih supplier atau muat ulang halaman.');
                return;
            }

            const sorted = [...currentProducts].sort((a, b) => {
                const aUnder = a.is_under_minimum ? 0 : 1;
                const bUnder = b.is_under_minimum ? 0 : 1;
                if (aUnder !== bUnder) return aUnder - bUnder;
                return a.stock_count - b.stock_count;
            });

            const tbody = $('#cekBarangBody');
            tbody.empty();

            sorted.forEach(function (p) {
                const isUnder = p.is_under_minimum;
                const suggestedQty = Math.max(1, (p.effective_min || p.min_stock || 0) - (p.stock_count || 0));

                const $tr = $('<tr>').addClass(isUnder ? 'danger' : '');

                const $checkTd = $('<td>').addClass('text-center').append(
                    $('<input>').attr({ type: 'checkbox', class: 'cek-product-check', value: p.id })
                        .data('name', p.name).data('harga', p.harga_beli || 0)
                );
                const $statusBadge = $('<span>').addClass('label')
                    .addClass(isUnder ? 'label-danger' : 'label-success')
                    .text(isUnder ? 'OUT OF STOCK' : 'Normal');
                const $qtyInput = $('<input>').attr({ type: 'number', class: 'form-control input-sm cek-qty', min: 1 })
                    .css('width', '70px').val(isUnder ? suggestedQty : 1);

                $tr.append(
                    $checkTd,
                    $('<td>').text(p.code),
                    $('<td>').text(p.name),
                    $('<td>').addClass('text-center').html(fmtQtyK(p.stock_count || 0, p)),
                    $('<td>').addClass('text-center').html(fmtQtyK(p.effective_min || p.min_stock || 0, p)),
                    $('<td>').addClass('text-center').html(fmtKonversiRatio(p)),
                    $('<td>').addClass('text-center').append($statusBadge),
                    $('<td>').append($qtyInput)
                );

                tbody.append($tr);
            });

            if (cekBarangTable) {
                cekBarangTable.destroy();
            }
            cekBarangTable = $('#tableCekBarang').DataTable({
                retrieve: false,
                destroy: true,
                pageLength: 10,
                order: [],
                columnDefs: [
                    { orderable: false, targets: [0, 7] }
                ],
                language: {
                    search: "Cari:",
                    lengthMenu: "Tampilkan _MENU_ baris",
                    info: "Menampilkan _START_-_END_ dari _TOTAL_ produk",
                    paginate: { previous: "Prev", next: "Next" },
                    zeroRecords: "Tidak ada produk ditemukan"
                }
            });
        });

        $(document).on('change', '#checkAll', function () {
            const checked = $(this).prop('checked');
            // Check/uncheck all rows (including non-visible DataTable pages)
            if (cekBarangTable) {
                cekBarangTable.rows().nodes().each(function (node) {
                    $(node).find('.cek-product-check').prop('checked', checked);
                });
            } else {
                $('.cek-product-check').prop('checked', checked);
            }
        });

        $('#btnTambahkanPO').on('click', function () {
            const selected = [];
            if (cekBarangTable) {
                cekBarangTable.rows().nodes().each(function (node) {
                    const $check = $(node).find('.cek-product-check:checked');
                    if ($check.length) {
                        const $row = $(node);
                        selected.push({
                            product_id: $check.val(),
                            name: $check.data('name'),
                            harga: $check.data('harga'),
                            qty: parseInt($row.find('.cek-qty').val()) || 1
                        });
                    }
                });
            } else {
                $('#cekBarangBody .cek-product-check:checked').each(function () {
                    const $row = $(this).closest('tr');
                    selected.push({
                        product_id: $(this).val(),
                        name: $(this).data('name'),
                        harga: $(this).data('harga'),
                        qty: parseInt($row.find('.cek-qty').val()) || 1
                    });
                });
            }

            if (selected.length === 0) {
                alert('Pilih minimal satu produk.');
                return;
            }

            // Remove the first empty row if it has no product selected
            const $firstRow = $('#product-repeater tr:first');
            if ($firstRow.find('.product').val() === null || $firstRow.find('.product').val() === '') {
                $firstRow.remove();
            }

            selected.forEach(function (item) {
                addBahanBaku();

                const $newRow = $('#product-repeater tr:last');
                const $productSelect = $newRow.find('.product');
                const $hargaInput = $newRow.find('.harga_beli');
                const $qtyInput = $newRow.find('.qty');

                // Set product selection without firing the change handler
                // (which would make an AJAX call before options are ready)
                $productSelect.val(item.product_id).trigger('change.select2');

                // Populate harga_beli directly from cached data to avoid /product/null
                $hargaInput.val(formatRupiah(item.harga)).trigger('input');

                // Set qty
                $qtyInput.val(item.qty);

                updateSubtotalAndTotal();
            });

            $('#modalCekBarang').modal('hide');
        });
    </script>
@endsection
