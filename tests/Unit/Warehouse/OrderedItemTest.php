<?php


namespace Tests\Unit\Warehouse;


use App\Warehouse\Domain\Exporters\PackingSlipGeneratorInterface;
use App\Warehouse\Domain\Orders\OrderedItemFactory;
use App\Warehouse\Domain\Orders\ProductFactory;
use App\Warehouse\Domain\Shipments\ShipmentFactory;
use Tests\Feature\Warehouse\DummyPackingSlipGenerator;
use Tests\TestCase;

class OrderedItemTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub

        $this->app->bind(PackingSlipGeneratorInterface::class, DummyPackingSlipGenerator::class);
    }

    /** @test */
    public function it_should_not_be_fully_fulfilled_when_not_all_quantities_are_fulfilled()
    {
        // Given
        $orderReference = "123456";
        $product = ProductFactory::productWithoutProductGroup("Test Product");
        $orderedItem = OrderedItemFactory::orderedItem(1, $product, 5, $orderReference);

        $shipment = ShipmentFactory::create($orderReference);
        $orderedItemsPartiallyShipped = clone $orderedItem;
        $orderedItemsPartiallyShipped->changeAmount(3);

        $shipment->changeOrderedItems(collect(array($orderedItemsPartiallyShipped)));

        // When
        $orderedItem->shipWith($shipment);

        // Then
        self::assertFalse($orderedItem->isFullyFulfilled());
    }

    /** @test */
    public function it_should_be_fully_fulfilled_when_all_quantities_are_fulfilled()
    {
        // Given
        $orderReference = "123456";
        $product = ProductFactory::productWithoutProductGroup("Test Product");
        $orderedItem = OrderedItemFactory::orderedItem(1, $product, 5, $orderReference);

        $shipment1 = ShipmentFactory::create($orderReference);
        $orderedItemsPartiallyShipped1 = clone $orderedItem;
        $orderedItemsPartiallyShipped1->changeAmount(3);

        $shipment1->changeOrderedItems(collect(array($orderedItemsPartiallyShipped1)));

        $shipment2 = ShipmentFactory::create($orderReference);
        $orderedItemsPartiallyShipped2 = clone $orderedItem;
        $orderedItemsPartiallyShipped2->changeAmount(2);

        $shipment2->changeOrderedItems(collect(array($orderedItemsPartiallyShipped2)));

        // When
        $orderedItem->shipWith($shipment1);
        $orderedItem->shipWith($shipment2);

        // Then
        self::assertTrue($orderedItem->isFullyFulfilled());
    }
}
