<?php


namespace App\Warehouse\Application\TransferNewOrdersToPicqer;

use App\SharedKernel\CleanArchitecture\DomainException;
use App\Warehouse\Domain\Exceptions\InvalidOrderException;
use App\Warehouse\Domain\Parties\Customer;
use App\Warehouse\Domain\Repositories\BackorderRepositoryInterface;
use App\Warehouse\Domain\Repositories\CustomerRepositoryInterface;
use App\Warehouse\Domain\Repositories\OrderRepositoryInterface;
use App\Warehouse\Domain\Services\OrderProcessorServiceInterface;
use App\Warehouse\Domain\Services\WarehouseServiceInterface;
use App\Warehouse\Infrastructure\Persistence\Picqer\Repositories\PicqerBackorderRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class TransferNewOrdersToPicqer implements TransferNewOrdersToPicqerInterface
{
    private Collection $processedOrders;
    private Collection $failedOrders;
    private OrderRepositoryInterface $sourceOrderRepository;
    private OrderRepositoryInterface $picqerOrderRepository;
    private CustomerRepositoryInterface $picqerCustomerRepository;
    private OrderProcessorServiceInterface $orderProcessor;
    private WarehouseServiceInterface $warehouseService;

    // Needed because sometimes we overwrite the $newOrder when adding it to Picqer
    // , which could be null
    private string $currentOrderReference;
    protected BackorderRepositoryInterface $backorderRepository;

    /**
     * TransferNewOrdersToPicqer constructor.
     * @param OrderRepositoryInterface $sourceOrderRepository
     * @param OrderRepositoryInterface $picqerOrderRepository
     * @param CustomerRepositoryInterface $picqerCustomerRepository
     * @param OrderProcessorServiceInterface $orderProcessor
     * @param WarehouseServiceInterface $warehouseService
     * @param PicqerBackorderRepository $backorderRepository
     */
    public function __construct(OrderRepositoryInterface $sourceOrderRepository,
                                OrderRepositoryInterface $picqerOrderRepository,
                                CustomerRepositoryInterface $picqerCustomerRepository,
                                OrderProcessorServiceInterface $orderProcessor,
                                WarehouseServiceInterface $warehouseService,
                                BackorderRepositoryInterface $backorderRepository)
    {
        $this->sourceOrderRepository = $sourceOrderRepository;
        $this->picqerOrderRepository = $picqerOrderRepository;
        $this->picqerCustomerRepository = $picqerCustomerRepository;
        $this->orderProcessor = $orderProcessor;
        $this->warehouseService = $warehouseService;
        $this->backorderRepository = $backorderRepository;
        $this->processedOrders = collect();
        $this->failedOrders = collect();
    }

    /**
     * @inheritDoc
     */
    public function execute(TransferNewOrdersToPicqerInput $input): TransferNewOrdersToPicqerResult
    {
        // We cannot use the $newOrder->reject() function to log the errors, because $newOrder does not exist
        // Therefore, TODO: Task 19259: Loggen van errors naar de agent als er iets misgaat in findNewOrders
        $newOrders = $this->sourceOrderRepository->findNewOrders();

        foreach ($newOrders as $newOrder)
        {
            try {
                $this->currentOrderReference = $newOrder->reference();

                if ($newOrder->deliveryOption() === null)
                {
                    throw new InvalidOrderException("Er is geen bezorgoptie ingesteld");
                }

                if ($newOrder->preferredDeliveryDate() === null)
                {
                    throw new InvalidOrderException("Er is geen bezorgdatum ingesteld");
                }

                $existingOrder = $this->picqerOrderRepository->findOneByReference($newOrder->reference());

                // TODO: Task 19207: Zorg dat snooze picklist werkt als we de referentie nog niet hebben
                if ($this->orderNotFound($existingOrder))
                {
                    $this->createOrUpdateCustomer($newOrder->customer());

                    $transferredOrder = $this->picqerOrderRepository->add($newOrder);
                    $this->orderProcessor->process($transferredOrder);

                    $newOrder->process();
                    $this->sourceOrderRepository->update($newOrder);
                    $this->processedOrders[] = $newOrder;
                }

            } catch (DomainException $e)
            {
                Log::error("Failed transferring new order with reference ".$this->currentOrderReference." to Picqer. Message: ".$e->getMessage().PHP_EOL."Stack Trace: ".$e->getTraceAsString());

                $reason = $e->getMessage();
                $newOrder->reject($reason);
                $this->sourceOrderRepository->update($newOrder);
                $this->failedOrders[] = $newOrder;
            } catch (\Exception | \Error $e)
            {
                Log::error("Failed transferring new order with reference ".$this->currentOrderReference." to Picqer. Message: ".$e->getMessage().PHP_EOL."Stack Trace: ".$e->getTraceAsString());
                $reason = "Onbekend, vraag iemand van IT om hulp";
                $newOrder->reject($reason);
                $this->sourceOrderRepository->update($newOrder);
                $this->failedOrders[] = $newOrder;

            }
        }

        return new TransferNewOrdersToPicqerResult($this->processedOrders, $this->failedOrders);
    }

    private function createOrUpdateCustomer(Customer $customer)
    {
        $existingCustomer = $this->picqerCustomerRepository->searchByCustomerNumber($customer->customerNumber());

        // If else statements zoveel mogelijk elimineren
        if ($this->customerNotFound($existingCustomer))
        {
            $this->picqerCustomerRepository->add($customer);
        } else {
            if ($customer !== $existingCustomer)
            {
                $this->picqerCustomerRepository->update($customer);
            }
        }
    }

    public function orderNotFound(?\App\Warehouse\Domain\Orders\Order $order): bool
    {
        return ! $order;
    }

    public function customerNotFound(?Customer $customer): bool
    {
        return ! $customer;
    }
}
