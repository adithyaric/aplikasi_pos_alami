<?php

namespace App\Exports;

use App\Models\Pembelian;
use Carbon\Carbon;
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

class PembelianSingleExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithDrawings, WithCustomStartCell, WithProperties
{
    use Exportable;

    protected $pembelian;
    protected $settings;

    public function __construct(Pembelian $pembelian, array $settings = [])
    {
        $this->pembelian = $pembelian;
        $this->settings = $settings;
    }

    public function collection()
    {
        return $this->pembelian->pembelianProducts;
    }

    public function headings(): array
    {
        return [
            'No',
            'Kode Barang',
            'Nama Barang',
            'Qty',
            'Satuan',
            'Harga',
            'Total',
        ];
    }

    public function map($item): array
    {
        static $no = 0;
        $no++;

        $k = $item->product->konversiDisplay($item->qty);

        return [
            $no,
            $item->product->code ?? '',
            $item->product->name ?? '',
            $item->qty.($k && $k !== '-' ? " ({$k})" : ''),
            $item->product->satuan ?? 'PCS',
            'Rp '.number_format($item->harga_beli, 0, ',', '.'),
            'Rp '.number_format($item->subtotal, 0, ',', '.'),
        ];
    }

    public function startCell(): string
    {
        return 'B14';
    }

    public function styles(Worksheet $sheet)
    {
        $companyName = $this->settings['name'] ?? 'NAMA PERUSAHAAN';
        $address     = $this->settings['address'] ?? 'ALAMAT';
        $phone       = $this->settings['telp'] ?? '';
        $email       = $this->settings['email'] ?? '';
        $website     = $this->settings['website'] ?? '';
        $contactInfo = trim("$phone | $email | $website", ' |');

        $sheet->getRowDimension(1)->setRowHeight(50);

        $sheet->setCellValue('D2', $companyName);
        $sheet->mergeCells('D2:H2');
        $sheet->getStyle('D2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->setCellValue('D3', $address);
        $sheet->mergeCells('D3:H3');
        $sheet->setCellValue('D4', $contactInfo);
        $sheet->mergeCells('D4:H4');
        $sheet->getStyle('D2:D4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getRowDimension(5)->setRowHeight(20);

        $sheet->mergeCells('B6:H6');
        $sheet->getStyle('B6:H6')->getBorders()->getTop()->setBorderStyle(Border::BORDER_THICK);

        $sheet->setCellValue('B8', 'PURCHASE ORDER (PO)');
        $sheet->mergeCells('B8:H8');
        $sheet->getStyle('B8')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->setCellValue('C10', 'Kode PO :');
        $sheet->setCellValue('D10', $this->pembelian->code);
        $sheet->setCellValue('C11', 'Tanggal PO :');
        $sheet->setCellValue('D11', Carbon::parse($this->pembelian->created_at)->isoFormat('DD MMMM YYYY'));
        $sheet->setCellValue('C12', 'Nama Supplier :');
        $sheet->setCellValue('D12', $this->pembelian->supplier->name ?? '');

        $sheet->getStyle('B14:H14')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '8EAADB']],
        ]);

        $highestRow = $sheet->getHighestRow();
        if ($highestRow > 14) {
            $sheet->getStyle('B15:H'.$highestRow)
                ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        }

        $sheet->getColumnDimension('B')->setWidth(5);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(30);
        $sheet->getColumnDimension('E')->setWidth(12);
        $sheet->getColumnDimension('F')->setWidth(10);
        $sheet->getColumnDimension('G')->setWidth(16);
        $sheet->getColumnDimension('H')->setWidth(18);

        $sheet->getStyle('E')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('G')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('H')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $totalRow = $highestRow + 1;
        $sheet->setCellValue('D'.$totalRow, 'Total');
        $sheet->setCellValue('H'.$totalRow, 'Rp '.number_format($this->pembelian->total, 0, ',', '.'));
        $sheet->getStyle('B'.$totalRow.':H'.$totalRow)->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM);
        $sheet->getStyle('B'.$totalRow.':H'.$totalRow)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('B'.$totalRow.':H'.$totalRow)->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('B'.$totalRow.':H'.$totalRow)->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('D'.$totalRow)->getFont()->setBold(true);
        $sheet->getStyle('H'.$totalRow)->getFont()->setBold(true);
        $sheet->getStyle('D'.$totalRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('H'.$totalRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $row = $totalRow + 3;
        $sheet->mergeCells('B'.$row.':D'.$row);
        $sheet->mergeCells('G'.$row.':H'.$row);
        $sheet->setCellValue('B'.$row, 'Dibuat Oleh');
        $sheet->setCellValue('G'.$row, 'Disetujui Oleh');
        $sheet->getStyle('B'.$row.':H'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $row++;
        $sheet->mergeCells('B'.$row.':D'.$row);
        $sheet->mergeCells('G'.$row.':H'.$row);
        $sheet->setCellValue('B'.$row, 'Staff Gudang');
        $sheet->setCellValue('G'.$row, 'Manager');
        $sheet->getStyle('B'.$row.':D'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('G'.$row.':H'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $row += 5;
        $sheet->mergeCells('B'.$row.':D'.$row);
        $sheet->mergeCells('G'.$row.':H'.$row);
        $sheet->setCellValue('B'.$row, 'Nama');
        $sheet->setCellValue('G'.$row, 'Nama');
        $sheet->getStyle('B'.($row - 1).':H'.($row - 1))
            ->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
    }

    public function drawings()
    {
        $drawing = new Drawing();
        $drawing->setName('Logo');
        $drawing->setDescription('Logo');

        // Use stored logo path or fallback to default
        $logoPath = $this->settings['logo'] ?? null;
        if ($logoPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($logoPath)) {
            $drawing->setPath(\Illuminate\Support\Facades\Storage::disk('public')->path($logoPath));
        } else {
            $drawing->setPath(public_path('img/logo.jpeg')); // fallback
        }

        $drawing->setHeight(80);
        $drawing->setCoordinates('B2');

        return [$drawing];
    }

    public function properties(): array
    {
        return [
            'creator' => config('app.name'),
            'title' => 'Purchase Order',
            'description' => 'PO '.$this->pembelian->code,
        ];
    }
}