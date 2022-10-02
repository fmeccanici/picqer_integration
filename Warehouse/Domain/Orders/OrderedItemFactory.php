<?php


namespace App\Warehouse\Domain\Orders;


use App\Warehouse\Infrastructure\Exceptions\OrderedItemFactoryException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class OrderedItemFactory
{
    public static function create(int $quantity = 1, array $attributes = []): Collection
    {
        $orderedItems = collect();

        for ($i = 0; $i < $quantity; $i++)
        {
            $productCode = Arr::get($attributes, 'productCode');

            if (! $productCode)
            {
                $productCode = uniqid();
            }

            $productGroup = Arr::get($attributes, 'productGroup');

            if (! $productGroup)
            {
                $productGroup = uniqid();
            }

            $amount = Arr::get($attributes, 'amount');

            if ($amount === null)
            {
                $amount = random_int(1, 100);
            }

            $orderReference = Arr::get($attributes, 'orderReference');

            if ($orderReference === null)
            {
                $orderReference = uniqid();
            }

            $product = ProductFactory::productWithProductGroup($productCode, $productGroup);
            $orderedItem = self::orderedItem(null, $product, $amount, $orderReference);
            $orderedItems->push($orderedItem);
        }

        return $orderedItems;
    }

    public static function createRails(int $quantity = 1, array $attributes = []): Collection
    {
        $productGroup = 'rails';

        return self::create($quantity, [
            'productGroup' => $productGroup
        ]);
    }

    public static function orderedItem(?int $id, Product $product, int $amount, ?string $orderReference = null, ?string $picklistId = null)
    {
        $orderedItem = new OrderedItem($product, "OnStock", null, null, null, $orderReference, $picklistId);
        $orderedItem->changeAmount($amount);
        return $orderedItem;
    }

    /**
     * @throws OrderedItemFactoryException
     */
    public static function fromArray(array $orderedItem): OrderedItem
    {
        $productId = Arr::get($orderedItem, 'product_id');

        if (! $productId)
        {
            throw new OrderedItemFactoryException('Product id cannot be null');
        }

        if (! $productGroup = Arr::get($orderedItem, 'product_group'))
        {
            $product = ProductFactory::productWithoutProductGroup($productId);
        } else {
            $product = ProductFactory::product($productId, $productGroup);
        }

        $shippingDateEstimation = null;
        if (isset($orderedItem["shipping_date_estimation"]))
        {
            $value = $orderedItem["shipping_date_estimation"]["value"];
            $valid_until = $orderedItem["shipping_date_estimation"]["valid_until"];

            if(isset($value) && isset($valid_until))
                $shippingDateEstimation = new ShippingDateEstimation(
                    CarbonImmutable::createFromTimeString($value),
                    CarbonImmutable::createFromTimeString($valid_until)
                );
        }

        $picklistId = Arr::get($orderedItem, 'picklist_id');
        $orderedItem = OrderedItemFactory::orderedItem(null, $product, $orderedItem["product_amount"]);
        $orderedItem->changeShippingDateEstimation($shippingDateEstimation);
        $orderedItem->changePicklistId($picklistId);

        return $orderedItem;
    }

    public static function fromMultipleInArray(array $multipleOrderedItems): Collection
    {
        $result = collect();

        foreach ($multipleOrderedItems as $orderedItem)
        {
            $result->push(self::fromArray($orderedItem));
        }

        return $result;
    }

    /**
     * @param int $amount
     * @param string $orderReference
     * @return Collection<OrderedItem>
     * @throws \Exception
     */
    public static function multipleRandom(int $amount, ?string $orderReference = null, ?int $picklistId = null): Collection
    {
        $products = ProductFactory::randomProducts($amount, null);
        $orderedItems = collect();

        for ($i = 0; $i < $amount; $i++)
        {
            $orderedItems->push(self::orderedItem(random_int(0, 9999), $products[$i], random_int(2, 10), $orderReference, $picklistId));
        }

        return $orderedItems;
    }
}
