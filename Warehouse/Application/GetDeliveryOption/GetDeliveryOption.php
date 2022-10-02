<?php


namespace App\Warehouse\Application\GetDeliveryOption;

use App\Warehouse\Domain\Services\DeliveryOptionServiceInterface;

class GetDeliveryOption implements GetDeliveryOptionInterface
{
    /**
     * @var DeliveryOptionServiceInterface
     */
    private DeliveryOptionServiceInterface $deliveryOptionService;

    /**
     * GetDeliveryOption constructor.
     * @param DeliveryOptionServiceInterface $deliveryOptionService
     */
    public function __construct(DeliveryOptionServiceInterface $deliveryOptionService)
    {
        $this->deliveryOptionService = $deliveryOptionService;
    }

    /**
     * @inheritDoc
     */
    public function execute(GetDeliveryOptionInput $input): GetDeliveryOptionResult
    {
        $deliveryOption = $this->deliveryOptionService->getDeliveryOption($input->carrierName(), $input->deliveryOptionName(), $input->deliveryCountry());
        return new GetDeliveryOptionResult($deliveryOption);
    }
}
