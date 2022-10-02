<?php


namespace App\Warehouse\Domain\ReviewRequests;


use App\SharedKernel\CleanArchitecture\Entity;
use App\Warehouse\Domain\Orders\Order;
use Carbon\CarbonImmutable;

class ReviewRequest extends Entity
{
    const MAX_DAYS_TO_WAIT_FOR_SENDING_NEW_REQUEST = 5;
    const REMIND_DELAY_IN_DAYS = 9;

    protected ?CarbonImmutable $lastSent;
    protected int $quantitySent;
    protected Order $order;
    protected string $orderReference;
    protected ?CarbonImmutable $deliveryDate;
    protected string $customerName;
    protected string $customerEmail;

    /**
     * @param string $orderReference
     * @param string $customerName
     * @param string $customerEmail
     * @param CarbonImmutable|null $deliveryDate
     * @param int $quantitySent
     * @param CarbonImmutable|null $lastSent
     */

    public function __construct(string $orderReference, string $customerName, string $customerEmail, ?CarbonImmutable $deliveryDate = null, int $quantitySent = 0, ?CarbonImmutable $lastSent = null)
    {
        $this->orderReference = $orderReference;
        $this->deliveryDate = $deliveryDate;
        $this->lastSent = $lastSent;
        $this->quantitySent = $quantitySent;
        $this->customerName = $customerName;
        $this->customerEmail = $customerEmail;
    }

    public function orderReference(): string
    {
        return $this->orderReference;
    }

    public function customerName(): string
    {
        return $this->customerName;
    }

    public function customerEmail(): string
    {
        return $this->customerEmail;
    }

    public function delayInDays(): int
    {
        if ($this->lastSent && $this->deliveryDate)
        {
            $differenceBetweenPreferredDeliveryDateAndReviewRequestSentDate = $this->lastSent->diffInDays($this->deliveryDate);
            $delay = max($differenceBetweenPreferredDeliveryDateAndReviewRequestSentDate, self::MAX_DAYS_TO_WAIT_FOR_SENDING_NEW_REQUEST);
        } else {
            $delay = self::MAX_DAYS_TO_WAIT_FOR_SENDING_NEW_REQUEST;
        }

        return $delay;
    }

    public function reminderDelayInDays(): int
    {
        return $this->delayInDays() + self::REMIND_DELAY_IN_DAYS;
    }

    public function send(): void
    {
        $this->lastSent = CarbonImmutable::now();
        $this->quantitySent ++;
    }

    protected function cascadeSetIdentity(int|string $id): void
    {
        // Nothing to be done
    }
}
