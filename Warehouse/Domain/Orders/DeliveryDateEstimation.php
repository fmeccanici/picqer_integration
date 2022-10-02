<?php


namespace App\Warehouse\Domain\Orders;


use Carbon\CarbonImmutable;

class DeliveryDateEstimation
{
    private CarbonImmutable $value;
    private CarbonImmutable $validUntil;

    private function validate(CarbonImmutable $value, CarbonImmutable $validUntil)
    {

    }

    public function __construct(CarbonImmutable $value, CarbonImmutable $validUntil)
    {
        //$this->validate($value, $validUntil);

        $this->value = $value;
        $this->validUntil = $validUntil;
    }

    public function deliveryDate(): CarbonImmutable
    {
        return $this->value;
    }

    public function validUntil(): CarbonImmutable
    {
        return $this->validUntil;
    }

    public function equalTo(DeliveryDateEstimation $other): bool
    {
        return $this->value->equalTo($other->value) &&
            $this->validUntil->equalTo($other->validUntil);
    }

    public function lessThan(DeliveryDateEstimation $other): bool
    {
        return $this->value->lessThan($other->value);
    }

    public function greaterThan(DeliveryDateEstimation $other): bool
    {
        return $this->value->greaterThan($other->value);
    }

    public function toArray(): array
    {
        // All DateTime objects should be serialized with RFC3339 format
        return [
            "value" => $this->value->toRfc3339String(),
            "valid_until" => $this->validUntil->toRfc3339String()
        ];
    }
}
