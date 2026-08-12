<?php

namespace App\Exports;

use App\Models\Pembelian;
use App\Support\ProductUnitConverter;
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
            'email' => (string) ($settings['email'] ?? '-'),
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
            'B' => 11,
            'C' => 14,
            'D' => 3,
            'E' => 14,
            'F' => 12,
            'G' => 14,
            'H' => 14,
            'I' => 3,
            'J' => 11,
            'K' => 14,
            'L' => 3,
            'M' => 14,
            'N' => 12,
            'O' => 14,
            'P' => 14,
            'Q' => 3,
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
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPaperSize(PageSetup::PAPERSIZE_A4)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0)
                    ->setHorizontalCentered(true);
                $sheet->getPageMargins()
                    ->setTop(0.25)
                    ->setRight(0.25)
                    ->setBottom(0.25)
                    ->setLeft(0.25);
                $sheet->setShowGridlines(false);
                $sheet->getPageSetup()->setPrintArea('A1:Q'.$lastRow);
            },
        ];
    }

    private function invoiceData(Pembelian $pembelian, int $sequence): array
    {
        $unitConverter = app(ProductUnitConverter::class);
        $items = $pembelian->pembelianProducts->values()->map(
            function ($item, int $index) use ($unitConverter): array {
                $breakdown = $item->product
                    ? $unitConverter->breakdown($item->product, $item->qty)
                    : [
                        'qty' => (int) round((float) ($item->qty ?? 0)),
                        'qty_besar' => 0,
                        'qty_terbesar' => 0,
                    ];

                return [
                    'no' => $index + 1,
                    'name' => (string) ($item->product?->name ?? '-'),
                    ...$breakdown,
                ];
            }
        )->all();

        return [
            'sequence' => $sequence,
            'number' => (string) ($pembelian->code ?? ''),
            'date' => Carbon::parse($pembelian->created_at ?: now())
                ->locale('id')
                ->translatedFormat('d F Y'),
            'supplier' => [
                'name' => (string) ($pembelian->supplier?->name ?? '-'),
                'address' => (string) ($pembelian->supplier?->alamat ?? '-'),
                'phone' => (string) ($pembelian->supplier?->no_telp ?? '-'),
            ],
            'items' => $items,
        ];
    }

    private function sections(): array
    {
        $row = 1;
        $sections = [];

        foreach ($this->invoices as $invoice) {
            $itemRows = max(1, count($invoice['items']));
            $itemStart = $row + 16;
            $itemEnd = $itemStart + $itemRows - 1;
            $end = $itemEnd + 6;
            $sections[] = [
                'start' => $row,
                'title' => $row + 1,
                'meta' => $row + 4,
                'vendor' => $row + 7,
                'header' => $row + 14,
                'units' => $row + 15,
                'item_start' => $itemStart,
                'item_end' => $itemEnd,
                'footer' => $itemEnd + 2,
                'phone' => $itemEnd + 5,
                'end' => $end,
            ];
            $row = $end + 1;
        }

        return $sections;
    }

    private function styleSection(Worksheet $sheet, array $section): void
    {
        $panelBorder = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ];
        $bodyStyle = [
            'font' => ['name' => 'Arial', 'size' => 10],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ];
        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFF2F2F2'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ];

        $sheet->getStyle('A'.$section['start'].':Q'.$section['end'])->applyFromArray($bodyStyle);
        $sheet->getStyle('J'.$section['title'].':P'.$section['title'])->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getStyle('J'.$section['meta'].':P'.$section['meta'])->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('J'.($section['meta'] + 1).':P'.($section['meta'] + 1))
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B'.$section['vendor'].':P'.$section['vendor'])
            ->getFont()->setBold(true);
        $sheet->getStyle('B'.($section['vendor'] + 1).':B'.($section['vendor'] + 5))
            ->getFont()->setBold(true);
        $sheet->getStyle('J'.($section['vendor'] + 1).':J'.($section['vendor'] + 5))
            ->getFont()->setBold(true);

        foreach (['B:H', 'J:P'] as $panel) {
            [$startColumn, $endColumn] = explode(':', $panel);
            $sheet->getStyle($startColumn.$section['header'].':'.$endColumn.$section['item_end'])
                ->applyFromArray($panelBorder);
            $sheet->getStyle($startColumn.$section['header'].':'.$endColumn.$section['header'])
                ->applyFromArray($headerStyle);
            $sheet->getStyle($startColumn.$section['units'].':'.$endColumn.$section['units'])
                ->applyFromArray($headerStyle);
        }

        $sheet->getStyle('F'.$section['item_start'].':H'.$section['item_end'])
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('N'.$section['item_start'].':P'.$section['item_end'])
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B'.$section['footer'].':P'.$section['footer'])->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('B'.$section['phone'].':H'.$section['phone'])
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getRowDimension($section['title'])->setRowHeight(24);
        $sheet->getRowDimension($section['meta'])->setRowHeight(20);
        $sheet->getRowDimension($section['meta'] + 1)->setRowHeight(22);
        $sheet->getRowDimension($section['header'])->setRowHeight(22);
        $sheet->getRowDimension($section['units'])->setRowHeight(20);
        for ($row = $section['item_start']; $row <= $section['item_end']; $row++) {
            $sheet->getRowDimension($row)->setRowHeight(30);
        }
    }
}
