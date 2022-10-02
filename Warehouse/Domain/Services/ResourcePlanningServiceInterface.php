<?php


namespace App\Warehouse\Domain\Services;


use Illuminate\Support\Collection;

interface ResourcePlanningServiceInterface
{
    public function produceGoods(Collection $orderLines, string $customerName, string $orderReference, string $orderCreationDate);
    public function getResourcesForProduct(string $productCode, ?string $productGroup): Collection;
}
