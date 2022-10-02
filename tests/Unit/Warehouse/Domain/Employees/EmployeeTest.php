<?php

namespace Tests\Unit\Warehouse\Domain\Employees;

use App\Warehouse\Domain\Employees\Employee;
use Tests\TestCase;

class EmployeeTest extends TestCase
{

    /** @test */
    public function it_should_return_full_name()
    {
        // Given
        $firstName = 'Test First Name';
        $lastName = 'Test Last Name';

        $employee = new Employee($firstName, $lastName);

        // When
        $fullName = $employee->name();

        // Then
        self::assertEquals("$firstName $lastName", $fullName);
    }
}
