<?php

namespace App\Exports;

use App\Services\FinancialReportService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class ProfitLossExport implements FromCollection, WithHeadings, WithMapping, WithTitle
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
}
