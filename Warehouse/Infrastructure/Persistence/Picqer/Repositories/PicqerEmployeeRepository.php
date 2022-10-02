<?php

namespace App\Warehouse\Infrastructure\Persistence\Picqer\Repositories;

use App\Warehouse\Domain\Employees\Employee;
use App\Warehouse\Infrastructure\ApiClients\PicqerApiClient;
use App\Warehouse\Infrastructure\Exceptions\PicqerEmployeeRepositoryOperationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class PicqerEmployeeRepository implements \App\Warehouse\Domain\Repositories\EmployeeRepositoryInterface
{
    private \Picqer\Api\Client $apiClient;

    public function __construct(PicqerApiClient $picqerApiClient)
    {
        $this->apiClient = $picqerApiClient->getClient();
    }

    /**
     * @throws PicqerEmployeeRepositoryOperationException
     */
    public function findOneById(string $id): ?Employee
    {
        $apiResponse = $this->apiClient->getUser($id);

        if (! Arr::get($apiResponse, 'success'))
        {
            $error = json_decode(Arr::get($apiResponse, 'errormessage'), true);
            $errorMessage = Arr::get($error, 'error_message');
            $errorCode = Arr::get($error, 'error_code');

            if ($errorCode === 0)
            {
                return null;
            }

            throw new PicqerEmployeeRepositoryOperationException('Failed getting user with id ' . $id . ', error: ' . $errorMessage);
        }

        $picqerUser = Arr::get($apiResponse, 'data');
        $firstName = Arr::get($picqerUser, 'firstname');
        $lastName = Arr::get($picqerUser, 'lastname');

        $employee = new Employee($firstName, $lastName);
        $employee->setIdentity($id);
        return $employee;
    }

    public function save(Employee $employee): void
    {
        // TODO: Implement save() method.
    }

    public function saveMany(Collection $employees): void
    {
        // TODO: Implement saveMany() method.
    }
}
