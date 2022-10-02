<?php


namespace Tests\Feature\Warehouse;


use App\Warehouse\Domain\Parties\Customer;
use Illuminate\Support\Collection;

class DummyCustomerRepository implements \App\Warehouse\Domain\Repositories\CustomerRepositoryInterface
{

    /**
     * @var Customer[]
     */
    private array $customers;

    public function __construct()
    {
        $this->customers = [];
    }

    /**
     * @inheritDoc
     */
    public function all(): Collection
    {
        return collect($this->customers);
    }

    /**
     * @inheritDoc
     */
    public function searchByCustomerNumber(string $customerNumber): ?Customer
    {
        foreach ($this->customers as $customer)
        {
            if ($customer->id() == $customerNumber)
            {
                return $customer;
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function searchByEmail(string $email): ?Customer
    {
        foreach ($this->customers as $customer)
        {
            if ($customer->email() == $email)
            {
                return $customer;
            }
        }

        return null;
    }

    public function add(Customer $customer): Customer
    {
        $this->customers[] = $customer;

        return $customer;
    }

    public function delete(string $email): void
    {
        $result = [];

        foreach ($this->customers as $customer)
        {
            if ($customer->email() !== $email)
            {
                $result[] = $customer;
            }
        }

        $this->customers = $result;
    }

    public function update(Customer $customer): Customer
    {
        $this->delete($customer->email());
        $this->add($customer);

        return $customer;
    }
}
