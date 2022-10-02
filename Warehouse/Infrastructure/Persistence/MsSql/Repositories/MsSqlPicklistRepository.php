<?php


namespace App\Warehouse\Infrastructure\Persistence\MsSql\Repositories;


use App\Warehouse\Domain\Picklists\Picklist;
use App\Warehouse\Domain\Repositories\PicklistRepositoryInterface;
use App\Warehouse\Infrastructure\Persistence\MsSql\Orders\Mappers\PicklistMapper;
use App\Warehouse\Infrastructure\Persistence\MsSql\Orders\Mappers\StateMapper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MsSqlPicklistRepository implements PicklistRepositoryInterface
{

    public function add(Picklist $picklist): Picklist
    {

        return $picklist;
    }

    public function find(string $id): ?Picklist
    {
        // TODO: Implement find() method.
    }

    public function update(Picklist $picklist): Picklist
    {
        $state = StateMapper::toId($picklist->status());

        // TODO: Database views cannot be updated, because they are read only.
        // TODO: Talk with Damon about updating SalesOrderInfo state.
        if (PicklistMapper::isOrderSplit($picklist->reference()))
        {
            DB::connection("snelstart")->table('Picqer.Orders')
                ->where('SalesOrderShipmentId', '=', $picklist->reference())
                ->update([
                    "ShipmentTrackAndTrace" => $picklist->trackAndTrace(),
                    "ShipmentRemarks" => $picklist->comments(),
                    "ShipmentState" => $state
                ]);
        } else {
            DB::connection("snelstart")->table('Picqer.Orders')
                ->where('SalesOrderShipmentId', '=', $picklist->reference())
                ->update([
                    "TrackAndTrace" => $picklist->trackAndTrace(),
                    "Remarks" => $picklist->comments(),
                    "State" => $state
                ]);
        }

        return $picklist;
    }

    public function findByReference(string $picklistReference): ?Picklist
    {
        $salesOrderId = $picklistReference;
        $shipmentId = null;

        if(Str::contains($picklistReference, '-')) {
            list($salesOrderId, $shipmentId) = explode('-', $picklistReference);
        }

        $msSqlOrder = DB::connection("snelstart")->table('Picqer.Orders')
            ->where('SalesOrderId', '=', $salesOrderId)
            ->first();

        if(!$msSqlOrder) {
            return null;
        }

        $msSqlOrderRows = DB::connection("snelstart")->table('Picqer.OrderRows')
            ->where('SalesOrderNumber', '=', $msSqlOrder->SalesOrderNumber)
            ->get();


        return PicklistMapper::toPicklist($msSqlOrder, $msSqlOrderRows);
    }

    // Check if sales order shipment id has dash (this means it has been splitted before)
    private function isOrderSplitted(string $picklistReference): bool
    {
        return sizeof(explode("-", $picklistReference)) === 2;
    }

    public function findByOrderReference(string $orderReference): ?Picklist
    {
        // TODO: Implement findByOrderReference() method.
    }

    public function findAll(): Collection
    {
        // TODO: Implement findAll() method.
    }

    public function findOneById(string $id): ?Picklist
    {
        // TODO: Implement findOneById() method.
    }
}
