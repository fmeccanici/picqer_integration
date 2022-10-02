<?php


namespace App\Warehouse\Infrastructure\Webhooks\Listeners;


use App\Warehouse\Domain\Repositories\OrderRepositoryInterface;
use App\Warehouse\Domain\Repositories\PicklistRepositoryInterface;
use App\Warehouse\Domain\Services\WarehouseServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class PicqerPicklistCreatedListener implements EventListenerInterface
{
    private WarehouseServiceInterface $warehouseService;
    private PicklistRepositoryInterface $picqerPicklistRepository;
    private OrderRepositoryInterface $orderRepository;

    public function __construct()
    {
        $this->warehouseService = App::make(WarehouseServiceInterface::class);
        $this->picqerPicklistRepository = App::make(PicklistRepositoryInterface::class);
        $this->orderRepository = App::make(OrderRepositoryInterface::class, [
            'name' => 'picqer'
        ]);
    }

    public function handle(Request $request)
    {
        $idPicklist = $request->input("data")["idpicklist"];

        \App\Jobs\HandlePicklistCreated::dispatchSync($idPicklist, $this->picqerPicklistRepository, $this->warehouseService, $this->orderRepository);
    }
}
