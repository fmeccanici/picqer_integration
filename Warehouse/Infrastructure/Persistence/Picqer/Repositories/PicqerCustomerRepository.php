<?php


namespace App\Warehouse\Infrastructure\Persistence\Picqer\Repositories;


use App\Warehouse\Domain\Parties\Customer;
use App\Warehouse\Infrastructure\ApiClients\PicqerApiClient;
use App\Warehouse\Infrastructure\Exceptions\PicqerCustomerRepositoryOperationException;
use App\Warehouse\Infrastructure\Persistence\Picqer\Customers\CustomerMapper;
use Illuminate\Support\Collection;
use Picqer\Api\Client;

class PicqerCustomerRepository implements \App\Warehouse\Domain\Repositories\CustomerRepositoryInterface
{
    private \Picqer\Api\Client $apiClient;

    public function __construct(PicqerApiClient $picqerApiClient)
    {
        $this->apiClient = $picqerApiClient->getClient();
    }

    public function add(Customer $customer): Customer
    {
        $picqerCustomer = CustomerMapper::toPicqer($customer);
        $result = $this->apiClient->addCustomer($picqerCustomer);

        if ($result["success"] === false)
        {
            throw new PicqerCustomerRepositoryOperationException("Failed adding customer: ".$result["errormessage"]);
        }

        $picqerCustomer = $result["data"];

        return CustomerMapper::toEntity($picqerCustomer);
    }

    public function searchByEmail(string $email): ?Customer
    {
        $apiResult = $this->apiClient->getAllCustomers();

        if ($apiResult["success"] === false)
        {
            throw new PicqerCustomerRepositoryOperationException("Failure in fetching all the customer".$apiResult["errormessage"]);
        }

        $customer = collect($apiResult["data"])->first(function (array $customer) use ($email) {
            return $customer["emailaddress"] === $email;
        });

        return $customer ? CustomerMapper::toEntity($customer) : null;
    }

    public function updateWhenDifferent(Customer $customer): Customer
    {
        $picqerCustomer = CustomerMapper::toPicqer($customer);
        $this->apiClient->updateCustomer($customer->id(), $picqerCustomer);
        return $customer;
    }

    public function all(): Collection
    {
        $result = $this->apiClient->getAllCustomers();

        if ($result["success"] === false)
        {
            throw new PicqerCustomerRepositoryOperationException("Failed fetching all customers");
        }

        $picqerCustomers = $result["data"];

        return collect($picqerCustomers)->map(function (array $picqerCustomer) {
                return CustomerMapper::toEntity($picqerCustomer);
        });
    }

    public function search(string $customerNumber): ?Customer
    {
        $response = $this->apiClient->getAllCustomers();

        if ($response["success"] === false)
        {
            throw new PicqerCustomerRepositoryOperationException("Failed fetching all customers");
        }

        $picqerCustomers = collect($response["data"]);

        $picqerCustomer = $picqerCustomers->first(function (array $picqerCustomer) use ($customerNumber) {
            return $picqerCustomer["idcustomer"] === $customerNumber;
        });

        return $picqerCustomer;
    }

    public function searchByCustomerNumber(string $customerNumber): ?Customer
    {
        return $this->all()->first(function (Customer $customer) use ($customerNumber) {
                return $customer->customerNumber() === $customerNumber;
        });
    }

    public function update(Customer $customer): Customer
    {
        $picqerCustomer = CustomerMapper::toPicqer($customer);
        $this->apiClient->updateCustomer($customer->id(), $picqerCustomer);
        return $customer;
    }
}
