<?php

namespace App\Warehouse\Infrastructure\Services\ReviewRequestSenders\FeedbackCompany;

use Illuminate\Contracts\Support\Arrayable;

class FeedbackCompanyInvitation implements Arrayable
{
    protected FeedbackCompanyInvitationDelay $delay;
    protected FeedbackCompanyInvitationDelay $reminder;

    /**
     * @param FeedbackCompanyInvitationDelay $delay
     * @param FeedbackCompanyInvitationDelay $reminder
     */
    public function __construct(FeedbackCompanyInvitationDelay $delay, FeedbackCompanyInvitationDelay $reminder)
    {
        $this->delay = $delay;
        $this->reminder = $reminder;
    }

    public function toArray()
    {
        return [
            'delay' => $this->delay->toArray(),
            'reminder' => $this->reminder->toArray()
        ];
    }
}
