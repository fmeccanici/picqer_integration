<?php


namespace App\Warehouse\Infrastructure\Persistence\MsSql\Orders\Mappers;

use App\Warehouse\Domain\Orders\OrderedItemFactory;
use App\Warehouse\Domain\Orders\ProductFactory;
use App\Warehouse\Domain\Picklists\Picklist;
use App\Warehouse\Domain\Picklists\SnoozePolicyInterface;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class PicklistMapper
{
    public static function toPicklist($msSqlOrder, $msSqlOrderRows)
    {
        $reference = $msSqlOrder->SalesOrderShipmentId;
        $orderReference = $msSqlOrder->SalesOrderNumber;

        if (self::isOrderSplit($reference))
        {
            $trackAndTrace = $msSqlOrder->ShipmentTrackAndTrace;
            $comments = $msSqlOrder->ShipmentRemarks;
            $status = OrderStateMapper::toEntity(StateMapper::toName($msSqlOrder->ShipmentState));
        } else {
            $trackAndTrace = $msSqlOrder->TrackAndTrace;
            $comments = $msSqlOrder->Remarks;
            $status = OrderStateMapper::toEntity(StateMapper::toName($msSqlOrder->State));
        }

        $orderedItems = collect($msSqlOrderRows)
            ->filter(static function($msSqlOrderRow) {
                return $msSqlOrderRow->ProductCode !== null;
            })
            ->map(function($msSqlOrderRow) {

                // TODO: Use first product code (should be filtered)
                $productCode = $msSqlOrderRow->ProductCode;
                $productCode = "5029496622522";

                $product = DB::connection("sitemanager")->table("view_product_or_product_option_external")
                    ->select(["*"])
                    ->where("productnumber_external", "=", $productCode)
                    ->first();

                if(!$product) {
                    return null;
                }

                $productId = $product->fsm_website_id ?? $product->id;

                $productGroupId = DB::connection("sitemanager")->table("fsm_website_product")
                    ->select(["*"])
                    ->where("id", "=", $productId)
                    ->first()
                    ->fsm_website_product_group_id;

                $productGroup = optional(DB::connection("sitemanager")->table("fsm_website_product_group_language")
                    ->select(["*"])
                    ->where("fsm_website_product_group_id", "=", $productGroupId)
                    ->first())
                    ->url_name ?? 'unknown';

                $product = ProductFactory::productWithProductGroup($productCode, $productGroup);
                $amount = (int) $msSqlOrderRow->Quantity;
                return OrderedItemFactory::orderedItem(null, $product, $amount);
            })
            ->filter();

        $snoozePolicy = App::make(SnoozePolicyInterface::class);
        return new Picklist($reference, $orderReference, $snoozePolicy, null, $trackAndTrace, $comments, $status, $orderedItems->all());
    }

    public static function toMsSqlOrder(Picklist $picklist): array
    {

    }

    public static function isOrderSplit(string $orderReference): bool
    {
        return sizeof(explode("-", $orderReference)) === 2;
    }
}
