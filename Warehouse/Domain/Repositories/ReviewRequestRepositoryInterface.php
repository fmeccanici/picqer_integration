<?php

namespace App\Warehouse\Domain\Repositories;

use App\Warehouse\Domain\ReviewRequests\ReviewRequest;

interface ReviewRequestRepositoryInterface
{
    /**
     * @param ReviewRequest $reviewRequest
     * @return void
     */
    public function save(ReviewRequest $reviewRequest): void;

    public function findOneByOrderReference(string $orderReference): ?ReviewRequest;
}
