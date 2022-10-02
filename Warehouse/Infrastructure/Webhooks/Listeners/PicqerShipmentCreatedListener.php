<?php


namespace App\Warehouse\Infrastructure\Webhooks\Listeners;

use App\Warehouse\Domain\Exceptions\ShipmentNotFoundException;
use App\Warehouse\Domain\Repositories\EmployeeRepositoryInterface;
use App\Warehouse\Domain\Repositories\ShipmentRepositoryInterface;
use App\Warehouse\Domain\Services\WarehouseServiceInterface;
use App\Warehouse\Infrastructure\Jobs\HandleShipmentCreatedJob;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;

class PicqerShipmentCreatedListener implements EventListenerInterface
{
    protected ShipmentRepositoryInterface $picqerShipmentRepository;
    protected EmployeeRepositoryInterface $picqerEmployeeRepository;

    public function __construct()
    {
        $this->picqerShipmentRepository = App::make(ShipmentRepositoryInterface::class);
        $this->picqerEmployeeRepository = App::make(EmployeeRepositoryInterface::class);
    }

    /**
     * @param Request $request
     * @return void
     * @throws ShipmentNotFoundException
     */
    public function handle(Request $request): void
    {
        $picklistReference = Arr::get($request, 'data.idpicklist');
        $createdByIdUser = Arr::get($request, 'data.created_by_iduser');
        $employee = $this->picqerEmployeeRepository->findOneById($createdByIdUser);

        $shipment = $this->picqerShipmentRepository->findOneByPicklistReference($picklistReference);

        if (! $shipment)
        {
            throw new ShipmentNotFoundException('Shipment for idpicklist ' . $picklistReference . ' not found');
        }

        $warehouseService = App::make(WarehouseServiceInterface::class);

        HandleShipmentCreatedJob::dispatch($warehouseService, $shipment, $employee->name(), true, null, true, 'picqer');
    }
}
