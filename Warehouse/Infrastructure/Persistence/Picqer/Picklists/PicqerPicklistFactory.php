<?php


namespace App\Warehouse\Infrastructure\Persistence\Picqer\Picklists;


class PicqerPicklistFactory
{
    public static function constant(int $idOrder, ?string $preferredDeliveryDate = null)
    {
        return [
            "idpicklist" => 76859,
            "picklistid" => "20140095",
            "idcustomer" => 5621,
            "idorder" => $idOrder,
            "idreturn" => null,
            "idwarehouse" => 18,
            "idtemplate" => 2,
            "idshippingprovider_profile" => null,
            "deliveryname" => "Desmonds Formal Wear",
            "deliverycontact" => "",
            "deliveryaddress" => "Emmerikseweg 57",
            "deliveryaddress2" => "",
            "deliveryzipcode" => "7077 AP",
            "deliverycity" => "Netterden",
            "deliveryregion" => null,
            "deliverycountry" => "NL",
            "emailaddress" => "YassinevanLingen@mailinator.com",
            "telephone" => null,
            "reference" => "",
            "assigned_to_iduser" => null,
            "invoiced" => false,
            "urgent" => false,
            "preferred_delivery_date" => $preferredDeliveryDate,
            "status" => "new",
            "totalproducts" => 2,
            "totalpicked" => 0,
            "snoozed_until" => null,
            "created" => "2014-08-19 12:13:38",
            "updated" => "2014-08-19 12:13:38",
            "closed_by_iduser" => 12345,
            "products" => [
                [
                    "idpicklist_product" => 611,
                    "idproduct" => 147,
                    "idorder_product" => 1008,
                    "idreturn_product_replacement" => null,
                    "idvatgroup" => 18,
                    "productcode" => "502.052.16",
                    "name" => "SUNDEROe",
                    "remarks" => "",
                    "amount" => 1,
                    "amount_picked" => 0,
                    "price" => 69.95,
                    "weight" => 10200,
                    "stocklocation" => "Plein C",
                    "partof_idpicklist_product" => null,
                    "has_parts" => false
                ],
                [
                    "idpicklist_product" => 612,
                    "idproduct" => 126654,
                    "idorder_product" => 1009,
                    "idreturn_product_replacement" => null,
                    "idvatgroup" => 18,
                    "productcode" => "1318512",
                    "name" => "4GB DDR3 PC10600\/1333Mhz REG ECC MEMORY (x4)",
                    "remarks" => null,
                    "amount" => 1,
                    "amount_picked" => 0,
                    "price" => 0,
                    "weight" => 0,
                    "stocklocation" => null,
                    "partof_idpicklist_product" => null,
                    "has_parts" => false
                ]
            ]
        ];
    }
}
