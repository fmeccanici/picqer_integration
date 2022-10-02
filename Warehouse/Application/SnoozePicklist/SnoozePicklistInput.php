<?php


namespace App\Warehouse\Application\SnoozePicklist;

use PASVL\Validation\ValidatorBuilder;

final class SnoozePicklistInput
{
    private string $picklistId;
    private string $snoozeUntil;

    private function validate($input)
    {
        $pattern = [
            "picklist_id" => ":string",

            // m-d-Y
            "snooze_until" => ":string"
        ];

        $validator = ValidatorBuilder::forArray($pattern)->build();
        $validator->validate($input);
    }

    public function __construct($input)
    {
        $this->validate($input);
        $this->picklistId = $input["picklist_id"];
        $this->snoozeUntil = $input["snooze_until"];
    }

    public function picklistId(): string
    {
        return $this->picklistId;
    }

    public function snoozeUntil(): string
    {
        return $this->snoozeUntil;
    }
}
