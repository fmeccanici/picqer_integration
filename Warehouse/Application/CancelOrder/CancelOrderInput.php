<?php


namespace App\Warehouse\Application\CancelOrder;

use HomeDesignShops\LaravelDdd\Support\Input;
use Illuminate\Support\Arr;

final class CancelOrderInput extends Input
{
    /**
     * @var array The PASVL validation rules
     */
    protected $rules = [
        'order_reference' => ':string'
    ];

    protected string $orderReference;


    /**
     * CancelOrderInput constructor.
     */
    public function __construct($input)
    {
        $this->validate($input);

        $this->orderReference = Arr::get($input, 'order_reference');
    }

    public function orderReference(): string
    {
        return $this->orderReference;
    }
}
