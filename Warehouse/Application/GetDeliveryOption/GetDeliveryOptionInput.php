<?php


namespace App\Warehouse\Application\GetDeliveryOption;

use PASVL\Validation\ValidatorBuilder;

final class GetDeliveryOptionInput
{
    private $carrierName;
    private $deliveryCountry;
    private $deliveryOptionName;

    private function validate($order)
    {
        $pattern = [
            "delivery_country" => ":string",
            "delivery_option_name" => ":string",
            "carrier_name" => ":string?"
        ];

        $validator = ValidatorBuilder::forArray($pattern)->build();
        $validator->validate($order);
    }

    public function __construct($input)
    {
        $this->validate($input);
        $this->deliveryCountry = $input["delivery_country"];
        $this->deliveryOptionName = $input["delivery_option_name"];
        $this->carrierName = $input["carrier_name"];
    }

    public function deliveryCountry(): string
    {
        return $this->deliveryCountry;
    }

    public function deliveryOptionName(): string
    {
        return $this->deliveryOptionName;
    }

    public function carrierName(): ?string
    {
        return $this->carrierName;
    }
}
