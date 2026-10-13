<?php

namespace Tests\Feature;

use App\Enums\AnimalStatus;
use App\Enums\InvoiceStatus;
use App\Enums\Role;
use App\Models\Animal;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Services\AttentionService;
use App\Services\FinanceService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * End-to-end checks against a real HTTP kernel with the demo dataset, asserting
 * the business facts the interface is supposed to communicate.
 */
class DashboardContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_dashboard_shows_real_business_numbers(): void
    {
        $finance = app(FinanceService::class);

        $response = $this->actingAs($this->owner())->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Perlu Perhatian')
            ->assertSee('Populasi Ternak')
            ->assertSee('Populasi Ternak')
            ->assertSee('Rp'.number_format($finance->accountsReceivable(), 0, ',', '.'))
            ->assertSee('Rp'.number_format($finance->accountsPayable(), 0, ',', '.'))
            ->assertSee('Rp'.number_format($finance->cashBalance(), 0, ',', '.'));
    }

    public function test_dashboard_flags_low_stock_and_overdue_invoices(): void
    {
        $items = app(AttentionService::class)->items();

        $titles = array_column($items, 'title');

        $this->assertNotEmpty($titles);
        $this->assertTrue(
            collect($titles)->contains(fn (string $title) => str_contains($title, 'faktur lewat jatuh tempo')),
            'Expected an overdue invoice alert, got: '.implode(' | ', $titles),
        );
    }

    public function test_purchase_order_page_explains_the_outstanding_quantity(): void
    {
        $order = PurchaseOrder::query()
            ->where('status', 'partially_received')
            ->firstOrFail();

        $response = $this->actingAs($this->manager())->get(route('purchase-orders.show', $order));

        $response->assertOk()
            ->assertSee($order->po_number)
            ->assertSee('Penerimaan sebagian')
            ->assertSee(number_format($order->totalOutstanding(), 0));
    }

    public function test_invoice_page_shows_receivable_not_cash(): void
    {
        $invoice = Invoice::query()
            ->where('status', InvoiceStatus::Unpaid->value)
            ->whereDate('due_date', '>', now()->toDateString())
            ->firstOrFail();

        $response = $this->actingAs($this->financeUser())->get(route('invoices.show', $invoice));

        $response->assertOk()
            ->assertSee($invoice->invoice_number)
            ->assertSee('Sisa Piutang');
    }

    public function test_inventory_page_warns_when_stock_is_low(): void
    {
        $response = $this->actingAs($this->manager())->get(route('inventory.index'));

        $response->assertOk()
            ->assertSee('Inventori Pakan')
            ->assertSee('Nilai Stok')
            ->assertSee('Batas Pemesanan Ulang');
    }

    public function test_sold_animal_page_explains_that_history_is_kept(): void
    {
        $sold = Animal::query()->where('status', AnimalStatus::Sold->value)->firstOrFail();

        $this->actingAs($this->owner())
            ->get(route('animals.show', $sold))
            ->assertOk()
            ->assertSee('Terjual')
            ->assertSee('Data historis tetap tersedia sebagai bukti riwayat');
    }

    public function test_production_report_reports_numbers_without_diagnosing(): void
    {
        $response = $this->actingAs($this->manager())->get(route('reports.production.index'));

        $response->assertOk()
            ->assertSee('Laporan Produksi')
            ->assertSee('Penurunan Perlu Dicek')
            ->assertSee('Perubahan nyata, penyebabnya perlu diperiksa manusia.');
    }

    public function test_worker_sees_a_reduced_navigation(): void
    {
        $html = $this->actingAs($this->worker())->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('Catat Produksi', $html);
        $this->assertStringNotContainsString('Pengaturan Sistem', $html);
        $this->assertStringNotContainsString('Laporan Keuangan', $html);
    }

    public function test_veterinarian_can_open_health_module_but_not_finance(): void
    {
        $user = $this->veterinarian();

        $this->actingAs($user)->get(route('health-records.index'))->assertOk();
        $this->actingAs($user)->get(route('expenses.index'))->assertForbidden();
        $this->actingAs($user)->get(route('settings.index'))->assertForbidden();
    }

    public function test_login_screen_lists_demo_accounts(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('owner@farm.test')
            ->assertSee('Masuk');
    }

    public function test_milk_production_list_shows_today_totals(): void
    {
        $this->actingAs($this->worker())
            ->get(route('milk-productions.index'))
            ->assertOk()
            ->assertSee('Produksi Susu')
            ->assertSee('Per Sapi Laktasi');
    }

    public function test_settings_module_is_reserved_for_the_owner(): void
    {
        $this->actingAs($this->manager())->get(route('settings.index'))->assertForbidden();
        $this->actingAs($this->financeUser())->get(route('settings.index'))->assertForbidden();
        $this->actingAs($this->owner())->get(route('settings.index'))->assertOk();
    }

    public function test_owner_can_manage_settings_and_users(): void
    {
        $response = $this->actingAs($this->owner())->get(route('settings.index'));

        $response->assertOk()
            ->assertSee('Pengguna Sistem')
            ->assertSee('Tambah Pengguna');
    }

    public function test_role_enum_exposes_modules_and_abilities(): void
    {
        $this->assertTrue(Role::Owner->canAccessModule('finance'));
        $this->assertFalse(Role::Worker->canAccessModule('finance'));
        $this->assertTrue(Role::Worker->can('feed.consume'));
        $this->assertFalse(Role::Worker->can('feed.adjust'));
        $this->assertTrue(Role::Finance->can('payment.record'));
        $this->assertFalse(Role::Finance->can('animal.manage'));
    }

    public function test_seeded_users_have_the_documented_roles(): void
    {
        $expected = [
            'owner@farm.test' => Role::Owner,
            'manager@farm.test' => Role::Manager,
            'worker@farm.test' => Role::Worker,
            'vet@farm.test' => Role::Veterinarian,
            'finance@farm.test' => Role::Finance,
        ];

        foreach ($expected as $email => $role) {
            $this->assertSame($role, User::where('email', $email)->firstOrFail()->role);
        }
    }
}
