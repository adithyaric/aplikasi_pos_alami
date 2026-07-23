<script>
    (function() {
        var dateTokenMap = {
            yyyy_mm: '{YYYY}{MM}',
            yyyy_roman: '{YYYY}{ROMAN_MM}',
            yy_mm: '{YY}{MM}',
            yy_roman: '{YY}{ROMAN_MM}',
            yyyy: '{YYYY}',
            yy: '{YY}',
            mm: '{MM}',
            roman: '{ROMAN_MM}',
            none: ''
        };
        var romanMonths = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
        var prefixInput = document.getElementById('po_builder_prefix_text');
        var separatorInput = document.getElementById('po_builder_separator');
        var dateFormatInput = document.getElementById('po_builder_date_format');
        var sequencePositionInput = document.getElementById('po_builder_sequence_position');
        var includeSupplierCodeInput = document.getElementById('po_builder_include_supplier_code');
        var rawFormatInput = document.getElementById('po_number_prefix');
        var previewInput = document.getElementById('po_number_preview');
        var paddingInput = document.getElementById('po_number_padding');
        var supplierCodeInput = document.querySelector('input[name="kode_supplier"]');
        var advancedToggleInput = document.getElementById('po_show_advanced');
        var advancedWrapper = document.getElementById('po_advanced_wrapper');

        if (!prefixInput || !separatorInput || !dateFormatInput || !sequencePositionInput || !includeSupplierCodeInput || !rawFormatInput || !previewInput || !paddingInput || !supplierCodeInput || !advancedToggleInput || !advancedWrapper) {
            return;
        }

        function buildGuidedFormat() {
            var separator = separatorInput.value || '-';
            var parts = [];
            var prefix = (prefixInput.value || '').trim();
            var dateToken = dateTokenMap[dateFormatInput.value] || '';

            if (sequencePositionInput.value === 'prefix') {
                parts.push('{SEQ}');
            }
            if (prefix) {
                parts.push(prefix);
            }
            if (includeSupplierCodeInput.checked) {
                parts.push('{SUPPLIER_CODE}');
            }
            if (dateToken) {
                parts.push(dateToken);
            }
            if (sequencePositionInput.value !== 'prefix') {
                parts.push('{SEQ}');
            }

            return parts.join(separator);
        }

        function formattedSequence() {
            var padding = parseInt(paddingInput.value || '5', 10);
            if (isNaN(padding) || padding < 3) {
                padding = 3;
            }

            return String(1).padStart(padding, '0');
        }

        function renderPreview(format) {
            var now = new Date();
            var preview = format || '';
            var supplierCode = (supplierCodeInput.value || 'SUP').trim().toUpperCase();
            var replacements = {
                '{SUPPLIER_CODE}': supplierCode,
                '{YYYY}': String(now.getFullYear()),
                '{YY}': String(now.getFullYear()).slice(-2),
                '{MM}': String(now.getMonth() + 1).padStart(2, '0'),
                '{ROMAN_MM}': romanMonths[now.getMonth() + 1],
                '{DD}': String(now.getDate()).padStart(2, '0'),
                '{SEQ}': formattedSequence()
            };

            Object.keys(replacements).forEach(function(token) {
                preview = preview.split(token).join(replacements[token]);
            });

            if (format.indexOf('{SEQ}') === -1) {
                preview += formattedSequence();
            }

            return preview;
        }

        function syncPreview() {
            previewInput.value = renderPreview(rawFormatInput.value || '');
        }

        function syncRawFromGuided() {
            rawFormatInput.value = buildGuidedFormat();
            syncPreview();
        }

        function toggleAdvanced() {
            advancedWrapper.style.display = advancedToggleInput.checked ? '' : 'none';
        }

        [prefixInput, separatorInput, dateFormatInput, sequencePositionInput, includeSupplierCodeInput].forEach(function(element) {
            element.addEventListener('input', syncRawFromGuided);
            element.addEventListener('change', syncRawFromGuided);
        });

        [rawFormatInput, paddingInput, supplierCodeInput].forEach(function(element) {
            element.addEventListener('input', syncPreview);
            element.addEventListener('change', syncPreview);
        });

        advancedToggleInput.addEventListener('change', toggleAdvanced);

        if (!rawFormatInput.value.trim()) {
            rawFormatInput.value = buildGuidedFormat();
        }

        toggleAdvanced();
        syncPreview();
    })();
</script>
