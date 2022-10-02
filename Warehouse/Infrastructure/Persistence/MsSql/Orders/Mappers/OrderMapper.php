<?php


namespace App\Warehouse\Infrastructure\Persistence\MsSql\Orders\Mappers;


use App\SharedKernel\AddressFactory;
use App\Warehouse\Domain\Exceptions\InvalidOrderException;
use App\Warehouse\Domain\Orders\Order;
use App\Warehouse\Domain\Orders\OrderedItemFactory;
use App\Warehouse\Domain\Orders\OrderFactory;
use App\Warehouse\Domain\Orders\ProductFactory;
use App\Warehouse\Domain\Parties\Customer;
use App\Warehouse\Domain\Picklists\Picklist;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderMapper
{
    /**
     * @throws \App\SharedKernel\AddressOperationException
     * @throws \App\Warehouse\Infrastructure\Exceptions\StateMapperOperationException
     * @throws \App\Warehouse\Domain\Exceptions\CountryCodeMapperOperationException
     * @throws InvalidOrderException
     */
    public static function toEntity($msSqlOrders, $msSqlOrderRows): Order
    {
        $orderedItems = [];

        foreach ($msSqlOrderRows as $msSqlOrderRow)
        {
            if ($msSqlOrderRow->ProductCode !== null)
            {
                // TODO: Use first product code (should be filtered)
                $productCode = $msSqlOrderRow->ProductCode;

                // TODO: Task 17202: Gebruik de tag van een product om het productie recept te koppelen (wordt gezet door Damon op view)
                $productGroup = $msSqlOrderRow->ProductGroup;

                if (! $productGroup)
                {
                    Log::error("Artikel met code " . $msSqlOrderRow->ProductCode . " heeft nog geen product groep gespecificeerd");
                    $product = ProductFactory::productWithoutProductGroup($productCode);

                } else {
                    $product = ProductFactory::productWithProductGroup($productCode, $productGroup);
                }

                // TODO: Refactor to use price object, Task 20213: Gebruik een Price object om de prijs excl en incl btw in te zetten
                $price = $msSqlOrderRow->Price - $msSqlOrderRow->Vat;
                $product->changeSellingPrice($price);

                if($productDescription = $msSqlOrderRow->Description) {
                    $product->changeDescription($productDescription);
                }

                $amount = $msSqlOrderRow->Quantity;

                $orderedItem = OrderedItemFactory::orderedItem(null, $product, $amount);
                $orderedItems[] = $orderedItem;

            }
        }

        // In Delight orders = shipments
        $msSqlOrder = $msSqlOrders[0];

        if (Str::contains($msSqlOrder->SalesOrderShipmentId, '-'))
        {
            $comments = $msSqlOrder->ShipmentRemarks;
            $state = StateMapper::fromNameToDomain(StateMapper::toName($msSqlOrder->ShipmentState));
            $preferredDeliveryDateString = $msSqlOrder->ShipmentActualDeliveryDate;
            $noReview = $msSqlOrder->ShipmentNoReview;
        } else {
            $comments = $msSqlOrder->Remarks;
            $state = StateMapper::fromNameToDomain(StateMapper::toName($msSqlOrder->State));
            $preferredDeliveryDateString = $msSqlOrder->ActualDeliveryDate;
            $noReview = $msSqlOrder->NoReview;
        }

        if(empty($comments)) {
            $comments = $msSqlOrder->SalesOrderRemarks;
        }

        $creationDate = CarbonImmutable::parse($msSqlOrders[0]->SalesOrderCreatedDate);

        $id = $msSqlOrder->SalesOrderId;

        $streetName = $msSqlOrder->BillingAddressLine;
        $zipcode = $msSqlOrder->BillingPostalCode;
        $city = $msSqlOrder->BillingCity;
        $countryCode = CountryCodeMapper::toCountryCode($msSqlOrder->BillingCountry);

        $address = AddressFactory::fromStreetAddress($streetName, $city, $zipcode, $countryCode);

        $deliveryStreetName = $msSqlOrder->DeliveryAddressLine;
        $deliveryZipCode = $msSqlOrder->DeliveryPostalCode;
        $deliveryCity = $msSqlOrder->DeliveryCity;
        $deliveryCountryCode = CountryCodeMapper::toCountryCode($msSqlOrder->DeliveryCountry);
        $deliveryAddress = AddressFactory::fromStreetAddress($deliveryStreetName, $deliveryCity, $deliveryZipCode, $deliveryCountryCode);;
        $deliveryAddress->changeName($msSqlOrder->DeliveryName);

        $invoiceStreetName = $msSqlOrder->BillingAddressLine;
        $invoiceZipcode = $msSqlOrder->BillingPostalCode;
        $invoiceCity = $msSqlOrder->BillingCity;
        $invoiceCountryCode = CountryCodeMapper::toCountryCode($msSqlOrder->BillingCountry);
        $invoiceAddress = AddressFactory::fromStreetAddress($invoiceStreetName, $invoiceCity, $invoiceZipcode, $invoiceCountryCode);

        $customerNumber = $msSqlOrder->CustomerNumber;
        $customerContactPerson = empty($msSqlOrder->CustomerContactPerson) ? $msSqlOrder->CustomerName : $msSqlOrder->CustomerContactPerson;
        $customerName = $msSqlOrder->CustomerName;
        $email = $msSqlOrder->CustomerEmail;
        $phoneNumber = $msSqlOrder->CustomerPhone;
        $mobilePhoneNumber = $msSqlOrder->CustomerMobilePhone;

        $customer = new Customer(null, $customerNumber, $customerName, $address, $deliveryAddress, $invoiceAddress, $email, $customerContactPerson, $phoneNumber, $mobilePhoneNumber);

        // In the above repo we append ShipmentNumber with '-' if it is split. Ideally this should all be moved to the mapper.
        $reference = $msSqlOrder->ShipmentNumber && ! Str::contains($msSqlOrder->SalesOrderNumber, '-') ? $msSqlOrder->SalesOrderNumber . '-' . $msSqlOrder->ShipmentNumber : $msSqlOrder->SalesOrderNumber;

        if ($preferredDeliveryDateString === null)
        {
            $preferredDeliveryDate = null;
        } else {
            $preferredDeliveryDate = CarbonImmutable::parse($preferredDeliveryDateString);
        }

        $picklists = collect($msSqlOrders)->each(function ($msSqlOrder) {
            $msSqlPicklistOrderRows = DB::connection("snelstart")->table('Picqer.OrderRows')
                ->select(['*'])
                ->where([
                    'SalesOrderId' => $msSqlOrder->SalesOrderId,
                    'ShipmentId' => $msSqlOrder->ShipmentId
                ])
                ->get()
                ->toArray();

            return PicklistMapper::toPicklist($msSqlOrder, $msSqlPicklistOrderRows);
        })

        // TODO: Remove and fix the PicklistMapper. See bug 18623 in Azure.
        ->filter(function($picklist) {
            return $picklist instanceof Picklist;
        });

        // TODO: Missing StateMapperOperationException handler
        $order = OrderFactory::order($id, $reference, $creationDate, $orderedItems, $customer, $state, $preferredDeliveryDate, null, $picklists);

        $couponDiscountCodeId = $msSqlOrder->CouponDiscountCodeId;
        $order->changeCouponDiscountCodeId($couponDiscountCodeId);

        if(!property_exists($msSqlOrder, 'DeliveryOptionName')) {
            return $order;
        }

        $deliveryOptionName = $msSqlOrder->DeliveryOptionName;
        $deliveryOptionCarrier = $msSqlOrder->DeliveryOptionCarrier;
        $deliveryOptionCountry = $msSqlOrder->DeliveryOptionCountry;

        $deliveryOptionLocationCode = $msSqlOrder->DeliveryOptionLocationCode;
        $deliveryOptionRetailNetworkId = $msSqlOrder->DeliveryOptionRetailNetworkId;

        $order->changeDeliveryOption($deliveryOptionCountry, $deliveryOptionName, $deliveryOptionCarrier, $deliveryOptionLocationCode, $deliveryOptionRetailNetworkId);
        $order->changeComments($comments);
        $order->changeNoReview($noReview);

        return $order;

    }

    public static function toMsSql(Order $order)
    {

    }

    public static function isOrderSplit(Order $order): bool
    {
        return sizeof(explode("-", $order->reference())) > 1;
    }

}
