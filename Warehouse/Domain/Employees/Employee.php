<?php

namespace App\Warehouse\Domain\Employees;

use App\SharedKernel\CleanArchitecture\AggregateRoot;

class Employee extends AggregateRoot
{
    protected string $firstName;
    protected string $lastName;

    /**
     * @param string $firstName
     * @param string $lastName
     */
    public function __construct(string $firstName, string $lastName)
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
    }

    public function name(): string
    {
        return "$this->firstName $this->lastName";
    }

    protected function cascadeSetIdentity(int|string $id): void
    {
        // Nothing to do
    }
}
