<?php


namespace App\Warehouse\Domain\Orders;


final class DeliveryDateEstimationEqualityComparer
{
    public static function equals(?DeliveryDateEstimation $lhs, ?DeliveryDateEstimation $rhs): bool
    {
        if (isset($lhs) && isset($rhs))
        {
            return $lhs->equalTo($rhs);
        }
        else
        {
            return is_null($lhs) && is_null($rhs);
        }
    }
}
