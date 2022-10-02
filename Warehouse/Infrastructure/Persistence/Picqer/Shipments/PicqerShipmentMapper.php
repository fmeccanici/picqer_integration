<?php

namespace App\Warehouse\Infrastructure\Persistence\Picqer\Shipments;

use App\Warehouse\Domain\Exporters\PackingSlipGeneratorInterface;
use App\Warehouse\Domain\Mails\MailerServiceInterface;
use App\Warehouse\Domain\Shipments\Shipment;
use App\Warehouse\Domain\Shipments\TrackAndTrace;
use App\Warehouse\Infrastructure\Exceptions\PicqerShipmentMapperException;
use App\Warehouse\Infrastructure\Persistence\Picqer\Orders\Mappers\OrderedItemMapper;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;

class PicqerShipmentMapper
{
    /**
     * @throws PicqerShipmentMapperException
     */
    public static function toEntity(array $picqerShipment): ?Shipment
    {
        $idOrder = Arr::get($picqerShipment, 'idorder');

        if (! $idOrder)
        {
            throw new PicqerShipmentMapperException('idorder not present');
        }

        $orderReference = Arr::get($picqerShipment, 'order_reference');
        $reference = Arr::get($picqerShipment, 'idshipment');
        $trackAndTraceCode = Arr::get($picqerShipment, 'trackingcode');
        $trackAndTraceUrl = Arr::get($picqerShipment, 'tracktraceurl');
        $trackAndTrace = new TrackAndTrace($trackAndTraceCode, $trackAndTraceUrl);

        $deliveryMethod = Arr::get($picqerShipment, 'delivery_method');

        $carrierName = Arr::get($picqerShipment, 'providername');

        $picqerProducts = Arr::get($picqerShipment, 'products');
        $orderedItems = OrderedItemMapper::toEntities(! $picqerProducts ? collect(): $picqerProducts);
        $picklistId = Arr::get($picqerShipment, 'idpicklist');
        $packingSlipGenerator = App::make(PackingSlipGeneratorInterface::class);
        $mailerService = App::make(MailerServiceInterface::class);
        $preferredDeliveryDateString = Arr::get($picqerShipment, 'preferred_delivery_date');
        $deliveryDate = ! $preferredDeliveryDateString ? null : CarbonImmutable::parse($preferredDeliveryDateString);
        $shippingExplanation = null;

        $trackAndTraceMailSent = Arr::get($picqerShipment, 'track_and_trace_mail_sent');
        return new Shipment($reference, $orderReference, $trackAndTrace, $deliveryMethod, $carrierName, $orderedItems, $picklistId, $packingSlipGenerator, $mailerService, $trackAndTraceMailSent, $deliveryDate, $shippingExplanation, false);

    }

    public static function toPicqer(Shipment $shipment): array
    {

    }
}
