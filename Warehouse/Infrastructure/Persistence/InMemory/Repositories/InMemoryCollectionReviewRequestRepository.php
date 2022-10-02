<?php

namespace App\Warehouse\Infrastructure\Persistence\InMemory\Repositories;

use App\Warehouse\Domain\ReviewRequests\ReviewRequest;

class InMemoryCollectionReviewRequestRepository implements \App\Warehouse\Domain\Repositories\ReviewRequestRepositoryInterface
{
    private \Illuminate\Support\Collection $reviewRequests;

    public function __construct()
    {
        $this->reviewRequests = collect();
    }

    /**
     * @inheritDoc
     */
    public function save(ReviewRequest $reviewRequest): void
    {
        $this->reviewRequests->push($reviewRequest);
    }

    /**
     * @param string $orderReference
     * @return ReviewRequest|null
     */
    public function findOneByOrderReference(string $orderReference): ?ReviewRequest
    {
        return $this->reviewRequests->first(function (ReviewRequest $reviewRequest) use ($orderReference) {
            return $orderReference == $reviewRequest->orderReference();
        });
    }
}
