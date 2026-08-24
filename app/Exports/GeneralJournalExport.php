<?php

namespace App\Exports;

use App\Services\FinancialReportService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class GeneralJournalExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    public function __construct(private readonly ?string $startDate = null, private readonly ?string $endDate = null)
    {
    }

    public function collection(): Collection
    {
        return app(FinancialReportService::class)->generalJournal($this->startDate, $this->endDate)
            ->flatMap(fn ($journal) => $journal->details->map(fn ($detail) => compact('journal', 'detail')))
            ->values();
    }

    public function headings(): array
    {
        return ['Tanggal', 'No. Jurnal', 'Referensi', 'Keterangan', 'Kode Akun', 'Nama Akun', 'Debit', 'Kredit'];
    }

    public function map($row): array
    {
        return [
            $row['journal']->transaction_date?->format('Y-m-d'),
            $row['journal']->journal_number,
            $row['journal']->ref_type ? $row['journal']->ref_type.($row['journal']->ref_id ? '#'.$row['journal']->ref_id : '') : 'MANUAL',
            $row['journal']->description,
            $row['detail']->account?->code,
            $row['detail']->account?->name,
            (float) $row['detail']->debit,
            (float) $row['detail']->credit,
        ];
    }

    public function title(): string
    {
        return 'Jurnal Umum';
    }
}
