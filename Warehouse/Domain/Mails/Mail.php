<?php


namespace App\Warehouse\Domain\Mails;

use App\SharedKernel\CleanArchitecture\ValueObject;

abstract class Mail extends ValueObject
{
    private Recipient $recipient;

    public function __construct(Recipient $recipient)
    {
        $this->recipient = $recipient;
    }

    public function recipient(): Recipient
    {
        return $this->recipient;
    }

    public function flow(): string
    {
        return static::FLOW_NAME;
    }

    abstract function subject(): string;

    abstract function data(): array;
}
