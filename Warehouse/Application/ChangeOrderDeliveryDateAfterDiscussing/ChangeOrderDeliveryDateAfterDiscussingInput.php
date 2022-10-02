<?php


namespace App\Warehouse\Application\ChangeOrderDeliveryDateAfterDiscussing;

use HomeDesignShops\LaravelDdd\Support\Input;
use Illuminate\Support\Arr;

final class ChangeOrderDeliveryDateAfterDiscussingInput extends Input
{
    /**
     * @var array The PASVL validation rules
     */
    protected $rules = [
        'order_reference' => ':string',
        'delivery_date' => ':string'
    ];

    protected string $orderReference;
    protected string $deliveryDate;


    /**
     * CancelOrderInput constructor.
     */
    public function __construct($input)
    {
        $this->validate($input);

        $this->orderReference = Arr::get($input, 'order_reference');
        $this->deliveryDate = Arr::get($input, 'delivery_date');
    }

    public function orderReference(): string
    {
        return $this->orderReference;
    }

    public function deliveryDate(): string
    {
        return $this->deliveryDate;
    }

}
