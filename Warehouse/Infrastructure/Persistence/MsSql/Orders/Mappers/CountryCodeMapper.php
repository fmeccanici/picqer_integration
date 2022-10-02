<?php


namespace App\Warehouse\Infrastructure\Persistence\MsSql\Orders\Mappers;


use App\Warehouse\Domain\Exceptions\CountryCodeMapperOperationException;

class CountryCodeMapper
{

    // TODO: Move to a config file
    protected static $map = [
        'NL' => 'Nederland',
        'BE' => ['Belgie', 'België'],
        'DE' => 'Duitsland',
        'EN' => 'Engeland',
        'FR' => 'Frankrijk',
        'LU' => 'Luxemburg'
    ];

    /**
     * @throws CountryCodeMapperOperationException
     */
    public static function toCountryCode(string $msSqlCountry): string
    {
        if(($value = array_search($msSqlCountry, self::$map)) !== false) {
            return $value;
        }

        foreach (self::$map as $countryCode => $country) {
            if(!is_array($country)) {
                continue;
            }

            if(in_array($msSqlCountry, $country, true)) {
                return $countryCode;
            }
        }

        throw new CountryCodeMapperOperationException("Country ".$msSqlCountry." is invalid");
    }

    /**
     * @throws CountryCodeMapperOperationException
     */
    public static function toMsSqlCountry(string $countryCode): string
    {
        if(array_key_exists($countryCode, self::$map)) {
            $countryName = self::$map[$countryCode];
            if (is_array($countryName))
            {
                return $countryName[0];
            } else {
                return $countryName;
            }
        }

        throw new CountryCodeMapperOperationException("Country code ".$countryCode." is invalid");
    }
}
