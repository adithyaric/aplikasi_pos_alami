<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script>
    var products = @json($products);
    var oldItems = @json($initialItems);
    var rowIndex = 0;

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

    function selectedBuyerPayload() {
        var buyerType = $('#buyer_type').val();
        var buyerId = buyerType === 'agent'
            ? $('#agent_id').val()
            : buyerType === 'canvas'
                ? $('#canvas_id').val()
                : $('#outlet_target_id').val();

        return {
            buyer_type: buyerType,
            buyer_id: buyerId
        };
    }

    function buildProductOptions(selectedValue) {
        var html = '<option value="">Pilih Produk</option>';

        products.forEach(function(product) {
            var selected = String(selectedValue || '') === String(product.id) ? 'selected' : '';
            var label = (product.code ? product.code + ' - ' : '') + product.name + ' [' + product.stock_summary + ']';

            html += '<option value="' + product.id + '" ' + selected + '>' + escapeHtml(label) + '</option>';
        });

        return html;
    }

    function buildUnitOptions(product, selectedUnit) {
        if (!product) {
            return '<option value="">Pilih Satuan</option>';
        }

        return product.units.map(function(unit) {
            var selected = String(selectedUnit || product.default_unit) === String(unit.value) ? 'selected' : '';
            return '<option value="' + escapeHtml(unit.value) + '" ' + selected + '>' + escapeHtml(unit.label) + '</option>';
        }).join('');
    }

    function initializeProductSelect($select) {
        $select.select2({
            width: '100%',
            placeholder: 'Pilih Produk',
            allowClear: true
        });
    }

    function addRow(item) {
        var currentIndex = rowIndex++;
        var selectedProduct = findProduct(item.product_id);
        var priceValue = item.price ? formatMoney(item.price) : (selectedProduct ? formatMoney(selectedProduct.harga_jual) : '0');
        var qtyValue = item.qty || 1;

        var html = '' +
            '<tr data-index="' + currentIndex + '">' +
            '  <td>' +
            '    <select class="form-control select2 item-product" name="items[' + currentIndex + '][product_id]" data-placeholder="Pilih Produk" required style="width:100%">' +
                    buildProductOptions(item.product_id) +
            '    </select>' +
            '    <div class="text-danger small row-error-product"></div>' +
            '  </td>' +
            '  <td class="item-stock text-muted">' + (selectedProduct ? selectedProduct.stock_summary : '-') + '</td>' +
            '  <td>' +
            '    <select class="form-control item-unit" name="items[' + currentIndex + '][unit]" required>' +
                    buildUnitOptions(selectedProduct, item.unit) +
            '    </select>' +
            '  </td>' +
            '  <td>' +
            '    <input type="number" min="1" step="1" class="form-control item-qty" name="items[' + currentIndex + '][qty]" value="' + qtyValue + '" required>' +
            '  </td>' +
            '  <td>' +
            '    <input type="text" class="form-control numeral-mask item-price" name="items[' + currentIndex + '][price]" value="' + priceValue + '" required>' +
            '  </td>' +
            '  <td class="item-subtotal">0</td>' +
            '  <td class="text-center">' +
            '    <button type="button" class="btn btn-danger btn-sm btn-remove-row"><i class="fa fa-trash"></i></button>' +
            '  </td>' +
            '</tr>';

        $('#items-body').append(html);

        var $row = $('#items-body tr').last();
        initializeProductSelect($row.find('.item-product'));
        $row.data('price-source', item.price ? 'manual' : 'default');
        moneyMask();
        recalcRow($row);
    }

    function recalcRow($row) {
        var qty = parseFloat($row.find('.item-qty').val()) || 0;
        var price = parseMoney($row.find('.item-price').val());
        $row.find('.item-subtotal').text(formatMoney(Math.round(qty * price)));
        recalcTotals();
    }

    function recalcTotals() {
        var subtotal = 0;

        $('#items-body tr').each(function() {
            subtotal += parseMoney($(this).find('.item-subtotal').text());
        });

        var discount = parseMoney($('#discount').val());
        $('#subtotal_display').val(formatMoney(subtotal));
        $('#grand_total_display').val(formatMoney(Math.max(0, subtotal - discount)));
    }

    function applySuggestedPrice($row, forceOverride) {
        var productId = $row.find('.item-product').val();
        var product = findProduct(productId);
        var buyer = selectedBuyerPayload();

        if (!product) {
            $row.find('.item-price').val('0');
            $row.data('price-source', 'default');
            recalcRow($row);
            return;
        }

        if (!buyer.buyer_type || !buyer.buyer_id) {
            if (forceOverride || ($row.data('price-source') || 'default') !== 'manual') {
                $row.find('.item-price').val(formatMoney(product.harga_jual));
                $row.data('price-source', 'default');
                moneyMask();
                recalcRow($row);
            }
            return;
        }

        $.get('{{ route('penjualan.last-price') }}', {
            buyer_type: buyer.buyer_type,
            buyer_id: buyer.buyer_id,
            product_id: productId
        }).done(function(response) {
            if (!forceOverride && ($row.data('price-source') || 'default') === 'manual') {
                return;
            }

            var resolvedPrice = response.price !== null && response.price !== undefined
                ? response.price
                : product.harga_jual;

            $row.find('.item-price').val(formatMoney(resolvedPrice));
            $row.data('price-source', response.price !== null && response.price !== undefined ? 'history' : 'default');
            moneyMask();
            recalcRow($row);
        }).fail(function() {
            if (forceOverride || ($row.data('price-source') || 'default') !== 'manual') {
                $row.find('.item-price').val(formatMoney(product.harga_jual));
                $row.data('price-source', 'default');
                moneyMask();
                recalcRow($row);
            }
        });
    }

    function selectedBuyerTermDays() {
        var buyerType = $('#buyer_type').val();
        var selector = buyerType === 'agent'
            ? '#agent_id'
            : buyerType === 'canvas'
                ? '#canvas_id'
                : '#outlet_target_id';
        var option = $(selector + ' option:selected');

        return parseInt(option.data('termin-days'), 10) || 0;
    }

    function updateDueDate() {
        var paymentType = $('#payment_type').val();
        var saleDate = $('input[name="sale_date"]').val();

        if (!paymentType || !saleDate) {
            return;
        }

        if (paymentType === 'cash') {
            $('#payment_status').val('paid').prop('disabled', true);
            $('#due_date').val('').prop('disabled', true);
            return;
        }

        $('#payment_status').prop('disabled', false);
        $('#due_date').prop('disabled', false);

        if (!$('#due_date').val()) {
            var dueDate = new Date(saleDate + 'T00:00:00');
            dueDate.setDate(dueDate.getDate() + selectedBuyerTermDays());
            $('#due_date').val(dueDate.toISOString().slice(0, 10));
        }
    }

    function updateBuyerFields() {
        var buyerType = $('#buyer_type').val();

        $('.buyer-select').hide().find('select').prop('disabled', true);

        if (buyerType) {
            $('.buyer-' + buyerType).show().find('select').prop('disabled', false);
        }

        updateDueDate();
    }

    $(document).on('change', '.item-product', function() {
        var $row = $(this).closest('tr');
        var product = findProduct($(this).val());
        var $unit = $row.find('.item-unit');

        $unit.html(buildUnitOptions(product, product ? product.default_unit : ''));
        $row.find('.item-stock').text(product ? product.stock_summary : '-');
        $row.data('price-source', 'default');
        applySuggestedPrice($row, true);
    });

    $(document).on('input change', '.item-qty, .item-price, #discount', function() {
        if ($(this).hasClass('item-price') && $(this).is(':focus')) {
            $(this).closest('tr').data('price-source', 'manual');
        }

        var $row = $(this).closest('tr');
        if ($row.length) {
            recalcRow($row);
        } else {
            recalcTotals();
        }
    });

    $(document).on('click', '.btn-remove-row', function() {
        if ($('#items-body tr').length === 1) {
            return;
        }

        var $row = $(this).closest('tr');
        $row.find('.item-product').select2('destroy');
        $row.remove();
        recalcTotals();
    });

    $('#add-row').on('click', function() {
        addRow({
            product_id: '',
            qty: 1,
            unit: '',
            price: '',
        });
    });

    $('#buyer_type, #agent_id, #canvas_id, #outlet_target_id, #payment_type, input[name="sale_date"]').on('change', function() {
        if (this.id === 'buyer_type') {
            updateBuyerFields();
        } else {
            updateDueDate();
        }

        if (this.id === 'buyer_type' || this.id === 'agent_id' || this.id === 'canvas_id' || this.id === 'outlet_target_id') {
            $('#items-body tr').each(function() {
                applySuggestedPrice($(this), false);
            });
        }
    });

    $('#warehouse-sale-form').on('submit', function() {
        $('#payment_status').prop('disabled', false);
        $('#due_date').prop('disabled', false);
    });

    $(function() {
        moneyMask();
        updateBuyerFields();

        if (oldItems.length) {
            oldItems.forEach(function(item) {
                addRow(item);
            });
        } else {
            addRow({
                product_id: '',
                qty: 1,
                unit: '',
                price: '',
            });
        }
    });
</script>
