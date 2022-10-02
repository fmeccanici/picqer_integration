<?php


namespace App\Warehouse\Infrastructure\Persistence\Picqer\Orders;


class PicqerOrderFactory
{
    public static function constant(int $idOrder, int $idCustomer, string $orderId, string $preferredDeliveryDate, ?string $reference = null): array
    {
        return [
            "idorder" => $idOrder,
            "idcustomer" => $idCustomer,
            "idtemplate" => 323,
            "idshippingprovider_profile" => null,
            "orderid" => $orderId,
            "deliveryname" => "PackableTriangle B.V.",
            "deliverycontactname" => "Jos Triepels",
            "deliveryaddress" => "Koppeling 15",
            "deliveryaddress2" => null,
            "deliveryzipcode" => "6983 HX",
            "deliverycity" => "Doesburg",
            "deliveryregion" => null,
            "deliverycountry" => "NL",
            "full_delivery_address" => "PackableTriangle B.V.\nJos Triepels\nKoppeling 15\n6983 HX Doesburg\nNederland",
            "invoicename" => "PackableTriangle B.V.",
            "invoicecontactname" => "Crediteurenadministratie",
            "invoiceaddress" => "Koppeling 15",
            "invoiceaddress2" => null,
            "invoicezipcode" => "6983 HX",
            "invoicecity" => "Doesburg",
            "invoiceregion" => null,
            "invoicecountry" => "NL",
            "full_invoice_address" => "PackableTriangle B.V.\nCrediteurenadministratie\nKoppeling 15\n6983 HX Doesburg\nNederland",
            "telephone" => null,
            "emailaddress" => null,
            "reference" => $reference,
            "customer_remarks" => null,
            "pickup_point_data" => null,
            "partialdelivery" => true,
            "auto_split" => true,
            "invoiced" => false,
            "preferred_delivery_date" => $preferredDeliveryDate,
            "discount" => 0,
            "calculatevat" => true,
            "status" => "completed",
            "public_status_page" => "https =>//example.picqer.com/s/eB1KLYRIN41p5xt2",
            "created" => "2013-07-17 16:01:42",
            "updated" => "2013-07-17 16:02:14",
            "warehouses" => [
                1829,
                2811
            ],
            "products" => [
                [
                    "idorder_product" => 86868,
                    "idproduct" => 633,
                    "idvatgroup" => 18,
                    "productcode" => "6531-RB-7-9",
                    "name" => "Hyperkewl Evaporative Cooling Vest Ultra Blue 7-9yr",
                    "remarks" => "",
                    "price" => 64.46,
                    "amount" => 1,
                    "amount_cancelled" => 0,
                    "weight" => 0,
                    "partof_idorder_product" => null,
                    "has_parts" => true
                ],
                [
                    "idorder_product" => 86879,
                    "idproduct" => 653,
                    "idvatgroup" => 18,
                    "productcode" => "6531-RE",
                    "name" => "Hyperkewl Evaporative Cooling Vest Ultra Blue 7-9yr",
                    "remarks" => "",
                    "price" => 164.46,
                    "amount" => 1,
                    "amount_cancelled" => 0,
                    "weight" => 1821,
                    "partof_idorder_product" => null,
                    "has_parts" => true
                ]
            ],
            "tags" => [
                "TopWebshop" => [
                    "idtag" => 1075,
                    "title" => "TopWebshop",
                    "color" => "#5993be",
                    "inherit" => true,
                    "textColor" => "#000000"
                ],
                "SummerProducts" => [
                    "idtag" => 1156,
                    "title" => "SummerProducts",
                    "color" => "#c7b4f6",
                    "inherit" => true,
                    "textColor" => "#000000"
                ]
            ],
            "orderfields" => [
                [
                    "idorderfield" => 35,
                    "title" => "Klantreferentie",
                    "value" => "1029371980276"
                ]
            ]
        ];
    }
}
