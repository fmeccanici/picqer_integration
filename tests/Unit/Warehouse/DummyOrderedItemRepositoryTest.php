<?php


namespace Tests\Unit\Warehouse;


use App\Warehouse\Domain\Orders\OrderedItemFactory;
use Tests\Feature\Warehouse\DummyOrderedItemRepository;
use Tests\TestCase;

class DummyOrderedItemRepositoryTest extends TestCase
{
    /**
     * @var DummyOrderedItemRepository
     */
    private DummyOrderedItemRepository $orderedItemRepository;

    protected function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub

        $this->orderedItemRepository = new DummyOrderedItemRepository();
    }

    /** @test */
    public function it_should_update_the_picklist_id()
    {
        // Given
        $orderReference = "12345678";
        $orderedItems = OrderedItemFactory::multipleRandom(50, $orderReference);
        $orderReference = $orderedItems->first()->orderReference();

        $this->orderedItemRepository->addMultiple($orderedItems);

        // When
        $picklistId = "666-666";
        foreach ($orderedItems as $orderedItem)
        {
            $orderedItem->changePicklistId($picklistId);
        }

        $this->orderedItemRepository->updateMultiple($orderedItems);

        // Then
        $orderedItems = $this->orderedItemRepository->findByOrderReference($orderReference);
        foreach ($orderedItems as $orderedItem)
        {
            self::assertEquals($picklistId, $orderedItem->picklistId());
        }
    }

    /** @test */
    public function it_should_add_ordered_items()
    {
        // Given
        $orderReference = "12345678";
        $orderedItems = OrderedItemFactory::multipleRandom(50, $orderReference);
        $orderReference = $orderedItems->first()->orderReference();

        // When
        $this->orderedItemRepository->addMultiple($orderedItems);

        // Then
        self::assertEquals($orderedItems, $this->orderedItemRepository->findByOrderReference($orderReference));
    }

}
