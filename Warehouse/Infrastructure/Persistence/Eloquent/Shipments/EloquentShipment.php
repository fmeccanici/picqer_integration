<?php

namespace App\Warehouse\Infrastructure\Persistence\Eloquent\Shipments;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $reference
 * @property string $order_reference
 * @property bool $track_and_trace_mail_sent
 */
class EloquentShipment extends Model
{
    protected $table = 'shipments';
    protected $connection = 'warehouse';

}
