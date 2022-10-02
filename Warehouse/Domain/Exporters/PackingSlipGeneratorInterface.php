<?php


namespace App\Warehouse\Domain\Exporters;


use App\Warehouse\Domain\Exceptions\PackingSlipGeneratorOperationException;
use App\Warehouse\Domain\Orders\OrderedItem;
use App\Warehouse\Domain\Shipments\PackingSlip;
use Illuminate\Support\Collection;

interface PackingSlipGeneratorInterface
{
    /**
     * @param Collection<OrderedItem> $orderedItems
     *
     * Path of stored file
     * @return mixed
     *
     *  @throws PackingSlipGeneratorOperationException
     */
    public function generateFor(Collection $orderedItems): PackingSlip;
}
