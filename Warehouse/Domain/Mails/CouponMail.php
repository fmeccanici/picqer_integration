<?php


namespace App\Warehouse\Domain\Mails;

use App\Warehouse\Domain\DiscountCodes\DiscountCode;
use App\Warehouse\Domain\Orders\Order;
use App\Warehouse\Domain\Parties\Customer;

class CouponMail extends Mail
{
    protected Order $order;
    protected DiscountCode $discountCode;
    protected Customer $customer;

    public const FLOW_NAME = 'CouponCode';

    /**
     * CouponMail constructor.
     * @param Customer $customer
     * @param Order $order
     * @param DiscountCode $discountCode
     */
    public function __construct(Customer $customer, Order $order, DiscountCode $discountCode)
    {
        $recipient = new Recipient($customer->contactName(), $customer->email(), $customer->firstName(), $customer->lastName());
        parent::__construct($recipient);
        $this->customer = $customer;
        $this->order = $order;
        $this->discountCode = $discountCode;
    }

    public function order(): Order
    {
        return $this->order;
    }

    public function discountCode(): DiscountCode
    {
        return $this->discountCode;
    }

    public function subject(): string
    {
        return "🎁️ Jouw digitale waardebon van Home Design Shops";
    }

    public function customer(): Customer
    {
        return $this->customer;
    }

    public function data(): array
    {
        return [
            'code' => $this->discountCode()->code(),
            'value' => '€' . number_format($this->discountCode()->discount(), 2, ',', '.'),
            'voornaam' => $this->recipient()->name(),
            'achternaam' => ""
        ];
    }
}
