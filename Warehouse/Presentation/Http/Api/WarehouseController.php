<?php


namespace App\Warehouse\Presentation\Http\Api;


use App\PostNL\Classes\PostNL;
use App\ProductCatalog\Infrastructure\Repositories\CatalogRepository;
use App\Warehouse\Application\CancelOrder\CancelOrder;
use App\Warehouse\Application\CancelOrder\CancelOrderInput;
use App\Warehouse\Application\CancelOrderAfterDiscussing\CancelOrderAfterDiscussing;
use App\Warehouse\Application\CancelOrderAfterDiscussing\CancelOrderAfterDiscussingInput;
use App\Warehouse\Application\ChangeDeliveryOption\ChangeDeliveryOption;
use App\Warehouse\Application\ChangeDeliveryOption\ChangeDeliveryOptionInput;
use App\Warehouse\Application\ChangeOrderDeliveryDate\ChangeOrderDeliveryDate;
use App\Warehouse\Application\ChangeOrderDeliveryDate\ChangeOrderDeliveryDateInput;
use App\Warehouse\Application\ChangeOrderDeliveryDateAfterDiscussing\ChangeOrderDeliveryDateAfterDiscussing;
use App\Warehouse\Application\ChangeOrderDeliveryDateAfterDiscussing\ChangeOrderDeliveryDateAfterDiscussingInput;
use App\Warehouse\Application\ChangePicklistComments\ChangePicklistComments;
use App\Warehouse\Application\ChangePicklistComments\ChangePicklistCommentsInput;
use App\Warehouse\Application\DelayBackorder\DelayBackorder;
use App\Warehouse\Application\DelayBackorder\DelayBackorderInput;
use App\Warehouse\Application\EstimateDeliveryDate\EstimateDeliveryDate;
use App\Warehouse\Application\EstimateDeliveryDate\EstimateDeliveryDateInput;
use App\Warehouse\Application\EstimateShippingDate\EstimateShippingDate;
use App\Warehouse\Application\EstimateShippingDate\EstimateShippingDateInput;
use App\Warehouse\Application\GenerateSawlistFromBatchPicklist\GenerateSawlistFromBatchPicklist;
use App\Warehouse\Application\GenerateSawlistFromBatchPicklist\GenerateSawlistFromBatchPicklistInput;
use App\Warehouse\Application\GetDeliveryOption\GetDeliveryOption;
use App\Warehouse\Application\GetDeliveryOption\GetDeliveryOptionInput;
use App\Warehouse\Application\GetOrderFromPicklist\GetOrderFromPicklist;
use App\Warehouse\Application\GetOrderFromPicklist\GetOrderFromPicklistInput;
use App\Warehouse\Application\GetProductItemsPerLevel\GetProductItemsPerLevel;
use App\Warehouse\Application\GetProductItemsPerLevel\GetProductItemsPerLevelInput;
use App\Warehouse\Application\ListBackorders\ListBackorders;
use App\Warehouse\Application\ListBackorders\ListBackordersInput;
use App\Warehouse\Application\ListDelayBackorderReasons\ListDelayBackorderReasons;
use App\Warehouse\Application\ListDelayBackorderReasons\ListDelayBackorderReasonsInput;
use App\Warehouse\Application\NotifyCustomerOfFullyShippedOrder\NotifyCustomerOfFullyShippedOrder;
use App\Warehouse\Application\NotifyCustomerOfFullyShippedOrder\NotifyCustomerOfFullyShippedOrderInput;
use App\Warehouse\Application\SearchPicklist\SearchPicklist;
use App\Warehouse\Application\SearchPicklist\SearchPicklistInput;
use App\Warehouse\Application\SnoozePicklist\SnoozePicklist;
use App\Warehouse\Application\SnoozePicklist\SnoozePicklistInput;
use App\Warehouse\Application\UnsnoozePicklist\UnsnoozePicklist;
use App\Warehouse\Application\UnsnoozePicklist\UnsnoozePicklistInput;
use App\Warehouse\Application\UpdateOrderComments\UpdateOrderComments;
use App\Warehouse\Application\UpdateOrderComments\UpdateOrderCommentsInput;
use App\Warehouse\Domain\Mails\MailerServiceInterface;
use App\Warehouse\Domain\Repositories\BackorderActionRepositoryInterface;
use App\Warehouse\Domain\Repositories\BackorderRepositoryInterface;
use App\Warehouse\Domain\Repositories\BatchPicklistRepositoryInterface;
use App\Warehouse\Domain\Repositories\CustomerRepositoryInterface;
use App\Warehouse\Domain\Repositories\OrderRepositoryInterface;
use App\Warehouse\Domain\Repositories\PicklistRepositoryInterface;
use App\Warehouse\Domain\Repositories\ShipmentRepositoryInterface;
use App\Warehouse\Domain\Services\DeliveryOptionServiceInterface;
use App\Warehouse\Domain\Services\OrderProcessorServiceInterface;
use App\Warehouse\Domain\Services\ResourcePlanningServiceInterface;
use App\Warehouse\Domain\Services\ShippingServiceInterface;
use App\Warehouse\Domain\Services\WarehouseServiceInterface;
use App\Warehouse\Infrastructure\Exporters\PicqerPackingSlipGenerator;
use App\Warehouse\Infrastructure\Jobs\HandleShipmentCreatedJob;
use App\Warehouse\Infrastructure\Jobs\TransferNewOrdersToPicqerJob;
use App\Warehouse\Infrastructure\Persistence\Eloquent\Repositories\EloquentOrderFlowRepository;
use App\Warehouse\Infrastructure\Persistence\MsSql\Repositories\MsSqlOrderRepository;
use App\Warehouse\Infrastructure\Persistence\MsSql\Repositories\MsSqlShipmentRepository;
use App\Warehouse\Infrastructure\Persistence\Picqer\Repositories\PicqerBackorderRepository;
use App\Warehouse\Infrastructure\Services\ShippingService;
use App\Warehouse\Infrastructure\Webhooks\WebhookManager;
use Exception;
use http\Exception\InvalidArgumentException;

class WarehouseController
{
    protected OrderRepositoryInterface $orderRepository;
    protected ResourcePlanningServiceInterface $resourcePlanningService;
    protected PicklistRepositoryInterface $picklistRepository;
    protected ShipmentRepositoryInterface $shipmentRepository;

    public function __construct()
    {
        $this->orderRepository = App::make(OrderRepositoryInterface::class);
        $this->resourcePlanningService = App::make(ResourcePlanningServiceInterface::class);
        $this->picklistRepository = App::make(PicklistRepositoryInterface::class);
        $this->shipmentRepository = App::make(ShipmentRepositoryInterface::class);
    }

    public function snoozePicklist(Request $request)
    {
        try
        {
            $useCase = new SnoozePicklist(App::make(PicklistRepositoryInterface::class));
            $input = new SnoozePicklistInput([
                "picklist_id" => $request->input("picklist_id"),
                "snooze_until" => $request->input("snooze_until")
            ]);

            $result = $useCase->execute($input);

            $response["meta"]["created_at"] = time();
            $response["payload"] = $result->picklist()->toArray();

            return $response;
        }
        catch (Exception $e)
        {
            $response["meta"]["created_at"] = time();
            $response["error"]["code"] = $e->getCode();
            $response["error"]["message"] = $e->getMessage();

            return $response;
        }
    }

    public function unsnoozePicklist(Request $request)
    {
        try
        {
            $useCase = new UnsnoozePicklist(App::make(PicklistRepositoryInterface::class));
            $input = new UnsnoozePicklistInput([
                "picklist_reference" => $request->input("picklist_reference"),
            ]);

            $result = $useCase->execute($input);

            $response["meta"]["created_at"] = time();
            $response["payload"] = $result->picklist()->toArray();

            return $response;
        }
        catch (Exception $e)
        {
            $response["meta"]["created_at"] = time();
            $response["error"]["code"] = $e->getCode();
            $response["error"]["message"] = $e->getMessage();

            return $response;
        }
    }

    public function transferNewOrdersToPicqer(Request $request)
    {
        try
        {
            $source = $request->input('from');
            $destination = 'picqer';

            $sourceOrderRepository = App::make(OrderRepositoryInterface::class, ['name' => $source]);
            $destinationOrderRepository = App::make(OrderRepositoryInterface::class, ['name' => $destination]);
            $customerRepository = App::make(CustomerRepositoryInterface::class);
            $orderProcessor = App::make(OrderProcessorServiceInterface::class);
            $warehouseService = App::make(WarehouseServiceInterface::class);
            $picqerBackorderRepository = App::make(PicqerBackorderRepository::class);

            TransferNewOrdersToPicqerJob::dispatchSync($sourceOrderRepository, $destinationOrderRepository, $customerRepository,
                                                        $orderProcessor, $warehouseService, $picqerBackorderRepository);

            $response["meta"]["created_at"] = time();

            return $response;
        }
        catch (Exception $e)
        {
            $response["meta"]["created_at"] = time();
            $response["error"]["code"] = $e->getCode();
            $response["error"]["message"] = $e->getMessage() . ' at: ' . $e->getTraceAsString();

            Log::error($e->getMessage().$e->getTraceAsString());

            return $response;
        }
    }

    public function changeDeliveryOption()
    {
        try {
            $orderRepository = App::make(OrderRepositoryInterface::class, [
                "name" => "picqer"
            ]);

            $shipmentRepository = App::make(ShipmentRepositoryInterface::class);

            $deliveryOption = request("delivery_option");
            $orderReference = request("order_reference");
            $deliveryAddress = request("delivery_address");

            $changeDeliveryOptionOfOrder = new ChangeDeliveryOption($orderRepository, $shipmentRepository);
            $changeDeliveryOptionOfOrderInput = new ChangeDeliveryOptionInput([
                "delivery_option" => $deliveryOption,
                "order_reference" => $orderReference,
                "delivery_address" => $deliveryAddress
            ]);

            $changeDeliveryOptionOfOrderResult = $changeDeliveryOptionOfOrder->execute($changeDeliveryOptionOfOrderInput);
            $response["meta"] = time();
            $response["data"] = $changeDeliveryOptionOfOrderResult->order()->deliveryOption()->toArray();

        } catch (Exception $e)
        {
            $response["meta"] = time();
            $response["error"]["code"] = 500;
            $response["error"]["message"] = $e->getMessage();
        }

        return $response;

    }

    public function getDeliveryOption(Request $request, DeliveryOptionServiceInterface $deliveryOptionService)
    {
        try {
            $input = $request->all();

            $country = $input["delivery_country"];
            $deliveryOptionName = $input["delivery_option_name"];
            $carrierName = $input["carrier_name"];

            $useCaseInput = new GetDeliveryOptionInput([
                "delivery_country" => $country,
                "delivery_option_name" => $deliveryOptionName,
                "carrier_name" => $carrierName
            ]);

            $useCase = new GetDeliveryOption($deliveryOptionService);

            $result = $useCase->execute($useCaseInput);
            $deliveryOption = $result->deliveryOption();

            $response["meta"]["created_at"] = time();
            $response["payload"]["delivery_option"] = $deliveryOption->toArray();

        } catch (\Exception $e)
        {
            $response["meta"]["created_at"] = time();
            $response["error"]["code"] = $e->getCode();
            $response["error"]["message"] = $e->getMessage();
        }

        return $response;

    }

    public function cancelOrder(Request $request, string $orderReference)
    {
        try {
            $orderRepository = App::make(OrderRepositoryInterface::class, [
                'name' => 'picqer'
            ]);

            $cancelOrder = new CancelOrder($orderRepository);
            $cancelOrderInput = new CancelOrderInput([
                'order_reference' => $orderReference,
            ]);

            $result = $cancelOrder->execute($cancelOrderInput);
            $response['data'] = $result->order()->toArray();
            $response['success'] = true;
        } catch (Exception|\Error $e)
        {
            $response['error'] = $e->getMessage();
            $response['success'] = false;
            $response['data'] = null;
        }

        return $response;
    }

    public function cancelOrderAfterDiscussing(Request $request, string $orderReference)
    {
        try {
            $orderRepository = App::make(OrderRepositoryInterface::class, [
                'name' => 'picqer'
            ]);

            $cancelOrder = new CancelOrderAfterDiscussing($orderRepository);
            $cancelOrderInput = new CancelOrderAfterDiscussingInput([
                'order_reference' => $orderReference,
            ]);

            $result = $cancelOrder->execute($cancelOrderInput);
            $response['data'] = $result->order()->toArray();
            $response['success'] = true;
        } catch (Exception|\Error $e)
        {
            $response['error'] = $e->getMessage();
            $response['success'] = false;
            $response['data'] = null;
        }

        return $response;
    }

    public function changeOrderDeliveryDate(Request $request, string $orderReference)
    {
        try {
            $orderRepository = App::make(OrderRepositoryInterface::class, [
                'name' => 'picqer'
            ]);

            $picklistRepository = App::make(PicklistRepositoryInterface::class, [
                'name' => 'picqer'
            ]);

            $deliveryDate = Arr::get($request, 'delivery_date');

            if (! $deliveryDate)
            {
                throw new InvalidArgumentException('Delivery date should be specified');
            }

            $changeOrderDeliveryDate = new ChangeOrderDeliveryDate($orderRepository, $picklistRepository);
            $changeOrderDeliveryDateInput = new ChangeOrderDeliveryDateInput([
                'order_reference' => $orderReference,
                'delivery_date' => $deliveryDate
            ]);

            $result = $changeOrderDeliveryDate->execute($changeOrderDeliveryDateInput);
            $response['data'] = $result->order()->toArray();
            $response['success'] = true;
        } catch (Exception|\Error $e)
        {
            $response['error'] = $e->getMessage();
            $response['success'] = false;
            $response['data'] = null;
        }

        return $response;
    }

    public function changeOrderDeliveryDateAfterDiscussing(Request $request, string $orderReference)
    {
        try {
            $orderRepository = App::make(OrderRepositoryInterface::class, [
                'name' => 'picqer'
            ]);

            $picklistRepository = App::make(PicklistRepositoryInterface::class, [
                'name' => 'picqer'
            ]);

            $deliveryDate = Arr::get($request, 'delivery_date');

            if (! $deliveryDate)
            {
                throw new InvalidArgumentException('Delivery date should be specified');
            }

            $changeOrderDeliveryDate = new ChangeOrderDeliveryDateAfterDiscussing($orderRepository, $picklistRepository);
            $changeOrderDeliveryDateInput = new ChangeOrderDeliveryDateAfterDiscussingInput([
                'order_reference' => $orderReference,
                'delivery_date' => $deliveryDate
            ]);

            $result = $changeOrderDeliveryDate->execute($changeOrderDeliveryDateInput);
            $response['data'] = $result->order()->toArray();
            $response['success'] = true;
        } catch (Exception|\Error $e)
        {
            $response['error'] = $e->getMessage();
            $response['success'] = false;
            $response['data'] = null;
        }

        return $response;
    }

    public function handleShipmentCreated(Request $request)
    {
        try {
            $agent = $request->input('agent');
            $orderReference = $request->input('order_reference');
            $sendTrackAndTrace = $request->input('send_track_and_trace') ?? true;
            $sendReviewRequest = $request->input('send_review_request') ?? true;
            $deliveryMethod = $request->input('delivery_method');
            $carrierName = $request->input('carrier_name');
            $remarks = $request->input('remarks');
            $trackAndTraceUrl = $request->input('track_and_trace_url');

            $shipmentRepository = App::make(ShipmentRepositoryInterface::class, [
                'name' => 'delight'
            ]);

            $shipment = $shipmentRepository->findOneByOrderReference($orderReference);

            if ($carrierName)
            {
                $shipment->changeCarrierName($carrierName);
            }

            if ($deliveryMethod)
            {
                $shipment->changeDeliveryMethod($deliveryMethod);
            }

            if ($remarks)
            {
                $shipment->changeShippingExplanation($remarks);
            }

            if ($trackAndTraceUrl)
            {
                $shipment->trackAndTrace()->changeUrl($trackAndTraceUrl);
            }

            $warehouseService = App::make(WarehouseServiceInterface::class);

            $employee = "";

            HandleShipmentCreatedJob::dispatch($warehouseService, $shipment, $employee, $sendTrackAndTrace, $trackAndTraceUrl, $sendReviewRequest, $agent);

            $response['data'] = $shipment->toArray();
            $response['success'] = true;
            $response['error'] = null;
        } catch (Exception | \Error $e)
        {
            $response['data'] = null;
            $response['success'] = false;
            $response['error'] = $e->getMessage();
        }


        return $response;
    }

    public function handleWebhook(Request $request, string $webhookName)
    {
        WebhookManager::handle($request, $webhookName);
    }

    public function streamPicklistPdf(Request $request, int $picklistId)
    {
        if (! $request->hasValidSignature())
        {
            abort(401);
        }

        $path = Storage::path(sprintf(PicqerPackingSlipGenerator::DEFAULT_FILENAME, $picklistId));
        $picklistPdf = file_get_contents($path);

        return response($picklistPdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="pakbon.pdf"'
        ]);
    }
}
