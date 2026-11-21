<?php

namespace Tests\Concerns;

use App\Enums\EventStatus;
use App\Enums\OrderItemStatus;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\OrderItem;
use App\Models\User;

/**
 * Skenario acuan Tahap B: venue 20 jt + katering 10 jt, komisi 3 jt,
 * biaya kebersihan 1,5 jt → G = 34.500.000, kode unik 123.
 */
trait BuildsPaidEvents
{
    /**
     * @return array{client: User, event: Event, invoice: Invoice, venueItem: OrderItem, cateringItem: OrderItem}
     */
    protected function paidReferenceEvent(string $eventDate, ?User $client = null): array
    {
        $client ??= User::factory()->klien()->create();
        $event = Event::factory()->create([
            'client_id' => $client->id,
            'status' => EventStatus::Terkonfirmasi,
            'event_date' => $eventDate,
        ]);

        $venueItem = OrderItem::factory()->untukVenueRoom()->create([
            'event_id' => $event->id,
            'status' => OrderItemStatus::Terkonfirmasi,
            'catalog_price_snapshot' => 20_000_000,
            'provider_price' => 20_000_000,
            'commission' => 2_000_000,
            'quantity' => 1,
            'hold_key' => 'venue:'.$event->id.':'.$eventDate,
        ]);
        $cateringItem = OrderItem::factory()->create([
            'event_id' => $event->id,
            'status' => OrderItemStatus::Terkonfirmasi,
            'catalog_price_snapshot' => 10_000_000,
            'provider_price' => 10_000_000,
            'commission' => 1_000_000,
            'quantity' => 1,
            'hold_key' => 'item:'.$event->id.':'.$eventDate,
        ]);

        $invoice = Invoice::factory()->lunas()->create([
            'event_id' => $event->id,
            'subtotal_provider' => 30_000_000,
            'total_commission' => 3_000_000,
            'cleaning_fee' => 1_500_000,
            'grand_total' => 34_500_000,
            'unique_code' => 123,
            'transfer_total' => 34_500_123,
        ]);

        return compact('client', 'event', 'invoice', 'venueItem', 'cateringItem');
    }
}
