<?php


namespace Tests\Unit\Warehouse\Infrastructure\Persistence\MsSql\Repositories;


use App\Warehouse\Domain\Orders\DeliveryOption;
use App\Warehouse\Domain\Services\DeliveryOptionServiceInterface;
use App\Warehouse\Domain\Services\ShippingServiceInterface;
use App\Warehouse\Infrastructure\Persistence\MsSql\Repositories\MsSqlOrderRepository;
use App\Warehouse\Infrastructure\Persistence\MsSql\Repositories\MsSqlPicklistRepository;
use Tests\TestCase;

// TODO: Mock DB facade such that tests run faster
class MsSqlPicklistRepositoryTest extends TestCase
{
    private MsSqlPicklistRepository $picklistRepository;
    private MsSqlOrderRepository $orderRepository;
    /**
     * @var DeliveryOption
     */
    private DeliveryOption $deliveryOption;

    protected function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub

        $this->picklistRepository = new MsSqlPicklistRepository();
        $this->deliveryOption  = new DeliveryOption("Post NL", "PostNL - Pakket", 3085, 118, 006);

        $mock = $this->createMock(DeliveryOptionServiceInterface::class);
        $mock->method('getDeliveryOption')
            ->willReturn($this->deliveryOption);

        $this->orderRepository = new MsSqlOrderRepository($mock);
    }

    /** @test */
    public function it_should_update_the_comments_when_order_is_split()
    {
        // TODO: Do not hardcode reference but use add() method
        $reference = "328894-3623";
        $picklist = $this->picklistRepository->findByReference($reference);
        $picklist->changeStatus("PICQERT");

        $comments = uniqid();
        $picklist->changeComments($comments);

        $this->picklistRepository->update($picklist);

        $foundPicklist = $this->picklistRepository->findByReference($reference);
        self::assertEquals($reference, $foundPicklist->reference());
        self::assertEquals($comments, $foundPicklist->comments());
    }

    /** @test */
    public function it_should_update_the_comments_when_order_is_not_split()
    {
        // TODO: Do not hardcode reference but use add() method
        $reference = "68376";
        $picklist = $this->picklistRepository->findByReference($reference);

        $comments = uniqid();
        $picklist->changeComments($comments);
        $picklist->changeStatus("PICQERT");

        $this->picklistRepository->update($picklist);

        $foundPicklist = $this->picklistRepository->findByReference($reference);
        self::assertEquals($reference, $foundPicklist->reference());
        self::assertEquals($comments, $foundPicklist->comments());
    }

    /** @test */
    public function it_should_return_a_picklist_with_comments()
    {
        $reference = "68376";
        $picklist = $this->picklistRepository->findByReference($reference);
        self::assertEquals($reference, $picklist->reference());
        self::assertNotNull($picklist);
        self::assertNotNull($picklist->comments());
    }
}