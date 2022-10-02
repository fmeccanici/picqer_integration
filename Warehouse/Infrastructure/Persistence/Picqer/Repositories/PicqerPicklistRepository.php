<?php


namespace App\Warehouse\Infrastructure\Persistence\Picqer\Repositories;


use App\Warehouse\Domain\Exceptions\PicklistNotFoundException;
use App\Warehouse\Domain\Picklists\Picklist;
use App\Warehouse\Infrastructure\ApiClients\PicqerApiClient;
use App\Warehouse\Infrastructure\Exceptions\PicqerPicklistMapperException;
use App\Warehouse\Infrastructure\Exceptions\PicqerPicklistRepositoryOperationException;
use App\Warehouse\Infrastructure\Persistence\Picqer\Picklists\Mappers\PicklistMapper;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class PicqerPicklistRepository implements \App\Warehouse\Domain\Repositories\PicklistRepositoryInterface
{
    private \Picqer\Api\Client $apiClient;

    public function __construct(PicqerApiClient $apiClient)
    {
        $this->apiClient = $apiClient->getClient();
    }

    /**
     * @inheritDoc
     */
    public function add(Picklist $picklist): Picklist
    {
        // TODO: Implement add() method.
    }

    /**
     * @inheritDoc
     * @param string $id
     * @return Picklist|null
     * @throws PicklistNotFoundException
     * @throws PicqerPicklistMapperException
     * @throws PicqerPicklistRepositoryOperationException
     */
    public function find(string $id): ?Picklist
    {
        $result = $this->apiClient->getPicklist($id);

        if (! $result["success"])
        {
            throw new PicqerPicklistRepositoryOperationException("Failed fetching picklist with id ".(string)$id);
        }

        $picqerPicklist = $result["data"];

        if (collect($picqerPicklist)->isEmpty())
        {
            return null;
        }

        $result = $this->apiClient->getOrder($picqerPicklist["idorder"]);

        if (! $result["success"])
        {
            throw new PicqerPicklistRepositoryOperationException("Failed fetching order: ".$result["errormessage"]);
        }

        $picqerOrder = $result["data"];

        return PicklistMapper::toEntity($picqerPicklist, $picqerOrder);
    }

    /**
     * @inheritDoc
     * @throws PicqerPicklistRepositoryOperationException
     */
    public function update(Picklist $picklist): Picklist
    {
        $snoozeUntil = $picklist->snoozedUntil() ? $picklist->snoozedUntil()->format(config('picqer.datetime_format')) : null;
        $result = $this->apiClient->snoozePicklist($picklist->id(), $snoozeUntil);

        if (! $result["success"])
        {
            throw new PicqerPicklistRepositoryOperationException("Failed snoozing picklist with reference ".$picklist->reference().": ".$result["errormessage"]);
        }

        $updatePicklistResponse = $this->apiClient->updatePicklist($picklist->id(), [
            'urgent' => $picklist->urgent()
        ]);

        if (! Arr::get($updatePicklistResponse, 'success'))
        {
            throw new PicqerPicklistRepositoryOperationException("Failed updating picklist with reference ".$picklist->reference().": ".$result["errormessage"]);
        }

        return $picklist;
    }

    /**
     * @inheritDoc
     * @throws PicqerPicklistRepositoryOperationException|PicqerPicklistMapperException
     */
    public function findByReference(string $picklistReference): ?Picklist
    {
        $result = $this->apiClient->getPicklistByPicklistid($picklistReference);

        if (! $result["success"])
        {
            throw new PicqerPicklistRepositoryOperationException("Failed fetching picklist: ".$result["errormessage"]);
        }

        $picqerPicklist = $result["data"];

        // Needed because there is a delay in when the we can get the picklist by picklistid.
        // In PicqerOrderRepository we load the picklist via getPicklists(), then we have the reference of the picklist.
        // When immediately calling getPicklistByPicklistid afterwards it is empty. So we need to wait for 1s.
        if (collect($picqerPicklist)->isEmpty())
        {
            sleep(1);
            $result = $this->apiClient->getPicklistByPicklistid($picklistReference);
            $picqerPicklist = $result["data"];
        }

        if (collect($picqerPicklist)->isEmpty())
        {
            throw new PicqerPicklistRepositoryOperationException('Picklist with reference ' . $picklistReference . ' not found');
        }

        $result = $this->apiClient->getOrder($picqerPicklist["idorder"]);

        if (! $result["success"])
        {
            throw new PicqerPicklistRepositoryOperationException("Failed fetching order: ".$result["errormessage"]);
        }

        $picqerOrder = $result["data"];

        return PicklistMapper::toEntity($picqerPicklist, $picqerOrder);
    }

    public function findByOrderReference(string $orderReference): ?Picklist
    {
        // TODO: Implement findByOrderReference() method.
    }

    public function findAll(): Collection
    {
        // TODO: Implement findAll() method.
    }

    /**
     * @throws PicqerPicklistRepositoryOperationException|PicqerPicklistMapperException
     */
    public function findOneById(string $id): ?Picklist
    {
        $apiResponse = $this->apiClient->getPicklist($id);

        if (! Arr::get($apiResponse, 'success'))
        {
            throw new PicqerPicklistRepositoryOperationException("Failed getting picklist with id " . $id . ": ".$apiResponse["errormessage"]);
        }

        $picqerPicklist = Arr::get($apiResponse, 'data');

        $result = $this->apiClient->getOrder($picqerPicklist["idorder"]);

        if (! $result["success"])
        {
            throw new PicqerPicklistRepositoryOperationException("Failed fetching order: ".$result["errormessage"]);
        }

        $picqerOrder = $result["data"];

        return PicklistMapper::toEntity($picqerPicklist, $picqerOrder);
    }
}
