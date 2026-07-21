<?php

namespace App\Exports;

use App\Models\RefundPembelian;
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

class ReturOutletSingleExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithDrawings, WithCustomStartCell, WithProperties
{
    use Exportable;

    protected RefundPembelian $retur;
    protected array $settings;

    public function __construct(RefundPembelian $retur, array $settings = [])
    {
        $this->retur    = $retur;
        $this->settings = $settings;
    }

    public function collection()
    {
        $items = $this->retur->refundPembelianItems()->with('product', 'stock')->get();

        return RefundPembelian::groupItems($items);
    }

    public function headings(): array
    {
        return ['No', 'Kode Barang', 'Nama Barang', 'Qty', 'Satuan', 'Alasan'];
    }

    public function map($item): array
    {
        static $no = 0;
        $no++;

        $k = $item->product?->konversiDisplay($item->qty) ?? '-';

        return [
            $no,
            $item->product->code ?? '-',
            $item->product->name ?? '-',
            $item->qty.($k && $k !== '-' ? " ({$k})" : ''),
            $item->product->satuan ?? 'PCS',
            $item->alasan,
        ];
    }

    public function startCell(): string
    {
        return 'B17';
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
        $sheet->mergeCells('D2:G2');
        $sheet->getStyle('D2')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->setCellValue('D3', $address);
        $sheet->mergeCells('D3:G3');
        $sheet->setCellValue('D4', $contactInfo);
        $sheet->mergeCells('D4:G4');
        $sheet->getStyle('D3:D4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getRowDimension(5)->setRowHeight(20);

        $sheet->mergeCells('B6:G6');
        $sheet->getStyle('B6:G6')->getBorders()->getTop()->setBorderStyle(Border::BORDER_THICK);

        $sheet->setCellValue('B8', 'DOKUMEN RETUR OUTLET (OUTLET → GUDANG)');
        $sheet->mergeCells('B8:G8');
        $sheet->getStyle('B8')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->setCellValue('C10', 'Kode Retur :');
        $sheet->setCellValue('D10', $this->retur->code);
        $sheet->setCellValue('C11', 'Tanggal :');
        $sheet->setCellValue('D11', Carbon::parse($this->retur->tanggal)->isoFormat('DD MMMM YYYY'));
        $sheet->setCellValue('C12', 'Cabang :');
        $sheet->setCellValue('D12', $this->retur->outlet->name ?? '-');
        $sheet->setCellValue('C13', 'No. DO :');
        $sheet->setCellValue('D13', $this->retur->deliveryOrder->code ?? '-');
        $sheet->setCellValue('C14', 'Operator :');
        $sheet->setCellValue('D14', $this->retur->user->name ?? '-');
        $sheet->getStyle('C10:C14')->getFont()->setBold(true);

        $sheet->setCellValue('C16', 'Detail Item Retur');
        $sheet->getStyle('C16')->getFont()->setBold(true);

        $sheet->getStyle('B17:G17')->applyFromArray([
            'font'      => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '8EAADB']],
        ]);

        $highestRow = $sheet->getHighestRow();
        if ($highestRow > 17) {
            $sheet->getStyle('B18:G'.$highestRow)
                ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        }

        $sheet->getColumnDimension('B')->setWidth(5);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(30);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(10);
        $sheet->getColumnDimension('G')->setWidth(25);

        $sheet->getStyle('B')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $row = $highestRow + 4;
        $sheet->mergeCells('B'.$row.':C'.$row);
        $sheet->mergeCells('D'.$row.':E'.$row);
        $sheet->mergeCells('F'.$row.':G'.$row);
        $sheet->setCellValue('B'.$row, 'Dibuat Oleh');
        $sheet->setCellValue('D'.$row, 'Diperiksa Oleh');
        $sheet->setCellValue('F'.$row, 'Disetujui Oleh');
        $sheet->getStyle('B'.$row.':G'.$row)->applyFromArray([
            'font'      => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $row++;
        $sheet->mergeCells('B'.$row.':C'.$row);
        $sheet->mergeCells('D'.$row.':E'.$row);
        $sheet->mergeCells('F'.$row.':G'.$row);
        $sheet->setCellValue('B'.$row, 'Staff Cabang');
        $sheet->setCellValue('D'.$row, 'Supervisor');
        $sheet->setCellValue('F'.$row, 'Manager');
        $sheet->getStyle('B'.$row.':G'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $row += 5;
        $sheet->mergeCells('B'.$row.':C'.$row);
        $sheet->mergeCells('D'.$row.':E'.$row);
        $sheet->mergeCells('F'.$row.':G'.$row);
        $sheet->setCellValue('B'.$row, 'Nama');
        $sheet->setCellValue('D'.$row, 'Nama');
        $sheet->setCellValue('F'.$row, 'Nama');
        $sheet->getStyle('B'.$row.':G'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B'.($row - 1).':G'.($row - 1))
            ->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
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
            'creator'     => config('app.name'),
            'title'       => 'Dokumen Retur Cabang',
            'description' => 'Retur '.$this->retur->code,
        ];
    }
}
