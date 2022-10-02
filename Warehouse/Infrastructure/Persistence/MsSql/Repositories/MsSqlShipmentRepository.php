<?php


namespace App\Warehouse\Infrastructure\Persistence\MsSql\Repositories;


use App\Warehouse\Domain\Repositories\ShipmentRepositoryInterface;
use App\Warehouse\Domain\Shipments\Shipment;
use App\Warehouse\Infrastructure\Exceptions\MsSqlShipmentRepositoryOperationException;
use App\Warehouse\Infrastructure\Persistence\MsSql\Shipments\Mappers\MsSqlShipmentMapper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MsSqlShipmentRepository implements ShipmentRepositoryInterface
{

    public function findAllByOrderReference(string $orderReference): Collection
    {
        // TODO: Implement findAllByOrderReference() method.
    }

    public function findOneByReference(string $reference): ?Shipment
    {
        // TODO: Implement findOneByReference() method.
    }

    public function findOneByPicklistReference(string $picklistReference): ?Shipment
    {
        // TODO: Implement findOneByPicklistReference() method.
    }

    /**
     * @throws MsSqlShipmentRepositoryOperationException
     */
    public function findOneByOrderReference(string $orderReference): ?Shipment
    {
        if(Str::contains($orderReference, '-')) {
            list($salesOrderNumber, $shipmentNumber) = explode('-', $orderReference);

            $msSqlOrder = DB::connection("snelstart")->table('Picqer.Orders')
                ->where([
                    'SalesOrderNumber' => $salesOrderNumber,
                    'ShipmentNumber' => $shipmentNumber
                ])
                ->first();
        } else {
            $salesOrderNumber = $orderReference;

            $msSqlOrder = DB::connection("snelstart")->table('Picqer.Orders')
                ->where([
                    'SalesOrderNumber' => $salesOrderNumber
                ])
                ->first();
        }

        if(!$msSqlOrder) {
            return null;
        }

        $msSqlOrderRows = DB::connection("snelstart")->table('Picqer.OrderRows')
            ->where('SalesOrderNumber', '=', $msSqlOrder->SalesOrderNumber)
            ->get();

        return MsSqlShipmentMapper::toShipment($msSqlOrder, $msSqlOrderRows);
    }

    public function save(Shipment $shipment): void
    {
        // TODO: Implement save() method.
    }
}
