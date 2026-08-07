<?php

namespace App\Services;

use App\Models\Pembelian;
use App\Models\Penjualan;
use Carbon\Carbon;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use ZipArchive;

class DocumentTemplateRenderer
{
    public function __construct(
        private readonly DocumentTemplateManager $templates,
        private readonly PenjualanBalanceService $balances,
    )
    {
    }

    public function renderPurchaseDocx(Pembelian $pembelian): string
    {
        $pembelian->loadMissing(['supplier', 'pembelianProducts.product']);
        $source = $this->templates->resolve(DocumentTemplateManager::PURCHASE_DOCX)['path'];
        $target = $this->temporaryPath('docx');

        $zip = new ZipArchive();
        abort_unless($zip->open($source) === true, 500, 'Template DOCX tidak dapat dibuka.');

        $document = new DOMDocument('1.0', 'UTF-8');
        $document->preserveWhiteSpace = false;
        $document->formatOutput = false;
        $document->loadXML($zip->getFromName('word/document.xml'));
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $context = $this->purchaseContext($pembelian);
        $this->expandDocxItemRows($xpath, count($context['items']));
        $this->replaceDocxTokens($xpath, $context['variables'], $context['items']);

        $zip->addFromString('word/document.xml', $document->saveXML());
        $this->replaceDocxLogo($zip);
        $workDir = $this->temporaryDirectory();
        $zip->extractTo($workDir);
        $zip->close();

        $this->repackZip($workDir, $target);
        $this->removeDirectory($workDir);

        return $target;
    }

    public function renderPurchaseXlsx(Pembelian $pembelian): string
    {
        $pembelian->loadMissing(['supplier', 'pembelianProducts.product']);
        $context = $this->purchaseContext($pembelian);

        return $this->renderXlsx(DocumentTemplateManager::PURCHASE_XLSX, $context);
    }

    public function renderSalesInvoiceXlsx(Penjualan $penjualan): string
    {
        $penjualan->loadMissing([
            'items.product', 'operator', 'salesman', 'paymentTransaction',
            'customer', 'agent', 'canvasBuyer', 'outletBuyer', 'tokoBuyer', 'outlet',
        ]);
        $context = $this->salesContext($penjualan);

        return $this->renderXlsx(DocumentTemplateManager::SALES_INVOICE_XLSX, $context);
    }

    public function renderSalesDeliveryXlsx(Penjualan $penjualan): string
    {
        $penjualan->loadMissing([
            'items.product', 'operator', 'salesman', 'paymentTransaction',
            'customer', 'agent', 'canvasBuyer', 'outletBuyer', 'tokoBuyer', 'outlet',
        ]);
        $context = $this->salesContext($penjualan);

        return $this->renderXlsx(DocumentTemplateManager::SALES_DELIVERY_XLSX, $context);
    }

    private function renderXlsx(string $type, array $context): string
    {
        $source = $this->templates->resolve($type)['path'];
        $spreadsheet = IOFactory::load($source);
        $itemRowIndexes = $this->expandXlsxItemRows($spreadsheet, $type, count($context['items']));

        $this->replaceXlsxTokens(
            $spreadsheet,
            $context['variables'],
            $context['items'],
            $type,
            $itemRowIndexes,
        );

        $target = $this->temporaryPath('xlsx');
        (new Xlsx($spreadsheet))->save($target);

        return $target;
    }

    private function expandDocxItemRows(DOMXPath $xpath, int $itemCount): void
    {
        foreach ($xpath->query('//w:body/w:tbl') as $table) {
            $rows = [];
            foreach ($xpath->query('./w:tr', $table) as $row) {
                $text = '';
                foreach ($xpath->query('.//w:t', $row) as $textNode) {
                    $text .= (string) $textNode->nodeValue;
                }

                if (preg_match('/\{\{(?:purchase\.items|sale\.items|item|items)\.(?!\d+\.)[a-z_]+\}\}/', $text)) {
                    $rows[] = $row;
                }
            }

            if ($rows === []) {
                continue;
            }

            $sourceRow = $rows[count($rows) - 1];
            $nextRow = $sourceRow->nextSibling;
            while ($nextRow && $nextRow->nodeName !== 'w:tr') {
                $nextRow = $nextRow->nextSibling;
            }

            $requiredRows = max(1, $itemCount);
            $extraRows = max(0, $requiredRows - count($rows));
            for ($offset = 0; $offset < $extraRows; $offset++) {
                $clone = $sourceRow->cloneNode(true);
                if ($nextRow) {
                    $table->insertBefore($clone, $nextRow);
                } else {
                    $table->appendChild($clone);
                }
                $rows[] = $clone;
            }

            foreach ($rows as $index => $row) {
                if ($row instanceof DOMElement) {
                    // This temporary marker keeps cloned rows tied to their item
                    // index while the XML is being rendered. It is removed below.
                    $row->setAttribute('data-alami-item-index', (string) $index);
                }
            }
        }
    }

    private function replaceDocxTokens(
        DOMXPath $xpath,
        array $variables,
        array $items,
    ): void
    {
        foreach ($xpath->query('//w:p') as $paragraph) {
            $textNodes = $xpath->query('.//w:t', $paragraph);
            if ($textNodes->length === 0) {
                continue;
            }

            $textValues = [];
            $ranges = [];
            $offset = 0;
            foreach ($textNodes as $textNode) {
                $text = (string) $textNode->nodeValue;
                $textValues[] = $text;
                $length = strlen($text);
                $ranges[] = [$offset, $offset + $length];
                $offset += $length;
            }

            $combined = implode('', $textValues);
            $crossesRuns = false;
            if (preg_match_all('/\{\{[^{}]+\}\}/', $combined, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $matchStart = $match[1];
                    $matchEnd = $matchStart + strlen($match[0]);
                    $firstRun = null;
                    $lastRun = null;
                    foreach ($ranges as $index => [$start, $end]) {
                        if ($matchStart >= $start && $matchStart < $end) {
                            $firstRun = $index;
                        }
                        if ($matchEnd > $start && $matchEnd <= $end) {
                            $lastRun = $index;
                        }
                    }
                    if ($firstRun !== null && $lastRun !== null && $firstRun !== $lastRun) {
                        $crossesRuns = true;
                        break;
                    }
                }
            }

            $row = $paragraph->parentNode;
            while ($row && $row->nodeName !== 'w:tr') {
                $row = $row->parentNode;
            }
            $itemIndex = null;
            if ($row instanceof DOMElement && $row->hasAttribute('data-alami-item-index')) {
                $itemIndex = (int) $row->getAttribute('data-alami-item-index');
            }

            if ($crossesRuns) {
                $textNodes->item(0)->nodeValue = $this->replaceTemplateTokens(
                    $combined,
                    $variables,
                    $items,
                    $itemIndex,
                );
                for ($index = 1; $index < $textNodes->length; $index++) {
                    $textNodes->item($index)->nodeValue = '';
                }

                continue;
            }

            foreach ($textNodes as $textNode) {
                $textNode->nodeValue = $this->replaceTemplateTokens(
                    (string) $textNode->nodeValue,
                    $variables,
                    $items,
                    $itemIndex,
                );
            }
        }

        foreach ($xpath->query('//w:tr') as $row) {
            if ($row instanceof DOMElement) {
                $row->removeAttribute('data-alami-item-index');
            }
        }
    }

    private function expandXlsxItemRows(Spreadsheet $spreadsheet, string $type, int $itemCount): array
    {
        $itemRowIndexes = [];

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            $tokenRows = [];
            foreach ($sheet->getRowIterator() as $row) {
                $hasItemToken = false;
                foreach ($row->getCellIterator() as $cell) {
                    $value = $this->spreadsheetText($cell->getValue());
                    if ($value !== null
                        && preg_match('/\{\{(?:purchase\.items|sale\.items|item|items)\.(?!\d+\.)[a-z_]+\}\}/', $value)) {
                        $hasItemToken = true;
                        break;
                    }
                }

                if ($hasItemToken) {
                    $tokenRows[] = $row->getRowIndex();
                }
            }

            if ($tokenRows === []) {
                continue;
            }

            $runs = [];
            $currentRun = [];
            foreach ($tokenRows as $row) {
                if ($currentRun === [] || $row === end($currentRun) + 1) {
                    $currentRun[] = $row;
                } else {
                    $runs[] = $currentRun;
                    $currentRun = [$row];
                }
            }
            if ($currentRun !== []) {
                $runs[] = $currentRun;
            }

            foreach ($runs as $run) {
                $startRow = $run[0];
                $endRow = $run[count($run) - 1];
                $leftTokens = false;
                $rightTokens = false;
                foreach ($sheet->getRowIterator($startRow, $startRow) as $row) {
                    foreach ($row->getCellIterator() as $cell) {
                        $value = $this->spreadsheetText($cell->getValue());
                        if ($value === null
                            || ! preg_match('/\{\{(?:purchase\.items|sale\.items|item|items)\.(?!\d+\.)[a-z_]+\}\}/', $value)) {
                            continue;
                        }

                        if ($cell->getColumn() >= 10) {
                            $rightTokens = true;
                        } else {
                            $leftTokens = true;
                        }
                    }
                }

                $twoColumns = $type === DocumentTemplateManager::PURCHASE_XLSX
                    && $leftTokens
                    && $rightTokens;
                $itemsPerRow = $twoColumns ? 2 : 1;
                $requiredRows = max(1, (int) ceil($itemCount / $itemsPerRow));
                $extraRows = max(0, $requiredRows - count($run));

                if ($extraRows > 0) {
                    $this->duplicateXlsxRows(
                        $sheet,
                        $endRow,
                        $endRow + 1,
                        $extraRows,
                    );
                }

                $rowMap = [];
                $mappedRows = max($requiredRows, count($run));
                for ($offset = 0; $offset < $mappedRows; $offset++) {
                    $row = $startRow + $offset;
                    $rowMap[$row] = $twoColumns
                        ? [
                            'left' => $offset * 2,
                            'right' => ($offset * 2) + 1,
                        ]
                        : ['index' => $offset];
                }
                $itemRowIndexes[$sheet->getTitle()] = ($itemRowIndexes[$sheet->getTitle()] ?? []) + $rowMap;
            }
        }

        return $itemRowIndexes;
    }

    private function duplicateXlsxRows(Worksheet $sheet, int $sourceRow, int $beforeRow, int $count): void
    {
        if ($count < 1) {
            return;
        }

        $highestColumn = $sheet->getHighestColumn();
        $sourceHeight = $sheet->getRowDimension($sourceRow)->getRowHeight();
        $sourceCells = [];
        foreach ($sheet->getRowIterator($sourceRow, $sourceRow) as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(true);
            foreach ($cellIterator as $cell) {
                $sourceCells[$cell->getColumn()] = $cell->getValue();
            }
        }
        $sourceMerges = $sheet->getMergeCells();

        $sheet->insertNewRowBefore($beforeRow, $count);
        for ($offset = 0; $offset < $count; $offset++) {
            $row = $beforeRow + $offset;
            $sheet->duplicateStyle(
                $sheet->getStyle('A'.$sourceRow.':'.$highestColumn.$sourceRow),
                'A'.$row.':'.$highestColumn.$row,
            );
            if ($sourceHeight !== null) {
                $sheet->getRowDimension($row)->setRowHeight($sourceHeight);
            }

            foreach ($sourceCells as $column => $value) {
                $sheet->setCellValue($column.$row, $value);
            }

            foreach ($sourceMerges as $merge) {
                [$start, $end] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::rangeBoundaries($merge);
                if ($start[1] !== $sourceRow || $end[1] !== $sourceRow) {
                    continue;
                }

                $newMerge = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($start[0]).$row
                    .':'.\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($end[0]).$row;
                if (! in_array($newMerge, $sheet->getMergeCells(), true)) {
                    $sheet->mergeCells($newMerge);
                }
            }
        }
    }

    private function replaceXlsxTokens(
        Spreadsheet $spreadsheet,
        array $variables,
        array $items,
        string $type,
        array $itemRowIndexes = [],
    ): void
    {
        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            $rowMap = $itemRowIndexes[$sheet->getTitle()] ?? [];
            foreach ($sheet->getRowIterator() as $row) {
                foreach ($row->getCellIterator() as $cell) {
                    $value = $this->spreadsheetText($cell->getValue());
                    if ($value === null || ! str_contains($value, '{{')) {
                        continue;
                    }

                    $cell->setValue($this->replaceTemplateTokens(
                        $value,
                        $variables,
                        $items,
                        $this->xlsxItemIndex($cell->getRow(), $cell->getColumn(), $type, $rowMap),
                    ));
                }
            }
        }
    }

    private function replaceTemplateTokens(string $value, array $variables, array $items, ?int $itemIndex = null): string
    {
        $scalarVariables = array_filter(
            $variables,
            fn ($token) => ! str_starts_with($token, '{{item.') && ! str_starts_with($token, '{{items.'),
            ARRAY_FILTER_USE_KEY,
        );
        $value = strtr($value, $scalarVariables);

        return (string) preg_replace_callback(
            '/\{\{(item|items|purchase\.items|sale\.items)(?:\.(\d+))?\.([a-z_]+)\}\}/',
            function (array $matches) use ($items, $itemIndex): string {
                $namespace = $matches[1];
                $number = $matches[2];
                $field = $matches[3];

                if ($number !== '') {
                    // Legacy {{items.0.name}} is zero-based; all other indexed forms are one-based.
                    $index = $namespace === 'items'
                        ? (int) $number
                        : (int) $number - 1;
                } else {
                    // Row-based syntax: the template row determines the item index.
                    $index = $itemIndex ?? 0;
                }

                return (string) (($items[$index] ?? [])[$field] ?? '');
            },
            $value,
        );
    }

    private function xlsxItemIndex(int $row, string $column, string $type, array $rowMap = []): ?int
    {
        $columnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($column);

        if (isset($rowMap[$row])) {
            if ($type === DocumentTemplateManager::PURCHASE_XLSX && $columnIndex >= 10) {
                return $rowMap[$row]['right'] ?? $rowMap[$row]['index'] ?? null;
            }

            return $rowMap[$row]['left'] ?? $rowMap[$row]['index'] ?? null;
        }

        return null;
    }

    private function spreadsheetText(mixed $value): ?string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }

        return null;
    }

    private function replaceDocxLogo(ZipArchive $zip): void
    {
        $settings = $this->templates->settings();
        $logoPath = $settings['logo'] ?? null;

        if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            $zip->addFromString('word/media/image1.png', Storage::disk('public')->get($logoPath));
        } elseif (is_file(public_path('img/logo.jpeg'))) {
            $zip->addFromString('word/media/image1.png', file_get_contents(public_path('img/logo.jpeg')));
        }
    }

    private function purchaseContext(Pembelian $pembelian): array
    {
        $settings = $this->templates->settings();
        $company = $this->companyContext($settings);
        $supplierModel = $pembelian->supplier;
        $supplier = [
            'code' => (string) ($supplierModel?->kode_supplier ?? ''),
            'name' => (string) ($supplierModel?->name ?? '-'),
            'address' => (string) ($supplierModel?->alamat ?? '-'),
            'phone' => (string) ($supplierModel?->no_telp ?? '-'),
            'email' => '',
            'contact' => (string) ($supplierModel?->kode_supplier ?? '-'),
        ];
        $date = $pembelian->created_at ?: now();
        $items = $pembelian->pembelianProducts->values()->map(fn ($item, $index) => $this->itemContext(
            $item->product?->code,
            $item->product?->name,
            $item->qty,
            $item->product?->satuan,
            $item->harga_beli,
            $item->subtotal,
            $index + 1
        ))->all();

        $purchase = [
            'number' => (string) $pembelian->code,
            'date' => $this->date($date),
            'date_serial' => $this->excelDate($date),
            'total' => (int) $pembelian->total,
            'location' => $this->locationFromAddress($company['address']),
            'items' => $items,
        ];

        $variables = $this->variables([
            'company' => $company,
            'purchase' => $purchase,
            'supplier' => $supplier,
            'items' => $items,
        ]);

        return compact('company', 'supplier', 'purchase', 'items', 'variables');
    }

    private function salesContext(Penjualan $penjualan): array
    {
        $settings = $this->templates->settings();
        $company = $this->companyContext($settings);
        $buyerEntity = $penjualan->buyerEntity();
        $buyer = [
            'type' => (string) ($penjualan->buyer_type_label ?? '-'),
            'name' => (string) ($penjualan->buyer_display_name ?? '-'),
            'address' => (string) ($penjualan->buyer_address ?: $buyerEntity?->alamat ?: '-'),
            'phone' => (string) ($penjualan->buyer_phone ?: $buyerEntity?->no_telp ?: '-'),
        ];
        $date = $penjualan->sale_date ?: $penjualan->created_at ?: now();
        $items = $penjualan->items->values()->map(fn ($item, $index) => $this->itemContext(
            $item->product?->code,
            $item->product?->name,
            $item->qty_input ?? $item->qty,
            $item->unit ?: $item->product?->satuan,
            $item->price,
            $item->subtotal,
            $index + 1,
            $item->discount ?? 0
        ))->all();
        $subtotal = array_sum(array_map(
            fn (array $item) => $item['subtotal'] + $item['discount'],
            $items
        ));
        $legacyDiscount = (int) ($penjualan->discount ?? 0);
        $itemDiscount = array_sum(array_column($items, 'discount'));
        $paid = $this->balances->payment($penjualan);
        $oldDebt = $this->balances->oldDebt($penjualan);
        $shippingCost = (float) ($penjualan->shipping_cost ?? 0);
        $newDebt = max(0, $oldDebt + $shippingCost + (float) ($penjualan->total ?? 0) - $paid);
        $sale = [
            'number' => (string) $penjualan->code,
            'date' => $this->date($date),
            'date_serial' => $this->excelDate($date),
            'subtotal' => $subtotal,
            'discount' => $itemDiscount + $legacyDiscount,
            'legacy_discount' => $legacyDiscount,
            'total' => (int) ($penjualan->total ?? 0),
            'paid' => $paid,
            'old_debt' => $oldDebt,
            'shipping_cost' => $shippingCost,
            'payment' => $paid,
            'new_debt' => $newDebt,
            'payment_type' => (string) ($penjualan->payment_type ?? '-'),
            'payment_status' => (string) ($penjualan->payment_status ?? '-'),
            'operator' => (string) ($penjualan->operator?->name ?? '-'),
            'salesman' => (string) ($penjualan->salesman?->name ?? ''),
            'items' => $items,
        ];

        $variables = $this->variables([
            'company' => $company,
            'sale' => $sale,
            'buyer' => $buyer,
            'operator' => ['name' => $sale['operator']],
            'salesman' => ['name' => $sale['salesman']],
            'items' => $items,
        ]);

        return compact('company', 'buyer', 'sale', 'items', 'variables');
    }

    private function variables(array $context): array
    {
        $variables = [];
        foreach ($context as $group => $values) {
            if (! is_array($values)) {
                continue;
            }

            if ($group === 'items') {
                $this->addIndexedItemVariables($variables, 'items', $values, true);

                continue;
            }

            foreach ($values as $key => $value) {
                if ($key === 'items' && is_array($value)) {
                    $this->addIndexedItemVariables($variables, $group.'.items', $value);

                    continue;
                }

                if (is_array($value)) {
                    foreach ($value as $nestedKey => $nestedValue) {
                        $variables['{{'.$group.'.'.$key.'.'.$nestedKey.'}}'] = (string) $nestedValue;
                    }
                } else {
                    $variables['{{'.$group.'.'.$key.'}}'] = (string) $value;
                }
            }
        }

        $variables['{{company.name}}'] ??= '';

        return $variables;
    }

    private function addIndexedItemVariables(array &$variables, string $prefix, array $items, bool $addRowAliases = false): void
    {
        foreach ($items as $index => $item) {
            foreach ($item as $key => $value) {
                $variables['{{'.$prefix.'.'.($index + 1).'.'.$key.'}}'] = (string) $value;

                if (! $addRowAliases) {
                    continue;
                }

                // Backward-compatible aliases for table rows and old custom templates.
                $variables['{{item.'.($index + 1).'.'.$key.'}}'] = (string) $value;
                $variables['{{items.'.$index.'.'.$key.'}}'] = (string) $value;
                if ($index === 0) {
                    $variables['{{item.'.$key.'}}'] = (string) $value;
                }
            }
        }
    }

    private function companyContext(array $settings): array
    {
        return [
            'name' => (string) ($settings['name'] ?? 'NAMA PERUSAHAAN'),
            'address' => (string) ($settings['address'] ?? '-'),
            'phone' => (string) ($settings['telp'] ?? '-'),
            'email' => (string) ($settings['email'] ?? '-'),
            'website' => (string) ($settings['website'] ?? ''),
            'contact' => (string) ($settings['telp'] ?? '-'),
        ];
    }

    private function itemContext($code, $name, $qty, $unit, $price, $subtotal, int $no, $discount = 0): array
    {
        return [
            'no' => $no,
            'code' => (string) ($code ?? ''),
            'name' => (string) ($name ?? '-'),
            'qty' => (float) $qty,
            'unit' => (string) ($unit ?? 'PCS'),
            'price' => (float) ($price ?? 0),
            'discount' => (float) ($discount ?? 0),
            'subtotal' => (float) ($subtotal ?? 0),
        ];
    }

    private function date($date): string
    {
        return Carbon::parse($date)->locale('id')->translatedFormat('d F Y');
    }

    private function excelDate($date): int
    {
        return \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(Carbon::parse($date)->startOfDay());
    }

    private function locationFromAddress(string $address): string
    {
        $parts = preg_split('/[,\n]/', $address);

        return trim((string) end($parts)) ?: '-';
    }

    private function temporaryPath(string $extension): string
    {
        $path = tempnam(sys_get_temp_dir(), 'alami-template-');
        $target = $path.'.'.$extension;
        rename($path, $target);

        return $target;
    }

    private function repackZip(string $directory, string $target): void
    {
        $archive = new ZipArchive();
        abort_unless($archive->open($target, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, 500);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($iterator as $file) {
            $local = ltrim(str_replace($directory, '', $file->getPathname()), DIRECTORY_SEPARATOR);
            $archive->addFile($file->getPathname(), $local);
        }
        $archive->close();
    }

    private function temporaryDirectory(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'alami-template-dir-');
        unlink($path);
        mkdir($path, 0700);

        return $path;
    }

    private function removeDirectory(string $directory): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($directory);
    }
}
