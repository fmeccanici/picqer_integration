<?php


namespace App\Warehouse\Infrastructure\Persistence\Eloquent\OrderFlows\Mappers;


use App\Warehouse\Domain\OrderFlows\BackOrder;
use App\Warehouse\Domain\OrderFlows\DropShipment;
use App\Warehouse\Domain\OrderFlows\OnStock;
use App\Warehouse\Domain\OrderFlows\OrderFlowInterface as OrderFlow;
use App\Warehouse\Domain\OrderFlows\OutOfStock;
use App\Warehouse\Infrastructure\Persistence\Eloquent\OrderFlows\EloquentOrderFlow;
use Exception;

class OrderFlowMapper
{
    public static function toEntity(EloquentOrderFlow $orderFlowModel): OrderFlow
    {
        if ($orderFlowModel->type === OnStock::TYPE)
        {
            return OnStockMapper::toEntity($orderFlowModel);
        }
        else if ($orderFlowModel->type === OutOfStock::TYPE)
        {
            return OutOfStockMapper::toEntity($orderFlowModel);
        }
        else if ($orderFlowModel->type === BackOrder::TYPE)
        {
            return BackOrderMapper::toEntity($orderFlowModel);
        }
        else if ($orderFlowModel->type === DropShipment::TYPE)
        {
            return DropShipmentMapper::toEntity($orderFlowModel);
        }
        else
        {
            throw new Exception("unknown order flow type");
        }
    }

    public static function toEntities(EloquentOrderFlow ...$orderFlowModels): array
    {
        return array_map(
            function (EloquentOrderFlow $orderFlowModel)
            {
                return self::toEntity($orderFlowModel);
            }
            , $orderFlowModels);
    }
}
