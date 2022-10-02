<?php


namespace App\Warehouse\Infrastructure\Persistence\Eloquent\OrderFlows\Mappers;


use App\Warehouse\Domain\Activities\Fulfill;
use App\Warehouse\Domain\Activities\PlacePurchaseOrder;
use App\Warehouse\Domain\OrderFlows\DropShipment;
use App\Warehouse\Domain\OrderFlows\OrderFlowInterface as OrderFlow;
use App\Warehouse\Infrastructure\Persistence\Eloquent\Activities\Mappers\ActivityMapper;
use App\Warehouse\Infrastructure\Persistence\Eloquent\OrderFlows\EloquentOrderFlow;
use ReflectionClass;

class DropShipmentMapper extends DropShipment
{
    public static function toEntity(EloquentOrderFlow $orderFlowModel): OrderFlow
    {
        $reflection = new ReflectionClass(DropShipment::class);

        /**
         * @var DropShipment $dropShipment
         */
        $dropShipment = $reflection->newInstanceWithoutConstructor();

        $dropShipment->id = $orderFlowModel->id;
        $dropShipment->onStock = $orderFlowModel->on_stock;
        $dropShipment->description = $orderFlowModel->description;

        $activityModels = $orderFlowModel->activities;

        $placePurchaseOrderActivityModel = $activityModels
            ->where("type", "=", PlacePurchaseOrder::TYPE)
            ->firstOrFail();

        $dropShipment->placePurchaseOrder = ActivityMapper::toEntity($placePurchaseOrderActivityModel);

        $fulfillActivityModel = $activityModels
            ->where("type", "=", Fulfill::TYPE)
            ->firstOrFail();

        $dropShipment->fulfill = ActivityMapper::toEntity($fulfillActivityModel);

        return $dropShipment;
    }
}
