<?php

namespace App\Warehouse\Domain\Orders;

use App\SharedKernel\CleanArchitecture\ValueObject;
use Carbon\CarbonImmutable;

class Action extends ValueObject
{
    protected string $description;
    protected CarbonImmutable $createdAt;
    protected string $actor;

    /**
     * @param string $description
     * @param CarbonImmutable $createdAt
     * @param string $actor
     */
    public function __construct(string $description, CarbonImmutable $createdAt, string $actor)
    {
        $this->description = $description;
        $this->createdAt = $createdAt;
        $this->actor = $actor;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function createdAt(): CarbonImmutable
    {
        return $this->createdAt;
    }

    public function actor(): string
    {
        return $this->actor;
    }
}
