<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script>
    var products = @json($products);
    var oldItems = @json($initialItems);
    var rowIndex = 0;
    var productChecklistTable = null;

    function moneyMask() {
        $('.numeral-mask').mask('#,##0', { reverse: true });
    }

    function parseMoney(value) {
        return parseInt(String(value || '0').replace(/[^\d]/g, ''), 10) || 0;
    }

    function formatMoney(value) {
        return (parseInt(value, 10) || 0).toLocaleString('id-ID');
    }

    function recalcDebtPreview() {
        var $oldDebt = $('#old_debt_override');
        var oldDebt = String($oldDebt.val() || '').trim() !== ''
            ? parseMoney($oldDebt.val())
            : parseMoney($oldDebt.data('auto-value'));
        var shippingCost = parseMoney($('#shipping_cost').val());
        var total = parseMoney($('#grand_total_display').val());
        var payment = parseMoney($('#payment_display').val());

        if (($('#payment_type').val() || '') === 'cash') {
            payment = total;
            $('#payment_display').val(formatMoney(payment));
        }

        $('#new_debt_display').val(formatMoney(Math.max(0, oldDebt + shippingCost + total - payment)));
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
        if (!product) {
            return 1;
        }

        var factors = product.unit_factors || {};
        return parseInt(factors[normalizeUnitKey(unit)] || 1, 10) || 1;
    }

    function baseUnitLabel(product) {
        return product && product.base_unit ? product.base_unit : 'PCS';
    }

    function formatQty(value) {
        var normalizedValue = Number(value || 0);

        return normalizedValue.toLocaleString('id-ID', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        });
    }

    function normalizedQtyFor(product, qty, unit) {
        return Math.round((parseFloat(qty) || 0) * unitFactorFor(product, unit));
    }

    function updateConversionHint($row, product, qty, unit) {
        var hint = '';

        if (product && unit) {
            var normalizedQty = normalizedQtyFor(product, qty, unit);
            hint = '= ' + formatQty(normalizedQty) + ' ' + baseUnitLabel(product);
        }

        $row.find('.item-unit-help').text(hint);
    }

    function defaultUnitLabel(product) {
        if (!product) {
            return '-';
        }

        var selected = (product.units || []).find(function(unit) {
            return String(unit.value) === String(product.default_unit);
        });

        return selected ? selected.label : (product.default_unit || '-');
    }

    function selectedProductIds() {
        var ids = {};

        $('#items-body .item-product').each(function() {
            var value = $(this).val();
            if (value) {
                ids[String(value)] = true;
            }
        });

        return ids;
    }

    function findExistingRowByProduct(productId) {
        return $('#items-body tr').filter(function() {
            return String($(this).find('.item-product').val() || '') === String(productId);
        }).first();
    }

    function resetChecklistModal() {
        $('#checkAllPenjualan').prop('checked', false);

        if (productChecklistTable) {
            productChecklistTable.destroy();
            productChecklistTable = null;
        }

        $('#cekBarangPenjualanBody').empty();
    }

    function ensureEmptyRowRemoved() {
        var $rows = $('#items-body tr');
        if ($rows.length !== 1) {
            return;
        }

        var $firstRow = $rows.first();
        if (!($firstRow.find('.item-product').val() || '')) {
            $firstRow.find('.item-product').select2('destroy');
            $firstRow.remove();
        }
    }

    function selectedBuyerPayload() {
        var buyerType = $('#buyer_type').val();
        var buyerId = buyerType === 'agent'
            ? $('#agent_id').val()
            : buyerType === 'canvas'
                ? $('#canvas_id').val()
                : buyerType === 'toko'
                    ? $('#toko_id').val()
                    : $('#outlet_target_id').val();

        return {
            buyer_type: buyerType,
            buyer_id: buyerId
        };
    }

    function refreshOldDebtPreview() {
        var buyer = selectedBuyerPayload();
        var $oldDebt = $('#old_debt_override');

        if (!buyer.buyer_type || !buyer.buyer_id) {
            $oldDebt.data('auto-value', '0');
            recalcDebtPreview();
            return;
        }

        $.get('{{ route('penjualan.old-debt') }}', {
            buyer_type: buyer.buyer_type,
            buyer_id: buyer.buyer_id,
            sale_date: $('input[name="sale_date"]').val(),
            exclude_id: $('#warehouse-sale-form').data('penjualan-id') || null,
        }).done(function(response) {
            $oldDebt.data('auto-value', formatMoney(response.old_debt || 0));
            recalcDebtPreview();
        });
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
        var discountValue = item.discount ? formatMoney(item.discount) : '0';
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
            '    <div class="text-muted small item-unit-help"></div>' +
            '  </td>' +
            '  <td>' +
            '    <input type="number" min="1" step="1" class="form-control item-qty" name="items[' + currentIndex + '][qty]" value="' + qtyValue + '" required>' +
            '  </td>' +
            '  <td>' +
            '    <input type="text" class="form-control numeral-mask item-discount" name="items[' + currentIndex + '][discount]" value="' + discountValue + '">' +
            '    <div class="text-muted small">Nominal per baris</div>' +
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

        return $row;
    }

    function recalcRow($row) {
        var product = findProduct($row.find('.item-product').val());
        var qty = parseFloat($row.find('.item-qty').val()) || 0;
        var unit = $row.find('.item-unit').val();
        var normalizedQty = normalizedQtyFor(product, qty, unit);
        var price = parseMoney($row.find('.item-price').val());
        var discount = parseMoney($row.find('.item-discount').val());
        var lineGrossSubtotal = Math.round(normalizedQty * price);
        updateConversionHint($row, product, qty, unit);
        $row.find('.item-subtotal').text(formatMoney(Math.max(0, lineGrossSubtotal - discount)));
        recalcTotals();
    }

    function recalcTotals() {
        var subtotal = 0;
        var discount = 0;
        var total = 0;

        $('#items-body tr').each(function() {
            var $row = $(this);
            var product = findProduct($row.find('.item-product').val());
            var qty = parseFloat($row.find('.item-qty').val()) || 0;
            var unit = $row.find('.item-unit').val();
            var normalizedQty = normalizedQtyFor(product, qty, unit);
            var price = parseMoney($row.find('.item-price').val());
            var itemDiscount = parseMoney($row.find('.item-discount').val());

            subtotal += Math.round(normalizedQty * price);
            discount += itemDiscount;
            total += parseMoney($row.find('.item-subtotal').text());
        });

        $('#subtotal_display').val(formatMoney(subtotal));
        $('#discount_display').val(formatMoney(discount));
        $('#grand_total_display').val(formatMoney(Math.max(0, total)));
        recalcDebtPreview();
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

    function updatePaymentStatusField() {
        var paymentType = $('#payment_type').val() || 'termin';

        $('#payment_type').val(paymentType);

        if (!$('#payment_status').val()) {
            $('#payment_status').val(paymentType === 'cash' ? 'paid' : 'unpaid');
        }
    }

    function updateBuyerFields() {
        var buyerType = $('#buyer_type').val();

        $('.buyer-select').hide().find('select').prop('disabled', true);

        if (buyerType) {
            $('.buyer-' + buyerType).show().find('select').prop('disabled', false);
        }

        updatePaymentStatusField();
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

    $(document).on('input change', '.item-qty, .item-discount, .item-price, .item-unit', function() {
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

    $(document).on('input change', '#old_debt_override, #shipping_cost', recalcDebtPreview);

    $(document).on('click', '.btn-remove-row', function() {
        if ($('#items-body tr').length === 1) {
            return;
        }

        var $row = $(this).closest('tr');
        $row.find('.item-product').select2('destroy');
        $row.remove();
        recalcTotals();
    });

    $('#modalCekBarangPenjualan').on('show.bs.modal', function() {
        resetChecklistModal();

        if (!products.length) {
            alert('Belum ada produk dengan stok tersedia.');
            return false;
        }

        var takenProductIds = selectedProductIds();
        var sortedProducts = products.slice().sort(function(a, b) {
            return String(a.name || '').localeCompare(String(b.name || ''), 'id');
        });
        var $tbody = $('#cekBarangPenjualanBody');

        sortedProducts.forEach(function(product) {
            var alreadySelected = !!takenProductIds[String(product.id)];
            var $row = $('<tr>');
            var $checkbox = $('<input>', {
                type: 'checkbox',
                class: 'cek-product-penjualan',
                value: product.id,
                disabled: alreadySelected
            }).data('qty', 1);
            var $qtyInput = $('<input>', {
                type: 'number',
                min: 1,
                value: 1,
                class: 'form-control input-sm cek-qty-penjualan',
                disabled: alreadySelected
            }).css('width', '70px');
            var $statusBadge = $('<span>')
                .addClass('label ' + (alreadySelected ? 'label-default' : 'label-success'))
                .text(alreadySelected ? 'Sudah dipilih' : 'Siap dipilih');

            $row.append(
                $('<td>').addClass('text-center').append($checkbox),
                $('<td>').text(product.code || '-'),
                $('<td>').text(product.name || '-'),
                $('<td>').text(product.stock_summary || '-'),
                $('<td>').text(defaultUnitLabel(product)),
                $('<td>').html(formatMoney(product.harga_jual || 0) + ' / ' + escapeHtml(baseUnitLabel(product))),
                $('<td>').addClass('text-center').append($statusBadge),
                $('<td>').append($qtyInput)
            );

            $tbody.append($row);
        });

        if ($.fn.DataTable) {
            productChecklistTable = $('#tableCekBarangPenjualan').DataTable({
                retrieve: false,
                destroy: true,
                pageLength: 10,
                order: [],
                columnDefs: [
                    { orderable: false, targets: [0, 6, 7] }
                ],
                language: {
                    search: 'Cari:',
                    lengthMenu: 'Tampilkan _MENU_ baris',
                    info: 'Menampilkan _START_-_END_ dari _TOTAL_ produk',
                    paginate: { previous: 'Prev', next: 'Next' },
                    zeroRecords: 'Tidak ada produk ditemukan'
                }
            });
        }
    });

    $(document).on('change', '#checkAllPenjualan', function() {
        var checked = $(this).prop('checked');
        var $checkboxes = productChecklistTable
            ? $(productChecklistTable.rows().nodes()).find('.cek-product-penjualan:not(:disabled)')
            : $('#cekBarangPenjualanBody .cek-product-penjualan:not(:disabled)');

        $checkboxes.prop('checked', checked);
    });

    $('#btnTambahkanPenjualan').on('click', function() {
        var selected = [];
        var $rows = productChecklistTable
            ? $(productChecklistTable.rows().nodes())
            : $('#cekBarangPenjualanBody tr');

        $rows.each(function() {
            var $row = $(this);
            var $checkbox = $row.find('.cek-product-penjualan:checked');

            if (!$checkbox.length) {
                return;
            }

            var product = findProduct($checkbox.val());
            if (!product || findExistingRowByProduct(product.id).length) {
                return;
            }

            selected.push({
                product_id: product.id,
                qty: parseInt($row.find('.cek-qty-penjualan').val(), 10) || 1,
                unit: product.default_unit,
            });
        });

        if (!selected.length) {
            alert('Pilih minimal satu produk yang belum ada di tabel.');
            return;
        }

        ensureEmptyRowRemoved();

        selected.forEach(function(item) {
            var $row = addRow({
                product_id: item.product_id,
                qty: item.qty,
                unit: item.unit,
                price: '',
            });

            applySuggestedPrice($row, true);
        });

        $('#modalCekBarangPenjualan').modal('hide');
    });

    $('#modalCekBarangPenjualan').on('hidden.bs.modal', function() {
        resetChecklistModal();
    });

    $('#add-row').on('click', function() {
        addRow({
            product_id: '',
            qty: 1,
            unit: '',
            price: '',
        });
    });

    $('#buyer_type, #agent_id, #canvas_id, #outlet_target_id, #toko_id').on('change', function() {
        if (this.id === 'buyer_type') {
            updateBuyerFields();
        }

        if (this.id === 'buyer_type' || this.id === 'agent_id' || this.id === 'canvas_id' || this.id === 'outlet_target_id' || this.id === 'toko_id') {
            $('#items-body tr').each(function() {
                applySuggestedPrice($(this), false);
            });

            refreshOldDebtPreview();
        }
    });

    $('input[name="sale_date"]').on('change', refreshOldDebtPreview);

    $(function() {
        moneyMask();
        updateBuyerFields();
        refreshOldDebtPreview();

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
