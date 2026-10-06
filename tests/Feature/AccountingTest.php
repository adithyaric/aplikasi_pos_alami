<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Journal;
use App\Models\Outlet;
use App\Models\Pembelian;
use App\Models\Penjualan;
use App\Models\PenjualanPayment;
use App\Models\User;
use App\Exports\GeneralJournalExport;
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

        $first = $service->createJournal('2026-08-24', 'SALES', 7, 'Penjualan test', $details, 'SALES:7');
        $updatedDetails = [
            ['account_id' => $cash->id, 'debit' => 1500, 'credit' => 0],
            ['account_id' => $sales->id, 'debit' => 0, 'credit' => 1500],
        ];
        $second = $service->createJournal('2026-08-24', 'SALES', 7, 'Penjualan test diperbarui', $updatedDetails, 'SALES:7');

        $this->assertSame(1, Journal::where('source_key', 'SALES:7')->count());
        $this->assertSame($first->id, $second->id);
        $report = app(FinancialReportService::class)->profitLoss('2026-08-01', '2026-08-31');
        $this->assertSame(1500.0, $report['income']);
        $this->assertSame(1500.0, $report['net_income']);
    }

    public function test_unpaid_sales_are_presented_as_retained_earnings_on_balance_sheet(): void
    {
        $sale = Penjualan::create([
            'code' => 'PNJ-UNPAID-001',
            'sale_channel' => 'warehouse',
            'buyer_type' => 'agent',
            'buyer_id' => 1,
            'sale_date' => '2026-08-24',
            'payment_type' => 'termin',
            'payment_status' => 'unpaid',
            'total' => 1000,
        ]);

        app(AccountingService::class)->syncSale($sale);

        $report = app(FinancialReportService::class)->balanceSheet('2026-08-24');
        $retainedEarnings = $report['equity']->firstWhere('account.code', '300002');

        $this->assertNotNull($retainedEarnings);
        $this->assertSame(1000.0, $retainedEarnings['balance']);
        $this->assertSame(1000.0, $report['total_assets']);
        $this->assertSame(1000.0, $report['total_liabilities'] + $report['total_equity']);
        $this->assertSame(0.0, $report['total_assets'] - ($report['total_liabilities'] + $report['total_equity']));
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

    public function test_sync_action_can_be_run_repeatedly_without_duplicating_journals(): void
    {
        $user = User::factory()->create(['role' => 'superadmin']);

        $this->actingAs($user)
            ->post(route('accounting.sync'))
            ->assertRedirect()
            ->assertSessionHas('toast_success');
        $firstCount = Journal::count();

        $this->actingAs($user)
            ->post(route('accounting.sync'))
            ->assertRedirect()
            ->assertSessionHas('toast_success');

        $this->assertSame($firstCount, Journal::count());
    }

    public function test_general_journal_defaults_to_all_and_filters_branch_and_pusat_in_page_and_export(): void
    {
        $user = User::factory()->create(['role' => 'superadmin']);
        $branch = Outlet::create(['name' => 'Cabang Alpha', 'jenis_outlet' => 'branch']);
        $otherBranch = Outlet::create(['name' => 'Cabang Beta', 'jenis_outlet' => 'branch']);
        $cash = Account::where('code', '110101')->firstOrFail();
        $sales = Account::where('code', '400001')->firstOrFail();
        $lines = [
            ['account_id' => $cash->id, 'debit' => 100, 'credit' => 0],
            ['account_id' => $sales->id, 'debit' => 0, 'credit' => 100],
        ];

        app(AccountingService::class)->createJournal('2026-10-06', 'MANUAL', null, 'Pusat entry', $lines, null, true);
        app(AccountingService::class)->createJournal('2026-10-06', 'MANUAL', null, 'Alpha entry', $lines, null, true, $branch->id);
        app(AccountingService::class)->createJournal('2026-10-06', 'MANUAL', null, 'Beta entry', $lines, null, true, $otherBranch->id);

        $this->actingAs($user)->get(route('accounting.general-journal'))
            ->assertOk()->assertSee('Pusat entry')->assertSee('Alpha entry')->assertSee('Beta entry');
        $this->actingAs($user)->get(route('accounting.journals.index'))
            ->assertOk()->assertSee('Pusat entry')->assertSee('Alpha entry')->assertSee('Beta entry');
        $this->actingAs($user)->get(route('accounting.general-journal', ['location' => 'pusat']))
            ->assertOk()->assertSee('Pusat entry')->assertDontSee('Alpha entry')->assertDontSee('Beta entry');
        $this->actingAs($user)->get(route('accounting.general-journal', ['location' => (string) $branch->id]))
            ->assertOk()->assertSee('Alpha entry')->assertDontSee('Pusat entry')->assertDontSee('Beta entry');
        $this->actingAs($user)->get(route('accounting.general-journal', ['location' => 'all']))
            ->assertOk()->assertSee('Pusat entry')->assertSee('Alpha entry')->assertSee('Beta entry');

        $this->assertSame(2, (new GeneralJournalExport(null, null, 'pusat'))->collection()->count());
        $this->assertSame(2, (new GeneralJournalExport(null, null, (string) $branch->id))->collection()->count());
        $this->assertSame(6, (new GeneralJournalExport(null, null, 'all'))->collection()->count());
    }

    public function test_branch_sale_and_payment_journals_keep_the_source_branch_when_resynced(): void
    {
        $branch = Outlet::create(['name' => 'Cabang Alpha', 'jenis_outlet' => 'branch']);
        $sale = Penjualan::create([
            'code' => 'BR-ACCOUNT-001', 'sale_channel' => 'branch', 'outlet_id' => $branch->id,
            'sale_date' => '2026-10-06', 'payment_type' => 'credit', 'payment_status' => 'partial', 'total' => 100,
        ]);
        PenjualanPayment::create([
            'penjualan_id' => $sale->id, 'amount' => 40, 'status' => 'partial',
            'payment_history' => [['amount' => 40, 'payment_date' => '2026-10-06']],
        ]);

        app(AccountingService::class)->syncSale($sale);
        $this->assertSame(2, Journal::where('ref_id', $sale->id)->where('outlet_id', $branch->id)->count());

        $sale->update(['sale_channel' => 'warehouse']);
        app(AccountingService::class)->syncSale($sale->fresh());
        $this->assertSame(0, Journal::where('ref_id', $sale->id)->whereNotNull('outlet_id')->count());
    }

    public function test_manual_journal_form_saves_and_updates_its_branch_note(): void
    {
        $user = User::factory()->create(['role' => 'superadmin']);
        $branch = Outlet::create(['name' => 'Cabang Alpha', 'jenis_outlet' => 'branch']);
        $cash = Account::where('code', '110101')->firstOrFail();
        $sales = Account::where('code', '400001')->firstOrFail();
        $payload = [
            'transaction_date' => '2026-10-06', 'description' => 'Jurnal cabang', 'outlet_id' => $branch->id,
            'details' => [
                ['account_id' => $cash->id, 'debit' => 100, 'credit' => 0],
                ['account_id' => $sales->id, 'debit' => 0, 'credit' => 100],
            ],
        ];

        $this->actingAs($user)->get(route('accounting.journals.create'))->assertOk()->assertSee('Cabang Alpha');
        $this->actingAs($user)->post(route('accounting.journals.store'), $payload)
            ->assertRedirect(route('accounting.journals.index', ['location' => $branch->id]));
        $journal = Journal::where('description', 'Jurnal cabang')->firstOrFail();
        $this->assertSame($branch->id, $journal->outlet_id);

        $this->actingAs($user)->put(route('accounting.journals.update', $journal), array_merge($payload, ['outlet_id' => '']))
            ->assertRedirect(route('accounting.journals.show', $journal));
        $this->assertNull($journal->fresh()->outlet_id);
    }

    public function test_fresh_seed_contains_demo_coa_and_journals_for_operational_records(): void
    {
        $this->seed();

        $expenseHeader = Account::where('code', '6900')->firstOrFail();
        $expenseAccount = Account::where('code', '690001')->firstOrFail();

        $this->assertSame($expenseHeader->id, $expenseAccount->parent_id);
        $this->assertGreaterThan(0, Penjualan::count());
        $this->assertGreaterThan(0, Pembelian::count());
        $this->assertGreaterThan(0, Journal::where('ref_type', 'SALES')->count());
        $this->assertGreaterThan(0, Journal::where('ref_type', 'PURCHASE')->count());
        $this->assertDatabaseHas('journals', [
            'source_key' => 'SEED:OPENING_BALANCE:DEMO',
            'is_manual' => 1,
        ]);

        $journalCount = Journal::count();
        $this->seed(\Database\Seeders\AccountingDemoSeeder::class);
        $this->assertSame($journalCount, Journal::count());
    }
}
