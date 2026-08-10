<?php

namespace App\Exports;

use App\Models\PembelianProduct;
use App\Models\Product;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class StockExport
{
    protected string $dateFrom;

    protected string $dateTo;

    protected array $settings;

    public function __construct(string $dateFrom, string $dateTo, array $settings = [])
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->settings = $settings;
    }

    public function store(string $outputPath): string
    {
        $templatePath = base_path('rekap_stok_harian.xlsx');
        abort_unless(is_file($templatePath), 500, 'Template rekap stok harian tidak ditemukan.');

        $workbook = IOFactory::load($templatePath);
        $sheet = $workbook->getSheetByName('Rekap Stok') ?: $workbook->getActiveSheet();
        $data = $this->buildData();

        $this->prepareProductColumns($sheet, $data['products']);
        $totalRow = $this->prepareCustomerRows($sheet, $data['rows']);
        $this->writeHeader($sheet, $data['products']);
        $this->writeRows($sheet, $data['products'], $data['rows'], $totalRow);
        $this->writePeriodAndCompany($sheet, $data['products']);
        $this->styleTable($sheet, $data['products'], $totalRow);
        $this->writeStockSummary($sheet, $data['products'], $totalRow);

        $writer = new Xlsx($workbook);
        $writer->setPreCalculateFormulas(true);
        $writer->save($outputPath);

        return $outputPath;
    }

    protected function buildData(): array
    {
        $lines = PembelianProduct::with(['product', 'pembelian'])
            ->whereHas('pembelian', function ($query) {
                $query->whereDate('created_at', '>=', $this->dateFrom)
                    ->whereDate('created_at', '<=', $this->dateTo);
            })
            ->orderBy('product_id')
            ->get();

        $products = [];
        $matrix = [];

        foreach ($lines as $line) {
            $product = $line->product;
            if (! $product) {
                continue;
            }

            $products[$product->id] = $product;
            $customerPo = trim((string) ($line->pembelian?->customer_po ?? '')) ?: 'Tanpa Customer PO';
            $matrix[$customerPo][$product->id] = ($matrix[$customerPo][$product->id] ?? 0) + (float) ($line->qty ?? 0);
        }

        if ($products === []) {
            Product::orderBy('name')->get()->each(function (Product $product) use (&$products) {
                $products[$product->id] = $product;
            });
        }

        $products = collect($products)->sortBy('name')->values()->all();
        $rows = collect($matrix)
            ->sortKeys()
            ->map(fn (array $quantities, string $customerPo) => [
                'name' => $customerPo,
                'quantities' => $quantities,
            ])
            ->values()
            ->all();

        if ($rows === []) {
            $rows[] = ['name' => 'Tidak ada data', 'quantities' => []];
        }

        return compact('products', 'rows');
    }

    protected function prepareProductColumns($sheet, array $products): void
    {
        $templateProductCount = 5;
        $productCount = max(1, count($products));

        for ($index = 0; $index < $templateProductCount; $index++) {
            $start = 3 + ($index * 3);
            $end = $start + 2;
            $range = Coordinate::stringFromColumnIndex($start).'5:'.Coordinate::stringFromColumnIndex($end).'5';
            if (in_array($range, $sheet->getMergeCells(), true)) {
                $sheet->unmergeCells($range);
            }
        }

        if ($productCount > $templateProductCount) {
            $sheet->insertNewColumnBefore('R', ($productCount - $templateProductCount) * 3);
        }

        // The template also contains the old summary area to the right of the
        // customer table. It is rendered below the table now, so remove every
        // column after the last product instead of leaving an empty tail.
        $removeFromIndex = 3 + ($productCount * 3);
        $highestColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        if ($highestColumnIndex >= $removeFromIndex) {
            $sheet->removeColumn(
                Coordinate::stringFromColumnIndex($removeFromIndex),
                $highestColumnIndex - $removeFromIndex + 1
            );
        }
    }

    protected function prepareCustomerRows($sheet, array $rows): int
    {
        if (in_array('A56:B56', $sheet->getMergeCells(), true)) {
            $sheet->unmergeCells('A56:B56');
        }

        $templateRowCount = 49;
        $rowCount = max(1, count($rows));
        if ($rowCount > $templateRowCount) {
            $sheet->insertNewRowBefore(56, $rowCount - $templateRowCount);
        } elseif ($rowCount < $templateRowCount) {
            $sheet->removeRow(7 + $rowCount, $templateRowCount - $rowCount);
        }

        return 7 + $rowCount;
    }

    protected function writeHeader($sheet, array $products): void
    {
        foreach ($products as $index => $product) {
            $start = 3 + ($index * 3);
            $end = $start + 2;
            $startColumn = Coordinate::stringFromColumnIndex($start);
            $endColumn = Coordinate::stringFromColumnIndex($end);

            $sheet->setCellValue($startColumn.'5', $product->name);
            $sheet->mergeCells($startColumn.'5:'.$endColumn.'5');
            $sheet->setCellValue($startColumn.'6', $product->satuan ?: 'PCS');
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($start + 1).'6', $product->satuan_besar ?: '-');
            $sheet->setCellValue($endColumn.'6', $product->satuan_terbesar ?: '-');
        }
    }

    protected function writeRows($sheet, array $products, array $rows, int $totalRow): void
    {
        $lastProductColumn = Coordinate::stringFromColumnIndex(2 + (count($products) * 3));

        foreach ($rows as $rowIndex => $row) {
            $excelRow = 7 + $rowIndex;
            $sheet->setCellValue('A'.$excelRow, $rowIndex + 1);
            $sheet->setCellValue('B'.$excelRow, $row['name']);

            foreach ($products as $productIndex => $product) {
                $qty = (float) ($row['quantities'][$product->id] ?? 0);
                $values = $this->equivalentQuantities($product, $qty);
                $start = 3 + ($productIndex * 3);

                foreach ($values as $offset => $value) {
                    $sheet->setCellValueByColumnAndRow($start + $offset, $excelRow, $value);
                }
            }
        }

        $sheet->setCellValue('A'.$totalRow, 'TOTAL');
        $sheet->mergeCells('A'.$totalRow.':B'.$totalRow);
        for ($column = 3; $column <= 2 + (count($products) * 3); $column++) {
            $letter = Coordinate::stringFromColumnIndex($column);
            $sheet->setCellValue($letter.$totalRow, '=SUM('.$letter.'7:'.$letter.($totalRow - 1).')');
        }

        $sheet->setAutoFilter('A6:'.$lastProductColumn.($totalRow - 1));
        $sheet->freezePane('C7');
    }

    protected function writeStockSummary($sheet, array $products, int $totalRow): void
    {
        $summaryHeaderRow = $totalRow + 4;
        $unitsRow = $summaryHeaderRow + 3;
        $firstDataRow = $unitsRow + 1;

        $from = Carbon::parse($this->dateFrom)->locale('id');
        $to = Carbon::parse($this->dateTo)->locale('id');
        $dateLabel = $from->translatedFormat('d F Y').' '.mb_strtoupper($from->translatedFormat('l'), 'UTF-8');
        if (! $from->isSameDay($to)) {
            $dateLabel .= ' s/d '.$to->translatedFormat('d F Y').' '.mb_strtoupper($to->translatedFormat('l'), 'UTF-8');
        }

        $sheet->setCellValue('B'.$summaryHeaderRow, $dateLabel);
        $sheet->mergeCells('B'.$summaryHeaderRow.':E'.$summaryHeaderRow);

        $firstProduct = $products[0];
        $sheet->setCellValue('C'.$unitsRow, $firstProduct->satuan ?: 'Pack');
        $sheet->setCellValue('D'.$unitsRow, $firstProduct->satuan_besar ?: 'Slop');
        $sheet->setCellValue('E'.$unitsRow, $firstProduct->satuan_terbesar ?: 'Ball');

        $sections = ['Stok Awal', 'Produksi', 'Retur', 'Total', 'Penjualan', 'Sisa Stok'];
        $row = $firstDataRow;
        foreach ($sections as $section) {
            foreach ($products as $productIndex => $product) {
                $sheet->setCellValue('A'.$row, $productIndex === 0 ? $section : '');
                $sheet->setCellValue('B'.$row, $product->name);

                if ($section === 'Produksi') {
                    $startColumn = 3 + ($productIndex * 3);
                    for ($offset = 0; $offset < 3; $offset++) {
                        $sourceColumn = Coordinate::stringFromColumnIndex($startColumn + $offset);
                        $summaryColumn = Coordinate::stringFromColumnIndex(3 + $offset);
                        $sheet->setCellValue($summaryColumn.$row, '='.$sourceColumn.$totalRow);
                    }
                }

                $row++;
            }
        }

        $lastSummaryRow = $row - 1;
        $sheet->getStyle('A'.$summaryHeaderRow.':E'.$summaryHeaderRow)->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9EAF7']],
        ]);
        $sheet->getStyle('A'.$unitsRow.':E'.$unitsRow)->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9EAF7']],
        ]);
        $sheet->getStyle('A'.$firstDataRow.':E'.$lastSummaryRow)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle('A'.$firstDataRow.':A'.$lastSummaryRow)->getFont()->setBold(true);
        $sheet->getStyle('C'.$firstDataRow.':E'.$lastSummaryRow)
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Keep the summary aligned with the first three unit columns of the
        // customer table, even when the report contains more products.
        $sheet->getColumnDimension('A')->setWidth(12);
        $sheet->getColumnDimension('B')->setWidth(24);
        foreach (['C', 'D', 'E'] as $column) {
            $sheet->getColumnDimension($column)->setWidth(12);
        }
    }

    protected function writePeriodAndCompany($sheet, array $products): void
    {
        $companyName = $this->settings['name'] ?? 'NAMA PERUSAHAAN';
        $address = $this->settings['address'] ?? '';
        $phone = $this->settings['telp'] ?? '';
        $sheet->setCellValue('A1', trim($companyName."\n".$address."\n".$phone));
        $sheet->getStyle('A1')->getAlignment()->setWrapText(true);

        $lastColumn = Coordinate::stringFromColumnIndex(2 + (count($products) * 3));
        $periodRange = 'A4:'.$lastColumn.'4';
        if (in_array($periodRange, $sheet->getMergeCells(), true)) {
            $sheet->unmergeCells($periodRange);
        }
        $sheet->mergeCells($periodRange);
        $sheet->setCellValue('A4', 'Periode: '.Carbon::parse($this->dateFrom)->format('d/m/Y').' s/d '.Carbon::parse($this->dateTo)->format('d/m/Y'));
        $sheet->getStyle('A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A4')->getFont()->setBold(true);
    }

    protected function styleTable($sheet, array $products, int $totalRow): void
    {
        $lastColumn = Coordinate::stringFromColumnIndex(2 + (count($products) * 3));
        $sheet->getStyle('A5:'.$lastColumn.'6')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9EAF7']],
        ]);
        $sheet->getStyle('A7:'.$lastColumn.$totalRow)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $sheet->getStyle('A'.$totalRow.':'.$lastColumn.$totalRow)->getFont()->setBold(true);
        $sheet->getStyle('A'.$totalRow.':'.$lastColumn.$totalRow)->getFill()->setFillType(Fill::FILL_SOLID);
        $sheet->getStyle('A'.$totalRow.':'.$lastColumn.$totalRow)->getFill()->getStartColor()->setRGB('FFF2CC');

        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(24);
        for ($column = 3; $column <= 2 + (count($products) * 3); $column++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setWidth(12);
        }
        $sheet->getStyle('A:'.$lastColumn)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A:A')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('C:'.$lastColumn)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    }

    protected function equivalentQuantities(Product $product, float $qty): array
    {
        $bigFactor = (float) ($product->konversi_qty ?: 0);
        $largestFactor = $bigFactor && $product->konversi_qty_terbesar
            ? $bigFactor * (float) $product->konversi_qty_terbesar
            : 0;

        return [
            $this->numericValue($qty),
            $this->numericValue($bigFactor > 0 ? $qty / $bigFactor : 0),
            $this->numericValue($largestFactor > 0 ? $qty / $largestFactor : 0),
        ];
    }

    protected function numericValue(float $value): int|float
    {
        $rounded = round($value, 4);

        return fmod($rounded, 1.0) === 0.0 ? (int) $rounded : $rounded;
    }
}
