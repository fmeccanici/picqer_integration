<?php

namespace App\Warehouse\Domain\Mails;

use App\Warehouse\Domain\Orders\Order;
use App\Warehouse\Domain\Parties\Customer;
use App\Warehouse\Domain\Shipments\Shipment;

class FullyShippedConfirmationEmail extends Mail
{
    private Shipment $shipment;
    private Order $order;

    public function __construct(Customer $customer, Order $order, Shipment $shipment)
    {
        parent::__construct($customer);
        $this->shipment = $shipment;
        $this->order = $order;
    }

    public function order(): Order
    {
        return $this->order;
    }

    public function shipment(): Shipment
    {
        return $this->shipment;
    }

    function subject(): string
    {
        return "Bestelling volledig verzonden";
    }

    function data(): array
    {
        return [];
    }
}
