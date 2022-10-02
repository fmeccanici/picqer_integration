<?php


namespace Tests\Feature\Warehouse;


use App\SharedKernel\AddressFactory;
use App\User;
use App\Warehouse\Domain\Exporters\PackingSlipGeneratorInterface;
use App\Warehouse\Domain\Orders\OrderFactory;
use App\Warehouse\Domain\Repositories\OrderRepositoryInterface;
use App\Warehouse\Domain\Repositories\ShipmentRepositoryInterface;
use App\Warehouse\Domain\Services\ReviewRequestSenderServiceInterface;
use App\Warehouse\Domain\Shipments\ShipmentFactory;
use App\Warehouse\Infrastructure\Persistence\InMemory\Repositories\InMemoryOrderRepository;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Passport\Passport;
use Mockery\MockInterface;
use Tests\TestCase;

class ChangeDeliveryOptionTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(PackingSlipGeneratorInterface::class, DummyPackingSlipGenerator::class);
        $this->artisan('db:seed --class=DeliveryOptionsSeeder');
        $this->app->bind(ReviewRequestSenderServiceInterface::class, DummyReviewRequestSenderService::class);
    }

    /** @test */
    public function it_should_change_delivery_option_of_order() {

        $this->withoutExceptionHandling();

        // Given
        Passport::actingAs(User::factory()->create());

        $url = route("change-delivery-option-of-order");
        $order = OrderFactory::create(1)->first();

        $shipmentRepositoryMock = $this->partialMock(ShipmentRepositoryInterface::class, function (MockInterface $mock) use ($order){
            $mock->shouldReceive('findAllByOrderReference')
                ->once()
                ->andReturn(collect(array()));
        });

        $this->app->instance(ShipmentRepositoryInterface::class, $shipmentRepositoryMock);

        $orderRepository = new InMemoryOrderRepository();
        $orderRepository->add($order);

        $this->app->bind(OrderRepositoryInterface::class, function () use ($orderRepository) {return $orderRepository;});

        // When
        $deliveryOptionName = "PostNL - Afhaalpunt";
        $carrierName = "PostNL";
        $country = "Nederland";
        $deliveryAddress = AddressFactory::fromStreetAddress("TestPickupPointAddress 123",
                                                            "TestPickupPointCity",
                                                                "TestPickupPointZipcode 1234",
                                                                "Nederland");
        $deliveryAddress->changeName("Test Name");

        $response = $this->put($url, [
            "delivery_option" => [
                "name" => $deliveryOptionName,
                "carrier_name" => $carrierName,
                "country" => $country
            ],
            "order_reference" => $order->reference(),
            "delivery_address" => [
                "name" => $deliveryAddress->name(),
                "street" => $deliveryAddress->fullStreetAddress(),
                "zipcode" => $deliveryAddress->zipcode(),
                "city" => $deliveryAddress->city()
            ]
        ]);


        // Then
        $updatedOrder = $orderRepository->findOneByReference($order->reference());
        $response->assertOk();
        self::assertEquals($deliveryOptionName, $updatedOrder->deliveryOption()->name());
        self::assertEquals($carrierName, $updatedOrder->deliveryOption()->carrier());
        self::assertEquals($country, $updatedOrder->customer()->deliveryAddress()->country());
        self::assertEquals($deliveryAddress->name(), $updatedOrder->customer()->deliveryAddress()->name());
        self::assertEquals($deliveryAddress->fullStreetAddress(), $updatedOrder->customer()->deliveryAddress()->fullStreetAddress());
        self::assertEquals($deliveryAddress->zipcode(), $updatedOrder->customer()->deliveryAddress()->zipcode());
        self::assertEquals($deliveryAddress->city(), $updatedOrder->customer()->deliveryAddress()->city());
    }

    /** @test */
    public function it_should_return_error_message_when_shipments_exist_for_order() {

        // Given
        Passport::actingAs(User::factory()->create());

        $url = route("change-delivery-option-of-order");
        $order = OrderFactory::create(1)->first();

        $shipmentRepositoryMock = $this->partialMock(ShipmentRepositoryInterface::class, function (MockInterface $mock) use ($order){
            $mock->shouldReceive('findAllByOrderReference')
                ->once()
                ->andReturn(collect(array(ShipmentFactory::create(2))));
        });

        $this->app->instance(ShipmentRepositoryInterface::class, $shipmentRepositoryMock);

        $orderRepository = new InMemoryOrderRepository();
        $orderRepository->add($order);

        $this->app->bind(OrderRepositoryInterface::class, function () use ($orderRepository) {return $orderRepository;});

        // When
        $deliveryOptionName = "PostNL - Afhaalpunt";
        $carrierName = "PostNL";
        $country = "Nederland";
        $deliveryAddress = AddressFactory::fromStreetAddress("TestPickupPointAddress 123",
            "TestPickupPointCity",
            "TestPickupPointZipcode 1234",
            "Nederland");
        $deliveryAddress->changeName("Test Delivery Address Name");

        // When
        $response = $this->put($url, [
            "delivery_option" => [
                "name" => $deliveryOptionName,
                "carrier_name" => $carrierName,
                "country" => $country
            ],
            "order_reference" => $order->reference(),
            "delivery_address" => [
                "name" => $deliveryAddress->name(),
                "street" => $deliveryAddress->fullStreetAddress(),
                "zipcode" => $deliveryAddress->zipcode(),
                "city" => $deliveryAddress->city()
            ]
        ]);

        // Then
        $response->assertJson([
            "meta" => time(),
            "error" => [
                "code" => 500,
                "message" => "Kan geen bezorgoptie wijzigen, want er is al een zending voor bestelling " . $order->reference()
            ]
        ]);
    }
}
