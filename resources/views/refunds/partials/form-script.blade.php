<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script>
    var penjualans = @json($penjualans);
    var initialItems = @json($initialItems);
    var selectedPenjualanId = @json($selectedPenjualanId);
    var rowIndex = 0;

    function findPenjualan(penjualanId) {
        return penjualans.find(function(penjualan) {
            return String(penjualan.id) === String(penjualanId || '');
        });
    }

    function escapeHtml(value) {
        return $('<div>').text(value || '').html();
    }

    function moneyMask() {
        $('.numeral-mask').mask('#,##0', { reverse: true });
    }

    function updateSaleInfo(penjualan) {
        $('#sale_channel_info').val(penjualan ? penjualan.sale_channel_label : '-');
        $('#buyer_type_info').val(penjualan ? penjualan.buyer_type_label : '-');
        $('#buyer_name_info').val(penjualan ? penjualan.buyer_display_name : '-');
    }

    function buildRowsFromSale(penjualan) {
        if (!penjualan) {
            return [];
        }

        return (penjualan.items || [])
            .filter(function(item) {
                return (parseInt(item.qty_remaining, 10) || 0) > 0;
            })
            .map(function(item) {
                return {
                    product_id: item.product_id,
                    product_name: item.product_name,
                    qty_sold: item.qty_sold,
                    qty_remaining: item.qty_remaining,
                    qty: item.qty_remaining,
                    alasan: ''
                };
            });
    }

    function mergeInitialItems(penjualan, items) {
        if (!penjualan || !items.length) {
            return buildRowsFromSale(penjualan);
        }

        return items.map(function(item) {
            var saleItem = (penjualan.items || []).find(function(candidate) {
                return String(candidate.product_id) === String(item.product_id);
            });

            return {
                product_id: item.product_id,
                product_name: saleItem ? saleItem.product_name : 'Produk',
                qty_sold: saleItem ? saleItem.qty_sold : item.qty,
                qty_remaining: saleItem ? saleItem.qty_remaining : item.qty,
                qty: item.qty,
                alasan: item.alasan || ''
            };
        });
    }

    function renderRows(items) {
        var $tbody = $('#refund-items-body');
        $tbody.empty();
        rowIndex = 0;

        if (!items.length) {
            $tbody.append('<tr class="empty-row"><td colspan="6" class="text-center text-muted">Pilih penjualan untuk memuat item retur.</td></tr>');
            return;
        }

        items.forEach(function(item) {
            addRow(item);
        });
    }

    function addRow(item) {
        var currentIndex = rowIndex++;
        var maxQty = parseInt(item.qty_remaining, 10) || 0;
        var qtyValue = Math.min(parseInt(item.qty, 10) || maxQty || 1, maxQty || 1);

        var html = '' +
            '<tr>' +
            '  <td>' +
            '    <input type="hidden" name="product[' + currentIndex + '][product_id]" value="' + escapeHtml(item.product_id) + '">' +
            '    <strong>' + escapeHtml(item.product_name) + '</strong>' +
            '  </td>' +
            '  <td class="text-center">' + escapeHtml(item.qty_sold) + '</td>' +
            '  <td class="text-center">' + escapeHtml(maxQty) + '</td>' +
            '  <td>' +
            '    <input type="number" min="1" max="' + escapeHtml(maxQty) + '" class="form-control" name="product[' + currentIndex + '][qty]" value="' + escapeHtml(qtyValue) + '" required>' +
            '  </td>' +
            '  <td>' +
            '    <input type="text" class="form-control" name="product[' + currentIndex + '][alasan]" value="' + escapeHtml(item.alasan || '') + '" placeholder="Alasan retur">' +
            '  </td>' +
            '  <td class="text-center">' +
            '    <button type="button" class="btn btn-danger btn-sm btn-remove-row"><i class="fa fa-trash"></i></button>' +
            '  </td>' +
            '</tr>';

        $('#refund-items-body').append(html);
    }

    function loadSaleItems(useInitialItems) {
        var penjualan = findPenjualan($('#penjualan_id').val());
        updateSaleInfo(penjualan);

        if (!penjualan) {
            renderRows([]);
            return;
        }

        var rows = useInitialItems
            ? mergeInitialItems(penjualan, initialItems)
            : buildRowsFromSale(penjualan);

        renderRows(rows);
    }

    $(document).on('click', '.btn-remove-row', function() {
        $(this).closest('tr').remove();

        if (!$('#refund-items-body tr').length) {
            renderRows([]);
        }
    });

    $('#penjualan_id').on('change', function() {
        initialItems = [];
        loadSaleItems(false);
    });

    $('#reload-sale-items').on('click', function() {
        loadSaleItems(false);
    });

    $(function() {
        moneyMask();
        $('.select2').select2({ width: '100%' });

        if (selectedPenjualanId) {
            $('#penjualan_id').val(String(selectedPenjualanId)).trigger('change.select2');
            loadSaleItems(initialItems.length > 0);
            return;
        }

        renderRows([]);
        updateSaleInfo(null);
    });
</script>
