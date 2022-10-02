<?php


namespace App\Warehouse\Domain\Services;


use App\Warehouse\Domain\Orders\DeliveryOption;

interface DeliveryOptionServiceInterface
{
    /**
     * @param string $carrierName
     * @param string $deliveryOptionName
     * @param string $deliveryCountry
     * @return DeliveryOption|null
     */
    public function getDeliveryOption(string $carrierName, string $deliveryOptionName, string $deliveryCountry): ?DeliveryOption;
}
