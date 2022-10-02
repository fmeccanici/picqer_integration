<?php


namespace Tests\Unit\Warehouse\Infrastructure\Persistence\MsSql\Repositories;


use App\Warehouse\Domain\Services\DeliveryOptionServiceInterface;
use App\Warehouse\Infrastructure\Persistence\MsSql\Repositories\MsSqlShipmentRepository;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class MsSqlShipmentRepositoryTest extends TestCase
{
    use DatabaseMigrations;

    protected DeliveryOptionServiceInterface $deliveryOptionServiceMock;
    protected MsSqlShipmentRepository $shipmentRepository;
    protected string $salesOrderShipmentId;
    protected string $splitSalesOrderShipmentId;

    protected function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub

        // TODO: Make add method on repository working, such that we do not have to hardcode the reference here
        $this->salesOrderShipmentId = '120100583';
        $this->splitSalesOrderShipmentId = '120100064-1';

        $this->shipmentRepository = new MsSqlShipmentRepository();
    }

    /** @test */
    public function it_should_return_a_shipment_when_non_split_order()
    {
        // Given

        // When
        $shipment = $this->shipmentRepository->findOneByOrderReference($this->salesOrderShipmentId);

        // Then
        self::assertNotNull($shipment);
    }

    /** @test */
    public function it_should_return_a_shipment_when_split_order()
    {
        // Given

        // When
        $shipment = $this->shipmentRepository->findOneByOrderReference($this->splitSalesOrderShipmentId);

        // Then
        self::assertNotNull($shipment);
    }
}
