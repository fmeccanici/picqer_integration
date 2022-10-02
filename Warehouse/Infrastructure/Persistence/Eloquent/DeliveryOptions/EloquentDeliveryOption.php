<?php


namespace App\Warehouse\Infrastructure\Persistence\Eloquent\DeliveryOptions;


use Illuminate\Database\Eloquent\Model;

/**
 * Class EloquentDeliveryOption
 * @package App\Shipping\Infrastructure\Persistence\Eloquent\DeliveryOptions
 *
 * @property string $name
 * @property string $carrier_name
 * @property string $country
 * @property int $product_code
 * @property int $characteristic
 * @property int $option
 */
class EloquentDeliveryOption extends Model
{
    protected $table = "delivery_options";
    protected $connection = "warehouse";
}
