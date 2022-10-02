<?php

namespace App\Warehouse\Infrastructure\Persistence\Eloquent\Repositories;

use App\Warehouse\Domain\ReviewRequests\ReviewRequest;
use App\Warehouse\Infrastructure\Persistence\Eloquent\ReviewRequests\EloquentReviewRequest;
use App\Warehouse\Infrastructure\Persistence\Eloquent\ReviewRequests\Mappers\EloquentReviewRequestMapper;

class EloquentReviewRequestRepository implements \App\Warehouse\Domain\Repositories\ReviewRequestRepositoryInterface
{

    /**
     * @inheritDoc
     */
    public function save(ReviewRequest $reviewRequest): void
    {
        $model = EloquentReviewRequest::query()
                                        ->where('id', $reviewRequest->identity())
                                        ->take(1)
                                        ->get()
                                        ->first();

        EloquentReviewRequestMapper::pruneModel($reviewRequest, $model);
        EloquentReviewRequestMapper::createOrUpdateModel($reviewRequest, $model);
    }

    /**
     * @param string $orderReference
     * @return ReviewRequest|null
     * @throws \Exception
     */
    public function findOneByOrderReference(string $orderReference): ?ReviewRequest
    {
        $model = EloquentReviewRequest::query()
            ->where("order_reference", $orderReference)
            ->take(1)
            ->get()
            ->first();

        return EloquentReviewRequestMapper::reconstituteEntity($model);
    }
}
