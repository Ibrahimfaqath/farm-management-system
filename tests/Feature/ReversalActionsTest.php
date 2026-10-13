<?php

namespace Tests\Feature;

use App\Enums\AnimalStatus;
use App\Enums\GoodsReceiptStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PurchaseOrderStatus;
use App\Models\Animal;
use App\Models\GoodsReceipt;
use App\Models\InventoryBatch;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The undo paths that the backend already enforced but the interface never
 * offered: cancelling a draft receipt, a purchase order and an invoice, and
 * returning a closed animal to the herd.
 *
 * These guard the business rules those actions depend on. Cancelling must never
 * move stock, and reopening must clear the exit record without touching
 * history — the exact invariants the controllers protect with abort_if().
 */
class ReversalActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_draft_receipt_can_be_cancelled_without_touching_stock(): void
    {
        $this->seed(DatabaseSeeder::class);

        $receipt = GoodsReceipt::factory()->create(['status' => GoodsReceiptStatus::Draft]);
        $batchesBefore = InventoryBatch::count();
        $movementsBefore = StockMovement::count();

        $this->actingAs($this->owner())
            ->post(route('goods-receipts.cancel', $receipt))
            ->assertRedirect(route('goods-receipts.index'))
            ->assertSessionHas('status');

        $this->assertSame(GoodsReceiptStatus::Cancelled, $receipt->fresh()->status);
        $this->assertSame($batchesBefore, InventoryBatch::count(), 'Cancelling must not create batches.');
        $this->assertSame($movementsBefore, StockMovement::count(), 'Cancelling must not create stock movements.');
    }

    public function test_a_confirmed_receipt_cannot_be_cancelled(): void
    {
        $this->seed(DatabaseSeeder::class);

        $receipt = GoodsReceipt::factory()->create(['status' => GoodsReceiptStatus::Posted]);

        $this->actingAs($this->owner())
            ->post(route('goods-receipts.cancel', $receipt))
            ->assertForbidden();

        $this->assertSame(GoodsReceiptStatus::Posted, $receipt->fresh()->status);
    }

    public function test_the_receipt_page_offers_cancellation_only_while_it_is_a_draft(): void
    {
        $this->seed(DatabaseSeeder::class);

        $draft = GoodsReceipt::factory()->create(['status' => GoodsReceiptStatus::Draft]);

        $this->actingAs($this->owner())->get(route('goods-receipts.show', $draft))
            ->assertOk()
            ->assertSee(route('goods-receipts.cancel', $draft), false)
            ->assertSee('data-modal-open="cancel-receipt-modal"', false);

        $posted = GoodsReceipt::factory()->create(['status' => GoodsReceiptStatus::Posted]);

        $this->actingAs($this->owner())->get(route('goods-receipts.show', $posted))
            ->assertOk()
            ->assertDontSee(route('goods-receipts.cancel', $posted), false);
    }

    public function test_an_unreceived_purchase_order_can_be_cancelled(): void
    {
        $this->seed(DatabaseSeeder::class);

        $order = PurchaseOrder::factory()->create(['status' => PurchaseOrderStatus::Approved]);

        $this->actingAs($this->owner())
            ->post(route('purchase-orders.cancel', $order))
            ->assertRedirect(route('purchase-orders.index'));

        $this->assertSame(PurchaseOrderStatus::Cancelled, $order->fresh()->status);
    }

    public function test_a_purchase_order_with_a_confirmed_receipt_cannot_be_cancelled(): void
    {
        $this->seed(DatabaseSeeder::class);

        $order = PurchaseOrder::factory()->create(['status' => PurchaseOrderStatus::PartiallyReceived]);
        GoodsReceipt::factory()->create([
            'purchase_order_id' => $order->id,
            'supplier_id' => $order->supplier_id,
            'status' => GoodsReceiptStatus::Posted,
        ]);

        $this->actingAs($this->owner())
            ->post(route('purchase-orders.cancel', $order))
            ->assertForbidden();

        $this->assertNotSame(PurchaseOrderStatus::Cancelled, $order->fresh()->status);
    }

    public function test_an_unpaid_invoice_can_be_cancelled(): void
    {
        $this->seed(DatabaseSeeder::class);

        $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Unpaid]);

        $this->actingAs($this->owner())
            ->post(route('invoices.cancel', $invoice))
            ->assertRedirect(route('invoices.index'));

        $this->assertSame(InvoiceStatus::Cancelled, $invoice->fresh()->status);
    }

    public function test_a_paid_invoice_cannot_be_cancelled(): void
    {
        $this->seed(DatabaseSeeder::class);

        $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Partial]);
        Payment::factory()->create(['invoice_id' => $invoice->id]);

        $this->actingAs($this->owner())
            ->post(route('invoices.cancel', $invoice))
            ->assertForbidden();

        $this->assertNotSame(InvoiceStatus::Cancelled, $invoice->fresh()->status);
    }

    public function test_a_closed_animal_can_be_returned_to_the_herd(): void
    {
        $this->seed(DatabaseSeeder::class);

        $animal = Animal::factory()->create([
            'status' => AnimalStatus::Sold,
            'exit_date' => now()->subDays(3),
            'exit_type' => 'sold',
            'exit_value' => 12_500_000,
        ]);

        $this->actingAs($this->owner())
            ->post(route('animals.reopen', $animal))
            ->assertRedirect(route('animals.show', $animal));

        $animal->refresh();

        $this->assertSame(AnimalStatus::Active, $animal->status);
        $this->assertNull($animal->exit_date);
        $this->assertNull($animal->exit_type);
        $this->assertSame(0.0, (float) $animal->exit_value);
    }

    public function test_the_animal_page_offers_the_return_action_only_while_it_is_closed(): void
    {
        $this->seed(DatabaseSeeder::class);

        $closed = Animal::factory()->create([
            'status' => AnimalStatus::Deceased,
            'exit_date' => now()->subDay(),
            'exit_type' => 'deceased',
        ]);

        $this->actingAs($this->owner())->get(route('animals.show', $closed))
            ->assertOk()
            ->assertSee(route('animals.reopen', $closed), false)
            ->assertSee('data-modal-open="reopen-modal"', false);

        $active = Animal::factory()->create(['status' => AnimalStatus::Active]);

        $this->actingAs($this->owner())->get(route('animals.show', $active))
            ->assertOk()
            ->assertDontSee(route('animals.reopen', $active), false);
    }

    public function test_reversal_actions_are_hidden_from_a_role_without_the_ability(): void
    {
        $this->seed(DatabaseSeeder::class);

        $animal = Animal::factory()->create([
            'status' => AnimalStatus::Sold,
            'exit_date' => now()->subDay(),
            'exit_type' => 'sold',
        ]);

        // A veterinarian has no animal.manage ability, so the action must not
        // be offered — the route itself still refuses the POST.
        $this->actingAs($this->veterinarian())
            ->get(route('animals.show', $animal))
            ->assertOk()
            ->assertDontSee(route('animals.reopen', $animal), false);

        $this->actingAs($this->veterinarian())
            ->post(route('animals.reopen', $animal))
            ->assertForbidden();

        $this->assertSame(AnimalStatus::Sold, $animal->fresh()->status);
    }
}
