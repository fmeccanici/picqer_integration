<?php


namespace App\Warehouse\Application\HandlePicklistCreated;

use PASVL\Validation\ValidatorBuilder;

final class HandlePicklistCreatedInput
{
    private string|int|null $picklistId;

    private function validate($order)
    {
        $pattern = [
            "picklist_id" => ":string"
        ];

        $validator = ValidatorBuilder::forArray($pattern)->build();
        $validator->validate($order);
    }

    public function __construct($input)
    {
        $this->validate($input);
        $this->picklistId = $input["picklist_id"];
    }

    public function picklistid(): string|int|null
    {
        return $this->picklistId;
    }

}
