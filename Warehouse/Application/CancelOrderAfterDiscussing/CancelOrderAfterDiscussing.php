<?php


namespace App\Warehouse\Application\CancelOrderAfterDiscussing;

use App\Warehouse\Domain\Exceptions\OrderNotFoundException;
use App\Warehouse\Domain\Exceptions\OrderOperationException;
use App\Warehouse\Domain\Repositories\OrderRepositoryInterface;

class CancelOrderAfterDiscussing implements CancelOrderAfterDiscussingInterface
{
    protected OrderRepositoryInterface $orderRepository;

    /**
     * CancelOrder constructor.
     */
    public function __construct(OrderRepositoryInterface $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    /**
     * @inheritDoc
     * @throws OrderNotFoundException
     * @throws OrderOperationException
     */
    public function execute(CancelOrderAfterDiscussingInput $input): CancelOrderAfterDiscussingResult
    {
        $order = $this->orderRepository->findOneByReference($input->orderReference());

        if (! $order)
        {
            throw new OrderNotFoundException('Order with reference ' . $input->orderReference() . ' not found');
        }

        $order->cancel(true);
        $this->orderRepository->update($order, ['status']);

        return new CancelOrderAfterDiscussingResult($order);
    }
}
