<?php


namespace Tests\Feature\Warehouse;


use App\Warehouse\Application\HandlePicklistCreated\HandlePicklistCreated;
use App\Warehouse\Application\HandlePicklistCreated\HandlePicklistCreatedInput;
use App\Warehouse\Domain\Orders\OrderFactory;
use App\Warehouse\Domain\Repositories\PicklistRepositoryInterface;
use App\Warehouse\Domain\Services\ReviewRequestSenderServiceInterface;
use App\Warehouse\Infrastructure\Persistence\InMemory\Repositories\InMemoryCollectionPicklistRepository;
use App\Warehouse\Infrastructure\Persistence\InMemory\Repositories\InMemoryCollectionShipmentRepository;
use App\Warehouse\Infrastructure\Persistence\InMemory\Repositories\InMemoryOrderRepository;
use App\Warehouse\Infrastructure\Services\WarehouseService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Tests\TestCase;
use Tests\Unit\Warehouse\DummyMailerService;

class HandlePicklistCreatedTest extends TestCase
{
    private InMemoryCollectionPicklistRepository $picklistRepository;
    private WarehouseService $warehouseService;
    private DummyReviewRequestSenderService $reviewRequestSender;
    private DummyMailerService $mailerService;
    private DummyCustomerRepository $customerRepository;
    private InMemoryOrderRepository $orderRepository;
    private InMemoryCollectionShipmentRepository $shipmentRepository;

    protected function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $this->picklistRepository = new InMemoryCollectionPicklistRepository();
        $this->reviewRequestSender = new DummyReviewRequestSenderService();
        $this->mailerService = new DummyMailerService();
        $this->customerRepository = new DummyCustomerRepository();
        $this->orderRepository = new InMemoryOrderRepository();
        $this->orderFulfillmentService = new DummyOrderFulfillmentService();
        $this->shipmentRepository = new InMemoryCollectionShipmentRepository();
        $this->warehouseService = new WarehouseService($this->picklistRepository, $this->mailerService,
                                                        $this->reviewRequestSender, $this->customerRepository,
                                                        $this->orderRepository, $this->orderFulfillmentService,
                                                        $this->shipmentRepository);

        $this->app->bind(PicklistRepositoryInterface::class, InMemoryCollectionPicklistRepository::class);
        $this->app->bind(ReviewRequestSenderServiceInterface::class, DummyReviewRequestSenderService::class);

    }

    /** @test */
    public function it_should_add_tag_to_order_of_picklist()
    {
        // Given
        $preferredDeliveryDate = CarbonImmutable::now()->next(CarbonInterface::MONDAY);
        $order = OrderFactory::create(1, [
            'preferredDeliveryDate' => $preferredDeliveryDate,
            'status' => 'new'
        ])->first();

        $picklist = $order->picklists()->first();
        $picklist->changePreferredDeliveryDate($preferredDeliveryDate);
        $this->picklistRepository->add($picklist);
        $this->orderRepository->add($order);
        $useCase = new HandlePicklistCreated($this->picklistRepository, $this->warehouseService, $this->orderRepository);
        $useCaseInput = new HandlePicklistCreatedInput([
            'picklist_id' => $picklist->id()
        ]);

        // When
        $useCaseResult = $useCase->execute($useCaseInput);
        $order = $this->orderRepository->findOneByReference($order->reference());

        // Then
        self::assertNotEmpty($order->tags());
        self::assertEquals('Verwerkbaar', $order->tags()->first());
    }

    /** @test */
    public function it_should_snooze_picklist()
    {
        // Given
        $preferredDeliveryDate = CarbonImmutable::now()->next(CarbonInterface::MONDAY);
        $order = OrderFactory::create(1, [
            'preferredDeliveryDate' => $preferredDeliveryDate,
            'status' => 'new'
        ])->first();

        $picklist = $order->picklists()->first();
        self::assertNull($picklist->snoozedUntil());
        $picklist->changePreferredDeliveryDate($preferredDeliveryDate);
        $this->picklistRepository->add($picklist);
        $this->orderRepository->add($order);
        $useCase = new HandlePicklistCreated($this->picklistRepository, $this->warehouseService, $this->orderRepository);
        $useCaseInput = new HandlePicklistCreatedInput([
            'picklist_id' => $picklist->id()
        ]);

        // When
        $useCaseResult = $useCase->execute($useCaseInput);
        $order = $this->orderRepository->findOneByReference($order->reference());

        // Then
        self::assertNotNull($picklist->snoozedUntil());
    }
}
