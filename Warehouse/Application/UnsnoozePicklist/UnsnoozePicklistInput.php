<?php


namespace App\Warehouse\Application\UnsnoozePicklist;

use PASVL\Validation\ValidatorBuilder;

final class UnsnoozePicklistInput
{
    private $picklistReference;

    private function validate($input)
    {
        $pattern = [
            "picklist_reference" => ":string",
        ];

        $validator = ValidatorBuilder::forArray($pattern)->build();
        $validator->validate($input);
    }

    public function __construct($input)
    {
        $this->validate($input);
        $this->picklistReference = $input["picklist_reference"];
    }

    public function picklistReference(): string
    {
        return $this->picklistReference;
    }

}
