<?php

namespace App\Warehouse\Infrastructure\Jobs;

use App\Jobs\WithoutOverlappingJob;
use App\Warehouse\Application\TransferNewOrdersToPicqer\TransferNewOrdersToPicqer;
use App\Warehouse\Application\TransferNewOrdersToPicqer\TransferNewOrdersToPicqerInput;
use App\Warehouse\Domain\Repositories\BackorderRepositoryInterface;
use App\Warehouse\Domain\Repositories\CustomerRepositoryInterface;
use App\Warehouse\Domain\Repositories\OrderRepositoryInterface;
use App\Warehouse\Domain\Services\OrderProcessorServiceInterface;
use App\Warehouse\Domain\Services\WarehouseServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TransferNewOrdersToPicqerJob extends WithoutOverlappingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected OrderRepositoryInterface $sourceOrderRepository;
    protected OrderRepositoryInterface $picqerOrderRepository;
    protected CustomerRepositoryInterface $picqerCustomerRepository;
    protected OrderProcessorServiceInterface $orderProcessor;
    protected WarehouseServiceInterface $warehouseService;
    protected \Illuminate\Support\Collection $processedOrders;
    protected \Illuminate\Support\Collection $failedOrders;
    protected BackorderRepositoryInterface $picqerBackorderRepository;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(OrderRepositoryInterface $sourceOrderRepository,
                                OrderRepositoryInterface $picqerOrderRepository,
                                CustomerRepositoryInterface $picqerCustomerRepository,
                                OrderProcessorServiceInterface $orderProcessor,
                                WarehouseServiceInterface $warehouseService,
                                BackorderRepositoryInterface $picqerBackorderRepository)
    {
        $this->onQueue('picqer');

        $this->sourceOrderRepository = $sourceOrderRepository;
        $this->picqerOrderRepository = $picqerOrderRepository;
        $this->picqerCustomerRepository = $picqerCustomerRepository;
        $this->orderProcessor = $orderProcessor;
        $this->warehouseService = $warehouseService;
        $this->picqerBackorderRepository = $picqerBackorderRepository;

        $this->processedOrders = collect();
        $this->failedOrders = collect();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // TODO: Better error handling, e.g. use TransferNewOrdersToPicqerRequest to validate input
        $useCase = new TransferNewOrdersToPicqer($this->sourceOrderRepository, $this->picqerOrderRepository, $this->picqerCustomerRepository,
                                                $this->orderProcessor, $this->warehouseService, $this->picqerBackorderRepository);

        $input = new TransferNewOrdersToPicqerInput();
        $useCase->execute($input);
    }

}
