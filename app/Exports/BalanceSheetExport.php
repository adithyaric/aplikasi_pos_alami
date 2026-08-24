<?php

namespace App\Exports;

use App\Services\FinancialReportService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class BalanceSheetExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    private array $report;

    public function __construct(private readonly string $asOfDate)
    {
        $this->report = app(FinancialReportService::class)->balanceSheet($asOfDate);
    }

    public function collection(): Collection
    {
        $left = $this->report['assets']->map(fn ($row) => [
            'side' => 'ASET',
            'type' => $row['account']->type_code,
            'account' => $row['account'],
            'balance' => $row['balance'],
        ]);
        $right = $this->report['liabilities']->concat($this->report['equity'])->map(fn ($row) => [
            'side' => 'LIABILITAS/EKUITAS',
            'type' => $row['account']->type_code,
            'account' => $row['account'],
            'balance' => $row['balance'],
        ]);

        return $left->concat($right)->push([
            'side' => 'LIABILITAS/EKUITAS',
            'type' => 'NET_INCOME',
            'account' => null,
            'balance' => $this->report['net_income'],
        ]);
    }

    public function headings(): array
    {
        return ['Sisi', 'Tipe', 'Kode Akun', 'Nama Akun', 'Saldo'];
    }

    public function map($row): array
    {
        return [$row['side'], $row['type'], $row['account']?->code, $row['account']?->name ?: 'Laba Tahun Berjalan', $row['balance']];
    }

    public function title(): string
    {
        return 'Neraca';
    }
}
