<?php

namespace App\Warehouse\Infrastructure\Persistence\MsSql\Shipments\Mappers;

use App\Warehouse\Domain\Exporters\PackingSlipGeneratorInterface;
use App\Warehouse\Domain\Mails\MailerServiceInterface;
use App\Warehouse\Domain\Orders\OrderedItemFactory;
use App\Warehouse\Domain\Orders\ProductFactory;
use App\Warehouse\Domain\Shipments\Shipment;
use App\Warehouse\Domain\Shipments\TrackAndTrace;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class MsSqlShipmentMapper extends Shipment
{
    public static function toShipment($msSqlOrder, $msSqlOrderRows)
    {
        $reference = $msSqlOrder->SalesOrderShipmentId;

        if (self::isOrderSplit($reference))
        {
            $orderReference = $msSqlOrder->SalesOrderNumber . '-' . $msSqlOrder->ShipmentNumber;
            $trackAndTrace = new TrackAndTrace($msSqlOrder->ShipmentTrackAndTrace, null);
            $comments = $msSqlOrder->ShipmentRemarks;
            $preferredDeliveryDateString = $msSqlOrder->ShipmentActualDeliveryDate;
            $carrierName = $msSqlOrder->ShipmentDeliveryCompany;
            $deliveryOptionName = $msSqlOrder->ShipmentDeliveryService;
            $doNotSendReviewInvitation = $msSqlOrder->ShipmentNoReview;
        } else {
            $orderReference = $msSqlOrder->SalesOrderNumber;
            $trackAndTrace = new TrackAndTrace($msSqlOrder->TrackAndTrace, null);
            $comments = $msSqlOrder->Remarks;
            $preferredDeliveryDateString = $msSqlOrder->ActualDeliveryDate;
            $carrierName = $msSqlOrder->DeliveryCompany;
            $deliveryOptionName = $msSqlOrder->DeliveryService;
            $doNotSendReviewInvitation = $msSqlOrder->NoReview;
        }

        $deliveryCountry = $msSqlOrder->DeliveryCountry;

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

        $packingSlipGenerator = App::make(PackingSlipGeneratorInterface::class);
        $mailerService = App::make(MailerServiceInterface::class);

        if ($preferredDeliveryDateString === null)
        {
            $deliveryDate = null;
        } else {
            $deliveryDate = CarbonImmutable::parse($preferredDeliveryDateString);
        }

        return new Shipment($reference, $orderReference, $trackAndTrace, $deliveryOptionName, $carrierName, $orderedItems, null, $packingSlipGenerator, $mailerService, false, $deliveryDate, $comments, $doNotSendReviewInvitation);
    }

    public static function isOrderSplit(string $orderReference): bool
    {
        return sizeof(explode("-", $orderReference)) === 2;
    }
}
