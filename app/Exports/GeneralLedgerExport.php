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

class GeneralLedgerExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, WithColumnWidths
{
    private array $report;

    public function __construct(private readonly int $accountId, private readonly ?string $startDate = null, private readonly ?string $endDate = null)
    {
        $this->report = app(FinancialReportService::class)->ledger($accountId, $startDate, $endDate);
    }

    public function collection(): Collection
    {
        $rows = collect([[
            'date' => null,
            'journal_number' => 'SALDO AWAL',
            'description' => 'Saldo sebelum periode',
            'debit' => 0,
            'credit' => 0,
            'balance' => $this->report['opening'],
        ]]);

        return $rows->concat($this->report['rows']);
    }

    public function headings(): array
    {
        return ['Tanggal', 'No. Jurnal', 'Keterangan', 'Debit', 'Kredit', 'Saldo'];
    }

    public function map($row): array
    {
        return [
            $row['date']?->format('Y-m-d'),
            $row['journal_number'],
            $row['description'],
            $row['debit'],
            $row['credit'],
            $row['balance'],
        ];
    }

    public function title(): string
    {
        return 'Buku Besar';
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
        $sheet->freezePane('A2');
        $lastRow = max(2, $sheet->getHighestRow());
        $sheet->getStyle('D2:F'.$lastRow)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setFitToWidth(1);

        return [];
    }

    public function columnWidths(): array
    {
        return ['A' => 13, 'B' => 18, 'C' => 40, 'D' => 16, 'E' => 16, 'F' => 16];
    }
}
