<?php


namespace App\Warehouse\Infrastructure\Persistence\Eloquent\OrderFlows\Mappers;


use App\Warehouse\Domain\Activities\PlacePurchaseOrder;
use App\Warehouse\Domain\Activities\ProcessPurchaseOrder;
use App\Warehouse\Domain\OrderFlows\OrderFlowInterface as OrderFlow;
use App\Warehouse\Domain\OrderFlows\OutOfStock;
use App\Warehouse\Infrastructure\Persistence\Eloquent\Activities\Mappers\ActivityMapper;
use App\Warehouse\Infrastructure\Persistence\Eloquent\OrderFlows\EloquentOrderFlow;
use ReflectionClass;

class OutOfStockMapper extends OutOfStock
{
    public static function toEntity(EloquentOrderFlow $orderFlowModel): OrderFlow
    {
        $reflection = new ReflectionClass(OutOfStock::class);

        /**
         * @var OutOfStock $outOfStock
         */
        $outOfStock = $reflection->newInstanceWithoutConstructor();

        $outOfStock->id = $orderFlowModel->id;
        $outOfStock->onStock = $orderFlowModel->on_stock;
        $outOfStock->description = $orderFlowModel->description;

        $activityModels = $orderFlowModel->activities;

        $placePurchaseOrderActivityModel = $activityModels
            ->where("type", "=", PlacePurchaseOrder::TYPE)
            ->firstOrFail();

        $outOfStock->placePurchaseOrder = ActivityMapper::toEntity($placePurchaseOrderActivityModel);

        $processPurchaseOrderActivityModel = $activityModels
            ->where("type", "=", ProcessPurchaseOrder::TYPE)
            ->firstOrFail();

        $outOfStock->processPurchaseOrder = ActivityMapper::toEntity($processPurchaseOrderActivityModel);

        return $outOfStock;
    }
}
