<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Journal;
use App\Models\JournalDetail;
use App\Models\Pembelian;
use App\Models\Penjualan;
use App\Models\PenjualanPayment;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentRevisionTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_payment_cannot_exceed_selected_cash_balance(): void
    {
        $user = User::factory()->create(['role' => 'superadmin']);
        $cash = Account::where('code', '110101')->firstOrFail();
        $equity = Account::where('code', '300001')->firstOrFail();
        app(AccountingService::class)->createJournal('2026-10-06', 'MANUAL', null, 'Saldo kas', [
            ['account_id' => $cash->id, 'debit' => 50, 'credit' => 0],
            ['account_id' => $equity->id, 'debit' => 0, 'credit' => 50],
        ]);
        $purchase = Pembelian::create(['code' => 'PO-PAY-1', 'total' => 100]);
        $payload = [
            'payment_date' => '2026-10-06', 'account_id' => $cash->id,
            'amount' => 60, 'status' => 'partial',
        ];

        $this->actingAs($user)->putJson(route('pembelian.pembayaran.update', $purchase), $payload)
            ->assertStatus(422)->assertJsonValidationErrors('amount');
        $this->assertNull($purchase->fresh()->pembelianTransaction);

        $this->actingAs($user)->putJson(route('pembelian.pembayaran.update', $purchase), array_replace($payload, ['amount' => 50]))
            ->assertOk();
        $this->assertSame(50.0, $purchase->fresh()->pembelianTransaction->amount);
    }

    public function test_draft_purchase_payment_also_reduces_available_cash(): void
    {
        $user = User::factory()->create(['role' => 'superadmin']);
        $cash = Account::where('code', '110101')->firstOrFail();
        $equity = Account::where('code', '300001')->firstOrFail();
        app(AccountingService::class)->createJournal('2026-10-06', 'MANUAL', null, 'Saldo kas', [
            ['account_id' => $cash->id, 'debit' => 100, 'credit' => 0],
            ['account_id' => $equity->id, 'debit' => 0, 'credit' => 100],
        ]);
        $first = Pembelian::create(['code' => 'PO-DRAFT-1', 'total' => 80]);
        $second = Pembelian::create(['code' => 'PO-DRAFT-2', 'total' => 30]);
        $payload = ['payment_date' => '2026-10-06', 'account_id' => $cash->id, 'status' => 'paid'];

        $this->actingAs($user)->putJson(route('pembelian.pembayaran.update', $first), array_replace($payload, ['amount' => 80]))->assertOk();
        $this->actingAs($user)->putJson(route('pembelian.pembayaran.update', $second), array_replace($payload, ['amount' => 30]))
            ->assertStatus(422)->assertJsonValidationErrors('amount');
    }

    public function test_cancelling_paid_purchase_restores_cash_journal_and_reopens_payment(): void
    {
        $user = User::factory()->create(['role' => 'superadmin']);
        $cash = Account::where('code', '110101')->firstOrFail();
        $payable = Account::where('code', '210101')->firstOrFail();
        $purchase = Pembelian::create(['code' => 'PO-PAY-2', 'total' => 40, 'is_published' => true]);
        $purchase->pembelianTransaction()->create([
            'payment_date' => '2026-10-06', 'amount' => 40, 'status' => 'paid',
            'account_id' => $cash->id,
            'payment_history' => [['amount' => 40, 'account_id' => $cash->id, 'payment_date' => '2026-10-06']],
        ]);
        app(AccountingService::class)->createJournal('2026-10-06', 'PURCHASE_PAYMENT', $purchase->id, 'Pembayaran', [
            ['account_id' => $payable->id, 'debit' => 40, 'credit' => 0],
            ['account_id' => $cash->id, 'debit' => 0, 'credit' => 40],
        ], 'PURCHASE_PAYMENT:'.$purchase->id.':0');
        $this->assertSame(-40.0, (float) JournalDetail::where('account_id', $cash->id)->sum('debit') - (float) JournalDetail::where('account_id', $cash->id)->sum('credit'));
        $this->actingAs($user)->get(route('pembelian.pembayaran.edit', $purchase))->assertOk()->assertSee('Batalkan Pembayaran');

        $this->actingAs($user)->delete(route('pembelian.pembayaran.cancel', [$purchase, 0]))->assertRedirect();

        $this->assertSame('unpaid', $purchase->fresh()->pembelianTransaction->status);
        $this->assertSame(0.0, $purchase->fresh()->pembelianTransaction->amount);
        $this->assertDatabaseMissing('journals', ['source_key' => 'PURCHASE_PAYMENT:'.$purchase->id.':0']);
        $this->assertSame(0.0, (float) JournalDetail::where('account_id', $cash->id)->sum('debit') - (float) JournalDetail::where('account_id', $cash->id)->sum('credit'));
        $this->actingAs($user)->get(route('pembelian.pembayaran.edit', $purchase))->assertOk()->assertSee('Simpan Pembayaran');
    }

    public function test_cancelling_automatic_cash_sale_moves_value_back_to_receivables(): void
    {
        $user = User::factory()->create(['role' => 'superadmin']);
        $sale = Penjualan::create([
            'code' => 'SALE-CASH-CANCEL', 'sale_channel' => 'warehouse',
            'sale_date' => '2026-10-06', 'payment_type' => 'cash',
            'payment_status' => 'paid', 'total' => 100,
        ]);
        $sale->paymentTransaction()->create([
            'payment_date' => '2026-10-06', 'amount' => 100, 'status' => 'paid',
            'payment_history' => [['amount' => 100, 'payment_date' => '2026-10-06']],
        ]);
        app(AccountingService::class)->syncSale($sale);
        $cash = Account::where('code', '110101')->firstOrFail();
        $receivable = Account::where('code', '110301')->firstOrFail();

        $this->actingAs($user)->delete(route('penjualan.pembayaran.cancel', [$sale, 0]))->assertRedirect();

        $sale->refresh();
        $this->assertSame('termin', $sale->payment_type);
        $this->assertSame('unpaid', $sale->payment_status);
        $this->assertSame(0.0, (float) JournalDetail::where('account_id', $cash->id)->sum('debit'));
        $this->assertSame(100.0, (float) JournalDetail::where('account_id', $receivable->id)->sum('debit'));
    }

    public function test_sale_payment_can_be_cancelled_for_warehouse_and_branch(): void
    {
        $user = User::factory()->create(['role' => 'superadmin']);
        foreach (['warehouse', 'branch'] as $channel) {
            $sale = Penjualan::create([
                'code' => 'SALE-CANCEL-'.$channel, 'sale_channel' => $channel,
                'sale_date' => '2026-10-06', 'payment_type' => 'termin',
                'payment_status' => 'paid', 'total' => 100,
            ]);
            PenjualanPayment::create([
                'penjualan_id' => $sale->id, 'amount' => 100, 'status' => 'paid',
                'payment_history' => [
                    ['amount' => 40, 'payment_date' => '2026-10-06'],
                    ['amount' => 60, 'payment_date' => '2026-10-06'],
                ],
            ]);
            app(AccountingService::class)->syncSale($sale);
            $this->actingAs($user)->get(route('penjualan.pembayaran.edit', $sale))->assertOk()->assertSee('Batalkan Pembayaran');

            $this->actingAs($user)->delete(route('penjualan.pembayaran.cancel', [$sale, 1]))->assertRedirect();

            $sale->refresh();
            $this->assertSame('partial', $sale->payment_status);
            $this->assertSame(40.0, $sale->paymentTransaction->amount);
            $this->assertCount(1, $sale->paymentTransaction->payment_history);
            $this->assertSame(1, Journal::where('source_key', 'like', 'SALES_PAYMENT:'.$sale->id.':%')->count());
        }
    }

    public function test_adding_installment_keeps_legacy_sale_payment_without_history(): void
    {
        $user = User::factory()->create(['role' => 'superadmin']);
        $sale = Penjualan::create([
            'code' => 'SALE-LEGACY-PAY', 'sale_channel' => 'warehouse',
            'sale_date' => '2026-10-06', 'payment_type' => 'termin',
            'payment_status' => 'partial', 'total' => 100,
        ]);
        $sale->paymentTransaction()->create([
            'payment_date' => '2026-10-06', 'amount' => 40, 'status' => 'partial',
            'payment_history' => [],
        ]);

        $this->actingAs($user)->put(route('penjualan.pembayaran.update', $sale), [
            'payment_date' => '2026-10-07', 'payment_method' => 'cash', 'amount' => 60,
        ])->assertRedirect();

        $this->assertCount(2, $sale->fresh()->paymentTransaction->payment_history);
        $this->assertSame(2, Journal::where('source_key', 'like', 'SALES_PAYMENT:'.$sale->id.':%')->count());
    }

    public function test_adding_installment_keeps_legacy_purchase_payment_without_history(): void
    {
        $user = User::factory()->create(['role' => 'superadmin']);
        $cash = Account::where('code', '110101')->firstOrFail();
        $equity = Account::where('code', '300001')->firstOrFail();
        app(AccountingService::class)->createJournal('2026-10-06', 'MANUAL', null, 'Saldo kas', [
            ['account_id' => $cash->id, 'debit' => 100, 'credit' => 0],
            ['account_id' => $equity->id, 'debit' => 0, 'credit' => 100],
        ]);
        $purchase = Pembelian::create(['code' => 'PO-LEGACY-PAY', 'total' => 100]);
        $purchase->pembelianTransaction()->create([
            'payment_date' => '2026-10-06', 'amount' => 40, 'status' => 'partial',
            'account_id' => $cash->id, 'payment_history' => [],
        ]);

        $this->actingAs($user)->putJson(route('pembelian.pembayaran.update', $purchase), [
            'payment_date' => '2026-10-07', 'account_id' => $cash->id,
            'amount' => 60, 'status' => 'paid',
        ])->assertOk();

        $this->assertCount(2, $purchase->fresh()->pembelianTransaction->payment_history);
        $this->assertSame(100.0, $purchase->fresh()->pembelianTransaction->amount);
    }
}
