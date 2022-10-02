<?php


namespace App\Warehouse\Infrastructure\Services;


use App\Warehouse\Domain\Orders\Order;
use App\Warehouse\Domain\Picklists\Picklist;
use App\Warehouse\Domain\Repositories\PicklistRepositoryInterface;
use App\Warehouse\Domain\Services\OrderProcessorServiceInterface;
use App\Warehouse\Domain\Services\WarehouseServiceInterface;
use App\Warehouse\Infrastructure\ApiClients\PicqerApiClient;
use App\Warehouse\Infrastructure\Exceptions\PicqerOrderProcessorOperationException;
use App\Warehouse\Infrastructure\Exceptions\PicqerPicklistRepositoryOperationException;
use App\Warehouse\Infrastructure\Persistence\Picqer\Repositories\PicqerPicklistRepository;

class PicqerOrderProcessorService implements OrderProcessorServiceInterface
{
    private \Picqer\Api\Client $apiClient;
    private PicklistRepositoryInterface $picqerPicklistRepository;
    private WarehouseServiceInterface $warehouseService;

    public function __construct(PicklistRepositoryInterface $picqerPicklistRepository,
                                WarehouseServiceInterface $warehouseService,
                                PicqerApiClient $apiClient)
    {
        $this->apiClient = $apiClient->getClient();
        $this->picqerPicklistRepository = $picqerPicklistRepository;
        $this->warehouseService = $warehouseService;
    }

    public function process(Order $order)
    {

        // TODO: Filter by reference does not work
        $picqerOrders = $this->apiClient->getAllOrders(["status" => "concept"])["data"];

        if (sizeof($picqerOrders) === 0)
        {
            throw new PicqerOrderProcessorOperationException("Couldn't find any open orders to be processed");
        }

        foreach ($picqerOrders as $picqerOrder)
        {
            if ($picqerOrder["reference"] === $order->reference())
            {
                $idOrder = $picqerOrder["idorder"];
                $result = $this->apiClient->processOrder($idOrder);

                if ($result["success"] === false)
                {
                    $errorMessage = $result["errormessage"];
                    throw new PicqerOrderProcessorOperationException("Order with reference ".$order->reference()." was not able to be processed: ".$errorMessage);
                }
            }
        }
    }
}
