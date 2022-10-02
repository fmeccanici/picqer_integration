<?php


namespace App\Warehouse\Infrastructure\Persistence\Eloquent\OrderFlows\Mappers;


use App\Warehouse\Domain\Activities\PlaceBackOrder;
use App\Warehouse\Domain\Activities\ProcessBackOrder;
use App\Warehouse\Domain\OrderFlows\BackOrder;
use App\Warehouse\Domain\OrderFlows\OrderFlowInterface as OrderFlow;
use App\Warehouse\Infrastructure\Persistence\Eloquent\Activities\Mappers\ActivityMapper;
use App\Warehouse\Infrastructure\Persistence\Eloquent\OrderFlows\EloquentOrderFlow;
use ReflectionClass;

class BackOrderMapper extends BackOrder
{
    public static function toEntity(EloquentOrderFlow $orderFlowModel): OrderFlow
    {
        $reflection = new ReflectionClass(BackOrder::class);

        /**
         * @var BackOrder $backOrder
         */
        $backOrder = $reflection->newInstanceWithoutConstructor();

        $backOrder->id = $orderFlowModel->id;
        $backOrder->onStock = $orderFlowModel->on_stock;
        $backOrder->description = $orderFlowModel->description;

        $activityModels = $orderFlowModel->activities;

        $placeBackOrderActivityModel = $activityModels
            ->where("type", "=", PlaceBackOrder::TYPE)
            ->firstOrFail();

        $backOrder->placeBackOrder = ActivityMapper::toEntity($placeBackOrderActivityModel);

        $processBackOrderActivityModel = $activityModels
            ->where("type", "=", ProcessBackOrder::TYPE)
            ->firstOrFail();

        $backOrder->processBackOrder = ActivityMapper::toEntity($processBackOrderActivityModel);

        return $backOrder;
    }
}
