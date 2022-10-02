<?php


namespace App\Warehouse\Application\HandleShipmentCreated;


use App\Warehouse\Domain\ReviewRequests\ReviewRequest;
use App\Warehouse\Domain\Shipments\Shipment;
use Illuminate\Support\Collection;

final class HandleShipmentCreatedResult
{
    private Collection $sentEmails;
    private Shipment $shipment;
    private ?ReviewRequest $reviewRequestSent;

    public function __construct(Collection $sentEmails, Shipment $shipment, ?ReviewRequest $reviewRequestSent)
    {
        $this->sentEmails = $sentEmails;
        $this->shipment = $shipment;
        $this->reviewRequestSent = $reviewRequestSent;
    }

    public function sentEmails(): Collection
    {
        return $this->sentEmails;
    }

    public function shipment(): Shipment
    {
        return $this->shipment;
    }

    public function reviewRequestSent(): ?ReviewRequest
    {
        return $this->reviewRequestSent;
    }
}
