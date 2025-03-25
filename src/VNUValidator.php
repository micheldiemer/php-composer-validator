<?php

namespace Md\Validator;

use Md\Validator\Url;

class VNUValidator implements IValidator
{
    public const VNU_LAN_URL = 'http://vnu:8888';
    public const W3C_URL_HOST = 'https://validator.nu';
    public const VNU_OUT_JSON = ['out' => 'json'];
    private Url $rawurl;


    public function __construct(bool $remote = false, ?Url $url = null)
    {

        $this->rawurl =
            ($remote
                ? Url::fromUrl(self::W3C_URL_HOST)
                : (is_null($url)
                    ? Url::fromUrl(self::VNU_LAN_URL)
                    : $url));

        $this->rawurl->addParams(self::VNU_OUT_JSON);
    }

    public function validatorIsAvailable(): bool
    {
        return HttpCurl::http_curl_test($this->rawurl->withoutParams());
    }


    public function validateCode(string $code, string $contentType  = 'text/html; charset=utf-8')
    {
        $url = $this->rawurl->full();
        $r = HttpCurl::http_curl($url, HttpMethods::POST, $contentType, ['text' => $code]);

        return [
            'local' => true,
            'url' => $url,
            'httpCode' => $r['httpCode'],
            'rawdata' => $r['rawdata'],
            'json' => $r['json'],
        ];
    }

    public function validateUrl(string $urlToValidate)
    {
        $result = [];

        # Vérification de l’adresse locale
        if (!$this->testUrl($urlToValidate, $result)) {
            die('validateUrl error');
            return [
                'httpCode' => 404,
                'rawdata' => null,
                'json' => null,
                'error' => "testUrl $urlToValidate false"
            ];
        }

        # Construction de l'URL
        $this->rawurl->addParams(['doc' => $urlToValidate]);

        # Interrogation du validateur avec curl
        $url = $this->rawurl->full();
        $r = HttpCurl::http_curl($url);
        $rawdata = $r['rawdata'];
        $httpcode = $r['httpCode'];

        if ($rawdata === false || $rawdata === '' || $httpcode === false):
            return [
                'httpCode' => $httpcode,
                'rawdata' => null,
                'json' => null,
                'error' => "curl $url urlToValidate $urlToValidate"
            ];
        endif;

        return ['httpCode' => $httpcode, 'rawdata' => $rawdata, 'json' => $r['json']];
    }

    private static function testUrl(string $url): bool
    {
        return HttpCurl::http_curl_test($url);
    }
}
