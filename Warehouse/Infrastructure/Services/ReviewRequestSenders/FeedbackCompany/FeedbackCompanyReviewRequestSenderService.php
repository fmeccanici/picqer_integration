<?php

namespace App\Warehouse\Infrastructure\Services\ReviewRequestSenders\FeedbackCompany;

use App\Warehouse\Domain\Orders\Order;
use App\Warehouse\Domain\Repositories\ReviewRequestRepositoryInterface;
use App\Warehouse\Domain\ReviewRequests\ReviewRequest;
use App\Warehouse\Domain\Services\ReviewRequestSenderServiceInterface;
use App\Warehouse\Infrastructure\ApiClients\FeedbackCompanyApiClient;
use App\Warehouse\Infrastructure\Exceptions\FeedbackCompanyApiClientException;
use App\Warehouse\Infrastructure\Persistence\FeedbackCompany\ReviewInvitations\Mappers\FeedbackCompanyReviewRequestMapper;
use GuzzleHttp\Exception\GuzzleException;

class FeedbackCompanyReviewRequestSenderService implements ReviewRequestSenderServiceInterface
{
    private FeedbackCompanyApiClient $feedbackCompanyApiClient;
    private ReviewRequestRepositoryInterface $reviewRequestRepository;

    public function __construct(FeedbackCompanyApiClient $apiClient, ReviewRequestRepositoryInterface $reviewRequestRepository)
    {
        $this->feedbackCompanyApiClient = $apiClient->getClient();
        $this->reviewRequestRepository = $reviewRequestRepository;
    }

    /**
     * @throws FeedbackCompanyApiClientException
     * @throws GuzzleException
     */
    public function send(Order $order): ReviewRequest
    {
        $reviewRequest = $this->reviewRequestRepository->findOneByOrderReference($order->reference());

        if (! $reviewRequest)
        {
            $reviewRequest = new ReviewRequest($order->reference(), $order->customer()->name(), $order->customer()->email(), $order->preferredDeliveryDate());
        }

        $this->createFeedbackCompanyOrder($reviewRequest);
        $reviewRequest->send();
        $this->reviewRequestRepository->save($reviewRequest);
        return $reviewRequest;
    }

    /**
     * @throws FeedbackCompanyApiClientException
     * @throws GuzzleException
     */
    private function createFeedbackCompanyOrder(ReviewRequest $reviewRequest)
    {
        $feedbackCompanyReviewRequest = FeedbackCompanyReviewRequestMapper::toFeedbackCompany($reviewRequest);
        $this->feedbackCompanyApiClient->getClient()->createOrder($feedbackCompanyReviewRequest->externalId(), $feedbackCompanyReviewRequest->customer(), $feedbackCompanyReviewRequest->invitation(), $feedbackCompanyReviewRequest->products());
    }

    public function isSent(Order $order): bool
    {
        return (bool) $this->reviewRequestRepository->findOneByOrderReference($order->reference());
    }
}
