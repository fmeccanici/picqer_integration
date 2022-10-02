<?php

namespace App\Warehouse\Infrastructure\Services\ReviewRequestSenders\FeedbackCompany;

use Illuminate\Contracts\Support\Arrayable;

class FeedbackCompanyInvitationDelay implements Arrayable
{
    const ALLOWED_UNITS = ['minutes', 'hours', 'days', 'weekdays'];

    protected string $unit;
    protected int $amount;

    /**
     * @param string $unit
     * @param int $amount
     */
    public function __construct(string $unit, int $amount)
    {
        $this->unit = $unit;
        $this->amount = $amount;
    }

    /**
     * @throws FeedbackCompanyInvitationDelayException
     */
    private function validate(string $unit, int $amount)
    {
        if (! in_array($unit, self::ALLOWED_UNITS))
        {
            throw new FeedbackCompanyInvitationDelayException('Unit ' . $unit . ' not valid');
        }

        if ($amount < 0)
        {
            throw new FeedbackCompanyInvitationDelayException('Amount should be non-negative');
        }
    }

    public function toArray()
    {
        return [
            'unit' => $this->unit,
            'amount' => $this->amount
        ];
    }
}
