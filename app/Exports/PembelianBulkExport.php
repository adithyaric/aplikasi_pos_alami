<?php

namespace App\Exports;

use App\Models\Pembelian;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PembelianBulkExport implements FromView, WithColumnWidths, WithEvents, WithTitle
{
    private Collection $invoices;

    private array $company;

    public function __construct(Collection $pembelians, array $settings = [])
    {
        $pembelians->each(
            fn (Pembelian $pembelian) => $pembelian->loadMissing([
                'supplier',
                'pembelianProducts.product',
                'pembelianTransaction',
            ])
        );

        $this->company = [
            'name' => (string) ($settings['name'] ?? 'NAMA PERUSAHAAN'),
            'address' => (string) ($settings['address'] ?? '-'),
            'phone' => (string) ($settings['telp'] ?? '-'),
            'payment' => (string) ($settings['payment'] ?? $settings['bank'] ?? ''),
        ];
        $this->invoices = $pembelians->values()->map(
            fn (Pembelian $pembelian, int $index) => $this->invoiceData($pembelian, $index + 1)
        );
    }

    public function view(): View
    {
        return view('exports.pembelian-bulk', [
            'company' => $this->company,
            'invoices' => $this->invoices,
        ]);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 3,
            'B' => 9,
            'C' => 18,
            'D' => 3,
            'E' => 36,
            'F' => 18,
            'G' => 18,
            'H' => 20,
            'I' => 3,
            'J' => 20,
            'K' => 3,
        ];
    }

    public function title(): string
    {
        return 'Bulk PO';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $sections = $this->sections();

                foreach ($sections as $index => $section) {
                    $this->styleSection($sheet, $section);
                    if ($index > 0) {
                        $sheet->setBreak('A'.$section['start'], Worksheet::BREAK_ROW);
                    }
                }

                $lastRow = $sections ? end($sections)['end'] : 1;
                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_PORTRAIT)
                    ->setPaperSize(PageSetup::PAPERSIZE_A4)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);
                $sheet->getPageMargins()
                    ->setTop(0.25)
                    ->setRight(0.25)
                    ->setBottom(0.25)
                    ->setLeft(0.25);
                $sheet->setShowGridlines(false);
                $sheet->getPageSetup()->setPrintArea('A1:K'.$lastRow);
            },
        ];
    }

    private function invoiceData(Pembelian $pembelian, int $sequence): array
    {
        $total = (float) ($pembelian->total ?? 0);
        $payment = (float) ($pembelian->pembelianTransaction?->amount ?? 0);
        $items = $pembelian->pembelianProducts->values()->map(
            fn ($item, int $index) => [
                'no' => $index + 1,
                'code' => (string) ($item->product?->code ?? ''),
                'name' => (string) ($item->product?->name ?? '-'),
                'qty' => (float) ($item->qty ?? 0),
                'unit' => (string) ($item->product?->satuan ?? 'PCS'),
                'discount' => 0,
                'price' => (float) ($item->harga_beli ?? 0),
                'subtotal' => (float) ($item->subtotal ?? 0),
            ]
        )->all();

        return [
            'sequence' => $sequence,
            'number' => (string) ($pembelian->code ?? ''),
            'date' => Carbon::parse($pembelian->created_at ?: now())
                ->locale('id')
                ->translatedFormat('d F Y'),
            'buyer' => $this->company,
            'items' => $items,
            'subtotal' => $total,
            'old_debt' => 0,
            'shipping_cost' => 0,
            'payment' => $payment,
            'new_debt' => max(0, $total - $payment),
        ];
    }

    private function sections(): array
    {
        $row = 1;
        $sections = [];

        foreach ($this->invoices as $invoice) {
            $itemRows = max(1, count($invoice['items']));
            $itemStart = $row + 10;
            $itemEnd = $itemStart + $itemRows - 1;
            $end = $itemEnd + 15;
            $sections[] = [
                'start' => $row,
                'header' => $row + 9,
                'item_start' => $itemStart,
                'item_end' => $itemEnd,
                'end' => $end,
            ];
            $row = $end + 1;
        }

        return $sections;
    }

    private function styleSection(Worksheet $sheet, array $section): void
    {
        $thinBorder = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ];
        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFEFEFEF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ];

        $sheet->getStyle('B'.$section['start'].':J'.$section['start'])->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);
        $sheet->getStyle('B'.($section['start'] + 1).':J'.($section['start'] + 1))
            ->getFont()->setBold(true);
        $sheet->getStyle('B'.$section['header'].':J'.$section['header'])
            ->applyFromArray([...$thinBorder, ...$headerStyle]);
        $sheet->getStyle('B'.$section['item_start'].':J'.$section['item_end'])
            ->applyFromArray($thinBorder);
        $sheet->getStyle('B'.$section['item_start'].':J'.$section['item_end'])
            ->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('H'.$section['item_start'].':H'.$section['item_end'])
            ->getNumberFormat()->setFormatCode('#,##0.##');
        $sheet->getStyle('J'.$section['item_start'].':J'.$section['item_end'])
            ->getNumberFormat()->setFormatCode('#,##0.##');
        $sheet->getStyle('J'.($section['item_end'] + 3).':J'.($section['item_end'] + 7))
            ->getNumberFormat()->setFormatCode('#,##0.##');
        $sheet->getStyle('B'.$section['header'].':J'.$section['item_end'])
            ->getAlignment()->setWrapText(true);
        $sheet->getStyle('B'.($section['item_end'] + 10).':J'.($section['item_end'] + 10))
            ->getFont()->setBold(true);
    }
}
