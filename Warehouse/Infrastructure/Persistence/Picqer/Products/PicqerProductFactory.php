<?php


namespace App\Warehouse\Infrastructure\Persistence\Picqer\Products;


class PicqerProductFactory
{
    public static function constant(): array
    {
        return [
            "idproduct" => 633,
            "idvatgroup" => 18,
            "idsupplier" => null,
            "name" => "Hyperkewl Evaporative Cooling Vest Ultra Blue 7-9yr",
            "price" => 54.46,
            "fixedstockprice" => 0,
            "productcode" => "6531-RB-7-9",
            "productcode_supplier" => "",
            "deliverytime" => null,
            "description" => "",
            "barcode" => "857825001442",
            "unlimitedstock" => false,
            "weight" => 200,
            "length" => 12,
            "width" => 12,
            "height" => 6,
            "active" => true,
            "productfields" => [
                [
                    "idproductfield" => 11,
                    "title" => "Eenheid",
                    "value" => "liter"
                ]
            ],
            "stock" => [
                [
                    "idwarehouse" => 18,
                    "stock" => 112,
                    "reserved" => 0,
                    "reservedbackorders" => 0,
                    "reservedpicklists" => 0,
                    "reservedallocations" => 0,
                    "freestock" => 112
                ]
            ]

        ];
    }
}
