<?php

namespace App\Warehouse\Infrastructure\Services\ReviewRequestSenders\FeedbackCompany;

use Illuminate\Contracts\Support\Arrayable;
use JetBrains\PhpStorm\ArrayShape;

class FeedbackCompanyCustomer implements Arrayable
{
    protected string $email;
    protected string $fullname;

    /**
     * @param string $email
     * @param string $fullname
     */
    public function __construct(string $email, string $fullname)
    {
        $this->email = $email;
        $this->fullname = $fullname;
    }


    #[ArrayShape(['email' => "string", 'fullname' => "string"])] public function toArray(): array
    {
        return [
            'email' => $this->email,
            'fullname' => $this->fullname
        ];
    }
}
