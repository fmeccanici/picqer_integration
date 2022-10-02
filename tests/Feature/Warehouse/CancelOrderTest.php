<?php

namespace Tests\Feature\Warehouse;

use App\User;
use App\Warehouse\Domain\Exceptions\OrderOperationException;
use App\Warehouse\Domain\Orders\OrderFactory;
use App\Warehouse\Domain\Repositories\OrderRepositoryInterface;
use App\Warehouse\Infrastructure\Persistence\InMemory\Repositories\InMemoryOrderRepository;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Passport\Passport;
use Mockery\MockInterface;
use Tests\TestCase;

class CancelOrderTest extends TestCase
{
    use DatabaseMigrations;

    protected InMemoryOrderRepository $orderRepository;

    protected function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $this->orderRepository = new InMemoryOrderRepository();
        $this->app->bind(OrderRepositoryInterface::class, function () {return $this->orderRepository;});
        Passport::actingAs(User::factory()->create());
    }

    /** @test */
    public function it_should_cancel_the_order_when_not_on_shipping_date_and_order_is_not_completed()
    {
        // Given
        $this->withoutExceptionHandling();
        $preferredDeliveryDate = CarbonImmutable::now()->addWeek();

        $order = OrderFactory::create(1, [
            'preferredDeliveryDate' => $preferredDeliveryDate
        ])->first();

        $this->orderRepository->add($order);
        $url = route('cancel-order', [
            'orderReference' => $order->reference(),
            'discussed' => 0
        ]);

        // When
        $response = $this->get($url);

        // Then
        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);

        $foundOrder = $this->orderRepository->findOneByReference($order->reference());
        self::assertTrue($foundOrder->cancelled());
    }

    /** @test */
    public function it_should_return_error_message_when_domain_exception_is_thrown()
    {
        // Given
        $errorMessage = 'Test Domain Exception';
        $orderRepository = $this->mock(OrderRepositoryInterface::class, function (MockInterface $mock) use ($errorMessage) {
            $mock->shouldReceive('findOneByReference')
                ->once()
                ->andThrow(new OrderOperationException($errorMessage));
        });

        $this->app->bind(OrderRepositoryInterface::class, function () use ($orderRepository) {return $orderRepository;});

        $order = OrderFactory::create(1)->first();

        $url = route('cancel-order', [
            'orderReference' => $order->reference(),
            'discussed' => 0
        ]);

        // When
        $response = $this->get($url);

        // Then
        $response->assertOk();
        $response->assertJson([
            'error' => $errorMessage,
            'success' => false,
            'data' => null
        ]);
    }

    /** @test */
    public function it_should_cancel_the_order_when_is_is_not_completed_and_shipping_date_is_not_today()
    {
        // Given
        $this->withoutExceptionHandling();
        $preferredDeliveryDate = CarbonImmutable::now()->addWeek();

        $order = OrderFactory::create(1, [
            'preferredDeliveryDate' => $preferredDeliveryDate
        ])->first();

        $this->orderRepository->add($order);
        $url = route('cancel-order', [
            'orderReference' => $order->reference()
        ]);

        // When
        $response = $this->get($url);

        // Then
        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);

        $foundOrder = $this->orderRepository->findOneByReference($order->reference());
        self::assertTrue($foundOrder->cancelled());
    }
}
