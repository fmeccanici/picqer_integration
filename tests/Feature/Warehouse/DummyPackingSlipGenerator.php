<?php


namespace Tests\Feature\Warehouse;


use App\Warehouse\Domain\Shipments\PackingSlip;
use Illuminate\Support\Collection;

class DummyPackingSlipGenerator implements \App\Warehouse\Domain\Exporters\PackingSlipGeneratorInterface
{

    /**
     * @inheritDoc
     */
    public  function generateFor(Collection $orderedItems): PackingSlip
    {
        return new PackingSlip("Test Path",'test-url');
    }
}
