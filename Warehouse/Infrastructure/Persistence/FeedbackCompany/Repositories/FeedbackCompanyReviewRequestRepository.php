<?php

namespace App\Warehouse\Infrastructure\Persistence\FeedbackCompany\Repositories;

use App\Warehouse\Domain\Repositories\ReviewRequestRepositoryInterface;
use App\Warehouse\Domain\ReviewRequests\ReviewRequest;
use App\Warehouse\Infrastructure\ApiClients\FeedbackCompanyApiClient;
use App\Warehouse\Infrastructure\Exceptions\FeedbackCompanyApiClientException;
use App\Warehouse\Infrastructure\Exceptions\FeedbackCompanyReviewRequestRepositoryException;
use App\Warehouse\Infrastructure\Persistence\FeedbackCompany\ReviewInvitations\Mappers\FeedbackCompanyReviewRequestMapper;
use Carbon\CarbonImmutable;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class FeedbackCompanyReviewRequestRepository implements ReviewRequestRepositoryInterface
{
    private FeedbackCompanyApiClient $feedbackCompanyApiClient;

    public function __construct(FeedbackCompanyApiClient $apiClient)
    {
        $this->feedbackCompanyApiClient = $apiClient->getClient();
    }

    /**
     * @throws FeedbackCompanyApiClientException
     * @throws GuzzleException
     */
    public function save(ReviewRequest $reviewRequest): void
    {
        $feedbackCompanyReviewInvite = FeedbackCompanyReviewRequestMapper::toFeedbackCompany($reviewRequest);
        $this->feedbackCompanyApiClient->getClient()->createOrder($feedbackCompanyReviewInvite->externalId(), $feedbackCompanyReviewInvite->customer(), $feedbackCompanyReviewInvite->invitation(), $feedbackCompanyReviewInvite->products());
    }

    public function findAllByOrderReference(): Collection
    {
        // TODO: Implement findAllByOrderReference() method.
    }

    /**
     * @throws FeedbackCompanyReviewRequestRepositoryException
     */
    public function findOneByOrderReference(string $orderReference): ?ReviewRequest
    {
        try {
            $apiResponse = $this->feedbackCompanyApiClient->listInvitations();

            if (! Arr::get($apiResponse, 'success'))
            {
                throw new FeedbackCompanyReviewRequestRepositoryException('Failed listing invitations');
            }

            $feedbackCompanyInvitations = Arr::get($apiResponse, 'invitations');

            if (! $feedbackCompanyInvitations)
            {
                throw new FeedbackCompanyReviewRequestRepositoryException('Invitations returned are null');
            }

            $feedbackCompanyInvitations = collect($feedbackCompanyInvitations);
            $latestFeedbackCompanyInvitation = $feedbackCompanyInvitations->filter(function (array $feedbackCompanyInvitation) {
                return Arr::get($feedbackCompanyInvitation, 'order_id');
            })->max(function (array $feedbackCompanyInvitation) {
                $sendAt = Arr::get($feedbackCompanyInvitation, 'send_at');
                if (! $sendAt)
                {
                    return false;
                }
                return CarbonImmutable::parse($sendAt);
            });



        } catch (FeedbackCompanyApiClientException|GuzzleException $e) {
            return null;
        }
    }
}
