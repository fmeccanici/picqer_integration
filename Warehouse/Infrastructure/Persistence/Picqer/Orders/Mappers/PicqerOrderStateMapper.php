<?php

namespace App\Warehouse\Infrastructure\Persistence\Picqer\Orders\Mappers;

use App\Warehouse\Infrastructure\Exceptions\PicqerOrderStateMapperException;
use Illuminate\Support\Arr;

class PicqerOrderStateMapper
{
    /**
     * @throws PicqerOrderStateMapperException
     */
    public static function toDomain(string $picqerStatus)
    {
        $mapping = [
            'concept' => 'new',
            'processing' => 'processing',
            'completed' => 'completed',
            'cancelled' => 'cancelled'
        ];

        $domainStatus = Arr::get($mapping, $picqerStatus);

        if (! $domainStatus)
        {
            throw new PicqerOrderStateMapperException('Picqer order state ' . $picqerStatus . ' cannot be mapped to domain order status');
        }

        return $domainStatus;
    }

    /**
     * @throws PicqerOrderStateMapperException
     */
    public static function toPicqer(string $domainStatus)
    {
        $mapping = [
            'new' => 'concept',
            'processing' => 'processing',
            'completed' => 'completed',
            'cancelled' => 'cancelled'
        ];

        $picqerStatus = Arr::get($mapping, $domainStatus);

        if (! $picqerStatus)
        {
            throw new PicqerOrderStateMapperException('Domain order state ' . $domainStatus . ' cannot be mapped to Picqer order status');
        }

        return $picqerStatus;
    }
}
