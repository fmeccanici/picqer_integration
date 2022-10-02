<?php

namespace App\Warehouse\Domain\Orders;

class DeliveryOptionFactory
{
    public static function binnenSpecialist(): DeliveryOption
    {
        return new DeliveryOption(null, 'Showroom - Binnenspecialist', null, null, null, 'NL');
    }
}
