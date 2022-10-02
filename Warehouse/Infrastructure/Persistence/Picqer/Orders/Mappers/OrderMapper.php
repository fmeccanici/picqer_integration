<?php


namespace App\Warehouse\Infrastructure\Persistence\Picqer\Orders\Mappers;


use App\Warehouse\Domain\Orders\Order;
use App\Warehouse\Domain\Orders\OrderedItem;
use App\Warehouse\Domain\Orders\Product;
use App\Warehouse\Domain\Services\WarehouseServiceInterface;
use App\Warehouse\Infrastructure\Exceptions\PicqerOrderMapperOperationException;
use App\Warehouse\Infrastructure\Exceptions\PicqerOrderStateMapperException;
use App\Warehouse\Infrastructure\Exceptions\PicqerPicklistMapperException;
use App\Warehouse\Infrastructure\Persistence\Picqer\Picklists\Mappers\PicklistMapper;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;


// TODO: Refactor array to PicqerOrder object which has an array
class OrderMapper extends Order
{
    public const BOL_COM_TAG = 'Bol.com';

    /**
     * @throws PicqerPicklistMapperException
     * @throws PicqerOrderStateMapperException
     */
    // TODO: Task 19471: Verbeter de Picqer Order Mapper (met name de toEntity methode)
    // Hierdoor is de attributes array niet meer nodig
    public static function toEntity(array $picqerOrder, array $picqerPicklists): Order
    {
        $creationDate = CarbonImmutable::createFromFormat("Y-m-d H:i:s", $picqerOrder["created"]);
        $preferredDeliveryDateString = $picqerOrder["preferred_delivery_date"];
        $preferredDeliveryDate = $preferredDeliveryDateString !== null ? CarbonImmutable::createFromFormat(config('picqer.datetime_format'), $preferredDeliveryDateString) : null;

        $picqerProducts = $picqerOrder["products"];

        $orderedItems = [];

        foreach ($picqerProducts as $picqerProduct)
        {
            $name = $picqerProduct["name"];
            $productCode = $picqerProduct["productcode"];
            $vatGroupId = $picqerProduct["idvatgroup"];
            $price = $picqerProduct["price"];
            $amount = $picqerProduct["amount"];

            $product = new Product(1, $productCode, null);
            $orderedItem = new OrderedItem($product, "OnStock", null, null, null);
            $orderedItem->changeAmount($amount);
            $orderedItems[] = $orderedItem;
        }

        // TODO: Refactor to use Order Factory
        $order = new Order($creationDate, $orderedItems, App::make(WarehouseServiceInterface::class), null);
        $order->changeId($picqerOrder["idorder"]);

        $order->changeReference($picqerOrder["reference"]);
        $order->preferredDeliveryDate = $preferredDeliveryDate;
        $order->changeStatus(PicqerOrderStateMapper::toDomain($picqerOrder['status']));

        $picklists = collect();

        foreach ($picqerPicklists as $picqerPicklist)
        {
            $picklist = PicklistMapper::toEntity($picqerPicklist, $picqerOrder);
            $picklists->add($picklist);
        }

        $order->changePicklists($picklists);

        $externalId = Arr::get($picqerOrder, 'idorder');
        $order->changeExternalId($externalId);

        return $order;
    }

    /**
     * @throws PicqerOrderMapperOperationException
     */
    public static function toPicqer(Order $order, array $picqerShippingProviderProfile, array $picqerDeliveryMethodOrderField): array
    {

        $picqerProducts = [];
        $pickupPointData = [];

        $deliveryOption = $order->deliveryOption();

        if ($deliveryOption !== null)
        {
            if ($deliveryOption->isEveningDelivery())
            {
                if ($order->preferredDeliveryDate() === null)
                {
                    throw new PicqerOrderMapperOperationException("When choosing evening delivery the preferred delivery date should be set on the order");
                }

                $pickupPointData = self::setPickupPointData($order, $pickupPointData, $deliveryOption);

                $pickupPointData["options"] = [
                    "pakjegemak" => false,
                    "mailbox" => false,
                    "parcel_machine" => false,
                    "pickup_moment" => "Avond",
                    "delivery_date" => $order->preferredDeliveryDate()->format("Y-m-d H:i:s"),
                    "mobile_phone_number" => $order->customer()->phoneNumber() ?? $order->customer()->mobilePhoneNumber()
                ];
            } else if ($deliveryOption->isPickupLocationNetherlands())
            {
                $pickupPointData = self::setPickupPointData($order, $pickupPointData, $deliveryOption);

                $pickupPointData["options"] = [
                    "pakjegemak" => true,
                    "mailbox" => false,
                    "parcel_machine" => false,
                    "pickup_moment" => null,
                    "delivery_date" => null,
                    "mobile_phone_number" => $order->customer()->phoneNumber() ?? $order->customer()->mobilePhoneNumber()
                ];
            } else if ($deliveryOption->isPickupLocationBelgium())
            {

                $pickupPointData = self::setPickupPointData($order, $pickupPointData, $deliveryOption);

                $locationCode = $order->deliveryOption()->locationCode();
                $retailNetworkId = $order->deliveryOption()->retailNetworkId();
                $pickupPointData["name"] = $order->customer()->deliveryAddress()->name();
                $pickupPointData["location_code"] = $locationCode;
                $pickupPointData["retail_network_id"] = $retailNetworkId;
                $pickupPointData["options"] = [
                    "pakjegemak" => true,
                    "mailbox" => false,
                    "parcel_machine" => false,
                    "pickup_moment" => null,
                    "delivery_date" => null,
                    "mobile_phone_number" => $order->customer()->phoneNumber() ?? $order->customer()->mobilePhoneNumber()
                ];
            }
        }

        foreach ($order->items() as $item)
        {
            // TODO: Maybe filter this out earlier
            if ($item->amount() === 0)
            {
                continue;
            }

            $picqerProducts[] = [
                "productcode" => $item->product()->productId(),
                "amount" => $item->amount(),
                "remarks" => $item->description(),
                'price' => $item->product()->sellingPrice()
            ];

        }

        $preferredDeliveryDateString = $order->preferredDeliveryDate();

        if ($preferredDeliveryDateString === null)
        {
            $preferredDeliveryDate = null;
        } else {
            $preferredDeliveryDate = $order->preferredDeliveryDate()->format("Y-m-d");
        }

        $picqerOrder = [
            "orderid" => $order->reference(),
            "idcustomer" => $order->customer()->id(),
            "deliveryname" => $order->customer()->name() ?? $order->customer()->contactName(),
            "deliverycontactname" => "",
            "deliveryaddress" => $order->customer()->deliveryAddress()->fullStreetAddress(),
            "deliveryzipcode" => $order->customer()->deliveryAddress()->zipcode(),
            "deliverycity" => $order->customer()->deliveryAddress()->city(),
            "deliverycountry" => $order->customer()->deliveryAddress()->countryCode(),
            "invoicename" => $order->customer()->name() ?? $order->customer()->contactName(),
            "invoicecontactname" => "",
            "invoiceaddress" => $order->customer()->invoiceAddress()->fullStreetAddress(),
            "invoicezipcode" => $order->customer()->invoiceAddress()->zipcode(),
            "invoicecity" => $order->customer()->invoiceAddress()->city(),
            "invoicecountry" => $order->customer()->invoiceAddress()->countryCode(),
            "telephone" => $order->customer()->phoneNumber(),
            "emailaddress" => $order->customer()->email(),
            "products" => $picqerProducts,
            "reference" => $order->reference(),
            "preferred_delivery_date" => $preferredDeliveryDate,
            "idshippingprovider_profile" => Arr::get($picqerShippingProviderProfile, 'idshippingprovider_profile'),
            "partialdelivery" => false,
            "customer_remarks" => $order->comments(),
            "orderfields" => [
                [
                    'idorderfield' => Arr::get($picqerDeliveryMethodOrderField, 'idorderfield'),
                    'value' => $deliveryOption->name()
                ]
            ]
        ];

        if (collect($pickupPointData)->isNotEmpty())
        {
            $picqerOrder['pickup_point_data'] = $pickupPointData;
        }

        return $picqerOrder;
    }

    /**
     * @param Order $order
     * @param array $pickupPointData
     * @param \App\Warehouse\Domain\Orders\DeliveryOption $deliveryOption
     * @return array
     */
    protected static function setPickupPointData(Order $order, array $pickupPointData, \App\Warehouse\Domain\Orders\DeliveryOption $deliveryOption): array
    {
        $pickupPointData["street"] = $order->customer()->deliveryAddress()->streetName();
        $pickupPointData["house_number"] = $order->customer()->deliveryAddress()->streetNumberWithAddition();
        $pickupPointData["zipcode"] = $order->customer()->deliveryAddress()->zipcode();
        $pickupPointData["city"] = $order->customer()->deliveryAddress()->city();
        $pickupPointData["country"] = $order->customer()->deliveryAddress()->countryCode();
        $pickupPointData["name"] = empty($order->customer()->deliveryAddress()->name()) ? $order->customer()->name() : $order->customer()->deliveryAddress()->name();
        $pickupPointData["carrier"] = $deliveryOption->carrier();
        return $pickupPointData;
    }

}
