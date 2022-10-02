<?php


namespace App\Warehouse\Domain\Shipments;


use App\SharedKernel\CleanArchitecture\Entity;
use App\Warehouse\Domain\Exceptions\PackingSlipGeneratorOperationException;
use App\Warehouse\Domain\Exporters\PackingSlipGeneratorInterface;
use App\Warehouse\Domain\Mails\CouponMail;
use App\Warehouse\Domain\Mails\MailerServiceInterface;
use App\Warehouse\Domain\Mails\TrackAndTraceMail;
use App\Warehouse\Domain\Orders\OrderedItem;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class Shipment extends Entity
{
    protected ?TrackAndTrace $trackAndTrace;
    protected ?CarbonImmutable $deliveryDate;
    protected string $orderReference;
    protected ?string $shippingExplanation;
    protected ?string $deliveryMethod;
    protected ?string $carrierName;
    protected Collection $orderedItems;
    protected ?string $picklistId;
    protected string $reference;
    protected PackingSlipGeneratorInterface $packingSlipGenerator;
    protected MailerServiceInterface $mailerService;
    protected bool $trackAndTraceMailSent;
    protected ?bool $doNotSendReviewInvitation;

    public function __construct(string $reference, string $orderReference, ?TrackAndTrace $trackAndTrace, ?string $deliveryMethod, ?string $carrierName, Collection $orderedItems, ?string $picklistId, PackingSlipGeneratorInterface $packingSlipGenerator, MailerServiceInterface $mailerService, bool $trackAndTraceMailSent, ?CarbonImmutable $deliveryDate, ?string $shippingExplanation, ?bool $doNotSendReviewInvitation)
    {
        $this->reference = $reference;
        $this->orderReference = $orderReference;
        $this->trackAndTrace = $trackAndTrace;
        $this->deliveryDate = $deliveryDate;
        $this->shippingExplanation = $shippingExplanation;
        $this->deliveryMethod = $deliveryMethod;
        $this->carrierName = $carrierName;
        $this->orderedItems = $orderedItems;
        $this->picklistId = $picklistId;
        $this->packingSlipGenerator = $packingSlipGenerator;
        $this->mailerService = $mailerService;
        $this->trackAndTraceMailSent = $trackAndTraceMailSent;
        $this->doNotSendReviewInvitation = $doNotSendReviewInvitation;
    }

    public function reference(): string
    {
        return $this->reference;
    }

    public function orderReference(): string
    {
        return $this->orderReference;
    }

    public function trackAndTrace(): ?TrackAndTrace
    {
        return $this->trackAndTrace;
    }

    public function deliveryDate(): ?CarbonImmutable
    {
        return $this->deliveryDate;
    }

    public function changeDeliveryDate(CarbonImmutable $deliveryDate)
    {
        $this->deliveryDate = $deliveryDate;
    }

    public function shippingExplanation(): ?string
    {
        return $this->shippingExplanation;
    }

    public function changeShippingExplanation(?string $shippingExplanation)
    {
        $this->shippingExplanation = $shippingExplanation;
    }

    public function deliveryMethod(): ?string
    {
        return $this->deliveryMethod;
    }

    public function changeDeliveryMethod(string $deliveryMethod): ?string
    {
        return $this->deliveryMethod = $deliveryMethod;
    }

    public function carrierName(): ?string
    {
        return $this->carrierName;
    }

    public function orderedItems(): Collection
    {
        return $this->orderedItems;
    }

    public function orderedItem(string $productCode): ?OrderedItem
    {
        foreach ($this->orderedItems as $orderedItem)
        {
            if ($orderedItem->product()->productId() === $productCode)
            {
                return $orderedItem;
            }
        }

        return null;
    }

    public function changeOrderedItems(Collection $orderedItems)
    {
        $this->orderedItems = $orderedItems;
    }

    public function changeCarrierName(?string $carrierName)
    {
        $this->carrierName = $carrierName;
    }

    public function doNotSendReviewInvitation(): bool
    {
        return $this->doNotSendReviewInvitation;
    }

    public function picklistId(): ?string
    {
        return $this->picklistId;
    }

    public function createPackingSlip(): ?PackingSlip
    {
        try {
            return $this->packingSlipGenerator->generateFor($this->orderedItems);
        } catch (PackingSlipGeneratorOperationException $e)
        {
            return null;
        }
    }

    public function sendTrackAndTraceMail(TrackAndTraceMail $trackAndTraceMail): void
    {
        $this->mailerService->send($trackAndTraceMail);
        $this->trackAndTraceMailSent = true;
    }

    public function sendCouponMail(CouponMail $couponMail): void
    {
        $this->mailerService->send($couponMail);
    }

    public function trackAndTraceMailSent(): bool
    {
        return $this->trackAndTraceMailSent;
    }

    public function trackAndTraceMailNotSent(): bool
    {
        return ! $this->trackAndTraceMailSent;
    }

    public function changeTrackAndTraceMailSent(bool $trackAndTraceMailSent)
    {
        $this->trackAndTraceMailSent = $trackAndTraceMailSent;
    }

    public function toArray(): array
    {
        $orderedItems = $this->orderedItems->map(function (OrderedItem $orderedItem) {
            return $orderedItem->toArray();
        });

        $orderedItemsAsArray = $orderedItems->toArray();

        return [
            "reference" => $this->reference,
            "order_reference" => $this->orderReference,
            "picklist_id" => $this->picklistId,
            "ordered_items" => $orderedItemsAsArray,
            "track_and_trace" => $this->trackAndTrace?->toArray(),
            "delivery_date" => optional($this->deliveryDate)->toDateString(),
            "shipping_explanation" => $this->shippingExplanation,
            "delivery_method" => $this->deliveryMethod,
            "carrier_name" => $this->carrierName,
            "track_and_trace_mail_sent" => $this->trackAndTraceMailSent,
        ];
    }

    protected function cascadeSetIdentity(int|string $id): void
    {
        // TODO: Implement cascadeSetIdentity() method.
    }
}
