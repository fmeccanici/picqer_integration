<?php


namespace App\Warehouse\Infrastructure\Jobs;


use App\Jobs\WithoutOverlappingJob;
use App\Warehouse\Application\HandleShipmentCreated\HandleShipmentCreatedResult;
use App\Warehouse\Domain\Services\WarehouseServiceInterface;
use App\Warehouse\Domain\Shipments\Shipment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class HandleShipmentCreatedJob extends WithoutOverlappingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected WarehouseServiceInterface $warehouseService;
    protected Shipment $shipment;
    protected bool $sendTrackAndTrace;
    protected ?string $trackAndTraceUrl;
    protected bool $sendReviewRequest;
    protected string $agent;
    protected string $packedByName;

    /**
     * Create a new job instance.
     * @param WarehouseServiceInterface $warehouseService
     * @param Shipment $shipment
     * @param string $packedByName
     * @param bool $sendTrackAndTrace
     * @param string|null $trackAndTraceUrl
     * @param bool $sendReviewRequest
     * @param string $agent
     */
    public function __construct(WarehouseServiceInterface $warehouseService, Shipment $shipment, string $packedByName, bool $sendTrackAndTrace = true, ?string $trackAndTraceUrl = null, bool $sendReviewRequest = true, string $agent = 'picqer')
    {
        $this->onQueue('shipping');

        $this->warehouseService = $warehouseService;
        $this->shipment = $shipment;
        $this->sendTrackAndTrace = $sendTrackAndTrace;
        $this->trackAndTraceUrl = $trackAndTraceUrl;
        $this->sendReviewRequest = $sendReviewRequest;
        $this->agent = $agent;
        $this->packedByName = $packedByName;
    }

    /**
     * Execute the job.
     *
     * @return HandleShipmentCreatedResult
     */
    public function handle(): HandleShipmentCreatedResult
    {
        return $this->warehouseService->handleShipmentCreated($this->shipment, $this->packedByName, $this->sendTrackAndTrace, $this->trackAndTraceUrl, $this->sendReviewRequest, $this->agent);
    }
}
