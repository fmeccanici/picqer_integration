<?php

namespace App\Warehouse\Domain\Mails;

use App\Warehouse\Domain\Backorders\Backorder;
use App\Warehouse\Domain\Parties\Customer;

class DelayBackorderMail extends Mail
{
    private Backorder $backorder;
    private string $reasonForDelay;
    private bool $customerCalled;
    private string $employeeName;
    private string $alteredDeliveryDate;
    private bool $talkedToCustomer;
    private Customer $customer;

    const FLOW_NAME = 'DelayBackorder';

    public function __construct(Backorder $backorder, string $reasonForDelay, bool $customerCalled, bool $talkedToCustomer, string $employeeName, string $alteredDeliveryDate)
    {
        $this->backorder = $backorder;

        $customer = $backorder->customer();
        $recipient = new Recipient($customer->name(), $customer->email(), $customer->firstName(), $customer->lastName());
        parent::__construct($recipient);

        $this->reasonForDelay = $reasonForDelay;
        $this->customerCalled = $customerCalled;
        $this->talkedToCustomer = $talkedToCustomer;
        $this->employeeName = $employeeName;
        $this->customer = $customer;
        $this->alteredDeliveryDate = $alteredDeliveryDate;
    }

    public function subject(): string
    {
        if($this->reasonForDelay() === 'Wijziging leverdatum - Achteraf op verzoek van klant') {
            $subject = "De bezorgdag van uw bestelling is gewijzigd.";
        } else {
            $subject = "Sorry, de bezorgdag van uw bestelling is gewijzigd.";
        }

        return $subject;
    }

    public function reasonForDelay(): string
    {
        return $this->reasonForDelay;
    }

    public function alteredDeliveryDate(): string
    {
        return $this->alteredDeliveryDate;
    }

    public function customerCalled(): bool
    {
        return $this->customerCalled;
    }

    public function talkedToCustomer(): bool
    {
        return $this->talkedToCustomer;
    }

    public function employeeName(): string
    {
        return $this->employeeName;
    }

    public function customer(): Customer
    {
        return $this->customer;
    }

    public function data(): array
    {
        $order = $this->backorder->order();

        return [
            'voornaam' => $this->recipient()->firstName(),
            'achternaam' => $this->recipient()->lastName(),
            'reden' => $this->reasonForDelay(),
            'transporteur' => $order->deliveryOption()->carrier(),
            'bezorgmethode' => $order->deliveryOption()->name(),
            'bezorgdatum' => $this->alteredDeliveryDate(),
            'adres' => $this->customer()->address()->fullStreetAddress(),
            'postcodePlaats' => $this->customer()->address()->zipcode() . ', ' . $this->customer()->address()->city(),
            'land' => $this->customer()->address()->countryCode(),
            'ordernummer' => $this->backorder->orderReference(),
            'klantGebeld' => $this->customerCalled(),
            'klantGesproken' => $this->talkedToCustomer(),
            'medewerker' => $this->employeeName()
        ];
    }
}
