<?php


namespace App\Warehouse\Infrastructure\Persistence\Picqer\Picklists\Mappers;


use App\Warehouse\Domain\Picklists\Picklist;
use App\Warehouse\Domain\Picklists\SnoozePolicyInterface;
use App\Warehouse\Infrastructure\Exceptions\PicqerPicklistMapperException;
use App\Warehouse\Infrastructure\Persistence\Picqer\Orders\Mappers\OrderedItemMapper;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\App;

class PicklistMapper
{
    public static function toEntity(array $picqerPicklist, array $picqerOrder): Picklist
    {
        $idPicklist = $picqerPicklist["idpicklist"];
        $picklistId = $picqerPicklist["picklistid"];

        $pickedById = $picqerPicklist["closed_by_iduser"];

        $comments = null;
        $trackAndTrace = null;

        $orderReference = $picqerOrder["reference"];

        if ($orderReference === null)
        {
            throw new PicqerPicklistMapperException("Order reference is not specified");
        }

        $status = $picqerPicklist["status"];

        $orderedItems = [];

        foreach ($picqerPicklist["products"] as $product)
        {
            $orderedItems[] = OrderedItemMapper::toEntity($product);
        }

        $preferredDeliveryDateString = $picqerPicklist["preferred_delivery_date"];

        if ($preferredDeliveryDateString === null)
        {
            $preferredDeliveryDate = null;
        } else {
            $preferredDeliveryDate = CarbonImmutable::createFromFormat(config('picqer.datetime_format'), $picqerPicklist["preferred_delivery_date"]);
        }

        $snoozedUntil = $picqerPicklist['snoozed_until'];

        if ($snoozedUntil !== null)
        {
            $snoozedUntil = CarbonImmutable::parse($snoozedUntil);
        }

        $tags = [];

        $snoozePolicy = App::make(SnoozePolicyInterface::class);
        return new Picklist($picklistId, $orderReference, $snoozePolicy, $idPicklist, $trackAndTrace, $comments, $status, $orderedItems, $pickedById, $preferredDeliveryDate, $tags, $snoozedUntil);
    }

    public static function toPicqer(Picklist $picklist): array
    {

    }
}
