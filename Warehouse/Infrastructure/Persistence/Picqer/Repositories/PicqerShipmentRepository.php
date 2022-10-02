<?php

namespace App\Warehouse\Infrastructure\Persistence\Picqer\Repositories;

use App\Warehouse\Domain\Repositories\ShipmentRepositoryInterface;
use App\Warehouse\Domain\Shipments\Shipment;
use App\Warehouse\Infrastructure\ApiClients\PicqerApiClient;
use App\Warehouse\Infrastructure\Exceptions\PicqerShipmentMapperException;
use App\Warehouse\Infrastructure\Persistence\Eloquent\Shipments\EloquentShipment;
use App\Warehouse\Infrastructure\Persistence\Picqer\Shipments\PicqerShipmentMapper;
use App\Warehouse\Infrastructure\PicqerShipmentRepositoryOperationException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class PicqerShipmentRepository implements ShipmentRepositoryInterface
{
    private \Picqer\Api\Client $apiClient;

    /**
     * @param PicqerApiClient $apiClient
     */
    public function __construct(PicqerApiClient $apiClient)
    {
        $this->apiClient = $apiClient->getClient();
    }

    /**
     * @param string $orderReference
     * @return Collection
     * @throws PicqerShipmentRepositoryOperationException
     */
    public function findAllByOrderReference(string $orderReference): Collection
    {
        $result = $this->apiClient->getOrders([
            "reference" => $orderReference
        ]);

        if (! $result["success"])
        {
            throw new PicqerShipmentRepositoryOperationException("Failed getting order with reference " . $orderReference . ": " . $result["errormessage"]);
        }

        $picqerOrders = collect($result["data"]);

        $picqerOrder = $picqerOrders->first(function (array $picqerOrder) use ($orderReference) {
            return $picqerOrder["status"] !== "cancelled" && $picqerOrder["reference"] === $orderReference;
        });

        $idOrder = $picqerOrder["idorder"];

        $result = $this->apiClient->getPicklists([
            "idorder" => $idOrder
        ]);

        if (! $result["success"])
        {
            throw new PicqerShipmentRepositoryOperationException("Failed getting picklist for order with reference " . $orderReference . ": " . $result["errormessage"]);
        }

        $picqerPicklistIds = collect($result["data"])->filter(function ($picqerPicklist) use ($idOrder) {
            return ($picqerPicklist["idorder"] === $idOrder);
        })->map(function (array $picqerPicklist) {
            return $picqerPicklist["idpicklist"];
        });

        $picqerShipments = collect();

        $picqerPicklistIds->each(function ($picqerPicklistId) use ($picqerShipments) {
            $result = $this->apiClient->getShipments($picqerPicklistId);

            if (! $result["success"])
            {
                throw new PicqerShipmentRepositoryOperationException("Failed getting shipments for picklist with id " . $picqerPicklistId . ": " . $result["errormessage"]);
            }

            if (collect($result["data"])->isNotEmpty())
            {
                $picqerShipments->push($result["data"]);
            }
        });

        $picqerShipments = array_merge($picqerShipments->toArray());

        // TODO: Convert picqer shipments to domain shipments. Task 18668: Converteer Picqer Shipments naar domain objecten
        return collect($picqerShipments);
    }

    /**
     * @param string $reference
     * @return Shipment|null
     */
    public function findOneByReference(string $reference): ?Shipment
    {

    }

    /**
     * @throws PicqerShipmentRepositoryOperationException|PicqerShipmentMapperException
     */
    public function findOneByPicklistReference(string $picklistReference): ?Shipment
    {
        $apiResponse = $this->apiClient->getShipments($picklistReference);

        if (! Arr::get($apiResponse, 'success'))
        {
            throw new PicqerShipmentRepositoryOperationException('Failed getting shipment of picklist ' . $picklistReference . ' error: ' . Arr::get($apiResponse, 'errormessage'));
        }

        $picqerShipments = Arr::get($apiResponse, 'data');

        if (sizeof($picqerShipments) === 0)
        {
            return null;
        }

        $picqerShipment = collect($picqerShipments)->sortByDesc(function (array $picqerShipment) {
            return CarbonImmutable::parse(Arr::get($picqerShipment, 'created'));
        })->first();

        $idOrder = Arr::get($picqerShipment, 'idorder');

        if (! $idOrder)
        {
            throw new PicqerShipmentRepositoryOperationException('idorder cannot be null');
        }

        $apiResponse = $this->apiClient->getOrder($idOrder);

        if (! Arr::get($apiResponse, 'success'))
        {
            throw new PicqerShipmentRepositoryOperationException('Failed getting order with idorder ' . $idOrder . ', error: ' . Arr::get($apiResponse, 'errormessage'));
        }

        $picqerOrder = Arr::get($apiResponse, 'data');
        $orderReference = Arr::get($picqerOrder, 'reference');

        if (! $orderReference)
        {
            throw new PicqerShipmentRepositoryOperationException('Order reference should be specified on Picqer order');
        }

        $picqerShipment['order_reference'] = $orderReference;

        $idPicklist = Arr::get($picqerShipment, 'idpicklist');

        if (! $idPicklist)
        {
            throw new PicqerShipmentRepositoryOperationException('idpicklist should be specified');
        }

        $apiResponse = $this->apiClient->getPicklist($idPicklist);

        if (! Arr::get($apiResponse, 'success'))
        {
            throw new PicqerShipmentRepositoryOperationException('Failed getting picklist with idpicklist ' . $idPicklist . ', error: ' . Arr::get($apiResponse, 'errormessage'));
        }

        $picqerPicklist = Arr::get($apiResponse, 'data');
        $picqerOrderLines = Arr::get($picqerPicklist, 'products');

        $picqerOrderLines = collect($picqerOrderLines)->map(function (array $picqerOrderLine) use ($idPicklist) {
            $picqerOrderLine['idpicklist'] = $idPicklist;
            return $picqerOrderLine;
        })->toArray();

        $picqerShipment['products'] = $picqerOrderLines;
        $picqerShipment['preferred_delivery_date'] = Arr::get($picqerOrder, 'preferred_delivery_date');

        $eloquentShipment = EloquentShipment::query()->where('order_reference', $orderReference)->first();

        if (! $eloquentShipment)
        {
            $picqerShipment['track_and_trace_mail_sent'] = false;
        } else {
            $picqerShipment['track_and_trace_mail_sent'] = $eloquentShipment->track_and_trace_mail_sent;
        }

        $picqerOrderFields = Arr::get($picqerOrder, 'orderfields');
        $orderFieldDeliveryMethod = collect($picqerOrderFields)->filter(function (array $picqerOrderField) {
            return Arr::get($picqerOrderField, 'title') == 'Bezorgoptie';
        })->first();

        $deliveryMethod = Arr::get($orderFieldDeliveryMethod, 'value');
        $picqerShipment['delivery_method'] = $deliveryMethod;

        return PicqerShipmentMapper::toEntity($picqerShipment);
    }

    public function save(Shipment $shipment): void
    {
        $eloquentShipment = EloquentShipment::query()->where('reference', $shipment->reference())->first();

        if (! $eloquentShipment)
        {
            $eloquentShipment = new EloquentShipment();
        }

        $eloquentShipment->reference = $shipment->reference();
        $eloquentShipment->order_reference = $shipment->orderReference();
        $eloquentShipment->track_and_trace_mail_sent = $shipment->trackAndTraceMailSent();
        $eloquentShipment->save();
    }

    public function findOneByOrderReference(string $orderReference): ?Shipment
    {
        // TODO: Implement findOneByOrderReference() method.
    }
}
