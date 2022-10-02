<?php


namespace App\Warehouse\Infrastructure\Persistence\Eloquent\OrderFlows\Mappers;


use App\Warehouse\Domain\Activities\Fulfill;
use App\Warehouse\Domain\OrderFlows\OnStock;
use App\Warehouse\Infrastructure\Persistence\Eloquent\Activities\Mappers\ActivityMapper;
use App\Warehouse\Infrastructure\Persistence\Eloquent\OrderFlows\EloquentOrderFlow;
use ReflectionClass;

class OnStockMapper extends OnStock
{
    public static function toEntity(EloquentOrderFlow $orderFlowModel): OnStock
    {
        $reflection = new ReflectionClass(OnStock::class);

        /**
         * @var OnStock $onStock
         */
        $onStock = $reflection->newInstanceWithoutConstructor();

        $onStock->id = $orderFlowModel->id;
        $onStock->onStock = $orderFlowModel->on_stock;
        $onStock->description = $orderFlowModel->description;

        $activityModels = $orderFlowModel->activities;

        $fulfillActivityModel = $activityModels
            ->where("type", "=", Fulfill::TYPE)
            ->firstOrFail();

        $onStock->fulfill = ActivityMapper::toEntity($fulfillActivityModel);

        return $onStock;
    }
}
