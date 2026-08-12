<?php

namespace App\Exports;

use App\Models\Stock;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class KartuStokExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithDrawings, WithCustomStartCell, WithProperties
{
    use Exportable;

    protected Stock $stock;

    protected array $transactions = [];

    protected array $settings;

    protected string $supplierName = '-';

    public function __construct(Stock $stock, $movements, array $settings = [])
    {
        $this->stock = $stock->loadMissing(['product', 'pembelian.supplier']);
        $this->settings = $settings;

        $runningStock = 0;
        $currentPrice = (float) ($this->stock->harga_beli ?? $this->stock->product?->harga_beli ?? 0);

        foreach ($movements as $movement) {
            $stokAwal = $runningStock;
            $masuk = (float) ($movement->qty_in ?? 0);
            $keluar = (float) ($movement->qty_out ?? 0);
            $stokAkhir = $stokAwal + $masuk - $keluar;
            $supplierName = null;

            if ($masuk > 0) {
                $relatedStock = Stock::with('pembelian.supplier')
                    ->where('product_id', $this->stock->product_id)
                    ->where('created_at', '<=', $movement->created_at)
                    ->orderByDesc('id')
                    ->first();

                if ($relatedStock) {
                    $currentPrice = (float) ($relatedStock->harga_beli ?? $currentPrice);
                    $supplierName = $relatedStock->pembelian?->supplier?->name;
                    if ($supplierName) {
                        $this->supplierName = $supplierName;
                    }
                }
            }

            $keterangan = trim((string) ($movement->notes ?? '')) ?: '-';
            if ($supplierName) {
                $keterangan = 'Supplier: '.$supplierName.' | '.$keterangan;
            }

            $this->transactions[] = [
                'no' => count($this->transactions) + 1,
                'tanggal' => optional($movement->created_at)->format('Y-m-d'),
                'stok_awal' => $stokAwal,
                'masuk' => $masuk,
                'keluar' => $keluar,
                'stok_akhir' => $stokAkhir,
                'harga' => $currentPrice,
                'nilai' => $stokAkhir * $currentPrice,
                'keterangan' => $keterangan,
            ];

            $runningStock = $stokAkhir;
        }

        if ($this->supplierName === '-' && $this->stock->pembelian?->supplier) {
            $this->supplierName = $this->stock->pembelian->supplier->name;
        }
    }

    public function collection()
    {
        return collect($this->transactions);
    }

    public function headings(): array
    {
        return [
            'No',
            'Tanggal',
            'Stok Awal',
            'Masuk',
            'Keluar',
            'Stok Akhir',
            'Harga Satuan (Rp)',
            'Nilai Persediaan',
            'Keterangan',
        ];
    }

    public function map($row): array
    {
        return [
            $row['no'],
            $row['tanggal'],
            $this->formatQty($row['stok_awal']),
            $this->formatQty($row['masuk']),
            $this->formatQty($row['keluar']),
            $this->formatQty($row['stok_akhir']),
            $row['harga'],
            $row['nilai'],
            $row['keterangan'],
        ];
    }

    public function startCell(): string
    {
        return 'B15';
    }

    public function styles(Worksheet $sheet)
    {
        $companyName = $this->settings['name'] ?? 'NAMA PERUSAHAAN';
        $address = $this->settings['address'] ?? 'ALAMAT';
        $phone = $this->settings['telp'] ?? '';
        $email = $this->settings['email'] ?? '';
        $website = $this->settings['website'] ?? '';
        $contactInfo = trim("$phone | $email | $website", ' |');

        $sheet->getRowDimension(1)->setRowHeight(50);
        $sheet->setCellValue('D2', $companyName);
        $sheet->mergeCells('D2:J2');
        $sheet->getStyle('D2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->setCellValue('D3', $address);
        $sheet->mergeCells('D3:J3');
        $sheet->setCellValue('D4', $contactInfo);
        $sheet->mergeCells('D4:J4');
        $sheet->getStyle('D3:D4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('B6:J6');
        $sheet->getStyle('B6:J6')->getBorders()->getTop()->setBorderStyle(Border::BORDER_THICK);
        $sheet->setCellValue('B8', 'KARTU STOK BARANG');
        $sheet->mergeCells('B8:J8');
        $sheet->getStyle('B8')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        foreach ([
            10 => ['Kode Barang', $this->stock->product?->code ?? '-'],
            11 => ['Nama Barang', $this->stock->product?->name ?? '-'],
            12 => ['Supplier', $this->supplierName],
        ] as $row => [$label, $value]) {
            $sheet->setCellValue('B'.$row, $label);
            $sheet->setCellValue('C'.$row, ':');
            $sheet->setCellValue('D'.$row, $value);
            $sheet->mergeCells('D'.$row.':J'.$row);
        }
        $sheet->getStyle('B10:B12')->getFont()->setBold(true);
        $sheet->getStyle('C10:C12')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D10:J12')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->setCellValue('B14', 'Detail Mutasi Stok');
        $sheet->getStyle('B14')->getFont()->setBold(true);

        $sheet->getStyle('B15:J15')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '8EAADB']],
        ]);

        $lastDataRow = 15 + count($this->transactions);
        if ($lastDataRow > 15) {
            $sheet->getStyle('B16:J'.$lastDataRow)
                ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle('B'.$lastDataRow.':J'.$lastDataRow)
                ->getBorders()->getBottom()->setBorderStyle(Border::BORDER_NONE);
            $sheet->getStyle('D16:G'.$lastDataRow)->getAlignment()->setWrapText(true);
            $sheet->getStyle('J16:J'.$lastDataRow)->getAlignment()->setWrapText(true);
            $sheet->getStyle('H16:I'.$lastDataRow)
                ->getNumberFormat()
                ->setFormatCode('"Rp" #,##0');

            foreach ($this->transactions as $index => $transaction) {
                $row = 16 + $index;
                $lineCount = max(
                    $this->wrappedLineCount($this->formatQty($transaction['stok_awal']), 14),
                    $this->wrappedLineCount($this->formatQty($transaction['masuk']), 14),
                    $this->wrappedLineCount($this->formatQty($transaction['keluar']), 14),
                    $this->wrappedLineCount($this->formatQty($transaction['stok_akhir']), 14),
                    $this->wrappedLineCount((string) $transaction['keterangan'], 34),
                );
                $sheet->getRowDimension($row)->setRowHeight(max(20, 15 * $lineCount));
            }
        }

        foreach (['B' => 6, 'C' => 14, 'D' => 13, 'E' => 13, 'F' => 13, 'G' => 13, 'H' => 16, 'I' => 18, 'J' => 34] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
        $sheet->getStyle('B:J')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('B:B')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D:I')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $summaryRow = $lastDataRow + 2;
        $totalMasuk = collect($this->transactions)->sum('masuk');
        $totalKeluar = collect($this->transactions)->sum('keluar');
        $stokAwal = $this->transactions[0]['stok_awal'] ?? 0;
        $stokAkhir = $this->transactions[count($this->transactions) - 1]['stok_akhir'] ?? 0;
        $sheet->setCellValue('B'.$summaryRow, 'Stok Awal :');
        $sheet->setCellValue('D'.$summaryRow, $this->formatQty($stokAwal));
        $sheet->setCellValue('F'.$summaryRow, 'Total Keluar :');
        $sheet->setCellValue('H'.$summaryRow, $this->formatQty($totalKeluar));
        $sheet->setCellValue('B'.($summaryRow + 1), 'Total Masuk :');
        $sheet->setCellValue('D'.($summaryRow + 1), $this->formatQty($totalMasuk));
        $sheet->setCellValue('F'.($summaryRow + 1), 'Total Akhir :');
        $sheet->setCellValue('H'.($summaryRow + 1), $this->formatQty($stokAkhir));
        $sheet->getStyle('B'.$summaryRow.':J'.($summaryRow + 1))
            ->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
    }

    protected function formatQty(float|int $qty): string
    {
        $product = $this->stock->product;
        if (! $product) {
            return (string) $qty;
        }

        return str_replace(' | ', "\n", $product->stockSummaryDisplay($qty));
    }

    protected function wrappedLineCount(string $value, int $width): int
    {
        $lines = 0;
        foreach (preg_split('/\R/', $value) ?: [''] as $line) {
            $lines += max(1, (int) ceil(mb_strlen($line) / $width));
        }

        return max(1, $lines);
    }

    public function drawings()
    {
        $drawing = new Drawing();
        $drawing->setName('Logo');
        $drawing->setDescription('Logo');
        $logoPath = $this->settings['logo'] ?? null;

        if ($logoPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($logoPath)) {
            $drawing->setPath(\Illuminate\Support\Facades\Storage::disk('public')->path($logoPath));
        } else {
            $drawing->setPath(public_path('img/logo.jpeg'));
        }

        $drawing->setHeight(80);
        $drawing->setCoordinates('B2');

        return [$drawing];
    }

    public function properties(): array
    {
        return [
            'creator' => config('app.name'),
            'title' => 'Kartu Stok',
            'description' => 'Kartu Stok '.($this->stock->product?->code ?? ''),
        ];
    }
}
