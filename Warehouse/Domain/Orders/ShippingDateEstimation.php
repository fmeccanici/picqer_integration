<?php


namespace App\Warehouse\Domain\Orders;


use App\SharedKernel\CleanArchitecture\ValueObject;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Support\Arrayable;

class ShippingDateEstimation extends valueObject
    implements Arrayable
{
    private CarbonImmutable $value;
    private CarbonImmutable $validUntil;

//    private function validate(CarbonImmutable $value, CarbonImmutable $validUntil)
//    {
//
//    }

    public function __construct(CarbonImmutable $value, CarbonImmutable $validUntil)
    {
//        $this->validate($value, $validUntil);

        $this->value = $value;
        $this->validUntil = $validUntil;
    }

    public function value(): CarbonImmutable
    {
        return $this->value;
    }

    public function validUntil(): CarbonImmutable
    {
        return $this->validUntil;
    }

    public function equalTo(ShippingDateEstimation $other): bool
    {
        return $this->value->equalTo($other->value) &&
            $this->validUntil->equalTo($other->validUntil);
    }

    public function lessThan(ShippingDateEstimation $other): bool
    {
        return $this->value->lessThan($other->value);
    }

    public function greaterThan(ShippingDateEstimation $other): bool
    {
        // TODO: Is this right ?
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
