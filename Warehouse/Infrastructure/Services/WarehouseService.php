<?php


namespace App\Warehouse\Infrastructure\Services;


use App\Warehouse\Application\GetDeliveryOption\GetDeliveryOption;
use App\Warehouse\Application\GetDeliveryOption\GetDeliveryOptionInput;
use App\Warehouse\Application\HandlePicklistCreated\HandlePicklistCreated;
use App\Warehouse\Application\HandlePicklistCreated\HandlePicklistCreatedInput;
use App\Warehouse\Application\HandleShipmentCreated\HandleShipmentCreated;
use App\Warehouse\Application\HandleShipmentCreated\HandleShipmentCreatedInput;
use App\Warehouse\Application\HandleShipmentCreated\HandleShipmentCreatedResult;
use App\Warehouse\Application\SnoozePicklist\SnoozePicklist;
use App\Warehouse\Application\SnoozePicklist\SnoozePicklistInput;
use App\Warehouse\Application\UnsnoozePicklist\UnsnoozePicklist;
use App\Warehouse\Application\UnsnoozePicklist\UnsnoozePicklistInput;
use App\Warehouse\Domain\Exceptions\PicklistNotFoundException;
use App\Warehouse\Domain\Mails\MailerServiceInterface;
use App\Warehouse\Domain\Orders\DeliveryOption;
use App\Warehouse\Domain\Picklists\Picklist;
use App\Warehouse\Domain\Repositories\CustomerRepositoryInterface;
use App\Warehouse\Domain\Repositories\DiscountCodeRepositoryInterface;
use App\Warehouse\Domain\Repositories\OrderRepositoryInterface;
use App\Warehouse\Domain\Repositories\PicklistRepositoryInterface;
use App\Warehouse\Domain\Repositories\ShipmentRepositoryInterface;
use App\Warehouse\Domain\Services\OrderFulfillmentServiceInterface;
use App\Warehouse\Domain\Services\ReviewRequestSenderServiceInterface;
use App\Warehouse\Domain\Shipments\Shipment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;

class WarehouseService implements \App\Warehouse\Domain\Services\WarehouseServiceInterface
{
    protected PicklistRepositoryInterface $picklistRepository;
    protected MailerServiceInterface $mailerService;
    protected ReviewRequestSenderServiceInterface $reviewRequestSender;
    protected CustomerRepositoryInterface $customerRepository;
    protected OrderRepositoryInterface $orderRepository;
    protected OrderFulfillmentServiceInterface $orderFulfillmentService;
    protected ShipmentRepositoryInterface $shipmentRepository;
    protected DiscountCodeRepositoryInterface $discountCodeRepository;

    /**
     * @param PicklistRepositoryInterface $picklistRepository
     * @param MailerServiceInterface $mailerService
     * @param ReviewRequestSenderServiceInterface $reviewRequestSender
     * @param CustomerRepositoryInterface $customerRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderFulfillmentServiceInterface $orderFulfillmentService
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param DiscountCodeRepositoryInterface $discountCodeRepository;
     */
    public function __construct(PicklistRepositoryInterface $picklistRepository,
                                MailerServiceInterface $mailerService,
                                ReviewRequestSenderServiceInterface $reviewRequestSender,
                                CustomerRepositoryInterface $customerRepository,
                                OrderRepositoryInterface $orderRepository,
                                OrderFulfillmentServiceInterface $orderFulfillmentService,
                                ShipmentRepositoryInterface $shipmentRepository,
                                DiscountCodeRepositoryInterface $discountCodeRepository)
    {
        $this->picklistRepository = $picklistRepository;
        $this->mailerService = $mailerService;
        $this->reviewRequestSender = $reviewRequestSender;
        $this->customerRepository = $customerRepository;
        $this->orderRepository = $orderRepository;
        $this->orderFulfillmentService = $orderFulfillmentService;
        $this->shipmentRepository = $shipmentRepository;
        $this->discountCodeRepository = $discountCodeRepository;
    }

    /**
     * @param string|int $picklistId
     * @param CarbonImmutable $snoozeUntil
     * @return Picklist
     * @throws  PicklistNotFoundException
     */
    public function snoozePicklistUntil(string|int $picklistId, CarbonImmutable $snoozeUntil): Picklist
    {
        $snoozePicklistInput = new SnoozePicklistInput([
            'picklist_id' => (string) $picklistId,
            'snooze_until' => $snoozeUntil->format(config('datetime.format'))
        ]);

        $snoozePicklist = new SnoozePicklist($this->picklistRepository);
        $result = $snoozePicklist->execute($snoozePicklistInput);

        return $result->picklist();
    }

    /**
     * @param string $picklistReference
     * @return Picklist
     */
    public function unsnoozePicklist(string $picklistReference): Picklist
    {
        $snoozePicklistInput = new UnsnoozePicklistInput([
            'picklist_reference' => $picklistReference,
        ]);
        $snoozePicklist = new UnsnoozePicklist($this->picklistRepository);
        $result = $snoozePicklist->execute($snoozePicklistInput);
        return $result->picklist();
    }

    /**
     * @param string $picklistReference
     * @return Picklist
     */
    public function handlePicklistCreated(string $picklistReference): Picklist
    {
        $handlePicklistCreatedInput = new HandlePicklistCreatedInput([
            'picklist_reference' => $picklistReference,
        ]);
        $handlePicklistCreated = new HandlePicklistCreated($this->picklistRepository, $this, $this->orderRepository);
        $result = $handlePicklistCreated->execute($handlePicklistCreatedInput);
        return $result->picklist();
    }

    /**
     * @param Shipment $shipment
     * @return HandleShipmentCreatedResult
     */
    public function handleShipmentCreated(Shipment $shipment, string $packedByName, bool $sendTrackAndTraceMail = true, ?string $trackAndTraceUrl = null, bool $sendReviewRequest = true, string $agent = 'picqer'): HandleShipmentCreatedResult
    {
        $input = [
            'shipment' => $shipment->toArray(),
            'send_track_and_trace_mail' => $sendTrackAndTraceMail,
            'send_review_request' => $sendReviewRequest,
            'track_and_trace_url' => $trackAndTraceUrl,
            'agent' => $agent,
            'packed_by_name' => $packedByName
        ];

        $useCaseInput = new HandleShipmentCreatedInput($input);

        $useCase = new HandleShipmentCreated($this->orderRepository,
            $this->mailerService,
            $this->reviewRequestSender,
            $this->customerRepository,
            $this->orderFulfillmentService,
            $this->shipmentRepository,
            $this->discountCodeRepository
        );

        return $useCase->execute($useCaseInput);
    }

    /**
     * @param Collection $orderedItems
     * @return CarbonImmutable
     */
    public function estimateShippingDate(Collection $orderedItems): CarbonImmutable
    {


    }

    /**
     * @param string $country
     * @param string $deliveryOptionName
     * @param string|null $carrierName
     * @param string|null $locationCode
     * @param string|null $retailNetworkId
     * @return DeliveryOption|null
     */
    public function getDeliveryOption(string $country, string $deliveryOptionName, ?string $carrierName, ?string $locationCode = null, ?string $retailNetworkId = null): ?DeliveryOption
    {
        $deliveryOptionService = App::make(DeliveryOptionService::class);
        $getDeliveryOption = new GetDeliveryOption($deliveryOptionService);
        $input = new GetDeliveryOptionInput([
            "delivery_country" => $country,
            "delivery_option_name" => $deliveryOptionName,
            "carrier_name" => $carrierName
        ]);

        $result = $getDeliveryOption->execute($input);

        if ($result->deliveryOption() === null)
        {
            return null;
        }

        return new DeliveryOption($carrierName, $deliveryOptionName, $result->deliveryOption()->productCode(), $result->deliveryOption()->characteristic(), $result->deliveryOption()->option(), $locationCode, $retailNetworkId);
    }
}
