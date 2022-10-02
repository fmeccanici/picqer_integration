<?php

namespace Tests\Unit\Warehouse\Domain\Shipments;

use App\Warehouse\Domain\Shipments\ShipmentFactory;
use App\Warehouse\Domain\Shipments\TrackAndTrace;
use Tests\TestCase;

class ShipmentTest extends TestCase
{
    /** @test */
    public function it_should_return_the_track_and_trace()
    {
        // Given
        $trackAndTraceCode = 'Test Track And Trace Code';
        $trackAndTraceUrl = 'test-track-and-trace-url';
        $trackAndTrace = new TrackAndTrace($trackAndTraceCode, $trackAndTraceUrl);

        $shipment = ShipmentFactory::create("Test Order Reference", [
            'trackAndTrace' => $trackAndTrace
        ]);

        // When
        $returnedTrackAndTrace = $shipment->trackAndTrace();

        // Then
        self::assertEquals($trackAndTrace, $returnedTrackAndTrace);
    }
}
