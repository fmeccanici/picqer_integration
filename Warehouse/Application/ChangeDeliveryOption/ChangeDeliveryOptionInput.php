<?php


namespace App\Warehouse\Application\ChangeDeliveryOption;

use App\SharedKernel\Address;
use App\SharedKernel\AddressFactory;
use App\Warehouse\Infrastructure\Persistence\MsSql\Orders\Mappers\CountryCodeMapper;
use HomeDesignShops\LaravelDdd\Support\Input;

final class ChangeDeliveryOptionInput extends Input
{
    /**
     * @var array The PASVL validation rules
     */
    // TODO: Task 18827: Refactor de input van de use case zodat deze 1 array heeft met delivery option
    protected $rules = [
        "delivery_option" => [
            "name" => ":string",
            "carrier_name" => ":string",
            "country" => ":string"
        ],
        "order_reference" => ":string",
        "delivery_address" => [
            "name" => ":string",
            "street" => ":string",
            "zipcode" => ":string",
            "city" => ":string"
        ]
    ];

    private string $deliveryOptionName;
    private string $orderReference;
    private string $carrierName;
    private string $country;
    /**
     * @var mixed
     */
    private $name;
    /**
     * @var mixed
     */
    private $street;
    /**
     * @var mixed
     */
    private $zipcode;
    /**
     * @var mixed
     */
    private $city;
    private string $deliveryAddressName;

    /**
     * ChangeDeliveryOptionInput constructor.
     */
    public function __construct(array $input)
    {
        $this->validate($input);
        $this->orderReference = $input["order_reference"];
        $this->deliveryOptionName = $input["delivery_option"]["name"];
        $this->carrierName = $input["delivery_option"]["carrier_name"];
        $this->country = $input["delivery_option"]["country"];
        $this->name = $input["delivery_option"]["name"];
        $this->street = $input["delivery_address"]["street"];
        $this->zipcode = $input["delivery_address"]["zipcode"];
        $this->city = $input["delivery_address"]["city"];
        $this->deliveryAddressName = $input["delivery_address"]["name"];
    }

    public function deliveryAddress(): ?Address
    {
        if ($this->name === null)
        {
            return null;
        }

        $deliveryAddress = AddressFactory::fromStreetAddress($this->street, $this->city, $this->zipcode, CountryCodeMapper::toCountryCode($this->country));
        $deliveryAddress->changeName($this->deliveryAddressName);
        return $deliveryAddress;
    }

    public function deliveryOptionName(): string
    {
        return $this->deliveryOptionName;
    }

    public function carrierName(): string
    {
        return $this->carrierName;
    }

    public function country(): string
    {
        return $this->country;
    }

    public function orderReference(): string
    {
        return $this->orderReference;
    }

}
