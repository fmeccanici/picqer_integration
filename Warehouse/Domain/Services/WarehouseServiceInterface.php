<?php


namespace App\Warehouse\Domain\Services;


use App\Warehouse\Domain\Orders\DeliveryOption;
use App\Warehouse\Domain\Picklists\Picklist;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

interface WarehouseServiceInterface
{
    public function snoozePicklistUntil(string|int $picklistId, CarbonImmutable $snoozeUntil): Picklist;
    public function unsnoozePicklist(string $picklistReference): Picklist;
    public function handlePicklistCreated(string $picklistReference);
    public function estimateShippingDate(Collection $orderedItems): CarbonImmutable;

    /**
     * @param string $country
     * @param string $deliveryOptionName
     * @param string $carrierName
     * @param string|null $locationCode
     * @param string|null $retailNetworkId
     * @return DeliveryOption
     */
    public function getDeliveryOption(string $country, string $deliveryOptionName, string $carrierName, ?string $locationCode = null, ?string $retailNetworkId = null): ?DeliveryOption;
}
