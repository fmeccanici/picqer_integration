<?php


namespace App\Warehouse\Domain\Mails;

use App\Warehouse\Domain\Orders\Order;
use App\Warehouse\Domain\Parties\Customer;
use App\Warehouse\Domain\Shipments\PackingSlip;
use App\Warehouse\Domain\Shipments\Shipment;
use App\Warehouse\Infrastructure\Persistence\MsSql\Orders\Mappers\CountryCodeMapper;
use App\Warehouse\Infrastructure\Persistence\Picqer\Orders\Mappers\TrackAndTraceMailDeliveryMethodMapper;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class TrackAndTraceMail extends Mail
{
    protected Order $order;
    protected Shipment $shipment;
    protected ?PackingSlip $pickupReport;
    protected Customer $customer;

    public const FLOW_NAME = 'TrackAndTrace';

    public function __construct(Customer $customer, Order $order, Shipment $shipment, ?PackingSlip $pickupReport)
    {
        $recipient = new Recipient($customer->contactName(), $customer->email(), $customer->firstName(), $customer->lastName());
        parent::__construct($recipient);
        $this->customer = $customer;
        $this->order = $order;
        $this->shipment = $shipment;
        $this->pickupReport = $pickupReport;
    }

    public function order(): Order
    {
        return $this->order;
    }

    public function shipment(): Shipment
    {
        return $this->shipment;
    }

    public function packingSlip(): ?PackingSlip
    {
        return $this->pickupReport;
    }

    public function subject(): string
    {
        return $this->recipient()->name().", uw bestelling is verzonden";
    }

    public function customer(): Customer
    {
        return $this->customer;
    }

    public function data(): array
    {
        CarbonImmutable::setLocale('nl');

        $data = [
            'ordernummer' => $this->shipment->orderReference(),
            'transporteur' => $this->shipment()->carrierName(),
            'bezorgmethode' => TrackAndTraceMailDeliveryMethodMapper::toFlowMailer($this->shipment()->deliveryMethod()),
            'postcodePlaats' => $this->customer()->deliveryAddress()->zipcode() . " " . $this->customer()->deliveryAddress()->city(),
            'verzenduitleg' => nl2br($this->shipment()->shippingExplanation()),
            'voornaam' => $this->recipient()->name(),
            'achternaam' => "",
            'land' => CountryCodeMapper::toMsSqlCountry($this->customer()->deliveryAddress()->countryCode()),
            'trackandtraceurl' => $this->shipment()->trackAndTrace()->url(),
            'adres' => $this->customer()->deliveryAddress()->fullStreetAddress(),
            'bezorgdatum' => Str::ucfirst($this->shipment()->deliveryDate()?->translatedFormat('l d F Y')),
            'locatienaam' => $this->customer->deliveryAddress()->name()
        ];

        CarbonImmutable::setLocale(App::getLocale());

        if ($packingSlip = $this->packingSlip())
        {
            $data['attachment'] = $packingSlip->url();
        }

        return $data;
    }
}
