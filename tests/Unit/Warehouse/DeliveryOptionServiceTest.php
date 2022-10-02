<?php


namespace Tests\Unit\Warehouse;


use App\Warehouse\Infrastructure\Services\DeliveryOptionService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;


class DeliveryOptionServiceTest extends TestCase
{
    use DatabaseMigrations;

    /**
     * @var DeliveryOptionService
     */
    private DeliveryOptionService $deliveryService;

    protected function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $this->deliveryService = new DeliveryOptionService();
        $this->artisan('db:seed --class=DeliveryOptionsSeeder');
    }

    /** @test */
    public function it_should_return_the_correct_delivery_option_1() {

        // Given
        $carrierName = "PostNL";
        $country = "Nederland";
        $deliveryOptionName = "PostNL - Pakket";

        // When
        $deliveryOption = $this->deliveryService->getDeliveryOption($carrierName, $deliveryOptionName, $country);

        // Then
        self::assertEquals(3085, $deliveryOption->productCode());
        self::assertEquals(null, $deliveryOption->characteristic());
        self::assertEquals(null, $deliveryOption->option());
    }


    /** @test */
    public function it_should_return_the_correct_delivery_option_2() {

        // Given
        $carrierName = "PostNL";
        $country = "Nederland";
        $deliveryOptionName = "PostNL - Avond";

        // When
        $deliveryOption = $this->deliveryService->getDeliveryOption($carrierName, $deliveryOptionName, $country);

        // Then
        self::assertEquals(3085, $deliveryOption->productCode());
        self::assertEquals(118, $deliveryOption->characteristic());
        self::assertEquals(006, $deliveryOption->option());
    }

    /** @test */
    public function it_should_return_the_correct_delivery_option_3() {

        // Given
        $carrierName = "PostNL";
        $country = "Nederland";
        $deliveryOptionName = "PostNL - Afhaalpunt";

        // When
        $deliveryOption = $this->deliveryService->getDeliveryOption($carrierName, $deliveryOptionName, $country);

        // Then
        self::assertEquals(3533, $deliveryOption->productCode());
    }

    /** @test */
    public function it_should_return_the_correct_delivery_option_4() {

        // Given
        $carrierName = "PostNL";
        $country = "Belgie";
        $deliveryOptionName = "PostNL - Pakket";

        // When
        $deliveryOption = $this->deliveryService->getDeliveryOption($carrierName, $deliveryOptionName, $country);

        // Then
        self::assertEquals(4946, $deliveryOption->productCode());
        self::assertEquals(null, $deliveryOption->characteristic());
        self::assertEquals(null, $deliveryOption->option());
    }

    /** @test */
    public function it_should_return_the_correct_delivery_option_5() {

        // Given
        $carrierName = "PostNL";
        $country = "Belgie";
        $deliveryOptionName = "PostNL - Afhaalpunt";

        // When
        $deliveryOption = $this->deliveryService->getDeliveryOption($carrierName, $deliveryOptionName, $country);

        // Then
        self::assertEquals(4936, $deliveryOption->productCode());
        self::assertEquals(null, $deliveryOption->characteristic());
        self::assertEquals(null, $deliveryOption->option());
    }

    /** @test */
    public function it_should_return_the_correct_delivery_option_6() {

        // Given
        $carrierName = "PostNL";
        $country = "De wereld";
        $deliveryOptionName = "PostNL - Pakket";

        // When
        $deliveryOption = $this->deliveryService->getDeliveryOption($carrierName, $deliveryOptionName, $country);

        // Then
        self::assertEquals(4920, $deliveryOption->productCode());
        self::assertEquals(null, $deliveryOption->characteristic());
        self::assertEquals(null, $deliveryOption->option());
    }

}
