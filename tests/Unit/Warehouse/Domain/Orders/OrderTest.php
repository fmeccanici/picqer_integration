<?php

namespace Tests\Unit\Warehouse\Domain\Orders;

use App\Warehouse\Domain\Exceptions\OrderOperationException;
use App\Warehouse\Domain\Exporters\PackingSlipGeneratorInterface;
use App\Warehouse\Domain\Orders\DeliveryOptionFactory;
use App\Warehouse\Domain\Orders\OrderFactory;
use App\Warehouse\Domain\Services\ReviewRequestSenderServiceInterface;
use App\Warehouse\Domain\Shipments\ShipmentFactory;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;
use Tests\Feature\Warehouse\DummyPackingSlipGenerator;
use Tests\Feature\Warehouse\DummyReviewRequestSenderService;
use Tests\TestCase;

class OrderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub

        $this->app->bind(PackingSlipGeneratorInterface::class, DummyPackingSlipGenerator::class);
        $this->app->bind(ReviewRequestSenderServiceInterface::class, DummyReviewRequestSenderService::class);

    }

    /** @test */
    public function it_should_throw_domain_exception_when_cancelling_and_order_is_completed()
    {
        // Then
        $this->expectException(OrderOperationException::class);
        $this->expectErrorMessage('Bestelling kan niet worden geannuleerd, omdat er al een zending voor is aangemaakt.');

        // Given
        $order = OrderFactory::create(1, [
            'status' => 'completed'
        ])->first();

        // When
        $order->cancel();
    }

    /** @test */
    public function it_should_throw_exception_when_cancelling_on_shipping_date()
    {
        // Then
        $this->expectException(OrderOperationException::class);
        $this->expectErrorMessage('Bestelling kan niet worden geannuleerd, omdat de verzenddatum vandaag is. Annuleren kan alleen na overleg.');

        // Given
        $shippingDate = CarbonImmutable::now();
        $daysToAdd = $shippingDate->isSaturday() ? 2: 1;
        $preferredDeliveryDate = $shippingDate->addDays($daysToAdd);

        $order = OrderFactory::create(1, [
            'preferredDeliveryDate' => $preferredDeliveryDate
        ])->first();

        // When
        $order->cancel();

    }

    /** @test */
    public function it_should_cancel_order_on_shipping_date_when_discussed()
    {
        // Given
        $shippingDate = CarbonImmutable::now();
        $daysToAdd = $shippingDate->isSaturday() ? 2: 1;
        $preferredDeliveryDate = $shippingDate->addDays($daysToAdd);

        $order = OrderFactory::create(1, [
            'preferredDeliveryDate' => $preferredDeliveryDate
        ])->first();

        // When
        $order->cancel(true);

        // Then
        self::assertTrue($order->cancelled());
    }

    public function it_should_return_saturday_as_shipping_date_when_preferred_delivery_date_is_monday()
    {
        // Given
        $preferredDeliveryDate = CarbonImmutable::now()->next(CarbonInterface::MONDAY);

        $order = OrderFactory::create(1, [
            'preferredDeliveryDate' => $preferredDeliveryDate
        ])->first();

        // When
        $shippingDate = $order->shippingDate();

        // Then
        self::assertEquals($preferredDeliveryDate->subDays(2), $shippingDate);
    }

    /** @test */
    public function it_should_return_wednesday_as_shipping_date_when_today_is_thursday()
    {
        // Given
        $preferredDeliveryDate = CarbonImmutable::now()->next(CarbonInterface::THURSDAY);

        $order = OrderFactory::create(1, [
            'preferredDeliveryDate' => $preferredDeliveryDate
        ])->first();

        // When
        $shippingDate = $order->shippingDate();

        // Then
        self::assertEquals($preferredDeliveryDate->subDays(1), $shippingDate);
    }

    /** @test */
    public function it_should_return_that_the_order_is_fully_fulfilled()
    {
        // Given
        $order = OrderFactory::constantUnprocessed();
        $shipment = ShipmentFactory::create($order->reference());
        $shipment->changeOrderedItems(collect($order->items()));

        // When
        $order->registerShipment($shipment);

        // Then
        self::assertTrue($order->isFullyFulfilled());
    }

    /** @test */
    public function it_should_return_that_the_order_is_fully_fulfilled_when_ordered_items_are_shuffled()
    {
        // Given
        $order = OrderFactory::constantUnprocessed();
        $shipment = ShipmentFactory::create($order->reference());
        $shuffledOrderedItems = collect();
        $shuffledOrderedItems->push($order->items()[1]);
        $shuffledOrderedItems->push($order->items()[0]);
        $shipment->changeOrderedItems($shuffledOrderedItems);

        // When
        $order->registerShipment($shipment);

        // Then
        self::assertTrue($order->isFullyFulfilled());

    }

    /** @test */
    public function it_should_return_that_the_order_is_not_fully_fulfilled()
    {
        // Given
        $order = OrderFactory::constantUnprocessed();
        $shipment = ShipmentFactory::create($order->reference());
        $orderedItems = $order->items();
        $partiallyDeliveredOrderItem = $orderedItems[0];
        $shipment->changeOrderedItems(collect(array($partiallyDeliveredOrderItem)));

        // When
        $order->registerShipment($shipment);

        // Then
        self::assertFalse($order->isFullyFulfilled());
    }

    /** @test */
    public function it_should_return_that_the_order_is_not_fully_fulfilled_when_amount_of_product_is_less()
    {
        // Given
        $order = OrderFactory::constantUnprocessed();
        $shipment = ShipmentFactory::create($order->reference());

        // When
        $orderedItems = $order->items();
        $amount = $orderedItems[0]->amount() - 1;
        $orderedItems[0]->changeAmount($amount);
        $partiallyDeliveredOrderItem = $orderedItems[0];

        $shipment->changeOrderedItems(collect(array($partiallyDeliveredOrderItem)));
        $order->registerShipment($shipment);

        // Then
        self::assertFalse($order->isFullyFulfilled());
    }

    /** @test */
    public function it_should_not_be_fully_fulfilled_when_order_is_partially_shipped()
    {
        // Given
        $order = OrderFactory::constantUnprocessed();
        $firstShipment = ShipmentFactory::create($order->reference());
        $secondShipment = ShipmentFactory::create($order->reference());
        $thirdShipment = ShipmentFactory::create($order->reference());

        $orderedItems = $order->items();
        $firstOrderedItem = $orderedItems[0];
        $secondOrderedItem = $orderedItems[1];

        $orderedItem = clone $firstOrderedItem;
        $amount = $orderedItems[0]->amount() - 1;
        $orderedItem->changeAmount($amount);

        $firstPartiallyDeliveredOrderedItem = $orderedItem;
        $secondPartiallyDeliveredOrderedItem = clone $firstOrderedItem;
        $secondPartiallyDeliveredOrderedItem->changeAmount(1);


        $firstShipment->changeOrderedItems(collect(array($firstPartiallyDeliveredOrderedItem)));
        $secondShipment->changeOrderedItems(collect(array($secondPartiallyDeliveredOrderedItem)));

        // When
        $order->registerShipment($firstShipment);
        $order->registerShipment($secondShipment);

        // Then
        self::assertFalse($order->isFullyFulfilled());
    }

    /** @test */
    public function it_should_be_fully_fulfilled_when_all_partial_shipments_are_registered()
    {
        // Given
        $order = OrderFactory::constantUnprocessed();
        $firstShipment = ShipmentFactory::create($order->reference());
        $secondShipment = ShipmentFactory::create($order->reference());
        $thirdShipment = ShipmentFactory::create($order->reference());

        $orderedItems = $order->items();
        $firstOrderedItem = $orderedItems[0];
        $secondOrderedItem = $orderedItems[1];

        $orderedItem = clone $firstOrderedItem;
        $amount = $orderedItems[0]->amount() - 1;
        $orderedItem->changeAmount($amount);

        $firstPartiallyDeliveredOrderedItem = $orderedItem;
        $secondPartiallyDeliveredOrderedItem = clone $firstOrderedItem;
        $secondPartiallyDeliveredOrderedItem->changeAmount(1);

        $firstShipment->changeOrderedItems(collect(array($firstPartiallyDeliveredOrderedItem)));
        $secondShipment->changeOrderedItems(collect(array($secondPartiallyDeliveredOrderedItem)));
        $thirdShipment->changeOrderedItems(collect(array($secondOrderedItem)));

        // When
        $order->registerShipment($firstShipment);
        $order->registerShipment($secondShipment);
        $order->registerShipment($thirdShipment);

        // Then
        self::assertTrue($order->isFullyFulfilled());
    }

    /** @test */
    public function it_should_throw_exception_when_shipment_is_registered_with_different_order_reference_than_order()
    {
        // Then
        $this->expectException(OrderOperationException::class);

        // Given
        $order = OrderFactory::constantUnprocessed();
        $differentOrderReference = "Wrong Order Reference 1234";
        $shipment = ShipmentFactory::create($differentOrderReference);
        $shipment->changeOrderedItems(collect($order->items()));

        // When
        $order->registerShipment($shipment);
    }

    /** @test */
    public function it_should_be_fully_fulfilled_when_both_shipments_are_registered()
    {
        // Given
        $order = OrderFactory::constantUnprocessed();
        $firstShipment = ShipmentFactory::create($order->reference());
        $firstShipment->changeOrderedItems(collect(array($order->items()[0])));

        $secondShipment = ShipmentFactory::create($order->reference());
        $secondShipment->changeOrderedItems(collect(array($order->items()[1])));

        // When
        $order->registerShipment($firstShipment);
        $order->registerShipment($secondShipment);

        // Then
        self::assertTrue($order->isFullyFulfilled());
    }

    /** @test */
    public function it_should_log_when_shipment_with_same_id_is_registered_but_with_different_ordered_items()
    {
        // Given
        $order = OrderFactory::constantUnprocessed();
        $firstShipment = ShipmentFactory::create($order->reference());
        $firstShipment->changeOrderedItems(collect(array($order->items()[0])));

        // Then
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message) use($firstShipment) {
                return str_contains($message, "Shipment with reference ".$firstShipment->reference()." is registered twice with different ordered items on order with reference ".$firstShipment->orderReference());
            });

        // When
        $order->registerShipment($firstShipment);
        $firstShipment->changeOrderedItems(collect(array($order->items()[1])));
        $order->registerShipment($firstShipment);

    }

    /** @test */
    public function it_should_complete_order_when_delivery_option_is_null()
    {
        // Given
        $order = OrderFactory::create(1, [
            'delivery_option' => null
        ])->first();

        // When
        $order->complete('Test Employee');

        // Then
        self::assertTrue($order->completed());
    }

    /** @test */
    public function it_should_complete_order_by_delight_when_delivery_option_is_null()
    {
        // Given
        $order = OrderFactory::create(1, [
            'delivery_option' => null
        ])->first();

        // When
        $order->completeByDelight();

        // Then
        self::assertTrue($order->completed());
    }

    /** @test */
    public function it_should_complete_order_by_picqer_when_delivery_option_is_null()
    {
        // Given
        $order = OrderFactory::create(1, [
            'delivery_option' => null
        ])->first();

        // When
        $order->completeByPicqer('Test Employee');

        // Then
        self::assertTrue($order->completed());
    }

    /** @test */
    public function it_should_add_action_when_rejecting_order()
    {
        // Given
        $rejectReason = 'Test Reason';
        $order = OrderFactory::create(1)->first();

        // When
        $order->reject($rejectReason);

        // Then
        $actionDescription = 'Status gewijzigd van Picqer - In behandeling naar Picqer - Afgewezen - Reden: ' . $rejectReason;
        self::assertEquals($actionDescription, $order->actions()->first()->description());
        self::assertEquals('Webservices', $order->actions()->first()->actor());
        self::assertEquals(CarbonImmutable::now()->format('Y-m-d H:i:s'), $order->actions()->first()->createdAt()->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_should_add_action_when_processing_order()
    {
        // Given
        $order = OrderFactory::create(1)->first();

        // When
        $order->process();

        // Then
        $actionDescription = 'Status gewijzigd van Picqer - Te verwerken naar Picqer - In Behandeling';
        self::assertEquals($actionDescription, $order->actions()->first()->description());
        self::assertEquals('Webservices', $order->actions()->first()->actor());
        self::assertEquals(CarbonImmutable::now()->format('Y-m-d H:i:s'), $order->actions()->first()->createdAt()->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_should_add_action_when_completing_order()
    {
        // Given
        $packedBy = 'Test Employee';
        $order = OrderFactory::create(1)->first();

        // When
        $order->complete($packedBy);

        // Then
        $actionDescription = 'Status gewijzigd van Picqer - In behandeling naar Picqer - Verzonden/Afgehaald - Bestelling is ingepakt door: ' . $packedBy;
        self::assertEquals($order->actions()->first()->description(), $actionDescription);
        self::assertEquals($order->actions()->first()->actor(), 'Webservices');
    }

    /** @test */
    public function it_should_add_action_when_completing_order_by_picqer()
    {
        // Given
        $packedBy = 'Test Employee';
        $order = OrderFactory::create(1)->first();

        // When
        $order->completeByPicqer($packedBy);

        // Then
        $actionDescription = 'Status gewijzigd van Picqer - In behandeling naar Picqer - Verzonden/Afgehaald - Bestelling is ingepakt door: ' . $packedBy;
        self::assertEquals($order->actions()->first()->description(), $actionDescription);
        self::assertEquals($order->actions()->first()->actor(), 'Webservices');
    }

    /** @test */
    public function it_should_add_different_action_for_binnen_specialist_orders_when_completing_order()
    {
        // Given
        $packedBy = 'Test Employee';
        $order = OrderFactory::create(1, [
            'delivery_option' => DeliveryOptionFactory::binnenSpecialist()
        ])->first();

        // When
        $order->complete($packedBy);

        // Then
        $actionDescription = 'Status gewijzigd van Picqer - In behandeling naar Verf orders Binnenspecialist - Bestelling is ingepakt door: ' . $packedBy;
        self::assertEquals($order->actions()->first()->description(), $actionDescription);
        self::assertEquals($order->actions()->first()->actor(), 'Webservices');
    }

    /** @test */
    public function it_should_add_different_action_for_binnen_specialist_orders_when_completing_order_by_picqer()
    {
        // Given
        $packedBy = 'Test Employee';
        $order = OrderFactory::create(1, [
            'delivery_option' => DeliveryOptionFactory::binnenSpecialist()
        ])->first();

        // When
        $order->completeByPicqer($packedBy);

        // Then
        $actionDescription = 'Status gewijzigd van Picqer - In behandeling naar Verf orders Binnenspecialist - Bestelling is ingepakt door: ' . $packedBy;
        self::assertEquals($order->actions()->first()->description(), $actionDescription);
        self::assertEquals($order->actions()->first()->actor(), 'Webservices');
    }
}
