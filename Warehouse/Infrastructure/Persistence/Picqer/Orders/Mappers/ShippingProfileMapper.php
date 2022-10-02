<?php


namespace App\Warehouse\Infrastructure\Persistence\Picqer\Orders\Mappers;


use Illuminate\Support\Arr;


// TODO: Refactor array to PicqerOrder object which has an array
class ShippingProfileMapper
{
    public const POST_NL_SHIPPING_PROVIDER = 'PostNL';
    public const POST_NL_STANDAARD_SHIPPING_PROVIDER_PROFILE = 'Standaard';
    public const INTRAPOST_SHIPPING_PROVIDER = 'Intrapost';
    public const INTRAPOST_BRIEFPOST_SHIPPING_PROVIDER_PROFILE = 'Briefpost';
    public const AFHALEN_SHIPPING_PROVIDER = 'Afhalen';
    public const AFHALEN_SHOWROOM_SHIPPING_PROVIDER_PROFILE = 'Showroom';
    public const BINNENSPECIALIST_SHIPPING_PROVIDER = 'Showroom';
    public const BINNENSPECIALIST_SHIPPING_PROVIDER_PROFILE = 'Binnenspecialist';

    public static function toEntity(int $picqerShippingProfileId): string
    {

    }

    /**
     * @param string $deliveryMethod
     * @return array
     */
    public static function toPicqer(string $deliveryMethod): array
    {
        $mapper = [
            'PostNL - Pakket' => [
                'shipping_provider_name' => self::POST_NL_SHIPPING_PROVIDER,
                'shipping_provider_profile_name' => self::POST_NL_STANDAARD_SHIPPING_PROVIDER_PROFILE
            ],
            'PostNL - Afhaalpunt' => [
                'shipping_provider_name' => self::POST_NL_SHIPPING_PROVIDER,
                'shipping_provider_profile_name' => self::POST_NL_STANDAARD_SHIPPING_PROVIDER_PROFILE
            ],
            'PostNL - Avond' => [
                'shipping_provider_name' => self::POST_NL_SHIPPING_PROVIDER,
                'shipping_provider_profile_name' => self::POST_NL_STANDAARD_SHIPPING_PROVIDER_PROFILE
            ],
            'PostNL - Briefpost' => [
                'shipping_provider_name' => self::INTRAPOST_SHIPPING_PROVIDER,
                'shipping_provider_profile_name' => self::INTRAPOST_BRIEFPOST_SHIPPING_PROVIDER_PROFILE
            ],
            'Showroom - Zoetermeer' => [
                'shipping_provider_name' => self::AFHALEN_SHIPPING_PROVIDER,
                'shipping_provider_profile_name' => self::AFHALEN_SHOWROOM_SHIPPING_PROVIDER_PROFILE
            ],
            'Showroom - Binnenspecialist' => [
                'shipping_provider_name' => self::BINNENSPECIALIST_SHIPPING_PROVIDER,
                'shipping_provider_profile_name' => self::BINNENSPECIALIST_SHIPPING_PROVIDER_PROFILE
            ],
        ];

        return Arr::get($mapper, $deliveryMethod) ?? [
                'shipping_provider_name' => self::POST_NL_SHIPPING_PROVIDER,
                'shipping_provider_profile_name' => self::POST_NL_STANDAARD_SHIPPING_PROVIDER_PROFILE
            ];
    }

}
