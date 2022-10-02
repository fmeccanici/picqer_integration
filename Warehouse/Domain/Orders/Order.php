<?php


namespace App\Warehouse\Domain\Orders;


use App\SharedKernel\CleanArchitecture\AggregateRoot;
use App\Warehouse\Domain\Exceptions\EmailNotFoundException;
use App\Warehouse\Domain\Exceptions\OrderOperationException;
use App\Warehouse\Domain\Exceptions\PicklistNotFoundException;
use App\Warehouse\Domain\Exceptions\PicklistOperationException;
use App\Warehouse\Domain\Mails\TrackAndTraceMail;
use App\Warehouse\Domain\Parties\Customer;
use App\Warehouse\Domain\Picklists\Picklist;
use App\Warehouse\Domain\Services\WarehouseServiceInterface;
use App\Warehouse\Domain\Shipments\Shipment;
use App\Warehouse\Domain\Shipments\TrackAndTrace;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Nette\NotImplementedException;

// TODO: Task 18964: Herschrijf Order en OrderedItem object zodat deze niet meer vervuild is met levertijden parameters
class Order extends AggregateRoot
    implements Arrayable
{
    protected string $reference;
    protected ?Customer $customer;
    protected ?CarbonImmutable $preferredDeliveryDate;
    protected string $status;
    protected ?string $comments;

    /**
     * @var Collection<Picklist>
     */
    protected Collection $picklists;
    protected CarbonImmutable $creationDate;

    /**
     * @var OrderedItem[]
     */
    protected array $orderedItems;
    protected ?DeliveryDateEstimation $deliveryDateEstimation;

    protected Collection $tags;
    protected Collection $registeredShipments;
    protected ?DeliveryOption $deliveryOption;

    protected WarehouseServiceInterface $warehouseService;
    protected Collection $actions;
    protected ?string $externalId;
    protected ?bool $noReview;
    protected ?TrackAndTrace $trackAndTrace = null;

    protected int|string|null $couponDiscountCodeId = null;

    public function __construct(CarbonImmutable $creationDate, array $orderedItems, WarehouseServiceInterface $warehouseService, ?DeliveryDateEstimation $deliveryDateEstimation, array $actions = [], array $tags = [], array $registeredShipments = [], ?DeliveryOption $deliveryOption = null, ?string $externalId = null, ?bool $noReview = false)
    {
//        $this->validate($creationDate, $orderedItems, $deliveryDateEstimation);

        $this->creationDate = $creationDate;
        $this->orderedItems = $orderedItems;
        $this->deliveryDateEstimation = $deliveryDateEstimation;

        $this->picklists = collect();
        $this->preferredDeliveryDate = null;
        $this->status = "unprocessed";
        $this->comments = null;
        $this->tags = collect($tags);
        $this->actions = collect($actions);
        $this->registeredShipments = collect($registeredShipments);
        $this->deliveryOption = $deliveryOption;
        $this->warehouseService = $warehouseService;
        $this->externalId = $externalId;
        $this->noReview = $noReview;
    }

    public function reference(): string
    {
        return $this->reference;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function changeId(string|int $id)
    {
        $this->id = $id;
    }

    public function externalId(): ?string
    {
        return $this->externalId;
    }

    public function changeExternalId(string $externalId)
    {
        $this->externalId = $externalId;
    }

    public function changeReference(string $reference)
    {
        $this->reference = $reference;
    }

    public function customer(): Customer
    {
        return $this->customer;
    }

    public function changeCustomer(?Customer $customer)
    {
        $this->customer = $customer;
    }

    public function preferredDeliveryDate(): ?CarbonImmutable
    {
        return $this->preferredDeliveryDate;
    }

    public function changeNoReview(?bool $noReview)
    {
        $this->noReview = $noReview;
    }

    public function noReview(): bool
    {
        return $this->noReview;
    }

    /**
     * @throws PicklistNotFoundException
     * @throws PicklistOperationException
     */
    public function changePreferredDeliveryDate(?CarbonImmutable $preferredDeliveryDate, bool $discussedWithLogisticsDepartment = false)
    {
        $this->preferredDeliveryDate = $preferredDeliveryDate;

        $picklist = $this->picklists->first();

        // TODO: Also snooze backorders
        if ($picklist !== null)
        {
            if ($picklist->snoozedUntil() && $preferredDeliveryDate && $picklist->snoozedUntil()->isSameDay(CarbonImmutable::now()) && ! $discussedWithLogisticsDepartment)
            {
                throw new PicklistOperationException('Kan bezorgdatum niet wijzigen, omdat de picklijst is gesnoozed tot vandaag. Overleg met logistiek om de wijziging alsnog door te voeren.');
            }

            $picklist->changePreferredDeliveryDate($preferredDeliveryDate, $discussedWithLogisticsDepartment);
        }

    }

    public function status(): string
    {
        return $this->status;
    }

    public function changeStatus(string $status)
    {
        $this->status = $status;
    }

    public function comments(): ?string
    {
        return $this->comments;
    }

    public function changeComments(?string $comments)
    {
        $this->comments = $comments;
    }

    public function prependComments(string $comments)
    {
        $this->comments = $comments . PHP_EOL . $this->comments;
    }

    public function creationDate(): CarbonImmutable
    {
        return $this->creationDate;
    }

    public function tags(): Collection
    {
        return $this->tags;
    }

    public function changeTags(Collection $tags)
    {
        $this->tags = $tags;
    }

    public function addTag(string $tag)
    {
        $this->tags->push($tag);
    }

    public function addAction(Action $action)
    {
        $this->actions->push($action);
    }

    public function actions(): Collection
    {
        return $this->actions;
    }

    public function items(): array
    {
        // TODO: This won't solve immutability leakage
        //return array_merge([], $this->orderedItems);

        // TODO: Fix immutability leakage
        return $this->orderedItems;
    }

    public function itemsWithProductId(string $productId): Collection
    {
        $result = collect();

        foreach ($this->orderedItems as $orderedItem)
        {
            $result->push($orderedItem);
        }

        return $result;
    }

    /**
     * @return Collection<Picklist>
     */
    public function picklists(): Collection
    {
        return $this->picklists->sortBy(function (Picklist $picklist) {
                return $picklist->reference();
        });
    }

    public function hasPicklist(string $reference): bool
    {
        return $this->picklist($reference) !== null;
    }

    public function picklist(string $reference): ?Picklist
    {
        return $this->picklists->first(function(Picklist $picklist) use ($reference) {
            return $picklist->reference() === $reference;
        });
    }

    public function changePicklist(Picklist $picklist)
    {
        $key = $this->picklists->search(function(Picklist $existingPicklist) use ($picklist) {
            return $picklist->reference() === $existingPicklist->reference();
        });

        $this->picklists->replace([
            $key => $picklist
        ]);
    }

    public function changePicklists(Collection $picklists)
    {
        $this->picklists = $picklists;
    }

    public function addPicklist(Picklist $picklist)
    {
        $this->picklists->push($picklist);
    }

    public function maxDeliveryLevelItem(): OrderedItem
    {
        return array_reduce($this->orderedItems,
            function (?OrderedItem $carry, OrderedItem $item) {
                if (is_null($carry)) {
                    return $item;
                }

                return $item->deliveryLevel() > $carry->deliveryLevel() ? $item : $carry;
            });
    }

    public function maxShippingDateItem(): ?OrderedItem
    {
        $itemsWithShippingDate = array_filter($this->orderedItems,
            fn(OrderedItem $orderedItem) => $orderedItem->hasShippingDate());

        return array_reduce($itemsWithShippingDate,
            function (?OrderedItem $carry, OrderedItem $item)
            {
                if (is_null($carry)) {
                    return $item;
                }

                return $item->shippingDateEstimation()->greaterThan($carry->shippingDateEstimation())
                    ? $item
                    : $carry;
            });
    }

    public function maxItem(): ?OrderedItem
    {
        $itemsWithShippingDate = array_filter($this->orderedItems,
            fn(OrderedItem $orderedItem) => $orderedItem->hasShippingDate());

        return array_reduce($itemsWithShippingDate,
            function (?OrderedItem $carry, OrderedItem $item)
            {
                if (is_null($carry)) {
                    return $item;
                }

                if ($item->deliveryLevel() === $carry->deliveryLevel())
                {
                    return $item->shippingDateEstimation()->greaterThan($carry->shippingDateEstimation())
                        ? $item
                        : $carry;
                }
                else
                {
                    return $item->deliveryLevel() > $carry->deliveryLevel()
                        ? $item
                        : $carry;
                }
            });
    }

    public function deliveryDateEstimation(): ?DeliveryDateEstimation
    {
        return $this->deliveryDateEstimation;
    }

    public function equalTo(Order $other): bool
    {
        return $this->creationDate->equalTo($other->creationDate) &&
            OrderedItemArrayEqualityComparer::equals($this->orderedItems, $other->orderedItems) &&
            DeliveryDateEstimationEqualityComparer::equals($this->deliveryDateEstimation, $other->deliveryDateEstimation);
    }

    public function deliveryOption(): ?DeliveryOption
    {
        return $this->deliveryOption;
    }

    public function changeDeliveryOption(string $country, string $deliveryOptionName, ?string $carrierName, ?string $locationId = null, ?string $retailNetworkId = null)
    {
        $this->deliveryOption = $this->warehouseService->getDeliveryOption($country, $deliveryOptionName, $carrierName, $locationId, $retailNetworkId);
    }

    public function toArray(): array
    {
        $orderedItems = array_map(function (OrderedItem $orderedItem) {
            return $orderedItem->toArray();
        }, $this->orderedItems);

        // All DateTime objects should be serialized with RFC3339 format
        return [
            "creation_date" => $this->creationDate->toRfc3339String(),
            "ordered_items" => $orderedItems,
            "delivery_date_estimation" => optional($this->deliveryDateEstimation)->toArray()
        ];
    }

    public function toCompleteArray(): array
    {
        $customer = $this->customer->toArray();

        $orderedItems = array_map(function (OrderedItem $orderedItem) {
            return $orderedItem->toArray();
        }, $this->orderedItems);

        if ($this->preferredDeliveryDate === null)
        {
            $preferredDeliveryDateString = null;
        } else {
            $preferredDeliveryDateString = $this->preferredDeliveryDate->format("Y-m-d");
        }

        $picklistsArray = [];
        foreach ($this->picklists->toArray()[0] as $picklist)
        {
            $picklistsArray[] = $picklist->toArray();
        }

        return [
            "order" => [
                "reference" => $this->reference(),
                "customer" => $customer["customer"],
                "preferred_delivery_date" => $preferredDeliveryDateString,
                "status" => $this->status,
                "comments" => $this->comments,
                "creation_date" => $this->creationDate->format("Y-m-d H:i:s"),
                "ordered_items" => $orderedItems,
                "picklists" => $picklistsArray
            ]
        ];
    }

    public function shippedOrderedItems(): Collection
    {
        $result = collect();

        foreach ($this->orderedItems as $orderedItem)
        {
            if ($orderedItem->shipmentId() !== null)
            {
                $result->push($orderedItem);
            }
        }

        return $result;
    }

    public function areAllOrderedItemsShipped(): bool
    {
        return sizeof($this->shippedOrderedItems()) === sizeof($this->orderedItems);
    }

    public function containsMultipleShipments(): bool
    {
        $shipmentIds = [];

        foreach ($this->orderedItems as $orderedItem)
        {
            $shipmentId = $orderedItem->shipmentId();

            if (! in_array($shipmentId, $shipmentIds))
            {
                $shipmentIds[] = $orderedItem->shipmentId();
            }
        }

        if (sizeof($shipmentIds) > 1)
        {
            return true;
        }

        return false;
    }

    public function isShippedInOneShipment(): bool
    {
        return ! $this->containsMultipleShipments();
    }

    private function notAllOrderedItemsAreShipped(): bool
    {
        return ! $this->areAllOrderedItemsShipped();
    }

    public function registerShipment(Shipment $shipment)
    {
        if ($shipment->orderReference() !== $this->reference)
        {
            throw new OrderOperationException("Shipment order reference ".$shipment->orderReference()." does not match order reference of ".$this->reference);
        }

        // This is a potential business use case, which we log to determine if it happens often.
        if ($this->hasShipment($shipment->reference()) && $shipment->orderedItems() !== $this->shipment($shipment->reference()))
        {
            Log::warning("Shipment with reference ".$shipment->reference()." is registered twice with different ordered items on order with reference ".$this->reference);
        }

        foreach ($shipment->orderedItems() as $shipmentOrderedItem)
        {
            $orderedItem = $this->orderedItemNotYetFulfilled($shipmentOrderedItem->product()->productId());

            if ($orderedItem === null)
            {
                // TODO: Make separate channel. Add product id to log and shipment reference.
                Log::error("Ordered item of shipment not found in order");
            } else {
                $orderedItem->shipWith($shipment);
            }

        }
    }

    public function hasShipment(string $reference): bool
    {
        return $this->shipment($reference) !== null;
    }

    public function shipment(string $reference): ?Shipment
    {
        foreach ($this->orderedItems as $orderedItem)
        {
            $shipment = $orderedItem->shipment($reference);

            if ($shipment !== null)
            {
                return $shipment;
            }
        }

        return null;
    }

    public function orderedItems(): Collection
    {
        return collect($this->orderedItems);
    }

    public function orderedItem(string $productCode): ?OrderedItem
    {
        foreach ($this->orderedItems as $orderedItem)
        {
            if ($productCode === $orderedItem->product()->productId())
            {
                return $orderedItem;
            }
        }

        return null;
    }

    public function orderedItemNotYetFulfilled(string $productCode): ?OrderedItem
    {
        foreach ($this->orderedItems as $orderedItem)
        {
            if ($productCode === $orderedItem->product()->productId() && ! $orderedItem->isFullyFulfilled())
            {
                return $orderedItem;
            }
        }

        return null;
    }

    public function findOrderedItem(Collection $orderedItems, string $productId): ?OrderedItem
    {
        foreach ($orderedItems as $orderedItem)
        {
            if ($productId === $orderedItem->product()->productId())
            {
                return $orderedItem;
            }
        }

        return null;
    }

    public function isFullyFulfilled(): bool
    {
        $amountOfOrderLines = sizeof($this->orderedItems);
        $amountOfOrderLinesFullyFulfilled = 0;

        foreach ($this->orderedItems as $orderedItem)
        {
            if ($orderedItem->isFullyFulfilled())
            {
                $amountOfOrderLinesFullyFulfilled += 1;
            }
        }

        return $amountOfOrderLines === $amountOfOrderLinesFullyFulfilled;
    }

    public function orderedItemsNotYetShipped(): Collection
    {
        $result = collect();

        foreach ($this->orderedItems as $orderedItem)
        {
            if (! $orderedItem->isFullyFulfilled())
            {
                $result->push($orderedItem);
            }
        }

        return $result;
    }

    public function process()
    {
        $this->status = 'processing';
        $actionDescription = 'Status gewijzigd van Picqer - Te verwerken naar Picqer - In Behandeling';
        $action = new Action($actionDescription, CarbonImmutable::now(), "Webservices");
        $this->addAction($action);
    }

    public function reject(string $reason)
    {
        // TODO: Gebruik geen magic strings
        // Task 19156: Gebruik geen magic strings maar maak een apart object aan
        $this->status = "denied";
        $actionDescription = 'Status gewijzigd van Picqer - In behandeling naar Picqer - Afgewezen - Reden: ' . $reason;
        $action = new Action($actionDescription, CarbonImmutable::now(), "Webservices");
        $this->addAction($action);
    }

    // TODO: Factory method for creating a shipment.
    /**
     * ["ean" => "<ean>",
     *  "quantity" => "<quantity>"
     * ]
     * @param Collection $orderLines
     * @return Shipment
     */
    public function createShipment(Collection $orderLines): Shipment
    {
        throw new NotImplementedException('createShipment() function is not yet implemented');
    }

    public function changeOrderedItems(array $orderedItems)
    {
        $this->orderedItems = $orderedItems;
    }

    public function addOrderedItem(OrderedItem $orderedItem)
    {
        $this->orderedItems[] = $orderedItem;
    }

    public function addMultipleOrderedItems(array $orderedItems)
    {
        $this->orderedItems = array_merge($this->orderedItems, $orderedItems);
    }

    public function sendTrackAndTraceMailForShipment(Shipment $shipment)
    {
        try {
            $trackAndTraceMail = new TrackAndTraceMail($this->customer(), $this, $shipment, $shipment->createPackingSlip());

            $fromEmailAddress = config('mail.from.address');

            if (! $fromEmailAddress)
            {
                throw new EmailNotFoundException('Email address not specified in config');
            }

            if (! $shipment->trackAndTraceMailSent())
            {
                $shipment->sendTrackAndTraceMail($trackAndTraceMail);
            }

        } catch (\Exception $e)
        {
            Log::warning("Failed sending track and trace mail: ". $e->getMessage(), $e->getTrace());
        }
    }

    public function complete(string $packedBy): void
    {
        // TODO: Task 19508: Maak een State object waarin we de agent en bezorg optie zetten
        $this->status = "completed";
        $actionDescription = 'Status gewijzigd van Picqer - In behandeling naar Picqer - Verzonden/Afgehaald - Bestelling is ingepakt door: ' . $packedBy;

        if ($this->deliveryOption === null)
        {
            return;
        }

        if ($this->deliveryOption->isBinnenSpecialist())
        {
            $this->status = 'completed_binnen_specialist';
            $actionDescription = 'Status gewijzigd van Picqer - In behandeling naar Verf orders Binnenspecialist - Bestelling is ingepakt door: ' . $packedBy;
        }

        $action = new Action($actionDescription, CarbonImmutable::now(), "Webservices");
        $this->addAction($action);
    }

    public function completeByDelight(): void
    {
        // TODO: Task 19508: Maak een State object waarin we de agent en bezorg optie zetten
        $this->status = "completed_by_delight";

        if ($this->deliveryOption === null)
        {
            return;
        }

        if ($this->deliveryOption->isBinnenSpecialist())
        {
            $this->status = 'completed_by_delight_binnen_specialist';
        }
    }

    public function completeByPicqer(string $packedBy): void
    {
        // TODO: Task 19508: Maak een State object waarin we de agent en bezorg optie zetten
        $this->status = "completed_by_picqer";
        $actionDescription = 'Status gewijzigd van Picqer - In behandeling naar Picqer - Verzonden/Afgehaald - Bestelling is ingepakt door: ' . $packedBy;

        if ($this->deliveryOption === null)
        {
            return;
        }

        if ($this->deliveryOption->isBinnenSpecialist())
        {
            $this->status = 'completed_by_picqer_binnen_specialist';
            $actionDescription = 'Status gewijzigd van Picqer - In behandeling naar Verf orders Binnenspecialist - Bestelling is ingepakt door: ' . $packedBy;
        }

        $action = new Action($actionDescription, CarbonImmutable::now(), "Webservices");
        $this->addAction($action);
    }

    public function completed(): bool
    {
        return $this->status === 'completed_by_picqer'
                || $this->status === 'completed_by_delight'
                || $this->status == 'completed'
                || $this->status == 'completed_by_picqer_binnen_specialist'
                || $this->status == 'completed_by_delight_binnen_specialist'
                || $this->status == 'completed_binnen_specialist';
    }

    public function new(): bool
    {
        return $this->status === "new";
    }

    /**
     * @throws OrderOperationException
     */
    public function cancel(bool $discussed = false): void
    {
        if ($this->completed())
        {
            throw new OrderOperationException('Bestelling kan niet worden geannuleerd, omdat er al een zending voor is aangemaakt.');
        }

        $cancelDate = CarbonImmutable::now();

        if ($cancelDate->isSameDay($this->shippingDate()) && $discussed === false)
        {
            throw new OrderOperationException('Bestelling kan niet worden geannuleerd, omdat de verzenddatum vandaag is. Annuleren kan alleen na overleg.');
        }

        $this->status = "cancelled";
    }

    public function shippingDate(): CarbonImmutable
    {
        $daysToSubtract = $this->preferredDeliveryDate->isMonday() ? 2: 1;
        return $this->preferredDeliveryDate->subDays($daysToSubtract);
    }

    public function cancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function changeTrackAndTrace(TrackAndTrace $trackAndTrace)
    {
        $this->trackAndTrace = $trackAndTrace;
    }

    public function trackAndTrace(): ?TrackAndTrace
    {
        return $this->trackAndTrace;
    }

    public function changeCouponDiscountCodeId(int|string|null $couponDiscountCodeId)
    {
        $this->couponDiscountCodeId = $couponDiscountCodeId;
    }

    public function couponDiscountCodeId(): int|string|null
    {
        return $this->couponDiscountCodeId;
    }

    public function hasAGeneratedCoupon(): bool
    {
        return $this->couponDiscountCodeId !== null;
    }

    protected function cascadeSetIdentity(int|string $id): void
    {
        // TODO: Implement cascadeSetIdentity() method.
    }
}
