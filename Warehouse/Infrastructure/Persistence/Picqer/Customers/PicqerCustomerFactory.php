<?php


namespace App\Warehouse\Infrastructure\Persistence\Picqer\Customers;


class PicqerCustomerFactory
{
    public static function constant(string $customerId, int $idCustomer): array
    {
        return [
            "idcustomer" => $idCustomer,
            "idtemplate" => null,
            "customerid" => $customerId,
            "name" => "Desmonds Formal Wear",
            "contactname" => "van Lingen",
            "telephone" => "06-85756303",
            "emailaddress" => "demo@mailinator.com",
            "discount" => 0,
            "vatnumber" => null,
            "calculatevat" => true,
            "default_order_remarks" => "",
            "auto_split" => true,
            "language" => "nl",
            "addresses" => [
                [
                    "idcustomeraddress" => 5487,
                    "name" => "Argus Tapes & Records",
                    "contactname" => null,
                    "address" => "Weijkmanlaan 68",
                    "address2" => null,
                    "zipcode" => "7077 AP",
                    "city" => "Netterden",
                    "region" => null,
                    "country" => "NL",
                    "defaultinvoice" => true,
                    "defaultdelivery" => true
                ]
            ]
        ];
    }
}
