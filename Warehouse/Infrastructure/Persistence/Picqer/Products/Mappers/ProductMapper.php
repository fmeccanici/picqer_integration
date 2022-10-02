<?php


namespace App\Warehouse\Infrastructure\Persistence\Picqer\Products\Mappers;


use App\Warehouse\Domain\Orders\Product;
use App\Warehouse\Domain\Orders\ProductFactory;

class ProductMapper
{
    /**
     * @param array $picqerProduct
     * @return Product
     */
    public static function toEntity(array $picqerProduct): Product
    {
        $productcode = $picqerProduct["productcode"];

        $product = ProductFactory::productWithoutProductGroup($productcode);
        if($productDescription = $picqerProduct["description"]) {
            $product->changeDescription($productDescription);
        }

        return $product;
    }
}
