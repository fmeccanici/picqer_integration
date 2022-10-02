<?php

namespace App\Warehouse\Domain\Orders;

abstract class OrderState
{
    public const NEW = 'new';
    public const PROCESSING = 'processing';
    public const COMPLETED = 'completed';
    public const COMPLETED_BY_PICQER = 'completed_by_picqer';
    public const COMPLETED_BY_DELIGHT = 'completed_by_delight';
    public const COMPLETED_BINNEN_SPECIALIST = 'completed_binnen_specialist';
    public const COMPLETED_BY_DELIGHT_BINNEN_SPECIALIST = 'completed_by_delight_binnen_specialist';
    public const COMPLETED_BY_PICQER_BINNEN_SPECIALIST = 'completed_by_picqer_binnen_specialist';
    public const DENIED = 'denied';
    public const UNKNOWN = 'onbekend';
}
