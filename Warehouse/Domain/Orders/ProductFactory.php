<?php


namespace App\Warehouse\Domain\Orders;


use Faker\Factory;
use Illuminate\Support\Arr;
use JetBrains\PhpStorm\Pure;

class ProductFactory
{
    #[Pure] public static function productWithoutProductGroup(string $reference): Product
    {
        return new Product(0, $reference, null);
    }

    #[Pure] public static function productWithProductGroup(string $reference, string $productGroup, array $attributes = []): Product
    {
        $length = Arr::get($attributes, 'length');
        if ($length === null)
        {
            $length = random_int(1, 2000);
        }

        return new Product(0, $reference, null, $length, $productGroup);
    }

    public static function product(string $productCode, string $productGroup, array $attributes = []): Product
    {
        $length = Arr::get($attributes, 'length');
        if ($length === null)
        {
            $length = random_int(1, 10000);
        }

        $name = Arr::get($attributes, 'name');
        $description = Arr::get($attributes, 'description');

        return new Product(0, $productCode, null, $length, $productGroup, $name, $description);
    }

    public static function randomProducts(int $amount, ?int $websiteId): array
    {
        $result = [];
        $faker = Factory::create();

        for ($i = 0; $i < $amount; $i++)
        {
            $productGroup = $faker->name;

            if ($websiteId === null)
            {
                $websiteId = random_int(0, 100);
            }

            $name = $faker->name;
            $description = $faker->realText();
            $sellingPrice = $faker->randomFloat();
            $purchasePrice = $faker->randomFloat();
            $length = random_int(1, 1000);
            $result[] = new Product($websiteId, uniqid(), null, $length, $productGroup, $name, $description, $sellingPrice, $purchasePrice);
        }

        return $result;
    }
}
