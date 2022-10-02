<?php

namespace App\Warehouse\Presentation\Console\Commands;

use App\Warehouse\Domain\Repositories\CustomerRepositoryInterface;
use App\Warehouse\Domain\Repositories\OrderRepositoryInterface;
use App\Warehouse\Domain\Services\OrderProcessorServiceInterface;
use App\Warehouse\Domain\Services\WarehouseServiceInterface;
use App\Warehouse\Infrastructure\Jobs\TransferNewOrdersToPicqerJob;
use App\Warehouse\Infrastructure\Persistence\Picqer\Repositories\PicqerBackorderRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class PicqerTransferNewOrdersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'picqer:transfer-new-orders
                            {from : The source to get the orders from}
                            {to : The destination to transfer the orders to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transfer new orders from or to Picqer';


    protected CustomerRepositoryInterface $customerRepository;
    protected OrderProcessorServiceInterface $orderProcessorService;
    protected WarehouseServiceInterface $warehouseService;
    protected PicqerBackorderRepository $picqerBackorderRepository;
    protected OrderRepositoryInterface $sourceOrderRepository;
    protected OrderRepositoryInterface $destinationOrderRepository;


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        OrderProcessorServiceInterface $orderProcessorService,
        WarehouseServiceInterface $warehouseService,
        PicqerBackorderRepository $picqerBackorderRepository
    )
    {
        parent::__construct();

        $this->customerRepository = $customerRepository;
        $this->orderProcessorService = $orderProcessorService;
        $this->warehouseService = $warehouseService;
        $this->picqerBackorderRepository = $picqerBackorderRepository;

        $this->sourceOrderRepository = App::make(OrderRepositoryInterface::class, ['name' => 'delight']);
        $this->destinationOrderRepository = App::make(OrderRepositoryInterface::class, ['name' => 'picqer']);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        TransferNewOrdersToPicqerJob::dispatch(
            $this->sourceOrderRepository,
            $this->destinationOrderRepository,
            $this->customerRepository,
            $this->orderProcessorService,
            $this->warehouseService,
            $this->picqerBackorderRepository
        );

        $this->getOutput()->success('Command successfully pushed to queue');

        return self::SUCCESS;
    }
}
