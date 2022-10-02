<?php


namespace App\Warehouse\Domain\Picklists;


use App\SharedKernel\CleanArchitecture\Entity;
use App\Warehouse\Domain\Exceptions\PicklistOperationException;
use App\Warehouse\Domain\Orders\OrderedItem;
use Carbon\CarbonImmutable;

class Picklist extends Entity
{
    public const READY_TO_BE_PROCESSED_TAG = 'Verwerkbaar';
    protected string $reference;
    protected ?string $trackAndTrace;
    protected ?string $comments;
    protected ?string $status;
    protected ?CarbonImmutable $preferredDeliveryDate;
    protected bool $urgent = false;

    /**
     * @var array<OrderedItem>|null
     */
    protected ?array $orderedItems;
    protected string $orderReference;
    protected ?string $pickedBy;
    protected string|int|null $id;

    /**
     * @var string[]
     */
    protected array $tags;
    protected ?CarbonImmutable $snoozedUntil;
    protected SnoozePolicyInterface $snoozePolicy;

    /**
     * Picklist constructor.
     * @param string $reference
     * @param string $orderReference
     * @param SnoozePolicyInterface $snoozePolicy
     * @param int|null $id
     * @param string|null $trackAndTrace
     * @param string|null $comments
     * @param string|null $status
     * @param array|null $orderedItems
     * @param string|null $pickedBy
     * @param CarbonImmutable|null $preferredDeliveryDate
     * @param array $tags
     * @param CarbonImmutable|null $snoozedUntil
     */
    public function __construct(string $reference, string $orderReference, SnoozePolicyInterface $snoozePolicy, int|string|null $id, ?string $trackAndTrace, ?string $comments, ?string $status, ?array $orderedItems, ?string $pickedBy = null, ?CarbonImmutable $preferredDeliveryDate = null, array $tags = [], ?CarbonImmutable $snoozedUntil = null, bool $urgent = false)
    {
        $this->id = $id;
        $this->reference = $reference;
        $this->trackAndTrace = $trackAndTrace;
        $this->comments = $comments;
        $this->status = $status;
        $this->orderedItems = $orderedItems;
        $this->orderReference = $orderReference;
        $this->pickedBy = $pickedBy;
        $this->preferredDeliveryDate = $preferredDeliveryDate;
        $this->tags = $tags;
        $this->snoozedUntil = $snoozedUntil;
        $this->snoozePolicy = $snoozePolicy;
        $this->urgent = $urgent;
    }

    public function id(): int|string|null
    {
        return $this->id;
    }

    public function changeId(?int $id)
    {
        $this->id = $id;
    }

    public function reference(): string
    {
        return $this->reference;
    }

    public function changeReference(string $reference)
    {
        $this->reference = $reference;
    }

    public function orderReference(): string
    {
        return $this->orderReference;
    }

    public function trackAndTrace(): ?string
    {
        return $this->trackAndTrace;
    }

    public function changeTrackAndTrace(?string $trackAndTrace): Picklist
    {
        $this->trackAndTrace = $trackAndTrace;
        return $this;
    }

    public function snooze(): void
    {
        if ($this->preferredDeliveryDate !== null)
        {
            $this->snoozedUntil = $this->snoozePolicy->calculateSnoozeUntil($this->preferredDeliveryDate);
        }
    }

    public function comments(): ?string
    {
        return $this->comments;
    }

    public function changeComments(?string $comments): Picklist
    {
        $this->comments = $comments;
        return $this;
    }

    public function status(): ?string
    {
        return $this->status;
    }

    public function changeStatus(?string $status): Picklist
    {
        $this->status = $status;
        return $this;
    }

    public function orderedItems(): ?array
    {
        return $this->orderedItems;
    }

    public function changeOrderedItems(?array $orderedItems)
    {
        $this->orderedItems = $orderedItems;
    }

    public function pickedBy(): ?string
    {
        return $this->pickedBy;
    }

    public function changePickedBy(?string $pickedBy)
    {
        $this->pickedBy = $pickedBy;
    }

    public function preferredDeliveryDate(): ?CarbonImmutable
    {
        return $this->preferredDeliveryDate;
    }

    /**
     * @throws PicklistOperationException
     */
    public function changePreferredDeliveryDate(?CarbonImmutable $preferredDeliveryDate, bool $discussedWithLogisticsDepartment = false)
    {
        if ($this->snoozedUntil && $preferredDeliveryDate && $this->snoozedUntil->isSameDay($preferredDeliveryDate) && ! $discussedWithLogisticsDepartment)
        {
            throw new PicklistOperationException('Kan bezorgdatum niet wijzigen, omdat de picklijst is gesnoozed tot vandaag. Overleg met logistiek om de wijzigin alsnog door te voeren.');
        }

        $this->preferredDeliveryDate = $preferredDeliveryDate;
    }

    public function hasOrderedItem(string $productCode): bool
    {
        foreach ($this->orderedItems as $orderedItem)
        {
            if ($orderedItem->product()->productId() == $productCode)
            {
                return true;
            }
        }

        return false;
    }

    public function orderedItem(string $productCode): ?OrderedItem
    {
        foreach ($this->orderedItems as $orderedItem)
        {
            if ($orderedItem->product()->productId() == $productCode)
            {
                return $orderedItem;
            }
        }

        return null;
    }

    public function tags(): array
    {
        return $this->tags;
    }

    public function makeUrgent(): void
    {
        $this->urgent = true;
    }

    public function urgent(): bool
    {
        return $this->urgent;
    }

    public function changeTags(array $tags)
    {
        $this->tags = $tags;
    }

    public function addTag(string $tag)
    {
        $this->tags[] = $tag;
    }

    public static function fromArray(array $pickList): Picklist
    {
        return new Picklist(null, $pickList["reference"], $pickList["order_reference"], $pickList["track_and_trace"], $pickList["comments"], $pickList["status"], null);
    }

    public function snoozeUntil(CarbonImmutable $snoozedUntil)
    {
        $this->snoozedUntil = $snoozedUntil;
    }

    public function snoozedUntil(): ?CarbonImmutable
    {
        return $this->snoozedUntil;
    }

    public function unsnooze()
    {
        $this->snoozedUntil = null;
    }

    public function isSnoozed(): bool
    {
        return $this->snoozedUntil !== null;
    }

    public function toArray(): array
    {
        $orderedItemsArray = array_map(function(OrderedItem $orderedItem) {
            return $orderedItem->toArray();
        }, $this->orderedItems);

        return [
            "reference" => $this->reference,
            "order_reference" => $this->orderReference,
            "ordered_items" => $orderedItemsArray,
            "track_and_trace" => $this->trackAndTrace,
            "status" => $this->status,
            "comments" => $this->comments,
            "snoozed_until" => $this->snoozedUntil
        ];
    }

    protected function cascadeSetIdentity(int|string $id): void
    {
        // TODO: Implement cascadeSetIdentity() method.
    }
}
