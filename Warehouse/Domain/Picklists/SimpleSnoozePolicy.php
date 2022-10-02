<?php

namespace App\Warehouse\Domain\Picklists;

use Carbon\CarbonImmutable;

class SimpleSnoozePolicy implements SnoozePolicyInterface
{

    /**
     * @inheritDoc
     */
    public function calculateSnoozeUntil(CarbonImmutable $preferredDeliveryDate): CarbonImmutable
    {
        $daysToSubtract = $preferredDeliveryDate->isMonday() ? 2: 1;
        return $preferredDeliveryDate->subDays($daysToSubtract);
    }
}
