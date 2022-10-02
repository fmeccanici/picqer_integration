<?php


namespace Tests\Feature\Warehouse;


use App\PostNL\Models\Carrier;
use Carbon\CarbonImmutable;

class DummyCarrier implements \App\PostNL\Classes\Carrier
{

    public function getEstimatedDeliveryDate(string $shippingDate, \App\PostNL\Models\Carrier $carrier): string
    {
        return CarbonImmutable::createFromFormat('d-m-Y', $shippingDate)->format("d-m-Y");
    }
}
