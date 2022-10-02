<?php


namespace App\Warehouse\Infrastructure\Persistence\Picqer\Orders\Mappers;


use App\Warehouse\Domain\Orders\OrderedItemFactory;
use App\Warehouse\Domain\Orders\ProductFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class OrderedItemMapper
{
    public static function toEntity(array $picqerOrderedItem)
    {
        $productCode = $picqerOrderedItem["productcode"];
        $amount = $picqerOrderedItem["amount"];
        $idPicklist = Arr::get($picqerOrderedItem, 'idpicklist');

        $product = ProductFactory::productWithoutProductGroup($productCode);

        $orderedItem = OrderedItemFactory::orderedItem(null, $product, $amount);
        $orderedItem->changePicklistId($idPicklist);
        return $orderedItem;
    }

    public static function toEntities(array $picqerOrderedItems): Collection
    {
        $picqerOrderedItems = collect($picqerOrderedItems);
        return $picqerOrderedItems->map(function (array $picqerOrderedItem) {
                return self::toEntity($picqerOrderedItem);
        });
    }
}
