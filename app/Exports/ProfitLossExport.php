<?php

namespace App\Exports;

use App\Services\FinancialReportService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProfitLossExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, WithColumnWidths
{
    private array $report;

    public function __construct(private readonly string $startDate, private readonly string $endDate)
    {
        $this->report = app(FinancialReportService::class)->profitLoss($startDate, $endDate);
    }

    public function collection(): Collection
    {
        return $this->report['rows']->concat([
            ['section' => 'TOTAL', 'account' => null, 'amount' => $this->report['net_income']],
        ]);
    }

    public function headings(): array
    {
        return ['Bagian', 'Kode Akun', 'Nama Akun', 'Nilai'];
    }

    public function map($row): array
    {
        return [
            $row['section'],
            $row['account']?->code,
            $row['account']?->name ?: 'LABA / RUGI BERSIH',
            $row['amount'],
        ];
    }

    public function title(): string
    {
        return 'Laba Rugi';
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:D1')->getFont()->setBold(true);
        $sheet->freezePane('A2');
        $lastRow = max(2, $sheet->getHighestRow());
        $sheet->getStyle('D2:D'.$lastRow)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setFitToWidth(1);

        return [];
    }

    public function columnWidths(): array
    {
        return ['A' => 28, 'B' => 14, 'C' => 32, 'D' => 18];
    }
}
