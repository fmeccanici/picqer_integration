<?php

namespace App\Warehouse\Infrastructure\Persistence\Eloquent\ReviewRequests;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $order_reference
 * @property ?string $delivery_date
 * @property int $quantity_sent
 * @property ?string $last_sent;
 * @property string $customer_name
 * @property string $customer_email
 */
class EloquentReviewRequest extends Model
{
    protected $connection = 'warehouse';
    protected $table = 'review_requests';

}
