<?php


namespace App\Warehouse\Domain\Orders;


use App\SharedKernel\CleanArchitecture\Entity;
use Illuminate\Contracts\Support\Arrayable;

class Product extends Entity
    implements Arrayable
{
    private int $websiteId;
    private int|string $productId;
    private ?int $productOptionId;
    private ?string $productGroup;
    private ?string $name;
    private ?string $description;
    private ?float $sellingPrice;
    private ?float $purchasePrice;
    protected ?float $length;

//    private function validate(int $websiteId, int $productId, ?int $productOptionId)
//    {
//
//    }

    public function __construct(int $websiteId, int|string $productId, ?int $productOptionId, ?float $length = null, ?string $productGroup = null, ?string $name = null, ?string $description = null,
                                ?float $sellingPrice = null, ?float $purchasePrice = null)
    {
//        $this->validate($websiteId, $productId, $productOptionId, $propertyIds);

        $this->websiteId = $websiteId;
        $this->productId = $productId;
        $this->productOptionId = $productOptionId;
        $this->productGroup = $productGroup;
        $this->length = $length;
        $this->name = $name;
        $this->description = $description;
        $this->purchasePrice = $purchasePrice;
        $this->sellingPrice = $sellingPrice;
    }

    public function productGroup(): ?string
    {
        return $this->productGroup;
    }

    public function websiteId(): int
    {
        return $this->websiteId;
    }

    public function productId(): int|string
    {
        return $this->productId;
    }

    public function length(): ?float
    {
        return $this->length;
    }

    public function productOptionId(): ?int
    {
        return $this->productOptionId;
    }

    public function name(): ?string
    {
        return $this->name;
    }

    public function changeName(?string $name)
    {
        $this->name = $name;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function changeDescription(?string $description)
    {
        $this->description = $description;
    }

    public function sellingPrice(): ?float
    {
        return $this->sellingPrice;
    }

    public function changeSellingPrice(?float $sellingPrice)
    {
        $this->sellingPrice = $sellingPrice;
    }

    public function purchasePrice(): ?float
    {
        return $this->purchasePrice;
    }

    public function changePurchasePrice(?float $purchasePrice)
    {
        $this->purchasePrice = $purchasePrice;
    }

    public function equalTo(Product $other): bool
    {
        return $this->websiteId === $other->websiteId &&
            $this->productId === $other->productId &&
            $this->productOptionId === $other->productOptionId;
    }

    public function toArray(): array
    {
        return [
            "website_id" => $this->websiteId,
            "product_id" => $this->productId,
            "product_option_id" => $this->productOptionId
        ];
    }

    protected function cascadeSetIdentity(int|string $id): void
    {
        // TODO: Implement cascadeSetIdentity() method.
    }
}
