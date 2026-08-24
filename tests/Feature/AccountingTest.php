<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Journal;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\FinancialReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class AccountingTest extends TestCase
{
    use RefreshDatabase;

    public function test_journal_engine_requires_balanced_lines(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(AccountingService::class)->createJournal(
            '2026-08-24',
            'MANUAL',
            null,
            'Tidak balance',
            [['account_id' => Account::where('code', '110101')->value('id'), 'debit' => 100, 'credit' => 0]],
            null,
            true
        );
    }

    public function test_source_journal_is_idempotent_and_report_uses_account_normal_balance(): void
    {
        $cash = Account::where('code', '110101')->firstOrFail();
        $sales = Account::where('code', '400001')->firstOrFail();

        $service = app(AccountingService::class);
        $details = [
            ['account_id' => $cash->id, 'debit' => 1250, 'credit' => 0],
            ['account_id' => $sales->id, 'debit' => 0, 'credit' => 1250],
        ];

        $service->createJournal('2026-08-24', 'SALES', 7, 'Penjualan test', $details, 'SALES:7');
        $service->createJournal('2026-08-24', 'SALES', 7, 'Penjualan test diperbarui', $details, 'SALES:7');

        $this->assertSame(1, Journal::where('source_key', 'SALES:7')->count());
        $report = app(FinancialReportService::class)->profitLoss('2026-08-01', '2026-08-31');
        $this->assertSame(1250.0, $report['income']);
        $this->assertSame(1250.0, $report['net_income']);
    }

    public function test_accounting_is_available_as_the_second_laporan_tab(): void
    {
        $user = User::factory()->create(['role' => 'superadmin']);

        $response = $this->actingAs($user)->get(route('laporan.index', ['tab' => 'accounting']));

        $response->assertOk();
        $response->assertSee('Akun &amp; Jurnal Umum', false);
        $response->assertSee('modal-create-account', false);
        $response->assertSee(route('accounting.accounts.store'), false);
    }

    public function test_admin_can_create_an_account_and_manual_journal_from_accounting_pages(): void
    {
        $user = User::factory()->create(['role' => 'superadmin']);

        $this->actingAs($user)->post(route('accounting.accounts.store'), [
            'code' => '600099',
            'name' => 'Beban Uji',
            'type_code' => 'EXPS',
        ])->assertRedirect(route('accounting.accounts.index'));

        $cash = Account::where('code', '110101')->firstOrFail();
        $expense = Account::where('code', '600099')->firstOrFail();
        $this->actingAs($user)->post(route('accounting.journals.store'), [
            'transaction_date' => '2026-08-24',
            'description' => 'Jurnal manual uji',
            'details' => [
                ['account_id' => $expense->id, 'debit' => 500, 'credit' => 0],
                ['account_id' => $cash->id, 'debit' => 0, 'credit' => 500],
            ],
        ])->assertRedirect(route('accounting.journals.index'));

        $this->assertDatabaseHas('journals', ['description' => 'Jurnal manual uji', 'is_manual' => 1]);
    }
}
