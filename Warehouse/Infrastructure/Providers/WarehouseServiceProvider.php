<?php


namespace App\Warehouse\Infrastructure\Providers;


use App\Providers\ServiceProvider;
use App\Warehouse\Domain\Exporters\PackingSlipGeneratorInterface;
use App\Warehouse\Domain\Mails\MailerServiceInterface;
use App\Warehouse\Domain\Picklists\SimpleSnoozePolicy;
use App\Warehouse\Domain\Picklists\SnoozePolicyInterface;
use App\Warehouse\Domain\Repositories\BackorderActionRepositoryInterface;
use App\Warehouse\Domain\Repositories\BackorderRepositoryInterface;
use App\Warehouse\Domain\Repositories\BatchPicklistRepositoryInterface;
use App\Warehouse\Domain\Repositories\CustomerRepositoryInterface;
use App\Warehouse\Domain\Repositories\DiscountCodeRepositoryInterface;
use App\Warehouse\Domain\Repositories\EmployeeRepositoryInterface;
use App\Warehouse\Domain\Repositories\OrderRepositoryInterface;
use App\Warehouse\Domain\Repositories\PicklistRepositoryInterface;
use App\Warehouse\Domain\Repositories\ReviewRequestRepositoryInterface;
use App\Warehouse\Domain\Repositories\ShipmentRepositoryInterface;
use App\Warehouse\Domain\Services\DeliveryOptionServiceInterface;
use App\Warehouse\Domain\Services\OrderFulfillmentServiceInterface;
use App\Warehouse\Domain\Services\OrderProcessorServiceInterface;
use App\Warehouse\Domain\Services\ResourcePlanningServiceInterface;
use App\Warehouse\Domain\Services\ReviewRequestSenderServiceInterface;
use App\Warehouse\Domain\Services\WarehouseServiceInterface;
use App\Warehouse\Infrastructure\ApiClients\PicqerApiClient;
use App\Warehouse\Infrastructure\Exporters\PicqerPackingSlipGenerator;
use App\Warehouse\Infrastructure\Mails\FlowMailerMailerService;
use App\Warehouse\Infrastructure\Persistence\Eloquent\Repositories\EloquentBackorderActionRepository;
use App\Warehouse\Infrastructure\Persistence\Eloquent\Repositories\EloquentDiscountCodeRepository;
use App\Warehouse\Infrastructure\Persistence\Eloquent\Repositories\EloquentReviewRequestRepository;
use App\Warehouse\Infrastructure\Persistence\MsSql\Repositories\MsSqlOrderRepository;
use App\Warehouse\Infrastructure\Persistence\Picqer\Repositories\PicqerBackorderCacheRepository;
use App\Warehouse\Infrastructure\Persistence\Picqer\Repositories\PicqerBatchPicklistRepository;
use App\Warehouse\Infrastructure\Persistence\Picqer\Repositories\PicqerCustomerRepository;
use App\Warehouse\Infrastructure\Persistence\Picqer\Repositories\PicqerEmployeeRepository;
use App\Warehouse\Infrastructure\Persistence\Picqer\Repositories\PicqerOrderRepository;
use App\Warehouse\Infrastructure\Persistence\Picqer\Repositories\PicqerPicklistRepository;
use App\Warehouse\Infrastructure\Persistence\Picqer\Repositories\PicqerShipmentRepository;
use App\Warehouse\Infrastructure\Services\DeliveryOptionService;
use App\Warehouse\Infrastructure\Services\OrderFulfillmentService;
use App\Warehouse\Infrastructure\Services\PicqerOrderProcessorService;
use App\Warehouse\Infrastructure\Services\ResourcePlanningService;
use App\Warehouse\Infrastructure\Services\ReviewRequestSenders\FeedbackCompany\FeedbackCompanyReviewRequestSenderService;
use App\Warehouse\Infrastructure\Services\WarehouseService;
use App\Warehouse\Presentation\Console\Commands\PicqerCacheBackordersCommand;
use App\Warehouse\Presentation\Console\Commands\PicqerTransferNewOrdersCommand;
use App\Warehouse\Presentation\Console\Commands\SetSynchronizeStockTags;
use App\Warehouse\Presentation\Console\Commands\SynchronizeStock;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;

class WarehouseServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->commands([
            PicqerCacheBackordersCommand::class,
            PicqerTransferNewOrdersCommand::class,
            SetSynchronizeStockTags::class,
            SynchronizeStock::class,
        ]);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        parent::boot();

        $this->registerWarehouseRoutes();
        // TODO: Change DummyMailerService::class to FlowMailer
        $this->app->bind(MailerServiceInterface::class, FlowMailerMailerService::class);
        $this->app->bind(DeliveryOptionServiceInterface::class, DeliveryOptionService::class);

        $this->app->bind(ShipmentRepositoryInterface::class, function (Application $app, array $with) {
            $repositoryName = key_exists('name', $with) ?
                config('warehouse.repositories.shipments.'.$with['name']) :
                config('warehouse.repositories.shipments.default');

            if ($repositoryName === PicqerShipmentRepository::class)
            {
                return new $repositoryName($this->app->make(PicqerApiClient::class));
            }

            return new $repositoryName;
        });

        $this->app->bind(EmployeeRepositoryInterface::class, PicqerEmployeeRepository::class);
        $this->app->bind(OrderFulfillmentServiceInterface::class, OrderFulfillmentService::class);
        $this->app->bind(WarehouseServiceInterface::class, WarehouseService::class);
        $this->app->bind(PicklistRepositoryInterface::class, PicqerPicklistRepository::class);
        $this->app->bind(CustomerRepositoryInterface::class, PicqerCustomerRepository::class);
        $this->app->bind(OrderProcessorServiceInterface::class, PicqerOrderProcessorService::class);
        $this->app->bind(ResourcePlanningServiceInterface::class, ResourcePlanningService::class);
        $this->app->bind(BackorderRepositoryInterface::class, PicqerBackorderCacheRepository::class);
        $this->app->bind(CustomerRepositoryInterface::class, PicqerCustomerRepository::class);
        $this->app->bind(BackorderActionRepositoryInterface::class, EloquentBackorderActionRepository::class);
        $this->app->bind(PackingSlipGeneratorInterface::class, PicqerPackingSlipGenerator::class);
        $this->app->bind(SnoozePolicyInterface::class, SimpleSnoozePolicy::class);

        // TODO: Implement Feedback Company Review Request Sender
        $this->app->bind(ReviewRequestSenderServiceInterface::class, FeedbackCompanyReviewRequestSenderService::class);
        $this->app->bind(ReviewRequestRepositoryInterface::class, EloquentReviewRequestRepository::class);

        $this->app->when(WarehouseController::class)
            ->needs(BackorderRepositoryInterface::class)
            ->give(function() {
                return new PicqerBackorderCacheRepository(cache()->store());
            });

        $this->app->bind(BatchPicklistRepositoryInterface::class, PicqerBatchPicklistRepository::class);

        $this->app->bind(DiscountCodeRepositoryInterface::class, EloquentDiscountCodeRepository::class);

        $this->app->bind(OrderRepositoryInterface::class, function (Application $app, array $with) {
            $repositoryName = key_exists('name', $with) ?
                config('warehouse.repositories.orders.'.$with['name']) :
                config('warehouse.repositories.orders.default');

            // TODO: This violates open closed, as everytime new dependencies are needed in repo you need to change this
            if ($repositoryName === MsSqlOrderRepository::class)
            {
                return new $repositoryName($this->app->make(DeliveryOptionServiceInterface::class));
            } else if ($repositoryName === PicqerOrderRepository::class)
            {
                return new $repositoryName($this->app->make(PicqerApiClient::class));
            }

            return new $repositoryName();

        });

        $this->loadMigrationsFrom(app_path('/Warehouse/Infrastructure/Persistence/Migrations'));
    }

    /**
     * @param Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('picqer:transfer-new-orders delight picqer')
            ->everyTwoMinutes()
            ->description('Transfer new orders from Delight to Picqer every two minutes');

        $schedule->command('picqer:cache-backorders')
            ->everyFiveMinutes()
            ->description('Cache the Picqer Backorders every five minutes');
    }

    /**
     * Registers the warehouse routes
     */
    protected function registerWarehouseRoutes(): void
    {
        Route::prefix('warehouse')
            ->middleware('web')
            ->namespace('App\\Warehouse\\Presentation\\Http')
            ->group(__DIR__ . '/../../Presentation/Http/Routes/web.php');

        Route::prefix('api/warehouse')
            ->middleware('auth:api')
            ->namespace('App\\Warehouse\\Presentation\\Http')
            ->group(__DIR__ . '/../../Presentation/Http/Routes/api.php');
    }

}
