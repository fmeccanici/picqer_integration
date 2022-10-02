<?php

namespace App\Warehouse\Domain\Shipments;

use App\SharedKernel\CleanArchitecture\ValueObject;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;

class TrackAndTrace extends ValueObject implements Arrayable
{
    protected ?string $code;
    protected ?string $url;

    /**
     * @param string|null $code
     * @param string|null $url
     */
    public function __construct(?string $code, ?string $url)
    {
        $this->code = $code;
        $this->url = $url;
    }

    public function code(): ?string
    {
        return $this->code;
    }

    public function url(): ?string
    {
        return $this->url;
    }

    public function changeUrl(string $url)
    {
        $this->url = $url;
    }

    public function changeCode(string $code)
    {
        $this->code = $code;
    }

    public function toArray()
    {
        return [
            'code' => $this->code,
            'url' => $this->url
        ];
    }

    public static function fromArray(array $trackAndTrace): TrackAndTrace
    {
        $code = Arr::get($trackAndTrace, 'code');
        $url = Arr::get($trackAndTrace, 'url');

        return new self($code, $url);
    }
}
