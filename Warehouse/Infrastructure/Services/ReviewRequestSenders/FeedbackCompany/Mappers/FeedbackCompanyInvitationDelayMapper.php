<?php

namespace App\Warehouse\Infrastructure\Services\ReviewRequestSenders\FeedbackCompany\Mappers;

use App\Warehouse\Domain\ReviewRequests\ReviewRequest;
use App\Warehouse\Infrastructure\Services\ReviewRequestSenders\FeedbackCompany\FeedbackCompanyInvitationDelay;

class FeedbackCompanyInvitationDelayMapper extends FeedbackCompanyInvitationDelay
{
    public static function toFeedbackCompany(ReviewRequest $reviewRequest): FeedbackCompanyInvitationDelay
    {
        $delay = new \ReflectionClass(FeedbackCompanyInvitationDelay::class);
        $delay = $delay->newInstanceWithoutConstructor();

        $delay->unit = 'days';
        $delay->amount = $reviewRequest->delayInDays();

        return $delay;
    }
}
