<?php

namespace App\Warehouse\Infrastructure\Persistence\InMemory\Repositories;

use App\Warehouse\Domain\Employees\Employee;
use App\Warehouse\Domain\Repositories\EmployeeRepositoryInterface;
use Illuminate\Support\Collection;

class InMemoryCollectionEmployeeRepository implements EmployeeRepositoryInterface
{
    protected Collection $employees;

    public function __construct()
    {
        $this->employees = collect();
    }

    public function findOneById(string $id): ?Employee
    {
        return $this->employees->first(function (Employee $employee) use ($id) {
            return $employee->identity() == $id;
        });
    }

    public function save(Employee $employee): void
    {
        $this->employees->push($employee);
    }

    public function saveMany(Collection $employees): void
    {
        $this->employees = $this->employees->merge($employees);
    }
}
