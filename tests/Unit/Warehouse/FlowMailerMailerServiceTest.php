<?php


namespace Tests\Unit\Warehouse;

use App\Warehouse\Domain\Exporters\PackingSlipGeneratorInterface;
use App\Warehouse\Domain\Mails\MailFactory;
use App\Warehouse\Domain\Services\ReviewRequestSenderServiceInterface;
use App\Warehouse\Infrastructure\Mails\FlowMailerMailerService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\Feature\Warehouse\DummyPackingSlipGenerator;
use Tests\Feature\Warehouse\DummyReviewRequestSenderService;
use Tests\TestCase;

class FlowMailerMailerServiceTest extends TestCase
{
    use DatabaseMigrations;

    private FlowMailerMailerService $mailerService;

    protected function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub

        $this->mailerService = new FlowMailerMailerService();
        $this->app->bind(PackingSlipGeneratorInterface::class, DummyPackingSlipGenerator::class);
        $this->app->bind(ReviewRequestSenderServiceInterface::class, DummyReviewRequestSenderService::class);

    }

    /** @test */
    public function it_should_send_the_track_and_trace_email()
    {
        // Given
        $mail = MailFactory::constantTrackAndTrace();
        $fromEmailAddress = "info@homedesignshops.nl";

        // When
        $sentMail = $this->mailerService->send($mail, $fromEmailAddress);

        // Then
        self::assertTrue($sentMail);
    }
}
