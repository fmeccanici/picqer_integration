<?php


namespace App\Warehouse\Infrastructure\Services;


use App\ResourcePlanning\Application\GetResourcesForProduct\GetResourcesForProduct;
use App\ResourcePlanning\Application\GetResourcesForProduct\GetResourcesForProductInput;
use App\ResourcePlanning\Application\ProduceGoodsUseCase\ProduceGoodsUseCase;
use App\ResourcePlanning\Application\ProduceGoodsUseCase\ProduceGoodsUseCaseInput;
use App\ResourcePlanning\Domain\FinishedGoods\FinishedGoodFactory;
use App\ResourcePlanning\Domain\Orders\OrderLine;
use App\ResourcePlanning\Domain\Repositories\ResourceRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;

class ResourcePlanningService implements \App\Warehouse\Domain\Services\ResourcePlanningServiceInterface
{

    public function produceGoods(Collection $orderedItems, string $customerName, string $orderReference, string $orderCreationDate)
    {
        $orderLines = collect();

        foreach ($orderedItems as $orderedItem)
        {
            // TODO: Don't use factory from other bounded context
            $finishedGood = FinishedGoodFactory::create($orderedItem->product()->productId(), $orderedItem->product()->productGroup(), $orderedItem->product()->length());

            // TODO: Task 19344: Maak een Composite UI bounded context die zorgt voor het genereren van de PDF met data uit Warehouse en Resource Planning
            // Zo hoeven we niet de picking container mee te geven aan Resource Planning maar genereren we de PDF met een composite UI
            $orderLines[] = new OrderLine($orderedItem->amount(), $finishedGood, null, $customerName,
                $orderReference, $orderCreationDate, null, null, $orderedItem->pickingContainer());
        }

        $input = new ProduceGoodsUseCaseInput($orderLines);
        $useCase = new ProduceGoodsUseCase();
        $result = $useCase->execute($input);

        return collect($result->producedGoods);
    }

    public function getResourcesForProduct(string $productCode, ?string $productGroup): Collection
    {
        $resourceRepository = App::make(ResourceRepositoryInterface::class);
        $getResourcesForProduct = new GetResourcesForProduct($resourceRepository);
        $input = new GetResourcesForProductInput([
            "product" => [
                "product_code" => $productCode,
                "product_group" => $productGroup
            ]
        ]);

        $result = $getResourcesForProduct->execute($input);
        return $result->resources();
    }
}
