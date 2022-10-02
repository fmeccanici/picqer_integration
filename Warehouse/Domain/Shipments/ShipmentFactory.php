<?php


namespace App\Warehouse\Domain\Shipments;


use App\Warehouse\Domain\Exceptions\ShipmentFactoryException;
use App\Warehouse\Domain\Exceptions\TrackAndTraceException;
use App\Warehouse\Domain\Exporters\PackingSlipGeneratorInterface;
use App\Warehouse\Domain\Mails\MailerServiceInterface;
use App\Warehouse\Domain\Orders\OrderedItemFactory;
use Carbon\CarbonImmutable;
use Faker\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;

class ShipmentFactory
{

    public static function create(string $orderReference, array $attributes = []): Shipment
    {
        $faker = Factory::create();
        $trackAndTrace = Arr::get($attributes, 'trackAndTrace');

        if (! $trackAndTrace)
        {
            $trackAndTraceCode = "Test Track And Trace Code";
            $trackAndTraceUrl = $faker->url;
            $trackAndTrace = new TrackAndTrace($trackAndTraceCode, $trackAndTraceUrl);
        }

        $reference = uniqid();
        $deliveryDate = CarbonImmutable::createFromDate(2022, 1, 1);
        $shippingExplanation = "Uitleg van transporteur";
        $deliveryMethod = "Briefpost";
        $carrierName = "PostNl";
        $picklistId = "1234-1234";
        $orderedItems = OrderedItemFactory::multipleRandom(10);
        $packingListGenerator = App::make(PackingSlipGeneratorInterface::class);
        $mailerService = App::make(MailerServiceInterface::class);

        $trackAndTraceMailSent = Arr::get($attributes, 'trackAndTraceMailSent');

        if ($trackAndTraceMailSent === null)
        {
            $trackAndTraceMailSent = (bool) rand(0, 1);
        }

        $doNotSendReviewInvitation = Arr::get($attributes, 'do_not_send_review_invitation');

        return new Shipment($reference, $orderReference, $trackAndTrace,  $deliveryMethod, $carrierName, $orderedItems, $picklistId, $packingListGenerator, $mailerService, $trackAndTraceMailSent, $deliveryDate, $shippingExplanation, (bool) random_int(0, 1), $doNotSendReviewInvitation);
    }

    /**
     * @throws TrackAndTraceException
     * @throws ShipmentFactoryException
     */
    public static function fromArray(array $shipment): Shipment
    {
        $reference = $shipment["reference"];
        $orderReference = $shipment["order_reference"];
        $trackAndTraceArray = Arr::get($shipment, 'track_and_trace');

        $trackAndTrace = $trackAndTraceArray ? TrackAndTrace::fromArray($trackAndTraceArray) : null;
        $deliveryDateString = Arr::get($shipment, 'delivery_date');
        $deliveryDate = ! $deliveryDateString ? null : CarbonImmutable::parse($deliveryDateString);
        $shippingExplanation = $shipment["shipping_explanation"];
        $deliveryMethod = $shipment["delivery_method"];
        $carrierName = $shipment["carrier_name"];

        $orderedItems = OrderedItemFactory::fromMultipleInArray($shipment["ordered_items"]);
        $picklistId = $shipment["picklist_id"];
        $packingListGenerator = App::make(PackingSlipGeneratorInterface::class);
        $mailerService = App::make(MailerServiceInterface::class);
        $trackAndTraceMailSent = Arr::get($shipment, 'track_and_trace_mail_sent');
        $doNotSendReviewInvitation = Arr::get($shipment, 'do_not_send_review_invitation');

        return new Shipment($reference, $orderReference, $trackAndTrace, $deliveryMethod, $carrierName, $orderedItems, $picklistId, $packingListGenerator, $mailerService, $trackAndTraceMailSent, $deliveryDate, $shippingExplanation, $doNotSendReviewInvitation);
    }
}
