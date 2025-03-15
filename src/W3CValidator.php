<?php

namespace Md\Validator;

class W3CValidator implements IValidator
{
    public const W3C_URL = 'https://validator.nu?out=json';

    public function validateCode(string $code, string $contentType)
    {
        $url = self::W3C_URL;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, 'cURL/' . PHP_VERSION);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: ' . $contentType]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $code);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $rawdata = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ['W3C_url' => $url, 'httpCode' => $httpcode, 'rawdata' => $rawdata, 'json' => json_decode($rawdata)];
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
