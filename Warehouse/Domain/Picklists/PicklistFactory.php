<?php


namespace App\Warehouse\Domain\Picklists;


use Illuminate\Support\Facades\App;

class PicklistFactory
{
    public static function dummy()
    {
        $snoozePolicy = App::make(SnoozePolicyInterface::class);
        return new Picklist("1234", "O1234", $snoozePolicy,"1234", null, null, null, []);
    }
}
