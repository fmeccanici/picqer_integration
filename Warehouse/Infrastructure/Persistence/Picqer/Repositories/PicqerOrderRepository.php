<?php


namespace App\Warehouse\Infrastructure\Persistence\Picqer\Repositories;


use App\Warehouse\Domain\Exceptions\InvalidOrderException;
use App\Warehouse\Domain\Orders\Order;
use App\Warehouse\Domain\Repositories\OrderRepositoryInterface;
use App\Warehouse\Infrastructure\ApiClients\PicqerApiClient;
use App\Warehouse\Infrastructure\Exceptions\MsSqlOrderRepositoryOperationException;
use App\Warehouse\Infrastructure\Exceptions\PicqerOrderMapperOperationException;
use App\Warehouse\Infrastructure\Exceptions\PicqerOrderRepositoryOperationException;
use App\Warehouse\Infrastructure\Persistence\Picqer\Customers\CustomerMapper;
use App\Warehouse\Infrastructure\Persistence\Picqer\Orders\Mappers\OrderMapper;
use App\Warehouse\Infrastructure\Persistence\Picqer\Orders\Mappers\ShippingProfileMapper;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PicqerOrderRepository implements OrderRepositoryInterface
{
    private \Picqer\Api\Client $apiClient;
    const PRODUCT_NOT_FOUND = 24;

    public function __construct(PicqerApiClient $apiClient)
    {
        $this->apiClient = $apiClient->getClient();
    }

    /**
     * @throws PicqerOrderRepositoryOperationException
     * @throws PicqerOrderMapperOperationException
     * @throws InvalidOrderException
     */
    public function add(Order $order): Order
    {
        $result = $this->apiClient->getCustomerByCustomerid($order->customer()->customerNumber());

        if ($result["success"] === false)
        {
            throw new PicqerOrderRepositoryOperationException("Failed fetching customer from Picqer: ".$result["errormessage"]);
        }

        $picqerCustomer = $result["data"];
        $picqerCustomerId = $picqerCustomer["idcustomer"];

        if ($picqerCustomerId === null)
        {
            throw new PicqerOrderRepositoryOperationException("Customer should be specified for order");
        }

        $order->customer()->changeId($picqerCustomerId);

        $apiResponse = $this->apiClient->getOrderFields();

        if (! Arr::get($apiResponse, 'success'))
        {
            throw new PicqerOrderRepositoryOperationException("Failed getting all order fields from Picqer: ".$result["errormessage"]);
        }

        $orderFields = Arr::get($apiResponse, 'data');

        $picqerDeliveryMethodOrderField = collect($orderFields)->filter(functioN (array $orderField) {
            return Arr::get($orderField, 'title') == 'Bezorgoptie';
        })->first();


        $picqerShippingProfile = ShippingProfileMapper::toPicqer($order->deliveryOption()->name());

        $result = $this->apiClient->getShippingProviders();

        if ($result["success"] === false)
        {
            throw new PicqerOrderRepositoryOperationException("Failed fetching shipping providers from Picqer: ".$result["errormessage"]);
        }

        $picqerShippingProviders = $result["data"];
        $picqerShippingProvider = collect($picqerShippingProviders)->filter(function (array $picqerShippingProvider) use ($picqerShippingProfile) {
                return Arr::get($picqerShippingProvider, 'name') == Arr::get($picqerShippingProfile, 'shipping_provider_name');
        })->first();

        $picqerShippingProviderProfile = collect(Arr::get($picqerShippingProvider, 'profiles'))->filter(function (array $picqerShippingProviderProfile) use ($picqerShippingProfile) {
            return Arr::get($picqerShippingProviderProfile, 'name') == Arr::get($picqerShippingProfile, 'shipping_provider_profile_name');
        })->first();

        $picqerOrder = OrderMapper::toPicqer($order, $picqerShippingProviderProfile, $picqerDeliveryMethodOrderField);

        $result = $this->apiClient->addOrder($picqerOrder);

        if ($result["success"] === false)
        {
            $errorMessage = json_decode($result['errormessage']);
            if ($errorMessage->error_code === self::PRODUCT_NOT_FOUND)
            {
                throw new InvalidOrderException("1 of meerdere artikelen zijn niet vindbaar in Picqer");
            }

            throw new PicqerOrderRepositoryOperationException("Failed adding order to Picqer: ".$result["errormessage"]);
        }

        $orderId = $result["data"]["orderid"];
        $order->changeId($orderId);

        $this->setOrderTags($order);

        return $order;
    }

    public function find(string $id): ?Order
    {
        $result = $this->apiClient->getOrder($id);

        if ($result["success"] === false)
        {
            throw new PicqerOrderRepositoryOperationException("Failed getting order: ".$result["errormessage"]);
        }

        $picqerOrder = $result["data"];

        $result = $this->apiClient->getCustomer($picqerOrder["idcustomer"]);

        if ($result["success"] === false)
        {
            throw new PicqerOrderRepositoryOperationException("Failed getting customer: ".$result["errormessage"]);
        }

        $picqerCustomer = $result["data"];

        $result = $this->apiClient->getAllPicklists();

        if ($result["success"] === false)
        {
            throw new PicqerOrderRepositoryOperationException("Failed getting all picklists: ".$result["errormessage"]);
        }

        $picqerPicklists = $result["data"];
        $picqerPicklistsFromOrder = [];

        foreach ($picqerPicklists as $picqerPicklist)
        {
            if ($picqerPicklist["idorder"] === $picqerOrder["idorder"])
            {
                $picqerPicklistsFromOrder[] = $picqerPicklist;
            }
        }

        $order = OrderMapper::toEntity($picqerOrder, $picqerPicklistsFromOrder);
        $customer = CustomerMapper::toEntity($picqerCustomer);
        $order->changeCustomer($customer);
        return $order;
    }

    /**
     * @throws PicqerOrderRepositoryOperationException
     * @throws PicqerOrderMapperOperationException
     */
    public function update(Order $order, $attributes = ['tags']): Order
    {
        // Delivery Option
        $result = $this->apiClient->getShippingProviders();

        if ($result["success"] === false)
        {
            throw new PicqerOrderRepositoryOperationException("Failed fetching shipping providers from Picqer: ".$result["errormessage"]);
        }

        if (in_array('tags', $attributes))
        {
            $this->setOrderTags($order);
        }

        if (in_array('delivery_information', $attributes))
        {
            $picqerShippingProviders = $result["data"];
            $picqerOrder = OrderMapper::toPicqer($order, $picqerShippingProviders);

            $deliveryContactName = $picqerOrder['deliverycontactname'];
            $deliveryName = $picqerOrder['deliveryname'];

            $result = $this->apiClient->updateOrder($order->id(), [
                "pickup_point_data" => Arr::get($picqerOrder, 'pickup_point_data'),
                "deliveryname" => $deliveryName,
                "deliverycontactname" => empty($deliveryContactName) ? $deliveryName : $deliveryContactName,
                "deliveryzipcode" => $picqerOrder["deliveryzipcode"],
                "deliverycity" => $picqerOrder["deliverycity"],
                "deliverycountry" => $picqerOrder["deliverycountry"],
                "delivery_address" => $picqerOrder["deliveryaddress"],
                "preferred_delivery_date" => $picqerOrder['preferred_delivery_date'],
            ]);
        }

        if (in_array('status', $attributes))
        {
            if ($order->cancelled())
            {
                $apiResponse = $this->apiClient->getAllOrders([
                    'reference' => $order->reference()
                ]);

                if (! Arr::get($apiResponse, 'success'))
                {
                    throw new PicqerOrderRepositoryOperationException("Failed getting order: ".$result["errormessage"]);
                }

                $picqerOrder = Arr::get($apiResponse, 'data')[0];
                $idOrder = Arr::get($picqerOrder, 'idorder');

                $apiResponse = $this->apiClient->cancelOrder($idOrder, [
                    'force' => true
                ]);

                if (! Arr::get($apiResponse, 'success'))
                {
                    throw new PicqerOrderRepositoryOperationException("Failed cancelling order: ".$apiResponse["errormessage"]);
                }
            }
        }

        if (! $result["success"])
        {
            throw new PicqerOrderRepositoryOperationException("Failed updating order with reference " . $order->reference() . ":" . $result["errormessage"]);
        }

        return $order;
    }

    public function findOneByReference(string $reference, bool $lazyLoadPicklists = false): ?Order
    {
        $result = $this->apiClient->getAllOrders([
            'reference' => $reference
        ]);

        if ($result["success"] === false)
        {
            throw new PicqerOrderRepositoryOperationException("Failed getting all orders: ".$result["errormessage"]);
        }

        $picqerOrders = $result["data"];

        foreach ($picqerOrders as $picqerOrder)
        {
            if ($picqerOrder["status"] !== "cancelled")
            {
                if ($lazyLoadPicklists === false)
                {
                    $result = $this->apiClient->getPicklists();

                    $picqerPicklists = $result["data"];
                    $picqerPicklistsFromOrder = [];

                    foreach ($picqerPicklists as $picqerPicklist)
                    {
                        if ($picqerPicklist["idorder"] === $picqerOrder["idorder"])
                        {
                            $picqerPicklistsFromOrder[] = $picqerPicklist;
                        }
                    }
                } else {
                    $picqerPicklistsFromOrder = [];
                }

                $order = OrderMapper::toEntity($picqerOrder, $picqerPicklistsFromOrder);
                $order->changeId($picqerOrder["idorder"]);

                if ($picqerOrder["idcustomer"] !== null)
                {
                    $customer = $this->apiClient->getCustomer($picqerOrder["idcustomer"])["data"];
                    $order->changeCustomer(CustomerMapper::toEntity($customer));

                } else {
                    $customer = null;
                    $order->changeCustomer($customer);
                }

                return $order;
            }

        }

        return null;
    }

    public function processOrder(string $reference): void
    {
        $order = $this->findOneByReference($reference);
        $this->apiClient->processOrder($order->id());
    }

    public function findNewOrders(): Collection
    {
        // TODO: Implement findNewOrders() method.
    }

    public function addMultiple(Collection $orders)
    {
        // TODO: Implement addMultiple() method.
    }

    public function findNewOrderReferences(): Collection
    {
        // TODO: Implement findNewOrderReferences() method.
    }

    public function findAll(): Collection
    {
        // TODO: Implement findAll() method.
    }

    /**
     * @throws PicqerOrderRepositoryOperationException
     */
    public function findAllByReference(string $reference): Collection
    {
        $apiResponse = $this->apiClient->getAllOrders();

        if (! Arr::get($apiResponse, 'success'))
        {
            throw new PicqerOrderRepositoryOperationException('Failed getting all orders from API with error: ' . Arr::get($apiResponse, 'errormessage'));
        }

        $picqerOrders = collect($apiResponse['data']);

        $picqerOrders = $picqerOrders->filter(function (array $picqerOrder) use ($reference) {
            return explode('-', $picqerOrder['reference'])[0] == $reference;
        });

        return $picqerOrders->map(function (array $picqerOrder) {
            return OrderMapper::toEntity($picqerOrder, []);
        });
    }

    /**
     * @param Order $order
     * @return array
     * @throws PicqerOrderRepositoryOperationException
     */
    private function setOrderTags(Order $order): void
    {
        if ($order->tags()->isNotEmpty()) {
            $idOrder = $order->externalId();

            if ($idOrder === null) {
                $apiResponse = $this->apiClient->getOrders([
                    'reference' => $order->reference()
                ]);

                if (!Arr::get($apiResponse, 'success')) {
                    throw new PicqerOrderRepositoryOperationException('Failed getting order by reference' . $apiResponse['errormessage']);
                }

                $picqerOrders = collect(Arr::get($apiResponse, 'data'));

                if ($picqerOrders->isEmpty()) {
                    throw new PicqerOrderRepositoryOperationException('Order with reference ' . $order->reference() . ' not found');
                }

                $picqerOrder = $picqerOrders[0];
                $idOrder = Arr::get($picqerOrder, 'idorder');
            }

            $order->tags()->each(function (string $tag) use ($idOrder) {
                $apiResponse = $this->apiClient->getTags([
                    'title' => $tag
                ]);

                if (!Arr::get($apiResponse, 'success')) {
                    throw new PicqerOrderRepositoryOperationException('Failed getting all tags' . $apiResponse['errormessage']);
                }

                $tags = collect(Arr::get($apiResponse, 'data'));

                if (collect($tags->isNotEmpty())) {
                    $tag = $tags->first(function (array $picqerTag) use ($tag) {
                        return Arr::get($picqerTag, 'title') === $tag;
                    });

                    $idTag = Arr::get($tag, 'idtag');
                    $this->apiClient->addOrderTag($idOrder, $idTag);
                }
            });
        }
    }

    /**
     * @param string $reference
     * @return Order|null
     * @throws InvalidOrderException
     * @throws \App\SharedKernel\AddressOperationException
     * @throws \App\Warehouse\Domain\Exceptions\CountryCodeMapperOperationException
     * @throws \App\Warehouse\Infrastructure\Exceptions\StateMapperOperationException
     */
    public function findOneInMSSqlByReference(string $reference): ?Order
    {
        $msSqlOrders = $this->getMsSqlOrders($reference);

        if (! $msSqlOrders)
        {
            Log::error('Order not found by reference "'.$reference.'"');

            return null;
        }

        $salesOrderRowsTotal = [];

        foreach ($msSqlOrders as $msSqlOrder)
        {
            try {
                $salesOrderRows = $this->getMsSqlOrderRows($msSqlOrder);
                $salesOrderRowsTotal = array_merge($salesOrderRowsTotal, $salesOrderRows);

                if ($msSqlOrder->ShipmentNumber !== null)
                {
                    $msSqlOrder->SalesOrderNumber = $msSqlOrder->SalesOrderNumber."-".$msSqlOrder->ShipmentNumber;
                }

                $carrierName = $msSqlOrder->DeliveryCompany;
                $deliveryCountry = $msSqlOrder->DeliveryCountry;
                $deliveryOptionName = $msSqlOrder->DeliveryService;

                if (self::isOrderSplit($msSqlOrder->SalesOrderNumber))
                {
                    $carrierName = $msSqlOrder->ShipmentDeliveryCompany;
                    $deliveryOptionName = $msSqlOrder->ShipmentDeliveryService;
                }

                if ($deliveryOptionName === null)
                {
                    $deliveryOption = null;
                } else if ($deliveryOption = $this->deliveryOptionService->getDeliveryOption($carrierName, $deliveryOptionName, $deliveryCountry))
                {

                    $msSqlOrder->DeliveryOptionName = $deliveryOption->name();
                    $msSqlOrder->DeliveryOptionProductCode = $deliveryOption->productCode();
                    $msSqlOrder->DeliveryOptionCharacteristic = $deliveryOption->characteristic();
                    $msSqlOrder->DeliveryOptionOption = $deliveryOption->option();
                    $msSqlOrder->DeliveryOptionCountry = $deliveryCountry;
                    $msSqlOrder->DeliveryOptionCarrier = $deliveryOption->carrier();
                }

                $locationCode = null;
                $retailNetworkId = null;
                $msSqlDeliveryOptions = $this->getMsSqlDeliveryOptions($reference);

                if ($msSqlDeliveryOptions !== null)
                {
                    $msSqlDeliveryOptions = json_decode($msSqlDeliveryOptions)->DeliveryOption->Options;

                    if (isset($msSqlDeliveryOptions->LocationCode) && isset($msSqlDeliveryOptions->RetailNetworkId))
                    {
                        $locationCode = $msSqlDeliveryOptions->LocationCode;
                        $retailNetworkId = $msSqlDeliveryOptions->RetailNetworkId;
                    }
                }

                $msSqlOrder->DeliveryOptionLocationCode = $locationCode;
                $msSqlOrder->DeliveryOptionRetailNetworkId = $retailNetworkId;
            } catch (\Exception $e)
            {
                Log::error("Something went wrong getting a new order from with SalesOrderShipmentId " . $msSqlOrder->SalesOrderShipmentId . " error_message: " . $e->getMessage());
                return null;
            }
        }

        return \App\Warehouse\Infrastructure\Persistence\MsSql\Orders\Mappers\OrderMapper::toEntity($msSqlOrders, $salesOrderRowsTotal);
    }

    /**
    * @param string $reference
    * @return array
    */
    protected function getMsSqlOrders(string $reference): array
    {
        if (Str::contains($reference, '-')) {
            $explodedReference = explode('-', $reference);
            $salesOrderNumber = $explodedReference[0];
            $shipmentNumber = $explodedReference[1];

            $msSqlOrders = DB::connection("snelstart")->table('Picqer.Orders')
                ->where([
                    'SalesOrderNumber' => $salesOrderNumber,
                    'ShipmentNumber' => $shipmentNumber
                ])
                ->get()
                ->toArray();
        } else {
            $salesOrderNumber = $reference;

            $msSqlOrders = DB::connection("snelstart")->table('Picqer.Orders')
                ->where('SalesOrderNumber', '=', $salesOrderNumber)
                ->whereNull('ShipmentNumber')
                ->get()
                ->toArray();
        }

        return $msSqlOrders;
    }

    public function updateCouponDiscountCodeId(Order $order): Order
    {
        $orderReference = $order->reference();

        if ($this->isOrderSplit($orderReference))
        {
            $salesOrderNumber = explode("-", $orderReference)[0];
            $shipmentNumber = explode("-", $orderReference)[1];

            $salesOrder = DB::connection("snelstart")->table('Picqer.Orders')
                ->where([
                    'SalesOrderNumber' => $salesOrderNumber,
                    "ShipmentNumber" => $shipmentNumber
                ])
                ->first();
        } else {
            $salesOrder = DB::connection("snelstart")->table('Picqer.Orders')
                ->where('SalesOrderNumber', '=', $orderReference)
                ->first();
        }

        if ($salesOrder === null)
        {
            throw new MsSqlOrderRepositoryOperationException("Sales order with SalesOrderNumber ".$orderReference." does not exist");
        }

        DB::connection("storemanager")->table("dbo.SalesOrderInfo")
            ->where([
                "SnelStartSalesOrder" => $salesOrder->SalesOrderId
            ])
            ->update([
                "CouponDiscountCodeId" => $order->couponDiscountCodeId()
            ]);

        return $order;
    }
}
