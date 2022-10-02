<?php


namespace App\Warehouse\Domain\Repositories;


use App\Warehouse\Domain\Parties\Customer;
use Illuminate\Support\Collection;

interface CustomerRepositoryInterface
{
    /**
     * @return Collection
     */
    public function all(): Collection;

    /**
     * @param string $customerNumber
     * @return Customer|null
     */
    public function searchByCustomerNumber(string $customerNumber): ?Customer;

    /**
     * @param string $email
     * @return Customer|null
     */
    public function searchByEmail(string $email): ?Customer;

    /**
     * @param Customer $customer
     * @return mixed
     */
    public function add(Customer $customer): Customer;

    /**
     * @param Customer $customer
     * @return mixed
     */
    public function update(Customer $customer): Customer;
}
