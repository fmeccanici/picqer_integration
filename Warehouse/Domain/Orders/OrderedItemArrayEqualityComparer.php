<?php


namespace App\Warehouse\Domain\Orders;


final class OrderedItemArrayEqualityComparer
{
    public static function equals(array $lhs, array $rhs): bool
    {
        $sizeLhs = count($lhs);
        $sizeRhs = count($rhs);

        if ($sizeLhs !== $sizeRhs)
        {
            return false;
        }
        else
        {
            for ($i = 0; $i < $sizeLhs; $i++)
            {
                if (! OrderedItemEqualityComparer::equals($lhs[$i], $rhs[$i])) {
                    return false;
                }
            }

            return true;
        }
    }
}
