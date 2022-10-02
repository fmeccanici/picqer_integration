<?php


namespace App\Warehouse\Domain\Orders;


final class ShippingDateEstimationEqualityComparer
{
    public static function equals(?ShippingDateEstimation $lhs, ?ShippingDateEstimation $rhs): bool
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
