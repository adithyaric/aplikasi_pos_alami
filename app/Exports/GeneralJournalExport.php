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

class GeneralJournalExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, WithColumnWidths
{
    public function __construct(private readonly ?string $startDate = null, private readonly ?string $endDate = null, private readonly string $location = 'pusat')
    {
    }

    public function collection(): Collection
    {
        return app(FinancialReportService::class)->generalJournal($this->startDate, $this->endDate, $this->location)
            ->flatMap(fn ($journal) => $journal->details->map(fn ($detail) => [
                'journal' => $journal,
                'detail' => $detail,
            ]))
            ->values();
    }

    public function headings(): array
    {
        return ['Tanggal', 'No. Jurnal', 'Lokasi', 'Referensi', 'Keterangan', 'Kode Akun', 'Nama Akun', 'Debit', 'Kredit'];
    }

    public function map($row): array
    {
        return [
            $row['journal']->transaction_date?->format('Y-m-d'),
            $row['journal']->journal_number,
            $row['journal']->outlet?->name ?? 'Pusat',
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

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);
        $sheet->freezePane('A2');
        $lastRow = max(2, $sheet->getHighestRow());
        $sheet->getStyle('H2:I'.$lastRow)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setFitToWidth(1);

        return [];
    }

    public function columnWidths(): array
    {
        return ['A' => 13, 'B' => 18, 'C' => 20, 'D' => 18, 'E' => 35, 'F' => 14, 'G' => 28, 'H' => 16, 'I' => 16];
    }
}
