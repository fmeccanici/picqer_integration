<?php


namespace App\Warehouse\Infrastructure\Persistence\MsSql\Repositories;


use App\Warehouse\Domain\Orders\Action;
use App\Warehouse\Domain\Orders\Order;
use App\Warehouse\Domain\Repositories\OrderRepositoryInterface;
use App\Warehouse\Domain\Services\DeliveryOptionServiceInterface;
use App\Warehouse\Infrastructure\Exceptions\MsSqlOrderRepositoryOperationException;
use App\Warehouse\Infrastructure\Exceptions\StateMapperOperationException;
use App\Warehouse\Infrastructure\Persistence\MsSql\Log\EloquentMsSqlLog;
use App\Warehouse\Infrastructure\Persistence\MsSql\Log\EloquentMsSqlLogItem;
use App\Warehouse\Infrastructure\Persistence\MsSql\Orders\Mappers\OrderMapper;
use App\Warehouse\Infrastructure\Persistence\MsSql\Orders\Mappers\OrderStateMapper;
use App\Warehouse\Infrastructure\Persistence\MsSql\Orders\Mappers\StateMapper;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MsSqlOrderRepository implements OrderRepositoryInterface
{

    private DeliveryOptionServiceInterface $deliveryOptionService;

    public function __construct(DeliveryOptionServiceInterface $deliveryOptionService)
    {
        $this->deliveryOptionService = $deliveryOptionService;
    }

    /**
     * @inheritDoc
     */
    public function add(Order $order): Order
    {
        // TODO: Implement add() method.
    }

    /**
     * @inheritDoc
     */
    public function find(string $orderNumber): ?Order
    {
        // TODO: Implement find() method.
    }

    // TODO: Task 19050: Herschrijf MsSqlOrderRepository zodat findByReference hergebruikt wordt in findNewOrders
    /**
     * @inheritDoc
     */
    public function findOneByReference(string $reference, bool $lazyLoadPicklists = false): ?Order
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

        return OrderMapper::toEntity($msSqlOrders, $salesOrderRowsTotal);
    }


    /**
     * @inheritDoc
     * @throws MsSqlOrderRepositoryOperationException|StateMapperOperationException
     */
    public function update(Order $order, array $attributes = ['tags']): Order
    {
        $reference = $order->reference();

        $stateId = StateMapper::toId(OrderStateMapper::toMsSql($order->status()));

        if ($this->isOrderSplit($order->reference()))
        {
            $salesOrderNumber = explode("-", $reference)[0];
            $shipmentNumber = explode("-", $reference)[1];

            $salesOrder = DB::connection("snelstart")->table('Picqer.Orders')
                ->where([
                    'SalesOrderNumber' => $salesOrderNumber,
                    "ShipmentNumber" => $shipmentNumber
                ])
                ->first();

            if ($salesOrder === null)
            {
                throw new MsSqlOrderRepositoryOperationException("Sales order with SalesOrderNumber ".$reference." does not exist");
            }

            $activityLogId = $salesOrder->ShipmentActivityLogId;

            DB::connection("snelstart")->table('Picqer.Orders')
                ->where([
                    "SalesOrderNumber" => $salesOrderNumber,
                    "ShipmentNumber" => $shipmentNumber,
                ])
                ->update([
                    "ShipmentRemarks" => $order->comments(),
                    "ShipmentState" => $stateId,
                    "ShipmentTrackAndTrace" => $order->trackAndTrace()?->code(),
                ]);

            DB::connection("snelstart")->table('Picqer.Orders')
                ->where([
                    "SalesOrderNumber" => $salesOrderNumber,
                    "ShipmentNumber" => $shipmentNumber
                ])
                ->update([
                    "Remarks" => $order->comments(),
                    "State" => $stateId,
                    "TrackAndTrace" => $order->trackAndTrace()?->code()
                ]);

        } else {
            $salesOrder = DB::connection("snelstart")->table('Picqer.Orders')
                ->where('SalesOrderNumber', '=', $reference)
                ->first();

            $activityLogId = $salesOrder->ActivityLogId;

            if ($salesOrder === null)
            {
                throw new MsSqlOrderRepositoryOperationException("Sales order with SalesOrderNumber ".$reference." does not exist");
            }

            DB::connection("snelstart")->table('Picqer.Orders')
                ->where('SalesOrderNumber', '=', $reference)
                ->update([
                    "Remarks" => $order->comments(),
                    "State" => $stateId,
                    "TrackAndTrace" => $order->trackAndTrace()?->code()
                ]);
        }

        if ($activityLogId === null)
        {
            $log = new EloquentMsSqlLog([
                'Created' => CarbonImmutable::now()->toDateTimeString()
            ]);
            $log->save();
            $activityLogId = $log->Id;
        }

        Log::notice('Activity log id: ' . $activityLogId);

        $logItemsCreated = EloquentMsSqlLogItem::where('Log', $activityLogId)->get();
        $logItemsCreatedDateTimes = $logItemsCreated->map->Created;

        $actionsNotLogged = $order->actions()->filter(function (Action $action) use ($logItemsCreatedDateTimes) {
            return (! in_array($action->createdAt()->format("Y-m-d H:i:s.u"), $logItemsCreatedDateTimes->toArray()));
        });

        $actionsNotLogged->each(function (Action $action) use ($activityLogId) {
            $eloquentMsSqlLogItem = new EloquentMsSqlLogItem([
                'Log' => $activityLogId,
                'Modifier' => $action->actor(),
                'Description' => $action->description(),
                'Created' => $action->createdAt()->format("Y-m-d H:i:s")
            ]);

            $eloquentMsSqlLogItem->save();
        });

        return $order;
    }

    // TODO: Task 19051: Vervang PICQERT etc. naar een static class die de strings koppelt aan een statische variabele

    /**
     * @return Collection
     * @throws StateMapperOperationException
     */
    public function findNewOrderReferences(): Collection
    {
        $stateId = StateMapper::toId("PICQERT");
        $newSalesOrderNumbers = $this->getNewSalesOrderNumbers($stateId);
        return collect($newSalesOrderNumbers);
    }

    public function findNewOrders(): Collection
    {
        $result = collect();

        $stateId = StateMapper::toId("PICQERT");
        $newSalesOrderNumbers = $this->getNewSalesOrderNumbers($stateId);

        foreach ($newSalesOrderNumbers as $newSalesOrderNumber)
        {
            $msSqlOrders = $this->getMsSqlOrders($newSalesOrderNumber);

            foreach ($msSqlOrders as $msSqlOrder)
            {
                try {
                    $msSqlOrderRows = $this->getMsSqlOrderRows($msSqlOrder);

                    if ($msSqlOrder->ShipmentNumber !== null)
                    {
                        $msSqlOrder->SalesOrderNumber = $msSqlOrder->SalesOrderNumber."-".$msSqlOrder->ShipmentNumber;
                    }

                    $deliveryCountry = $msSqlOrder->DeliveryCountry;

                    if (self::isOrderSplit($msSqlOrder->SalesOrderNumber))
                    {
                        $carrierName = $msSqlOrder->ShipmentDeliveryCompany;
                        $deliveryOptionName = $msSqlOrder->ShipmentDeliveryService;
                    } else {
                        $carrierName = $msSqlOrder->DeliveryCompany;
                        $deliveryOptionName = $msSqlOrder->DeliveryService;
                    }


                    if($deliveryOption = $this->deliveryOptionService->getDeliveryOption($carrierName, $deliveryOptionName, $deliveryCountry)) {
                        $msSqlOrder->DeliveryOptionName = $deliveryOptionName;
                        $msSqlOrder->DeliveryOptionProductCode = $deliveryOption->productCode();
                        $msSqlOrder->DeliveryOptionCharacteristic = $deliveryOption->characteristic();
                        $msSqlOrder->DeliveryOptionOption = $deliveryOption->option();
                        $msSqlOrder->DeliveryOptionCountry = $deliveryCountry;
                        $msSqlOrder->DeliveryOptionCarrier = $carrierName;
                    }


                $locationCode = null;
                $retailNetworkId = null;
                $msSqlDeliveryOptions = $this->getMsSqlDeliveryOptions($newSalesOrderNumber);

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

                    $result->push(OrderMapper::toEntity(array($msSqlOrder), $msSqlOrderRows));
                } catch (\Exception | \Error $e)
                {
                    Log::error("Something went wrong getting a new order with SalesOrderShipmentId " . $msSqlOrder->SalesOrderShipmentId . " error_message: " . $e->getMessage());
                }
            }
        }

        return $result;
    }

    public function addMultiple(Collection $orders)
    {
        // TODO: Implement addMultiple() method.
    }

    public function findByDelightReference(string $delightReference): ?Order
    {
        // TODO: Implement findByDelightReference() method.
    }

    private function isOrderSplit(string $orderReference): bool
    {
        return sizeof(explode("-", $orderReference)) > 1;
    }

    /**
     * @param int $stateId
     * @return Collection
     */
    protected function getNewSalesOrderNumbers(int $stateId): Collection
    {
        $newSalesOrderNumbersNonSplitOrders = DB::connection("snelstart")->table('Picqer.Orders')
            ->where('State', '=', $stateId)
            ->whereNull('ShipmentNumber')
            ->select(["SalesOrderNumber", "ShipmentNumber"]);

        $newSalesOrderNumbersSplitOrders = DB::connection("snelstart")->table('Picqer.Orders')
            ->where('ShipmentState', '=', $stateId)
            ->whereNotNull('ShipmentNumber')
            ->select(["SalesOrderNumber", "ShipmentNumber"]);

        $newSalesOrderNumbers = collect($newSalesOrderNumbersNonSplitOrders->union($newSalesOrderNumbersSplitOrders)
                                                                            ->get()
                                                                            ->unique()
                                                                            ->toArray());

        return $newSalesOrderNumbers->map(function ($salesOrderNumberWithShipmentNumber) {

            $shipmentNumber = $salesOrderNumberWithShipmentNumber->ShipmentNumber;
            $salesOrderNumber = $salesOrderNumberWithShipmentNumber->SalesOrderNumber;

            if ($shipmentNumber !== null)
            {
                return $salesOrderNumberWithShipmentNumber->SalesOrderNumber . '-' . $salesOrderNumberWithShipmentNumber->ShipmentNumber;
            } else {
                return $salesOrderNumber;
            }
        });
    }

    /**
     * @param string $reference
     * @return array
     */
    protected function getMsSqlOrders(string $reference): array
    {
        if (Str::contains($reference, '-'))
        {
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

    /**
     * @param mixed $msSqlOrder
     * @return array
     */
    protected function getMsSqlOrderRows(mixed $msSqlOrder): array
    {
        $msSqlOrderRows = DB::connection("snelstart")->table('Picqer.OrderRows')
            ->where([
                'SalesOrderId' => $msSqlOrder->SalesOrderId,
                'ShipmentId' => $msSqlOrder->ShipmentId
            ])
            ->whereNotNull('ProductCode')
            ->get()
            ->filter(function ($msSqlOrderRow) {
                    return ! $msSqlOrderRow->VirtualProduct;
            })
            ->toArray();


        return $msSqlOrderRows;
    }

    /**
     * @param string $reference
     * @return mixed
     */
    protected function getMsSqlDeliveryOptions(string $reference)
    {
        return optional(
            DB::connection("sitemanager")->table("fsm_website_basket")
            ->where("ordernumber_internal", "=", $reference)
            ->first()
        )->delivery_options;
    }


    public function findAll(): Collection
    {
        // TODO: Implement findAll() method.
    }

    /**
     * @throws MsSqlOrderRepositoryOperationException
     */
    public function findAllByReference(string $reference): Collection
    {
        if (Str::contains($reference, '-'))
        {
            throw new MsSqlOrderRepositoryOperationException('Give the actual order reference, so without "-"');
        }


        $msSqlOrders = DB::connection("snelstart")->table('Picqer.Orders')
            ->where([
                'SalesOrderNumber' => $reference
            ])
            ->get();

        return $msSqlOrders->map(function ($msSqlOrder) {
            return OrderMapper::toEntity(array($msSqlOrder), []);
        });
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
