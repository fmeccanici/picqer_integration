<?php

namespace App\Warehouse\Infrastructure\Persistence\FeedbackCompany\ReviewInvitations\Mappers;

use App\Warehouse\Domain\ReviewRequests\ReviewRequest;
use App\Warehouse\Infrastructure\Services\ReviewRequestSenders\FeedbackCompany\FeedbackCompanyReviewRequest;
use App\Warehouse\Infrastructure\Services\ReviewRequestSenders\FeedbackCompany\Mappers\FeedbackCompanyCustomerMapper;
use App\Warehouse\Infrastructure\Services\ReviewRequestSenders\FeedbackCompany\Mappers\FeedbackCompanyInvitationMapper;

class FeedbackCompanyReviewRequestMapper extends FeedbackCompanyReviewRequest
{
    public static function toFeedbackCompany(ReviewRequest $reviewRequest): FeedbackCompanyReviewRequest
    {
        $feedbackCompanyReviewRequest = new \ReflectionClass(FeedbackCompanyReviewRequest::class);
        $feedbackCompanyReviewRequest = $feedbackCompanyReviewRequest->newInstanceWithoutConstructor();

        $feedbackCompanyReviewRequest->externalId = $reviewRequest->orderReference();
        $feedbackCompanyReviewRequest->customer = FeedbackCompanyCustomerMapper::toFeedbackCompany($reviewRequest);
        $feedbackCompanyReviewRequest->invitation = FeedbackCompanyInvitationMapper::toFeedbackCompany($reviewRequest);

        // TODO: Task 19076: Implementeer FeedbackCompanyReviewRequestMapper voor products en filter code
        $products = null;
        $filterCode = null;

        return $feedbackCompanyReviewRequest;
    }
}
