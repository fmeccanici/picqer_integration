<?php


namespace App\Warehouse\Infrastructure\Persistence\Picqer\Orders\Mappers;


use Illuminate\Support\Arr;


// TODO: Refactor array to PicqerOrder object which has an array
class TrackAndTraceMailDeliveryMethodMapper
{
    public static function toEntity(int $picqerShippingProfileId): string
    {

    }

    /**
     * @param string $deliveryMethod
     * @return string
     */
    public static function toFlowMailer(string $deliveryMethod): string
    {
        $mapper = [
            'PostNL - Pakket' => 'Pakket',
            'PostNL - Afhaalpunt' => 'Afhaalpunt',
            'PostNL - Avond' => 'Avond',
            'PostNL - Briefpost' => 'Briefpost',
            'PostNL - Extra vroeg' => 'Extra vroeg',
            'Pakket' => 'Pakket',
            'Avond' => 'Avond',
            'Briefpost' => 'Briefpost',
            'Afhaalpunt' => 'Afhaalpunt',
            'UPS - Groot transport - Standaard' => 'Standaard',
            'UPS - Orac Decor' => 'Standaard',
            'Wesseling - Groot transport - Standaard' => 'Standaard',
            'Wesseling - Groot transport - Ochtend' => 'Ochtend',
            'Wesseling - Groot transport - Middag' => 'Middag',
            'Tielbeke - Groot transport - Standaard' => 'Standaard',
            'Tielbeke - Groot transport - Ochtend' => 'Ochtend',
            'Tielbeke - Groot transport - Middag' => 'Middag',
            'Groot transport - Standaard' => 'Standaard',
            'Groot transport - Ochtend' => 'Ochtend',
            'Groot transport - Middag' => 'Middag',
            'Van Straaten - Briefpost' => 'Briefpost',
            'Showroom - Zoetermeer' => 'Afhaalpunt',
            'Showroom' => 'Afhaalpunt',
            'Showroom - Zeist' => 'Afhaalpunt',
            'Showroom - Hellevoetsluis' => 'Afhaalpunt',
            'GLS - Pakket' => 'Pakket',
            'DPD - Pakket' => 'Pakket',
            'DHL - Pakket' => 'Pakket',
            'FedEx - Pakket' => 'Pakket',
            'TNT - Pakket' => 'Pakket',
            'Transmission - Bode Scholten - Groot transport - Standraad' => 'Standaard',
            'NE DistriService - Groot transport - Standaard' => 'Standaard',
            'Showroom - Binnenspecialist' => 'Showroom - Binnenspecialist'
        ];

        return Arr::get($mapper, $deliveryMethod) ?? 'Standaard';
    }

}
