<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script>
    var products = @json($products);
    var initialItems = @json($initialItems);
    var rowIndex = 0;
    var latestInvoice = null;
    var isBranchScoped = @json($isBranchScoped);

    function moneyMask() {
        $('.numeral-mask').mask('#,##0', { reverse: true });
    }

    function parseMoney(value) {
        return parseInt(String(value || '0').replace(/[^\d]/g, ''), 10) || 0;
    }

    function formatMoney(value) {
        return (parseInt(value, 10) || 0).toLocaleString('id-ID');
    }

    function escapeHtml(value) {
        return $('<div>').text(value || '').html();
    }

    function findProduct(productId) {
        return products.find(function(product) {
            return String(product.id) === String(productId);
        });
    }

    function normalizeUnitKey(unit) {
        return String(unit || '').trim().toLowerCase();
    }

    function unitFactorFor(product, unit) {
        if (!product) return 1;
        return parseInt((product.unit_factors || {})[normalizeUnitKey(unit)] || 1, 10) || 1;
    }

    function normalizedQtyFor(product, qty, unit) {
        return Math.round((parseFloat(qty) || 0) * unitFactorFor(product, unit));
    }

    function selectedBuyerPayload() {
        return {
            return_scope: $('#return_scope').val(),
            buyer_type: $('#buyer_type').val(),
            buyer_id: $('#buyer_id').val()
        };
    }

    function buildProductOptions(selectedValue) {
        var html = '<option value="">Pilih Produk</option>';
        products.forEach(function(product) {
            var selected = String(selectedValue || '') === String(product.id) ? 'selected' : '';
            var label = (product.code ? product.code + ' - ' : '') + product.name;
            html += '<option value="' + product.id + '" ' + selected + '>' + escapeHtml(label) + '</option>';
        });
        return html;
    }

    function buildUnitOptions(product, selectedUnit) {
        if (!product) return '<option value="">Pilih Satuan</option>';
        return (product.units || []).map(function(unit) {
            var selected = String(selectedUnit || product.default_unit) === String(unit.value) ? 'selected' : '';
            return '<option value="' + escapeHtml(unit.value) + '" ' + selected + '>' + escapeHtml(unit.label) + '</option>';
        }).join('');
    }

    function addRow(item) {
        var currentIndex = rowIndex++;
        var selectedProduct = findProduct(item.product_id);
        var priceValue = item.price ? formatMoney(item.price) : (selectedProduct ? formatMoney(selectedProduct.harga_jual) : '0');
        var qtyValue = item.qty || 1;
        var unitValue = item.unit || (selectedProduct ? selectedProduct.default_unit : '');

        var html = '' +
            '<tr data-index="' + currentIndex + '">' +
            '  <td><select class="form-control select2 item-product" name="product[' + currentIndex + '][product_id]" required style="width:100%">' + buildProductOptions(item.product_id) + '</select></td>' +
            '  <td><select class="form-control item-unit" name="product[' + currentIndex + '][unit]" required>' + buildUnitOptions(selectedProduct, unitValue) + '</select></td>' +
            '  <td><input type="number" min="1" step="1" class="form-control item-qty" name="product[' + currentIndex + '][qty]" value="' + escapeHtml(qtyValue) + '" required></td>' +
            '  <td><input type="text" class="form-control numeral-mask item-price" name="product[' + currentIndex + '][price]" value="' + escapeHtml(priceValue) + '" required></td>' +
            '  <td class="item-subtotal">0</td>' +
            '  <td><input type="text" class="form-control" name="product[' + currentIndex + '][alasan]" value="' + escapeHtml(item.alasan || '') + '" placeholder="Alasan retur"></td>' +
            '  <td class="text-center"><button type="button" class="btn btn-danger btn-sm btn-remove-row"><i class="fa fa-trash"></i></button></td>' +
            '</tr>';

        $('#refund-items-body').append(html);
        var $row = $('#refund-items-body tr').last();
        $row.find('.item-product').select2({ width: '100%', placeholder: 'Pilih Produk', allowClear: true });
        moneyMask();
        recalcRow($row);

        if (!item.price && selectedProduct) {
            applyLastPrice($row);
        }
    }

    function recalcRow($row) {
        var product = findProduct($row.find('.item-product').val());
        var qty = parseFloat($row.find('.item-qty').val()) || 0;
        var unit = $row.find('.item-unit').val();
        var normalizedQty = normalizedQtyFor(product, qty, unit);
        var price = parseMoney($row.find('.item-price').val());
        $row.find('.item-subtotal').text(formatMoney(Math.round(normalizedQty * price)));
        recalcTotal();
    }

    function recalcTotal() {
        var total = 0;
        $('#refund-items-body tr').each(function() {
            total += parseMoney($(this).find('.item-subtotal').text());
        });
        $('#return-total-display').text(formatMoney(total));
        renderInvoicePreview();
    }

    function updateBuyerState() {
        var buyerType = $('#buyer_type').val();
        if (!isBranchScoped) {
            $('.buyer-block').hide();
            if (buyerType) {
                $('.buyer-' + buyerType).show();
            }
        }

        var selected = $('.buyer-select:visible').val();
        if (isBranchScoped) {
            selected = $('#shop_buyer_id').val();
        }

        $('#buyer_id').val(selected || '');
        $('#return_scope').val(isBranchScoped ? 'branch_customer_return' : (buyerType === 'outlet' ? 'warehouse_branch_return' : 'warehouse_affiliate_return'));
        loadInvoicePreview();
    }

    function renderInvoicePreview() {
        var $preview = $('#invoice-preview');
        var currentTotal = parseMoney($('#return-total-display').text());

        if (!$('#buyer_id').val()) {
            $preview.hide();
            return;
        }

        if (!latestInvoice) {
            $preview.removeClass('alert-info alert-danger').addClass('alert-warning')
                .html('Belum ada invoice unpaid terbaru untuk pembeli ini.')
                .show();
            return;
        }

        var exceeds = currentTotal > Number(latestInvoice.max_return_total || 0);
        $preview.removeClass('alert-info alert-warning alert-danger')
            .addClass(exceeds ? 'alert-danger' : 'alert-info')
            .html(
                'Invoice yang akan dipotong: <strong>' + latestInvoice.code + '</strong>' +
                ' | Total invoice: <strong>Rp ' + formatMoney(latestInvoice.total) + '</strong>' +
                ' | Maks retur: <strong>Rp ' + formatMoney(latestInvoice.max_return_total) + '</strong>' +
                (exceeds ? '<br>Total retur melebihi batas dan akan ditolak saat disimpan.' : '')
            )
            .show();
    }

    function loadInvoicePreview() {
        var payload = selectedBuyerPayload();
        latestInvoice = null;

        if (!payload.buyer_type || !payload.buyer_id) {
            renderInvoicePreview();
            return;
        }

        $.get('{{ route('refund.latest-invoice') }}', payload)
            .done(function(response) {
                latestInvoice = response.invoice || null;
                renderInvoicePreview();
            })
            .fail(function() {
                latestInvoice = null;
                renderInvoicePreview();
            });
    }

    function applyLastPrice($row) {
        var product = findProduct($row.find('.item-product').val());
        var payload = selectedBuyerPayload();

        if (!product || !payload.buyer_type || !payload.buyer_id) {
            return;
        }

        $.get('{{ route('refund.last-price') }}', $.extend({}, payload, { product_id: product.id }))
            .done(function(response) {
                var price = response.price || product.harga_jual || 0;
                $row.find('.item-price').val(formatMoney(price));
                moneyMask();
                recalcRow($row);
            });
    }

    $(document).on('change', '#buyer_type, .buyer-select', updateBuyerState);

    $(document).on('change', '.item-product', function() {
        var $row = $(this).closest('tr');
        var product = findProduct($(this).val());
        $row.find('.item-unit').html(buildUnitOptions(product, product ? product.default_unit : ''));
        $row.find('.item-price').val(product ? formatMoney(product.harga_jual) : '0');
        moneyMask();
        applyLastPrice($row);
        recalcRow($row);
    });

    $(document).on('input change', '.item-qty, .item-unit, .item-price', function() {
        recalcRow($(this).closest('tr'));
    });

    $(document).on('click', '.btn-remove-row', function() {
        if ($('#refund-items-body tr').length === 1) return;
        $(this).closest('tr').remove();
        recalcTotal();
    });

    $('#add-row').on('click', function() {
        addRow({});
    });

    $(function() {
        moneyMask();
        $('.select2').select2({ width: '100%' });
        updateBuyerState();

        if (initialItems.length) {
            initialItems.forEach(function(item) {
                addRow(item);
            });
        } else {
            addRow({});
        }
    });
</script>
