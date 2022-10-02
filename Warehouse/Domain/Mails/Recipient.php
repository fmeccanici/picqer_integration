<?php


namespace App\Warehouse\Domain\Mails;

use App\SharedKernel\CleanArchitecture\ValueObject;

class Recipient extends ValueObject
{
    protected string $name;
    protected string $email;
    private ?string $firstName;
    private ?string $lastName;

    public function __construct(string $name, string $email, ?string $firstName, ?string $lastName)
    {
        $this->name = $name;
        $this->email = $email;
        $this->firstName = $firstName;
        $this->lastName = $lastName;

        if(!$this->firstName && !$this->lastName) {
            $this->splitNameIntoFirstAndLastName($name);
        }
    }

    public function name(): string
    {
        return $this->name;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function firstName(): ?string
    {
        return $this->firstName;
    }

    public function lastName(): ?string
    {
        return $this->lastName;
    }

    private function splitNameIntoFirstAndLastName(string $name): void
    {
        $nameParts = explode(" ", $name);
        if(count($nameParts) > 1) {
            $this->firstName = array_shift($nameParts);
            $this->lastName = implode(" ", $nameParts);
        }
        else
        {
            $this->firstName = $name;
        }
    }
}
