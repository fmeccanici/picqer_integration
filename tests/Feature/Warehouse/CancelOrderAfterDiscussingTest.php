<?php

namespace Tests\Feature\Warehouse;

use App\User;
use App\Warehouse\Domain\Orders\OrderFactory;
use App\Warehouse\Domain\Repositories\OrderRepositoryInterface;
use App\Warehouse\Infrastructure\Persistence\InMemory\Repositories\InMemoryOrderRepository;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Passport\Passport;
use Tests\TestCase;

class CancelOrderAfterDiscussingTest extends TestCase
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
    public function it_should_cancel_order_on_shipping_date()
    {
        // Given
        $this->withoutExceptionHandling();
        $shippingDate = CarbonImmutable::now();
        $daysToAdd = $shippingDate->isSaturday() ? 2: 1;
        $preferredDeliveryDate = $shippingDate->addDays($daysToAdd);

        $order = OrderFactory::create(1, [
            'preferredDeliveryDate' => $preferredDeliveryDate
        ])->first();

        $this->orderRepository->add($order);
        $url = route('cancel-order-after-discussing', [
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
