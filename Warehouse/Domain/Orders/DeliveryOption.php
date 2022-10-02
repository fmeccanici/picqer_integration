<?php


namespace App\Warehouse\Domain\Orders;


use App\SharedKernel\CleanArchitecture\ValueObject;
use Illuminate\Contracts\Support\Arrayable;

class DeliveryOption extends ValueObject implements Arrayable
{
    private string $name;
    private ?int $productCode;
    private ?int $characteristic;
    private ?int $option;
    private ?string $carrier;
    private ?string $locationCode;
    private ?string $retailNetworkId;

    /**
     * DeliveryOption constructor.
     * @param string|null $carrier
     * @param string $name
     * @param int|null $productCode
     * @param int|null $characteristic
     * @param int|null $option
     * @param string|null $locationCode
     * @param string|null $retailNetworkId
     */
    public function __construct(?string $carrier, string $name, ?int $productCode, ?int $characteristic, ?int $option, ?string $locationCode = null, ?string $retailNetworkId = null)
    {
        $this->carrier = $carrier;
        $this->name = $name;
        $this->productCode = $productCode;
        $this->characteristic = $characteristic;
        $this->option = $option;
        $this->locationCode = $locationCode;
        $this->retailNetworkId = $retailNetworkId;
    }

    public function carrier(): ?string
    {
        return $this->carrier;
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return int|null
     */
    public function productCode(): ?int
    {
        return $this->productCode;
    }

    /**
     * @return int|null
     */
    public function characteristic(): ?int
    {
        return $this->characteristic;
    }

    /**
     * @return int|null
     */
    public function option(): ?int
    {
        return $this->option;
    }

    public function locationCode(): ?string
    {
        return $this->locationCode;
    }

    public function retailNetworkId(): ?string
    {
        return $this->retailNetworkId;
    }

    // TODO: Add country to attribute of this class
    // TODO: Move PickupPointBelgium and PickupPointNetherlands to seperate class, so we don't have magic numbers and don't violate open/closed: see Task 18716: Verplaats PickupPointBelgium en PickupPointNetherlands naar eigen class
    public function isPickupLocationBelgium(): bool
    {
        return $this->productCode === 4936;
    }

    public function isPickupLocationNetherlands(): bool
    {
        return $this->productCode === 3533 && $this->name == 'PostNL - Afhaalpunt';
    }

    public function isBinnenSpecialist(): bool
    {
        return $this->name == 'Showroom - Binnenspecialist';
    }

    // TODO: Task 18825: Refactor DeliveryOption product codes naar een aparte PostNLProductCodes class met statische variabelen (product codes, characteristic en option) en link naar documentatie
    public function isEveningDelivery(): bool
    {
        return $this->productCode === 3085 && $this->characteristic === 118 && $this->option === 006;
    }

    public function toArray()
    {
        return [
            "carrier" => $this->carrier,
            "name" => $this->name,
            "product_code" => $this->productCode,
            "characteristic" => $this->characteristic,
            "option" => $this->option
        ];
    }
}
