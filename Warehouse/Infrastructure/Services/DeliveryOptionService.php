<?php


namespace App\Warehouse\Infrastructure\Services;

use App\Warehouse\Domain\Orders\DeliveryOption;
use App\Warehouse\Domain\Services\DeliveryOptionServiceInterface;
use App\Warehouse\Infrastructure\Persistence\Eloquent\DeliveryOptions\EloquentDeliveryOption;

class DeliveryOptionService implements DeliveryOptionServiceInterface
{

    /**
     * @param ?string $carrierName
     * @param string $deliveryOptionName
     * @param string|null $deliveryCountry
     * @return DeliveryOption|null
     */
    public function getDeliveryOption(?string $carrierName, string $deliveryOptionName, ?string $deliveryCountry): ?DeliveryOption
    {
        // TODO: Change to GetDeliveryOption use case
        if($model = EloquentDeliveryOption::where([
            "carrier_name" => $carrierName,
            "name" => $deliveryOptionName,
            "country" => $deliveryCountry
        ])->first()) {
            return new DeliveryOption($model->carrier_name, $model->name, $model->product_code, $model->characteristic, $model->option);
        }

        return null;
    }
}
