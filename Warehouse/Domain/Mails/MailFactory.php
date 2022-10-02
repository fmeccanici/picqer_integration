<?php


namespace App\Warehouse\Domain\Mails;


use App\Warehouse\Domain\Orders\OrderFactory;
use App\Warehouse\Domain\Shipments\PackingSlip;
use App\Warehouse\Domain\Shipments\ShipmentFactory;

class MailFactory
{
    public static function constantTrackAndTrace(): TrackAndTraceMail
    {
        $order = OrderFactory::constantUnprocessed();
        $shipment = ShipmentFactory::create($order->reference());
        $packingSlip = new PackingSlip('Constant-Packing-Slip-Path', 'constant-packig-slip-url');
        return new TrackAndTraceMail($order->customer(), $order, $shipment, $packingSlip);
    }
}
