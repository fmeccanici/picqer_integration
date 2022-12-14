<?php

namespace Tests\Unit\Warehouse\Infrastructure\Persistence\Eloquent\Repositories;

use App\Warehouse\Domain\ReviewRequests\ReviewRequest;
use App\Warehouse\Infrastructure\Persistence\Eloquent\Repositories\EloquentReviewRequestRepository;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class EloquentReviewRequestRepositoryTest extends TestCase
{
    use DatabaseMigrations;

    private EloquentReviewRequestRepository $reviewRequestRepository;

    protected function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub

        $this->reviewRequestRepository = new EloquentReviewRequestRepository();
    }

    /** @test */
    public function it_should_return_one_review_request()
    {
        // Given
        $orderReference = 'Test Order Reference';
        $customerName = 'Test Customer Name';
        $customerEmail = 'Test Customer Email';
        $reviewRequest = new ReviewRequest($orderReference, $customerName, $customerEmail);

        // When
        $this->reviewRequestRepository->save($reviewRequest);

        // Then
        $foundReviewRequest = $this->reviewRequestRepository->findOneByOrderReference($orderReference);
        self::assertEquals($reviewRequest, $foundReviewRequest);
    }
}
