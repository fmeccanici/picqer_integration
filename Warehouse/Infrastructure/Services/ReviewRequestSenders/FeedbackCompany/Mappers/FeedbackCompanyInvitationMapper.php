<?php

namespace App\Warehouse\Infrastructure\Services\ReviewRequestSenders\FeedbackCompany\Mappers;

use App\Warehouse\Domain\ReviewRequests\ReviewRequest;
use App\Warehouse\Infrastructure\Services\ReviewRequestSenders\FeedbackCompany\FeedbackCompanyInvitation;

class FeedbackCompanyInvitationMapper extends FeedbackCompanyInvitation
{
    public static function toFeedbackCompany(ReviewRequest $reviewRequest): FeedbackCompanyInvitation
    {
        $delay = FeedbackCompanyInvitationDelayMapper::toFeedbackCompany($reviewRequest);
        $reminder = FeedbackCompanyInvitationReminderMapper::toFeedbackCompany($reviewRequest);

        $feedbackCompanyInvitation = new \ReflectionClass(FeedbackCompanyInvitation::class);
        $feedbackCompanyInvitation = $feedbackCompanyInvitation->newInstanceWithoutConstructor();
        $feedbackCompanyInvitation->delay = $delay;
        $feedbackCompanyInvitation->reminder = $reminder;
        return $feedbackCompanyInvitation;
    }
}
