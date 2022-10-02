<?php


namespace Tests\Feature\Warehouse;


use App\Warehouse\Domain\Orders\Order;
use App\Warehouse\Domain\ReviewRequests\ReviewRequest;

class DummyReviewRequestSenderService implements \App\Warehouse\Domain\Services\ReviewRequestSenderServiceInterface
{

    public function send(Order $order): ReviewRequest
    {

    }

    public function isSent(Order $order): bool
    {
        return true;
    }
}
