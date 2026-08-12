<?php

namespace App\Services;

use App\Models\Pembelian;
use App\Models\Penjualan;
use App\Models\Supplier;
use App\Support\ProductUnitConverter;
use Carbon\Carbon;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use ZipArchive;

class DocumentTemplateRenderer
{
    public function __construct(
        private readonly DocumentTemplateManager $templates,
        private readonly PenjualanBalanceService $balances,
        private readonly ProductUnitConverter $units,
    )
    {
    }

    public function renderPurchaseDocx(Pembelian $pembelian): string
    {
        $pembelian->loadMissing(['supplier', 'pembelianProducts.product', 'pembelianTransaction']);
        $source = $pembelian->supplier
            ? $this->templates->resolvePurchase(DocumentTemplateManager::PURCHASE_DOCX, $pembelian->supplier)['path']
            : $this->templates->resolve(DocumentTemplateManager::PURCHASE_DOCX)['path'];
        $target = $this->temporaryPath('docx');
        abort_unless(copy($source, $target), 500, 'Template DOCX tidak dapat disalin.');

        $zip = new ZipArchive();
        abort_unless($zip->open($target) === true, 500, 'Template DOCX tidak dapat dibuka.');

        $document = new DOMDocument('1.0', 'UTF-8');
        $document->preserveWhiteSpace = false;
        $document->formatOutput = false;
        $document->loadXML($zip->getFromName('word/document.xml'));
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $context = $this->purchaseContext($pembelian);
        $this->expandDocxItemRows($xpath, count($context['items']));
        $this->ensureDocxItemRowBorders($xpath);
        $this->replaceDocxLogoToken($zip, $document);
        $this->replaceDocxSignatureToken($zip, $document);
        $this->replaceDocxTokens($xpath, $context['variables'], $context['items']);

        $zip->addFromString('word/document.xml', $document->saveXML());
        $this->replaceDocxLogo($zip);
        $zip->close();

        return $target;
    }

    public function renderPurchaseXlsx(Pembelian $pembelian): string
    {
        $pembelian->loadMissing(['supplier', 'pembelianProducts.product', 'pembelianTransaction']);
        $context = $this->purchaseContext($pembelian);

        return $this->renderXlsx(DocumentTemplateManager::PURCHASE_XLSX, $context, $pembelian->supplier);
    }

    public function renderPurchaseDocument(Pembelian $pembelian): array
    {
        $pembelian->loadMissing(['supplier']);
        $type = $pembelian->supplier
            ? $this->templates->purchaseTemplateType($pembelian->supplier)
            : DocumentTemplateManager::PURCHASE_XLSX;

        $path = $type === DocumentTemplateManager::PURCHASE_DOCX
            ? $this->renderPurchaseDocx($pembelian)
            : $this->renderPurchaseXlsx($pembelian);

        return [
            'path' => $path,
            'type' => $type,
            'extension' => $this->templates->definition($type)['extension'],
            'number' => $this->purchaseNumber($pembelian),
        ];
    }

    /**
     * Render the currently filtered purchase orders as one supplier-specific
     * document. Each PO remains a separate vertical section (XLSX) or page
     * section (DOCX); item rows are never merged into one table.
     */
    public function renderPurchaseBatch(Collection $pembelians): array
    {
        abort_unless($pembelians->isNotEmpty(), 404, 'Tidak ada PO untuk dicetak.');

        $pembelians->each(
            fn (Pembelian $pembelian) => $pembelian->loadMissing(['supplier', 'pembelianProducts.product', 'pembelianTransaction'])
        );
        $supplier = $pembelians->first()->supplier;
        $type = $supplier
            ? $this->templates->purchaseTemplateType($supplier)
            : DocumentTemplateManager::PURCHASE_XLSX;
        $paths = [];

        try {
            foreach ($pembelians as $pembelian) {
                $paths[] = $type === DocumentTemplateManager::PURCHASE_DOCX
                    ? $this->renderPurchaseDocx($pembelian)
                    : $this->renderPurchaseXlsx($pembelian);
            }

            $path = $type === DocumentTemplateManager::PURCHASE_DOCX
                ? $this->combineDocxDocuments($paths)
                : $this->combineXlsxDocuments($paths);

            foreach ($paths as $temporaryPath) {
                if ($temporaryPath !== $path && is_file($temporaryPath)) {
                    @unlink($temporaryPath);
                }
            }

            return [
                'path' => $path,
                'type' => $type,
                'extension' => $this->templates->definition($type)['extension'],
                'count' => $pembelians->count(),
            ];
        } catch (\Throwable $exception) {
            foreach ($paths as $temporaryPath) {
                if (is_file($temporaryPath)) {
                    @unlink($temporaryPath);
                }
            }

            throw $exception;
        }
    }

    public function purchaseNumber(Pembelian $pembelian): string
    {
        $pembelian->loadMissing('supplier');
        if (! $pembelian->supplier) {
            return (string) $pembelian->code;
        }

        $sequence = 1;
        if (preg_match('/(\d+)$/', (string) $pembelian->code, $matches)) {
            $sequence = max(1, (int) $matches[1]);
        }

        return $pembelian->supplier->previewPoCode(
            $pembelian->created_at ?: now(),
            null,
            $sequence,
        );
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

    private function renderXlsx(string $type, array $context, ?Supplier $supplier = null): string
    {
        $source = $supplier
            ? $this->templates->resolvePurchase($type, $supplier)['path']
            : $this->templates->resolve($type)['path'];
        $spreadsheet = IOFactory::load($source);
        $itemRowIndexes = $this->expandXlsxItemRows($spreadsheet, $type, count($context['items']));
        $this->ensureXlsxItemRowBorders($spreadsheet, $itemRowIndexes);

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

    private function ensureDocxItemRowBorders(DOMXPath $xpath): void
    {
        $namespace = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

        foreach ($xpath->query('//w:tr[@data-alami-item-index]') as $row) {
            foreach ($xpath->query('./w:tc', $row) as $cell) {
                $cellProperties = $xpath->query('./w:tcPr', $cell)->item(0);
                if (! $cellProperties instanceof DOMElement) {
                    $cellProperties = $cell->ownerDocument->createElementNS($namespace, 'w:tcPr');
                    $cell->insertBefore($cellProperties, $cell->firstChild);
                }

                $borders = $xpath->query('./w:tcBorders', $cellProperties)->item(0);
                if (! $borders instanceof DOMElement) {
                    $borders = $cell->ownerDocument->createElementNS($namespace, 'w:tcBorders');
                    $cellProperties->appendChild($borders);
                }

                foreach (['top', 'left', 'bottom', 'right'] as $side) {
                    $border = $xpath->query('./w:'.$side, $borders)->item(0);
                    if (! $border instanceof DOMElement) {
                        $border = $cell->ownerDocument->createElementNS($namespace, 'w:'.$side);
                        $borders->appendChild($border);
                    }

                    $border->setAttributeNS($namespace, 'w:val', 'single');
                    $border->setAttributeNS($namespace, 'w:sz', '4');
                    $border->setAttributeNS($namespace, 'w:space', '0');
                    $border->setAttributeNS($namespace, 'w:color', '000000');
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

    private function ensureXlsxItemRowBorders(Spreadsheet $spreadsheet, array $itemRowIndexes): void
    {
        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            foreach (array_keys($itemRowIndexes[$sheet->getTitle()] ?? []) as $rowIndex) {
                $columns = [];
                foreach ($sheet->getRowIterator($rowIndex, $rowIndex) as $row) {
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(true);
                    foreach ($cellIterator as $cell) {
                        $value = $this->spreadsheetText($cell->getValue());
                        if ($value === null
                            || ! preg_match('/\{\{(?:purchase\.items|sale\.items|item|items)\.(?!\d+\.)[a-z_]+\}\}/', $value)) {
                            continue;
                        }

                        $columnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($cell->getColumn());
                        $columns[] = $columnIndex;
                        foreach ($sheet->getMergeCells() as $merge) {
                            [$start, $end] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::rangeBoundaries($merge);
                            if ($start[1] <= $rowIndex && $end[1] >= $rowIndex
                                && $columnIndex >= $start[0]
                                && $columnIndex <= $end[0]) {
                                for ($column = $start[0]; $column <= $end[0]; $column++) {
                                    $columns[] = $column;
                                }
                            }
                        }
                    }
                }

                if ($columns === []) {
                    continue;
                }

                sort($columns);
                $columns = array_values(array_unique($columns));
                $rangeStart = $columns[0];
                $previous = $columns[0];
                $ranges = [];
                foreach (array_slice($columns, 1) as $column) {
                    if ($column !== $previous + 1) {
                        $ranges[] = [$rangeStart, $previous];
                        $rangeStart = $column;
                    }
                    $previous = $column;
                }
                $ranges[] = [$rangeStart, $previous];

                foreach ($ranges as [$start, $end]) {
                    $startColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($start);
                    $endColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($end);
                    $sheet->getStyle($startColumn.$rowIndex.':'.$endColumn.$rowIndex)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                'color' => ['argb' => 'FF000000'],
                            ],
                        ],
                    ]);
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

                    if (trim($value) === '{{company.logo}}') {
                        $cell->setValue(null);
                        $logoPath = $this->companyLogoPath();
                        if ($logoPath) {
                            $drawing = new Drawing();
                            $drawing->setName('Company Logo');
                            $drawing->setDescription('Logo perusahaan');
                            $drawing->setPath($logoPath);
                            $drawing->setHeight(45);
                            $drawing->setCoordinates($cell->getCoordinate());
                            $drawing->setWorksheet($sheet);
                        }

                        continue;
                    }

                    if (trim($value) === '{{company.ttd}}') {
                        $cell->setValue(null);
                        $signaturePath = $this->companySignaturePath();
                        if ($signaturePath) {
                            $drawing = new Drawing();
                            $drawing->setName('Company TTD');
                            $drawing->setDescription('TTD perusahaan');
                            $drawing->setPath($signaturePath);
                            $drawing->setHeight(45);
                            $drawing->setCoordinates($cell->getCoordinate());
                            $drawing->setWorksheet($sheet);
                        }

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

                return $this->formatTemplateValue(
                    $namespace.'.'.$field,
                    ($items[$index] ?? [])[$field] ?? '',
                );
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

    private function replaceDocxLogoToken(ZipArchive $zip, DOMDocument $document): void
    {
        $logoPath = $this->companyLogoPath();
        if (! $logoPath) {
            return;
        }

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        foreach ($xpath->query('//w:p') as $paragraph) {
            $textNodes = $xpath->query('.//w:t', $paragraph);
            $text = '';
            foreach ($textNodes as $textNode) {
                $text .= (string) $textNode->nodeValue;
            }

            if (trim($text) !== '{{company.logo}}') {
                continue;
            }

            foreach ($textNodes as $textNode) {
                $textNode->nodeValue = '';
            }
            $this->appendDocxImage($zip, $document, $paragraph, $logoPath, 'Company Logo', 'company-logo');
        }
    }

    private function replaceDocxSignatureToken(ZipArchive $zip, DOMDocument $document): void
    {
        $signaturePath = $this->companySignaturePath();
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        foreach ($xpath->query('//w:p') as $paragraph) {
            $textNodes = $xpath->query('.//w:t', $paragraph);
            $text = '';
            foreach ($textNodes as $textNode) {
                $text .= (string) $textNode->nodeValue;
            }

            $token = '{{company.ttd}}';
            if (! str_contains($text, $token)) {
                continue;
            }

            $remainingText = str_replace($token, '', $text);
            foreach ($textNodes as $index => $textNode) {
                $textNode->nodeValue = $index === 0 ? $remainingText : '';
            }

            if ($signaturePath) {
                $this->appendDocxImage(
                    $zip,
                    $document,
                    $paragraph,
                    $signaturePath,
                    'Company TTD',
                    'company-ttd',
                );
            }
        }
    }

    private function appendDocxImage(
        ZipArchive $zip,
        DOMDocument $document,
        ?DOMElement $paragraph,
        string $path,
        string $name,
        string $fileBaseName,
    ): void {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (! in_array($extension, ['jpg', 'jpeg', 'png'], true) || ! is_file($path) || ! $paragraph) {
            return;
        }

        $mimeType = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            default => 'image/png',
        };
        $mediaName = $fileBaseName.'.'.$extension;
        $zip->addFromString('word/media/'.$mediaName, file_get_contents($path));

        $relationships = new DOMDocument('1.0', 'UTF-8');
        $relationships->preserveWhiteSpace = false;
        $relationships->loadXML($zip->getFromName('word/_rels/document.xml.rels'));
        $relationshipNamespace = 'http://schemas.openxmlformats.org/package/2006/relationships';
        $relationshipId = 0;

        foreach ($relationships->documentElement->childNodes as $relationship) {
            if ($relationship instanceof DOMElement
                && preg_match('/^rId(\d+)$/', (string) $relationship->getAttribute('Id'), $matches)) {
                $relationshipId = max($relationshipId, (int) $matches[1]);
            }
        }

        $relationshipId = 'rId'.($relationshipId + 1);
        $relationship = $relationships->createElementNS($relationshipNamespace, 'Relationship');
        $relationship->setAttribute('Id', $relationshipId);
        $relationship->setAttribute('Type', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/image');
        $relationship->setAttribute('Target', 'media/'.$mediaName);
        $relationships->documentElement->appendChild($relationship);
        $zip->addFromString('word/_rels/document.xml.rels', $relationships->saveXML());

        $this->ensureDocxImageContentType($zip, $extension, $mimeType);

        $drawing = sprintf(
            '<w:r xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
                <w:rPr><w:noProof/></w:rPr>
                <w:drawing>
                    <wp:inline distT="0" distB="0" distL="0" distR="0">
                        <wp:extent cx="1800000" cy="900000"/>
                        <wp:docPr id="900000001" name="%s"/>
                        <wp:cNvGraphicFramePr><a:graphicFrameLocks noChangeAspect="1"/></wp:cNvGraphicFramePr>
                        <a:graphic>
                            <a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">
                                <pic:pic>
                                    <pic:nvPicPr><pic:cNvPr id="900000001" name="%s"/><pic:cNvPicPr/></pic:nvPicPr>
                                    <pic:blipFill><a:blip r:embed="%s"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>
                                    <pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="1800000" cy="900000"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr>
                                </pic:pic>
                            </a:graphicData>
                        </a:graphic>
                    </wp:inline>
                </w:drawing>
            </w:r>',
            htmlspecialchars($name, ENT_XML1),
            htmlspecialchars($name, ENT_XML1),
            $relationshipId,
        );
        $fragment = $document->createDocumentFragment();
        $fragment->appendXML($drawing);
        $paragraph->appendChild($fragment);
    }

    private function ensureDocxImageContentType(ZipArchive $zip, string $extension, string $mimeType): void
    {
        $contentTypes = new DOMDocument('1.0', 'UTF-8');
        $contentTypes->preserveWhiteSpace = false;
        $contentTypes->loadXML($zip->getFromName('[Content_Types].xml'));
        $xpath = new DOMXPath($contentTypes);
        $namespace = 'http://schemas.openxmlformats.org/package/2006/content-types';
        $xpath->registerNamespace('ct', $namespace);

        if ($xpath->query("/ct:Types/ct:Default[@Extension='{$extension}']")->length === 0) {
            $default = $contentTypes->createElementNS($namespace, 'Default');
            $default->setAttribute('Extension', $extension);
            $default->setAttribute('ContentType', $mimeType);
            $contentTypes->documentElement->appendChild($default);
            $zip->addFromString('[Content_Types].xml', $contentTypes->saveXML());
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
        $items = $pembelian->pembelianProducts->values()->map(function ($item, $index) {
            $itemContext = $this->itemContext(
                $item->product?->code,
                $item->product?->name,
                $item->qty,
                $item->product?->satuan,
                $item->harga_beli,
                $item->subtotal,
                $index + 1
            );
            $breakdown = $item->product
                ? $this->units->breakdown($item->product, $item->qty)
                : [
                    'qty' => (int) $item->qty,
                    'qty_besar' => 0,
                    'qty_terbesar' => 0,
                    'qty_total' => (int) $item->qty,
                ];

            return [
                ...$itemContext,
                ...$breakdown,
            ];
        })->all();

        $purchase = [
            'number' => $this->purchaseNumber($pembelian),
            'date' => $this->date($date),
            'date_serial' => $this->excelDate($date),
            'total' => (int) $pembelian->total,
            'subtotal' => (int) $pembelian->total,
            'old_debt' => 0,
            'shipping_cost' => 0,
            'payment' => (float) ($pembelian->pembelianTransaction?->amount ?? 0),
            'new_debt' => max(0, (float) $pembelian->total - (float) ($pembelian->pembelianTransaction?->amount ?? 0)),
            'location' => $this->locationFromAddress($company['address']),
            'items' => $items,
        ];
        $buyer = [
            'type' => 'Perusahaan',
            'name' => $company['name'],
            'address' => $company['address'],
            'phone' => $company['phone'],
        ];
        $sale = [
            'number' => $purchase['number'],
            'date' => $purchase['date'],
            'date_serial' => $purchase['date_serial'],
            'subtotal' => $purchase['subtotal'],
            'discount' => 0,
            'total' => $purchase['total'],
            'old_debt' => $purchase['old_debt'],
            'shipping_cost' => $purchase['shipping_cost'],
            'payment' => $purchase['payment'],
            'paid' => $purchase['payment'],
            'new_debt' => $purchase['new_debt'],
            'payment_type' => (string) ($pembelian->pembelianTransaction?->payment_method ?? '-'),
            'payment_status' => (string) ($pembelian->pembelianTransaction?->status ?? 'unpaid'),
            'items' => $items,
        ];

        $variables = $this->variables([
            'company' => $company,
            'purchase' => $purchase,
            'supplier' => $supplier,
            'buyer' => $buyer,
            'sale' => $sale,
            'items' => $items,
        ]);

        return compact('company', 'supplier', 'buyer', 'purchase', 'sale', 'items', 'variables');
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
                        $variables['{{'.$group.'.'.$key.'.'.$nestedKey.'}}'] = $this->formatTemplateValue(
                            $group.'.'.$key.'.'.$nestedKey,
                            $nestedValue,
                        );
                    }
                } else {
                    $variables['{{'.$group.'.'.$key.'}}'] = $this->formatTemplateValue(
                        $group.'.'.$key,
                        $value,
                    );
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
                $formattedValue = $this->formatTemplateValue($prefix.'.'.$key, $value);
                $variables['{{'.$prefix.'.'.($index + 1).'.'.$key.'}}'] = $formattedValue;

                if (! $addRowAliases) {
                    continue;
                }

                // Backward-compatible aliases for table rows and old custom templates.
                $aliasValue = $this->formatTemplateValue('item.'.$key, $value);
                $variables['{{item.'.($index + 1).'.'.$key.'}}'] = $aliasValue;
                $variables['{{items.'.$index.'.'.$key.'}}'] = $this->formatTemplateValue('items.'.$key, $value);
                if ($index === 0) {
                    $variables['{{item.'.$key.'}}'] = $aliasValue;
                }
            }
        }
    }

    private function formatTemplateValue(string $path, mixed $value): string
    {
        $rupiahPaths = [
            'purchase.items.price',
            'purchase.items.discount',
            'purchase.items.subtotal',
            'sale.items.price',
            'sale.items.discount',
            'sale.items.subtotal',
            'sale.subtotal',
            'sale.discount',
            'sale.total',
            'item.price',
            'item.discount',
            'item.subtotal',
            'items.price',
            'items.discount',
            'items.subtotal',
        ];

        if (in_array($path, $rupiahPaths, true)) {
            return 'Rp '.number_format((float) ($value ?? 0), 0, ',', '.');
        }

        return (string) ($value ?? '');
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
            'logo' => (string) ($settings['logo'] ?? ''),
            // Rendered as an image only when the admin places {{company.ttd}}.
            'ttd' => '',
            'nib' => (string) ($settings['nib'] ?? '-'),
            'nppbkc' => (string) ($settings['nppbkc'] ?? '086894235-071000-8120013020427'),
            'gol_pab' => (string) ($settings['gol_pab'] ?? 'III B'),
        ];
    }

    private function companyLogoPath(): ?string
    {
        $logoPath = $this->templates->settings()['logo'] ?? null;

        if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            return Storage::disk('public')->path($logoPath);
        }

        return null;
    }

    private function companySignaturePath(): ?string
    {
        $signaturePath = $this->templates->settings()['head_office_signature'] ?? null;

        if ($signaturePath && Storage::disk('public')->exists($signaturePath)) {
            return Storage::disk('public')->path($signaturePath);
        }

        return null;
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

    private function combineXlsxDocuments(array $paths): string
    {
        $combined = IOFactory::load($paths[0]);
        $targetSheet = $combined->getActiveSheet();
        $this->trimXlsxSheetToData($targetSheet);
        $nextRow = $targetSheet->getHighestDataRow() + 2;

        foreach ($combined->getAllSheets() as $sheet) {
            if ($sheet === $targetSheet) {
                continue;
            }

            $nextRow = $this->appendXlsxSection($targetSheet, $sheet, $nextRow) + 2;
            $combined->removeSheetByIndex($combined->getIndex($sheet));
        }

        foreach (array_slice($paths, 1) as $path) {
            $part = IOFactory::load($path);
            foreach ($part->getWorksheetIterator() as $sheet) {
                $nextRow = $this->appendXlsxSection($targetSheet, $sheet, $nextRow) + 2;
            }
            $part->disconnectWorksheets();
            unset($part);
        }

        $targetSheet->setTitle('Bulk PO');
        $targetSheet->getPageSetup()->setPrintArea(
            'A1:'.$targetSheet->getHighestDataColumn().$targetSheet->getHighestDataRow()
        );
        $combined->setActiveSheetIndex(0);
        $target = $this->temporaryPath('xlsx');
        (new Xlsx($combined))->save($target);
        $combined->disconnectWorksheets();

        return $target;
    }

    private function trimXlsxSheetToData(Worksheet $sheet): void
    {
        $highestDataRow = $sheet->getHighestDataRow();
        foreach ($sheet->getMergeCells() as $merge) {
            [, $end] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::rangeBoundaries($merge);
            if ($end[1] > $highestDataRow) {
                $sheet->unmergeCells($merge);
            }
        }

        $highestRow = $sheet->getHighestRow();
        if ($highestRow > $highestDataRow) {
            $sheet->removeRow($highestDataRow + 1, $highestRow - $highestDataRow);
        }
    }

    private function appendXlsxSection(Worksheet $target, Worksheet $source, int $startRow): int
    {
        $sourceEndRow = $source->getHighestDataRow();
        $rowOffset = $startRow - 1;

        foreach ($source->getRowIterator(1, $sourceEndRow) as $sourceRow) {
            $sourceRowIndex = $sourceRow->getRowIndex();
            $targetRowIndex = $sourceRowIndex + $rowOffset;
            $height = $source->getRowDimension($sourceRowIndex)->getRowHeight();
            if ($height > 0) {
                $target->getRowDimension($targetRowIndex)->setRowHeight($height);
            }

            $cellIterator = $sourceRow->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(true);
            foreach ($cellIterator as $sourceCell) {
                $targetCoordinate = $sourceCell->getColumn().$targetRowIndex;
                $target->duplicateStyle($sourceCell->getStyle(), $targetCoordinate);
                if ($sourceCell->getValue() !== null) {
                    $target->setCellValue($targetCoordinate, $sourceCell->getValue());
                }
            }
        }

        foreach ($source->getMergeCells() as $merge) {
            [$start, $end] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::rangeBoundaries($merge);
            if ($end[1] > $sourceEndRow) {
                continue;
            }

            $startColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($start[0]);
            $endColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($end[0]);
            $targetMerge = $startColumn.($start[1] + $rowOffset).':'
                .$endColumn.($end[1] + $rowOffset);
            if (! in_array($targetMerge, $target->getMergeCells(), true)) {
                $target->mergeCells($targetMerge);
            }
        }

        foreach ($source->getDrawingCollection() as $drawing) {
            $copy = clone $drawing;
            [$column, $row] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::coordinateFromString(
                $drawing->getCoordinates()
            );
            $copy->setCoordinates($column.((int) $row + $rowOffset));
            $copy->setWorksheet($target, true);
        }

        $target->setBreak('A'.$startRow, Worksheet::BREAK_ROW);

        return $startRow + $sourceEndRow - 1;
    }

    private function combineDocxDocuments(array $paths): string
    {
        if (count($paths) < 2) {
            return $paths[0];
        }

        $namespace = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
        $zip = new ZipArchive();
        abort_unless($zip->open($paths[0]) === true, 500, 'Dokumen DOCX tidak dapat digabungkan.');

        $document = new DOMDocument('1.0', 'UTF-8');
        $document->preserveWhiteSpace = false;
        $document->formatOutput = false;
        $document->loadXML($zip->getFromName('word/document.xml'));
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('w', $namespace);
        $body = $xpath->query('/w:document/w:body')->item(0);
        $sectionProperties = $xpath->query('./w:sectPr', $body)->item(0);

        foreach (array_slice($paths, 1) as $path) {
            $pageBreak = $document->createElementNS($namespace, 'w:p');
            $run = $document->createElementNS($namespace, 'w:r');
            $break = $document->createElementNS($namespace, 'w:br');
            $break->setAttributeNS($namespace, 'w:type', 'page');
            $run->appendChild($break);
            $pageBreak->appendChild($run);
            $body->insertBefore($pageBreak, $sectionProperties);

            $partZip = new ZipArchive();
            abort_unless($partZip->open($path) === true, 500, 'Dokumen DOCX tidak dapat dibuka.');
            $partDocument = new DOMDocument('1.0', 'UTF-8');
            $partDocument->preserveWhiteSpace = false;
            $partDocument->loadXML($partZip->getFromName('word/document.xml'));
            $partXpath = new DOMXPath($partDocument);
            $partXpath->registerNamespace('w', $namespace);
            $partBody = $partXpath->query('/w:document/w:body')->item(0);
            foreach ($partBody->childNodes as $child) {
                if ($child instanceof DOMElement && $child->nodeName === 'w:sectPr') {
                    continue;
                }

                $body->insertBefore($document->importNode($child, true), $sectionProperties);
            }
            $partZip->close();
        }

        $zip->addFromString('word/document.xml', $document->saveXML());
        $zip->close();

        return $paths[0];
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
