<?php


namespace App\Warehouse\Application\HandleShipmentCreated;

use App\Warehouse\Domain\DiscountCodes\DiscountCode;
use App\Warehouse\Domain\Mails\CouponMail;
use App\Warehouse\Domain\Mails\MailerServiceInterface;
use App\Warehouse\Domain\Mails\TrackAndTraceMail;
use App\Warehouse\Domain\Orders\Action;
use App\Warehouse\Domain\Orders\Order;
use App\Warehouse\Domain\Orders\OrderedItem;
use App\Warehouse\Domain\Orders\Product;
use App\Warehouse\Domain\Repositories\CustomerRepositoryInterface;
use App\Warehouse\Domain\Repositories\DiscountCodeRepositoryInterface;
use App\Warehouse\Domain\Repositories\OrderRepositoryInterface;
use App\Warehouse\Domain\Repositories\ShipmentRepositoryInterface;
use App\Warehouse\Domain\ReviewRequests\ReviewRequest;
use App\Warehouse\Domain\Services\OrderFulfillmentServiceInterface;
use App\Warehouse\Domain\Services\ReviewRequestSenderServiceInterface;
use App\Warehouse\Domain\Shipments\Shipment;
use App\Warehouse\Domain\Shipments\ShipmentFactory;
use App\Warehouse\Infrastructure\Persistence\Picqer\Repositories\PicqerOrderRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class HandleShipmentCreated implements HandleShipmentCreatedInterface
{
    protected OrderRepositoryInterface $orderRepository;
    protected MailerServiceInterface $mailerService;
    protected Collection $sentEmails;
    protected ReviewRequestSenderServiceInterface $reviewRequestSender;
    protected CustomerRepositoryInterface $customerRepository;
    protected OrderFulfillmentServiceInterface $orderFulfillmentService;
    protected ShipmentRepositoryInterface $shipmentRepository;
    protected DiscountCodeRepositoryInterface $discountCodeRepository;

    /**
     * NotifyCustomerOfFullyShippedOrder constructor.
     * @param OrderRepositoryInterface $orderRepository
     * @param MailerServiceInterface $mailerService
     * @param ReviewRequestSenderServiceInterface $reviewRequestSender
     * @param CustomerRepositoryInterface $customerRepository
     * @param OrderFulfillmentServiceInterface $orderFulfillmentService
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param DiscountCodeRepositoryInterface $discountCodeRepository
     */
    public function __construct(OrderRepositoryInterface $orderRepository,
                                MailerServiceInterface $mailerService,
                                ReviewRequestSenderServiceInterface $reviewRequestSender,
                                CustomerRepositoryInterface $customerRepository,
                                OrderFulfillmentServiceInterface $orderFulfillmentService,
                                ShipmentRepositoryInterface $shipmentRepository,
                                DiscountCodeRepositoryInterface $discountCodeRepository)
    {
        $this->orderRepository = $orderRepository;
        $this->mailerService = $mailerService;
        $this->sentEmails = collect();
        $this->reviewRequestSender = $reviewRequestSender;
        $this->customerRepository = $customerRepository;
        $this->orderFulfillmentService = $orderFulfillmentService;
        $this->shipmentRepository = $shipmentRepository;
        $this->discountCodeRepository = $discountCodeRepository;
    }

    /**
     * @inheritDoc
     */
    public function execute(HandleShipmentCreatedInput $input): HandleShipmentCreatedResult
    {
        $shipment = ShipmentFactory::fromArray($input->shipment());
        $order = $this->orderRepository->findOneByReference($shipment->orderReference());

        if ($this->orderNotFound($order))
        {
            Log::error("Order with reference ".$shipment->orderReference()." not found");

            return new HandleShipmentCreatedResult(collect(), $shipment, null);
        }

        if ($input->agent() == 'picqer')
        {
            $order->completeByPicqer($input->packedByName());
        } elseif ($input->agent() == 'delight')
        {
            $order->completeByDelight();
        } else {
            $order->complete($input->packedByName());
        }

        $order->changeTrackAndTrace($shipment->trackAndTrace());

        //TODO: Ugly code: new implementation is needed
        $msSqlOrder = $this->getMsSqlOrderByOrder($order);

        if(!$msSqlOrder->hasAGeneratedCoupon()) {
            if ($couponProduct = $this->filterCouponProductFromOrder($msSqlOrder)) {
                $this->handleCouponMail($couponProduct, $shipment, $order);
            }
        }

        $this->orderRepository->update($order);

        if ($input->sendTrackAndTraceMail()) {
            $this->sendTrackAndTraceMail($shipment, $order);
        }

        $reviewRequest = null;

        if ($this->orderFulfillmentService->isFulfilled($order) && $input->sendReviewRequest())
        {
            $reviewRequest = $this->sendReviewRequestWhenNotAlreadySent($order);
        }

        return new HandleShipmentCreatedResult($this->sentEmails, $shipment, $reviewRequest);
    }

    /**
     * @param Order $order
     * @return Order
     */
    private function getMsSqlOrderByOrder(Order $order): Order
    {
        $msSqlOrder = $order;
        if(get_class($this->orderRepository) === PicqerOrderRepository::class) {
            try {
                $msSqlOrder = $this->orderRepository->findOneInMSSqlByReference($order->reference());
            } catch (\Exception $e) {
                Log::error('Order not found by reference in MSSQL "' . $order->reference() . '"');
            }
        }

        return $msSqlOrder;
    }

    /**
     * @param Product $couponProduct
     * @param Shipment $shipment
     * @param Order $order
     */
    private function handleCouponMail(Product $couponProduct, Shipment $shipment, Order $order): void
    {
        if($couponValue = self::parseCouponValueFromDescription($couponProduct->description())) {
            $couponCode = $this->discountCodeRepository->generateValidCouponCode();

            $discountCode = new DiscountCode($couponCode, $couponValue, false, 1);
            $discountCode = $this->discountCodeRepository->add($discountCode);

            $order->changeCouponDiscountCodeId($discountCode->identity());
            $this->orderRepository->updateCouponDiscountCodeId($order);

            $this->sentEmails->add(self::sendCouponMail($shipment, $order, $discountCode));
        } else {
            //TODO: What to do if value couldn't be parsed
        }
    }

    /**
     * @param string $description
     * @return float|null
     */
    private static function parseCouponValueFromDescription(string $description): ?float
    {
        if($parsableValue = explode('€', $description)[1]) {
            return str_replace(',', '.', preg_replace('/[^0-9,]+/', '', $parsableValue));
        }

        return null;
    }

    /**
     * @param Order $order
     * @return Product|null
     */
    private function filterCouponProductFromOrder(Order $order): ?Product
    {
        return $order->orderedItems()->first(function(OrderedItem $orderedItem) {
            return strtolower($orderedItem->product()->productId()) === strtolower(config('warehouse.discountcode.coupon'));
        })?->product();
    }

    /**
     * @param Shipment $shipment
     * @param Order $order
     * @param DiscountCode $discountCode
     * @return CouponMail
     */
    protected static function sendCouponMail(Shipment $shipment, Order $order, DiscountCode $discountCode): CouponMail
    {
        $couponMail = new CouponMail($order->customer(), $order, $discountCode);
        $shipment->sendCouponMail($couponMail);

        $actionDescription = 'Er is een kortingscode aangemaakt en per e-mail verstuurd naar de klant ' . $discountCode->code() . ' t.w.v. €' . str_replace('.', ',', $discountCode->discount());
        $action = new Action($actionDescription, CarbonImmutable::now(), "Webservices");
        $order->addAction($action);

        return $couponMail;
    }

    /**
     * @param Order $order
     * @return ReviewRequest|null
     */
    private function sendReviewRequestWhenNotAlreadySent(Order $order): ?ReviewRequest
    {
        $reviewRequest = null;

        if (! $this->reviewRequestSender->isSent($order))
        {
            $reviewRequest = $this->reviewRequestSender->send($order);
        }

        return $reviewRequest;
    }

    private function orderNotFound(?Order $order): bool
    {
        return $order === null;
    }

    /**
     * @param Shipment $shipment
     * @param Order|null $order
     * @return void
     */
    private function sendTrackAndTraceMail(Shipment $shipment, ?Order $order): void
    {
        $packingSlip = $shipment->createPackingSlip();
        $trackAndTraceMail = new TrackAndTraceMail($order->customer(), $order, $shipment, $packingSlip);
        $shipment->sendTrackAndTraceMail($trackAndTraceMail);
        $this->shipmentRepository->save($shipment);
    }
}
