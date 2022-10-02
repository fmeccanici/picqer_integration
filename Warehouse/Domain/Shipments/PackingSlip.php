<?php


namespace App\Warehouse\Domain\Shipments;


use App\SharedKernel\CleanArchitecture\ValueObject;

class PackingSlip extends ValueObject
{
    protected string $path;
    protected string $url;

    /**
     * PackingList constructor.
     * @param string $path
     * @param string $url
     */
    public function __construct(string $path, string $url)
    {
        $this->path = $path;
        $this->url = $url;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function url(): string
    {
        return $this->url;
    }
}
