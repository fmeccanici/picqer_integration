<?php


namespace App\Warehouse\Application\CancelOrder;

use App\Warehouse\Domain\Exceptions\OrderNotFoundException;
use App\Warehouse\Domain\Exceptions\OrderOperationException;
use App\Warehouse\Domain\Repositories\OrderRepositoryInterface;

class CancelOrder implements CancelOrderInterface
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
    public function execute(CancelOrderInput $input): CancelOrderResult
    {
        $order = $this->orderRepository->findOneByReference($input->orderReference());

        if (! $order)
        {
            throw new OrderNotFoundException('Order with reference ' . $input->orderReference() . ' not found');
        }

        $order->cancel();
        $this->orderRepository->update($order, ['status']);

        return new CancelOrderResult($order);
    }
}
