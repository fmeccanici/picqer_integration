<?php


namespace App\Warehouse\Infrastructure\Exporters;



use App\Warehouse\Domain\Exceptions\PackingSlipGeneratorOperationException;
use App\Warehouse\Domain\Exporters\PackingSlipGeneratorInterface;
use App\Warehouse\Domain\Orders\OrderedItem;
use App\Warehouse\Domain\Shipments\PackingSlip;
use App\Warehouse\Infrastructure\ApiClients\PicqerApiClient;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Picqer\Api\Client;

class PicqerPackingSlipGenerator implements PackingSlipGeneratorInterface
{
    /**
     * The default filename for saving the picklist.
     * The %s contains the packing slip id.
     */
    public const DEFAULT_FILENAME = 'picqer/picklists/picklist_%s.pdf';

    protected Client $apiClient;
    protected Filesystem $filesystem;
    protected string $filename;

    public function __construct(PicqerApiClient $apiClient, Filesystem $filesystem, $filename = self::DEFAULT_FILENAME)
    {
        $this->apiClient = $apiClient->getClient();
        $this->filesystem = $filesystem;
        $this->filename = $filename;
    }

    public function generateFor(Collection $orderedItems): PackingSlip
    {
        /** @var OrderedItem $orderedItem */
        $orderedItem = $orderedItems->first(function (OrderedItem $orderedItem) {
            return $orderedItem->picklistId();
        });

        if(!$orderedItem) {
            throw new PackingSlipGeneratorOperationException("No order item found for packing slip");
        }

        $picklistId = $orderedItem->picklistId();
        $result = $this->apiClient->getPackinglistPdf($picklistId);

        if ($result["success"] === false)
        {
            throw new PackingSlipGeneratorOperationException("Failed getting packing slip for picklist with id " . $picklistId);
        }

        $pdfContent = base64_decode($result['data']['pdf']);

        // Save the picklist to disk
        $filename = sprintf($this->filename, (string)$picklistId);

        $this->filesystem->put($filename, $pdfContent);
        $path = $this->filesystem->path($filename);
        $url = URL::signedRoute('stream-picklist', [
            'picklistId' => $picklistId
        ]);

        return new PackingSlip($path, $url);
    }
}
