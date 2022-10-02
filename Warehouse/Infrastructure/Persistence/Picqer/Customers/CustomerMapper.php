<?php


namespace App\Warehouse\Infrastructure\Persistence\Picqer\Customers;


use App\SharedKernel\AddressFactory;
use App\SharedKernel\AddressOperationException;
use App\Warehouse\Domain\Parties\Customer;
use App\Warehouse\Infrastructure\Exceptions\PicqerCustomerMapperOperationException;

class CustomerMapper
{
    /**
     * @throws AddressOperationException
     * @throws PicqerCustomerMapperOperationException
     */
    public static function toEntity(array $picqerCustomer): Customer
    {
        $name = $picqerCustomer["name"];

        $id = $picqerCustomer["idcustomer"];
        $customerNumber = $picqerCustomer["customerid"];

        $picqerAddresses = collect($picqerCustomer["addresses"]);

        $deliveryAddress = $picqerAddresses->first(function(array $picqerAddress) {
            return $picqerAddress["defaultdelivery"] === true;
        });

        if ($deliveryAddress === null)
        {
            throw new PicqerCustomerMapperOperationException("Delivery address not specified");
        }

        $deliveryAddress = AddressFactory::fromStreetAddress($deliveryAddress["address"], $deliveryAddress["city"], $deliveryAddress["zipcode"], $deliveryAddress["country"]);

        $invoiceAddress = $picqerAddresses->first(function(array $picqerAddress) {
            return $picqerAddress["defaultinvoice"] === true;
        });

        if ($invoiceAddress === null)
        {
            throw new PicqerCustomerMapperOperationException("Invoice address not specified");
        }

        $invoiceAddress = AddressFactory::fromStreetAddress($invoiceAddress["address"], $invoiceAddress["city"], $invoiceAddress["zipcode"], $invoiceAddress["country"]);

        $address = $invoiceAddress;
        $email = $picqerCustomer["emailaddress"];
        $contactName = $picqerCustomer["contactname"];
        $phoneNumber = $picqerCustomer["telephone"];

        return new Customer($id, $customerNumber, $name, $address, $deliveryAddress, $invoiceAddress, $email, $contactName, $phoneNumber);
    }


    public static function toPicqer(Customer $customer): array
    {

        $picqerCustomer = [];
        $picqerCustomer["name"] = $customer->name();
        $picqerCustomer["idcustomer"] = $customer->id();
        $picqerCustomer["customerid"] = $customer->customerNumber();
        $picqerCustomer["contactname"] = $customer->contactName();
        $picqerCustomer["telephone"] = $customer->phoneNumber();
        $picqerCustomer["emailaddress"] = $customer->email();


        $picqerCustomer["addresses"][] = [
            "name" => $customer->name(),
            "address" => $customer->address()->fullStreetAddress(),
            "zipcode" => $customer->address()->zipcode(),
            "city" => $customer->address()->city(),
            "region" => null,
            "country" => $customer->address()->countryCode(),
            "defaultinvoice" => false,
            "defaultdelivery" => false
        ];

        $picqerCustomer["addresses"][] = [
            "name" => $customer->name(),
            "address" => $customer->invoiceAddress()->fullStreetAddress(),
            "zipcode" => $customer->invoiceAddress()->zipcode(),
            "city" => $customer->invoiceAddress()->city(),
            "region" => null,
            "country" => $customer->invoiceAddress()->countryCode(),
            "defaultinvoice" => true,
            "defaultdelivery" => false
        ];

        $picqerCustomer["addresses"][] = [
            "name" => $customer->name(),
            "address" => $customer->deliveryAddress()->fullStreetAddress(),
            "zipcode" => $customer->deliveryAddress()->zipcode(),
            "city" => $customer->deliveryAddress()->city(),
            "region" => null,
            "country" => $customer->deliveryAddress()->countryCode(),
            "defaultinvoice" => false,
            "defaultdelivery" => true
        ];
        return $picqerCustomer;
    }
}
