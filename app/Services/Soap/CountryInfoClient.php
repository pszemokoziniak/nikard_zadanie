<?php

namespace App\Services\Soap;

use SoapClient;
use SoapFault;

class CountryInfoClient
{
    private SoapClient $client;

    private const WSDL = 'http://webservices.oorsprong.org/websamples.countryinfo/CountryInfoService.wso?WSDL';

    /**
     * @throws SoapFault
     */
    public function __construct(?SoapClient $client = null)
    {
        $this->client = $client ?? new SoapClient(self::WSDL, [
            'exceptions' => true,
            'cache_wsdl' => WSDL_CACHE_BOTH,
            'connection_timeout' => 5,
        ]);
    }

    /**
     * Zwraca stolicę kraju dla kodu ISO2 (np. "PL", "DE").
     *
     * @throws SoapFault
     */
    public function getCapitalCity(string $iso2): ?string
    {
        $resp = $this->client->__soapCall('CapitalCity', [
            ['sCountryISOCode' => strtoupper($iso2)],
        ]);

        return $resp->CapitalCityResult ?? null;
    }
}
