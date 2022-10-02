<?php

namespace App\Warehouse\Infrastructure\Services\ReviewRequestSenders\FeedbackCompany\Mappers;

use App\Warehouse\Domain\ReviewRequests\ReviewRequest;
use App\Warehouse\Infrastructure\Services\ReviewRequestSenders\FeedbackCompany\FeedbackCompanyCustomer;

class FeedbackCompanyCustomerMapper extends FeedbackCompanyCustomer
{
    public static function toFeedbackCompany(ReviewRequest $reviewRequest): FeedbackCompanyCustomer
    {
        $feedbackCompanyCustomer = new \ReflectionClass(FeedbackCompanyCustomer::class);
        $feedbackCompanyCustomer = $feedbackCompanyCustomer->newInstanceWithoutConstructor();
        $feedbackCompanyCustomer->email = $reviewRequest->customerEmail();
        $feedbackCompanyCustomer->fullname = $reviewRequest->customerName();

        return $feedbackCompanyCustomer;
    }

}
