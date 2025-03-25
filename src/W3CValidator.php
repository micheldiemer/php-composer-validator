<?php

namespace Md\Validator;

use Md\Validator\HttpMethods;

class W3CValidator implements IValidator
{
    public const W3C_URL_RAW = 'https://validator.nu';
    public const W3C_URL = self::W3C_URL_RAW . '?out=json';

    public function validatorIsAvailable(): bool
    {
        $r = HttpCurl::http_curl(self::W3C_URL_RAW, HttpMethods::HEAD);
        return $r['httpCode'] === 200;
    }

    public function validateCode(string $code, string $contentType  = 'text/html; charset=utf-8')
    {
        $url = self::W3C_URL;

        $r = HttpCurl::http_curl($url, HttpMethods::POST, $contentType, ['text' => $code]);

        return [
            'W3C_url' => $url,
            'httpCode' => $r['httpCode'],
            'rawdata' => $r['rawdata'],
            'json' => json_decode($r['rawdata'])
        ];
    }


    public function validateUrl(string $url)
    {
        throw new \Exception('Not implemented yet');
    }
    public function validateLocalUrl(string $localUrl)
    {
        throw new \Exception('Not implemented yet');
    }
    public function validateRemoteUrl(string $remoteUrl)
    {
        throw new \Exception('Not implemented yet');
    }
}
