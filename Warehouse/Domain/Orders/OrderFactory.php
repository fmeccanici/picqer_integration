<?php


namespace App\Warehouse\Domain\Orders;


use App\SharedKernel\AddressFactory;
use App\Warehouse\Domain\Parties\Customer;
use App\Warehouse\Domain\Parties\CustomerFactory;
use App\Warehouse\Domain\Picklists\Picklist;
use App\Warehouse\Domain\Picklists\SnoozePolicyInterface;
use App\Warehouse\Domain\Services\WarehouseServiceInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;

class OrderFactory extends Order
{
    public static function fromArray(array $order): Order
    {
        // TODO: Why create from time string? And not date/datetime string?
        $creationDate = CarbonImmutable::createFromTimeString($order["creation_date"]);
        $orderItems = array_map(function ($orderItem)
        {
            $websiteId = $orderItem["website_id"];
            $productId = $orderItem["product_id"];
            $productOptionId = Arr::get($orderItem, "product_option_id");
            $propertyIds = Arr::get($orderItem, "property_ids");
            $product = new Product($websiteId, $productId, $productOptionId);

            $availability = $orderItem["availability"];
            $deliveryLevel = Arr::get($orderItem, "delivery_level");

            $shippingDateEstimation = null;
            if (isset($orderItem["shipping_date_estimation"]))
            {
                $value = $orderItem["shipping_date_estimation"]["value"];
                $valid_until = $orderItem["shipping_date_estimation"]["valid_until"];

                if(isset($value) && isset($valid_until))
                {
                    $shippingDateEstimation = new ShippingDateEstimation(
                        CarbonImmutable::createFromTimeString($value),
                        CarbonImmutable::createFromTimeString($valid_until)
                    );
                }
            }

            return new OrderedItem($product, $availability, $propertyIds, $deliveryLevel, $shippingDateEstimation);
        }, $order["ordered_items"]);

        // TODO: Change constructor, breaks delivery date estimation use cases
        return new Order($creationDate, $orderItems, App::make(WarehouseServiceInterface::class), null);
    }

    // TODO: Merge this with above function toArray: this will break the delivery date estimation code
    public static function fromCompleteArray(array $order): Order
    {
        $creationDate = CarbonImmutable::createFromFormat("Y-m-d H:i:s", $order["creation_date"]);
        $reference = $order["reference"];
        $customer = CustomerFactory::fromArray($order["customer"]);

        if ($order["preferred_delivery_date"] === null)
        {
            $preferredDeliveryDate = null;
        } else {
            $preferredDeliveryDate = CarbonImmutable::createFromFormat("Y-m-d", $order["preferred_delivery_date"]);
        }
        $status = $order["status"];
        $comments = $order["comments"];

        $orderItems = array_map(function ($orderItem)
        {
            $productId = $orderItem["product_id"];
            $amount = $orderItem["product_amount"];
            $product = ProductFactory::productWithoutProductGroup($productId);

            return OrderedItemFactory::orderedItem(null, $product, $amount);
        }, $order["ordered_items"]);


        $picklists = array_map(function (array $picklistArray) {
            $id = null;
            $reference = $picklistArray["reference"];
            $orderReference = $picklistArray["order_reference"];

            $trackAndTrace = $picklistArray["track_and_trace"];
            $status = $picklistArray["status"];
            $comments = $picklistArray["comments"];

            $orderedItemsArray = $picklistArray["ordered_items"];

            $orderedItems = array_map(function(array $orderedItemArray) {
                return OrderedItemFactory::fromArray($orderedItemArray);
            }, $orderedItemsArray);

            return new Picklist(null, $reference, $orderReference, $trackAndTrace, $comments, $status, $orderedItems);
        }, $order["picklists"]);

        $order = self::order(uniqid(), $reference, $creationDate, $orderItems, $customer,
                    $status, $preferredDeliveryDate, $comments, collect($picklists));

        return $order;
    }

    public static function processed(string|int $id, string $reference, CarbonImmutable $creationDate, array $orderedItems, Customer $customer, ?CarbonImmutable $preferredDeliveryDate,
                                     ?string $comments, Collection $picklists)
    {
        return self::order($id, $reference, $creationDate, $orderedItems, $customer, "processed", $preferredDeliveryDate, $comments, $picklists);
    }

    public static function unprocessed(string|int $id, string $reference, CarbonImmutable $creationDate, array $orderedItems, Customer $customer, ?CarbonImmutable $preferredDeliveryDate,
                                     ?string $comments, Collection $picklists)
    {
        return self::order($id, $reference, $creationDate, $orderedItems, $customer, "unprocessed", $preferredDeliveryDate, $comments, $picklists);
    }

    public static function constantUnprocessed(?array $orderedItems = null): Order
    {
        $customerNumber = "1234";
        $customerName = "John Doe";
        $address = AddressFactory::fromStreetAddress("Doe John Street 3A", "Rotterdam", "1234AB", "NL");
        $deliveryAddress = AddressFactory::fromStreetAddress("Doe John Street 3B", "Rotterdam", "1234AB", "NL");
        $invoiceAddress = AddressFactory::fromStreetAddress("Doe John Street 3C", "Rotterdam", "1234AB", "NL");
        $email = "johndoe@doe.com";
        $contactName = "Doe John";
        $phoneNumber = "0612345678";

        $customer = new Customer(null, $customerNumber, $customerName, $address, $deliveryAddress, $invoiceAddress, $email, $contactName, $phoneNumber);

        $reference = "123456";
        $orderId = 2;
        $preferredDeliveryDate = CarbonImmutable::create(2021, 12, 31);
        $creationDate = CarbonImmutable::create(2021, 12, 31);
        $comments = null;

        $orderedItemId = 23;
        $productCode1 = "11";
        $productCode2 = "22";
        $amount = 99;
        $productGroup = "Test Product Group";

        if ($orderedItems === null)
        {
            $product1 = ProductFactory::product($productCode1, $productGroup);
            $product2 = ProductFactory::product($productCode2, $productGroup);
            $orderedItem1 = OrderedItemFactory::orderedItem($orderedItemId, $product1, $amount, $reference);
            $orderedItem2 = OrderedItemFactory::orderedItem($orderedItemId, $product2, $amount, $reference);
            $orderedItems = array($orderedItem1, $orderedItem2);
        }

        $picklistId = uniqid();
        $picklistReference1 = "1234";

        $snoozePolicy = App::make(SnoozePolicyInterface::class);
        $picklist = new Picklist($picklistReference1, $reference, $snoozePolicy,  $picklistId,  null, null, null, $orderedItems);

        $picklists = collect(array($picklist));

        return self::order($orderId, $reference, $creationDate, $orderedItems, $customer, "unprocessed", $preferredDeliveryDate, $comments, $picklists);
    }


    public static function constantUnprocessedTwoPicklists(): Order
    {
        $customerNumber = "1234";
        $customerName = "John Doe";
        $address = AddressFactory::fromStreetAddress("Doe John Street 3A", "Rotterdam", "1234AB", "NL");
        $deliveryAddress = AddressFactory::fromStreetAddress("Doe John Street 3B", "Rotterdam", "1234AB", "NL");
        $invoiceAddress = AddressFactory::fromStreetAddress("Doe John Street 3C", "Rotterdam", "1234AB", "NL");
        $email = "johndoe@doe.com";
        $contactName = "Doe John";
        $phoneNumber = "0612345678";

        $customer = new Customer(null, $customerNumber, $customerName, $address, $deliveryAddress, $invoiceAddress, $email, $contactName, $phoneNumber);

        $reference = "123456";
        $orderId = 2;
        $preferredDeliveryDate = CarbonImmutable::create(2021, 12, 31);
        $creationDate = CarbonImmutable::create(2021, 12, 31);
        $comments = null;

        $orderedItemId = 23;
        $productCode = "1";
        $amount = 1;
        $productGroup = "Test Product Group";

        $product = ProductFactory::product($productCode, $productGroup);
        $orderedItem1 = OrderedItemFactory::orderedItem($orderedItemId, $product, $amount);
        $orderedItem2 = OrderedItemFactory::orderedItem($orderedItemId, $product, $amount);

        $orderedItems = array($orderedItem1, $orderedItem2);

        $picklistId1 = 1;
        $picklistId2 = 2;
        $picklistReference1 = "1234";
        $picklistReference2 = "6666";

        $snoozePolicy = App::make(SnoozePolicyInterface::class);
        $picklist1 = new Picklist($picklistReference1, $reference, $snoozePolicy, $picklistId1, null, null, null, array($orderedItem1));
        $picklist2 = new Picklist($picklistReference2, $reference, $snoozePolicy, $picklistId2, null, null, null, array($orderedItem2));

        $picklists = collect(array($picklist1, $picklist2));

        return self::order($orderId, $reference, $creationDate, $orderedItems, $customer, "unprocessed", $preferredDeliveryDate, $comments, $picklists);
    }

    public static function order(string|int $id, string $reference, CarbonImmutable $creationDate, array $orderedItems, Customer $customer, string $status, ?CarbonImmutable $preferredDeliveryDate,
                                                 ?string $comments, Collection $picklists, ?DeliveryOption $deliveryOption = null): Order
    {
        $warehouseService = App::make(WarehouseServiceInterface::class);

        $order = new Order($creationDate, $orderedItems, $warehouseService, null, [], [], [], $deliveryOption);
        $order->changeId($id);
        $order->changeReference($reference);
        $order->changeCustomer($customer);
        $order->changeStatus($status);
        $order->changeComments($comments);
        $order->preferredDeliveryDate = $preferredDeliveryDate;
        $order->changePicklists($picklists);

        return $order;
    }

    public static function create(int $amount, array $attributes = []): Collection
    {
        $result = collect();

        for ($i = 0; $i < $amount; $i++)
        {
            $id = null;
            $creationDate = CarbonImmutable::create(random_int(0, 3000), random_int(1, 12), random_int(1, 28));
            $orderedItems = OrderedItemFactory::multipleRandom(5);

            if (isset($attributes['customerNumber']))
            {
                $customer = CustomerFactory::create(1, [
                    'customer_number' => $attributes['customerNumber']
                ])->first();
            } else {
                $customer = CustomerFactory::create()->first();
            }

            if (key_exists('status', $attributes))
            {
                $status = $attributes['status'];
            } else {
                $status = "unprocessed";
            }

            $preferredDeliveryDate = Arr::get($attributes, 'preferredDeliveryDate');

            if (! $preferredDeliveryDate)
            {
                $preferredDeliveryDate = $creationDate->addDays(random_int(1, 5));
            }

            $comments = "";

            if (key_exists('order_reference', $attributes))
            {
                $orderReference = $attributes['order_reference'];
            } else {
                $orderReference = uniqid();
            }

            $snoozePolicy = App::make(SnoozePolicyInterface::class);
            $picklist = new Picklist(uniqid(), $orderReference,$snoozePolicy, uniqid(), null, "", "", $orderedItems->toArray(), null, $preferredDeliveryDate);


            $deliveryOption = Arr::get($attributes, 'delivery_option');
            if (key_exists('delivery_option', $attributes) && $deliveryOption === null)
            {
                $deliveryOption = null;
            } else if (! key_exists('delivery_option', $attributes))
            {
                $deliveryOption = new DeliveryOption("Test Carrier", "Test Name", 3085, null, null);
            }

            $order = self::order(uniqid(), $orderReference, $creationDate, $orderedItems->all(), $customer, $status, $preferredDeliveryDate, $comments, collect(array($picklist)), $deliveryOption);

            $result->push($order);
        }

        return $result;
    }
}
