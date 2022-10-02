<?php


namespace App\Warehouse\Domain\Services;


use App\Warehouse\Domain\Orders\Order;
use App\Warehouse\Domain\ReviewRequests\ReviewRequest;

interface ReviewRequestSenderServiceInterface
{
    /**
     * @param Order $order
     * @return ReviewRequest
     */
    public function send(Order $order): ReviewRequest;

    /**
     * @param Order $order
     * @return bool
     */
    public function isSent(Order $order): bool;
}
