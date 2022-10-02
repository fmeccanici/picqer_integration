<?php


namespace App\Warehouse\Domain\Orders;


final class OrderedItemEqualityComparer
{
    public static function equals(?OrderedItem $lhs, ?OrderedItem $rhs): bool
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
