<?php


namespace Tests\Unit\Warehouse;


use App\Warehouse\Domain\Exceptions\PackingSlipGeneratorOperationException;
use App\Warehouse\Domain\Orders\OrderedItemFactory;
use App\Warehouse\Domain\Shipments\PackingSlip;
use App\Warehouse\Infrastructure\ApiClients\PicqerApiClient;
use App\Warehouse\Infrastructure\Exporters\PicqerPackingSlipGenerator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\MockObject\MockObject;
use Picqer\Api\Client;
use Tests\TestCase;

class PicqerPackingSlipGeneratorTest extends TestCase
{
    /** @test */
    public function it_should_save_a_packing_slip_in_the_storage_and_have_the_correct_path_and_url()
    {
        // Given
        $picklistId = 1;
        $storage = Storage::fake();

        $picqerClientMock = $this->createMock(Client::class);
        $picqerClientMock->method('getPackingListPdf')
            ->with($picklistId)
            ->willReturn(["success" => true, "data" => ['idpicklist' => $picklistId, 'pdf' => 'PDF content']]);


        $picqerPackingSlipGenerator = new PicqerPackingSlipGenerator(
            $this->getPicqerApiClientMock($picqerClientMock),
            $storage
        );

        // When
        $packingSlip = $picqerPackingSlipGenerator->generateFor(OrderedItemFactory::multipleRandom(2, null, $picklistId));

        // Then
        self::assertInstanceOf(PackingSlip::class, $packingSlip);
        self::assertEquals(
            $storage->path(sprintf(PicqerPackingSlipGenerator::DEFAULT_FILENAME, $picklistId)),
            $packingSlip->path()
        );

        self::assertEquals(
            URL::signedRoute('stream-picklist', [
                'picklistId' => $picklistId
            ]),
            $packingSlip->url()
        );
    }

    /** @test */
    public function it_should_throw_an_exception_when_no_order_item_was_found()
    {
        // Given
        $picklistId = 404;
        $storage = Storage::fake();

        $picqerClientMock = $this->createMock(Client::class);
        $picqerClientMock->method('getPackingListPdf')
            ->with($picklistId)
            ->willReturn(["success" => false, "data" => []]);

        $picqerPackingSlipGenerator = new PicqerPackingSlipGenerator(
            $this->getPicqerApiClientMock($picqerClientMock),
            $storage
        );

        // Expect
        $this->expectException(PackingSlipGeneratorOperationException::class);
        $this->expectErrorMessage("No order item found for packing slip");

        // When
        $picqerPackingSlipGenerator->generateFor(collect());
    }

    /** @test */
    public function it_should_throw_an_exception_when_the_picklist_data_was_invalid()
    {
        // Given
        $picklistId = 404;
        $storage = Storage::fake();

        $picqerClientMock = $this->createMock(Client::class);
        $picqerClientMock->method('getPackingListPdf')
            ->with($picklistId)
            ->willReturn(["success" => false, "data" => []]);

        $picqerPackingSlipGenerator = new PicqerPackingSlipGenerator(
            $this->getPicqerApiClientMock($picqerClientMock),
            $storage
        );

        // Expect
        $this->expectException(PackingSlipGeneratorOperationException::class);
        $this->expectErrorMessage("Failed getting packing slip for picklist with id " . $picklistId);

        // When
        $picqerPackingSlipGenerator->generateFor(OrderedItemFactory::multipleRandom(2, null, $picklistId));
    }

    /**
     * @param MockObject|Client $picqerClientMock
     * @return PicqerApiClient|MockObject
     */
    protected function getPicqerApiClientMock(MockObject|Client $picqerClientMock): PicqerApiClient|MockObject
    {
        $picqerApiClientMock = $this->createMock(PicqerApiClient::class);
        $picqerApiClientMock->method('getClient')
            ->willReturn($picqerClientMock);
        return $picqerApiClientMock;
    }
}
