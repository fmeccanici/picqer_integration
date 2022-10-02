<?php

namespace App\Warehouse\Domain\Repositories;

use App\Warehouse\Domain\Employees\Employee;
use Illuminate\Support\Collection;

interface EmployeeRepositoryInterface
{
    public function findOneById(string $id): ?Employee;
    public function save(Employee $employee): void;
    public function saveMany(Collection $employees): void;
}
