<?php


namespace Tests\Feature\Warehouse;


use App\ResourcePlanning\Domain\Resources\BasePaint;
use Illuminate\Support\Collection;

class DummyResourcePlanningService implements \App\Warehouse\Domain\Services\ResourcePlanningServiceInterface
{


    public function getResourcesForProduct(string $productCode, ?string $productGroup): Collection
    {
        return collect(array(new BasePaint("Test Product Code", "Test Product Name")));
    }

    public function produceGoods(Collection $orderLines, string $customerName, string $orderReference, string $orderCreationDate)
    {
        // TODO: Implement produceGoods() method.
    }
}
