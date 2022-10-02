<?php

namespace App\Warehouse\Domain\Picklists;

use Carbon\CarbonImmutable;

interface SnoozePolicyInterface
{
    /**
     * @param CarbonImmutable $preferredDeliveryDate
     * @return CarbonImmutable
     */
    public function calculateSnoozeUntil(CarbonImmutable $preferredDeliveryDate): CarbonImmutable;
}
