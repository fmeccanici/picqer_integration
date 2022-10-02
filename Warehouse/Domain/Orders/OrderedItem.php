<?php


namespace App\Warehouse\Domain\Orders;


use App\SharedKernel\CleanArchitecture\Entity;
use App\Warehouse\Domain\Exceptions\OrderedItemValidationException;
use App\Warehouse\Domain\Shipments\Shipment;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;

class OrderedItem extends Entity
    implements Arrayable
{
    private Product $product;
    private ?array $propertyIds;
    private string $availability;
    private ?int $deliveryLevel;
    private ?ShippingDateEstimation $shippingDateEstimation;
    private int $amount;
    private ?string $orderReference;
    private ?string $picklistId;
    private ?string $description;
    protected ?string $pickingContainer;

    /**
     * @var Shipment[]
     */
    private array $shipments;

    private function validate(
        Product $product,
        string $availability,
        ?array $propertyIds,
        ?int $deliveryLevel,
        ?ShippingDateEstimation $shippingDateEstimation)
    {
        if ($availability !== "OnStock" && $availability !== "NotOnStock")
        {
            throw new OrderedItemValidationException("availability must be equal to OnStock or to NotOnStock");
        }

        if (isset($deliveryLevel) && !collect([0, 1, 2, 3, 4, 5])->contains($deliveryLevel))
        {
            throw new OrderedItemValidationException("deliveryLevel must be in [0, 1, 2, 3, 4, 5]");
        }
    }

    public function __construct(
        Product $product,
        string $availability,
        ?array $propertyIds,
        ?int $deliveryLevel,
        ?ShippingDateEstimation $shippingDateEstimation,
        ?string $orderReference = null,
        ?string $picklistId = null,
        array $shipments = [],
        ?string $description = null,
        ?string $pickingContainer = null)
    {
        $this->validate(
            $product,
            $availability,
            $propertyIds,
            $deliveryLevel,
            $shippingDateEstimation
        );

        $this->product = $product;
        $this->propertyIds = $propertyIds;
        $this->availability = $availability;
        $this->deliveryLevel = $deliveryLevel;
        $this->shippingDateEstimation = $shippingDateEstimation;
        $this->orderReference = $orderReference;
        $this->picklistId = $picklistId;
        $this->shipments = $shipments;
        $this->description = $description;
        $this->pickingContainer = $pickingContainer;
    }

    public function orderReference(): ?string
    {
        return $this->orderReference;
    }

    public function picklistId(): ?string
    {
        return $this->picklistId;
    }

    public function changePicklistId(?string $picklistId)
    {
        $this->picklistId = $picklistId;
    }

    public function shipWith(Shipment $shipment)
    {
        if ($this->shipment($shipment->reference()) === null)
        {
            $this->shipments[] = $shipment;
        }
    }

    public function changePickingContainer(string $pickingContainer)
    {
        $this->pickingContainer = $pickingContainer;
    }

    public function pickingContainer(): ?string
    {
        return $this->pickingContainer;
    }

    public function shipment(string $shipmentReference): ?Shipment
    {
        foreach ($this->shipments as $shipment)
        {
            if ($shipment->reference() === $shipmentReference)
            {
                return $shipment;
            }
        }

        return null;
    }

    public function isFullyFulfilled(): bool
    {
        $quantityFulfilled = 0;

        foreach ($this->shipments as $shipment)
        {
            $quantityFulfilled += $shipment->orderedItem($this->product->productId())->amount();
        }

        return $this->amount === $quantityFulfilled;
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function changeAmount(int $amount)
    {
        $this->amount = $amount;
    }

    public function product(): Product
    {
        return $this->product;
    }

    public function propertyIds(): ?array
    {
        return $this->propertyIds;
    }

    public function productOptions(): array
    {
        return array_filter(
                array_merge(
                    Arr::wrap($this->product()->productOptionId()),
                    Arr::wrap($this->propertyIds)
                ));
    }

    public function availability(): string
    {
        return $this->availability;
    }

    public function deliveryLevel(): ?int
    {
        return $this->deliveryLevel;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function changeDescription(?string $description)
    {
        $this->description = $description;
    }

    public function shippingDateEstimation(): ?ShippingDateEstimation
    {
        return $this->shippingDateEstimation;
    }

    public function changeShippingDateEstimation(?ShippingDateEstimation $shippingDateEstimation)
    {
        $this->shippingDateEstimation = $shippingDateEstimation;
    }

    public function hasShippingDate(): bool
    {
        return isset($this->shippingDateEstimation);
    }

    public function onStock(): bool
    {
        return $this->availability === "OnStock";
    }

    public function equalTo(OrderedItem $other): bool
    {
        return $this->product->equalTo($other->product) &&
            IntArrayEqualityComparer::equals($this->propertyIds, $other->propertyIds) &&
            $this->availability === $other->availability &&
            $this->deliveryLevel === $other->deliveryLevel &&
            ShippingDateEstimationEqualityComparer::equals($this->shippingDateEstimation, $other->shippingDateEstimation);
    }

    public function toArray(): array
    {
        return [
            "product_id" => (string) $this->product->productId(),
            "product_amount" => $this->amount,
            "product_group" => $this->product->productGroup(),
            "shipping_date_estimation" => optional($this->shippingDateEstimation)->toArray(),
            'picklist_id' => $this->picklistId
        ];
    }

    protected function cascadeSetIdentity(int|string $id): void
    {
        // TODO: Implement cascadeSetIdentity() method.
    }
}
