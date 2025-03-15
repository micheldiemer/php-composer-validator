<?php

namespace Md\Validator;

class VNUValidator implements IValidator
{
    public const VNU_LAN_HOST = null;
    public const VNU_LAN_HOST_NAME = 'vnu';
    public const VNU_LAN_SECURE = false;
    public const VNU_LAN_PORT = 8888;

    public static $VNU_lan_url = 'http://vnu:8888/?out=json';

    public static function setValidatorUrl(string|null $fullUrl = null, string|null $host = self::VNU_LAN_HOST, string|null $hostName = self::VNU_LAN_HOST_NAME, int|null $port = self::VNU_LAN_PORT, $secure = self::VNU_LAN_SECURE): string
    {
        if (!is_null($fullUrl)) {
            self::$VNU_lan_url = $fullUrl;
            return self::$VNU_lan_url;
        }
        $_scheme = 'http' . ($secure ? 's' : '');
        $_host = (is_null($host)) ? gethostbyname($hostName) : $host;
        $_port = $port > 0 ? ":{$port}" : '';
        self::$VNU_lan_url = $_scheme . '://' . $_host . ':' . $_port . '/?out=json';
        return self::$VNU_lan_url;
    }

    public function validateCode(string $code, string $contentType)
    {
        throw new \Exception('Not implemented yet');
    }

    public function validateUrl(string $url)
    {
        throw new \Exception('Not implemented yet');
    }
    public function validateLocalUrl(string $localUrl, string $validatorUrl = '')
    {
        if ($validatorUrl === '') {
            $validatorUrl = self::$VNU_lan_url;
        }
        # Vérification de l’adresse locale
        $encodedLocalUrl = urlencode(trim($localUrl));
        $url_data_res = fopen($encodedLocalUrl, 'r');
        if ($url_data_res === false):
            return ['httpCode' => 404, 'rawdata' => null, 'json' => ['error' => "fopen $localUrl"]];
        endif;
        fclose($url_data_res);

        # Construction de l'URL
        $url = $validatorUrl . '&doc=' . $encodedLocalUrl;

        # Interrogation du validateur avec curl
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, 'cURL/' . PHP_VERSION);
        //curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        //curl_setopt($ch, CURLOPT_POST, true);
        //curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: ' . $type]);
        //curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($file));
        curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $rawdata = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($rawdata === false || $rawdata === '' || $httpcode === false):
            return ['httpCode' => $httpcode, 'rawdata' => null, 'json' => ['error' => "curl $url"]];
        endif;

        try {
            $data = json_decode($rawdata, null, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $data = ['error' => $e->getMessage()];
        }

        return ['httpCode' => $httpcode, 'rawdata' => $rawdata, 'json' => $data];
    }

    public function validateRemoteUrl(string $remoteUrl)
    {
        throw new \Exception('Not implemented yet');
    }
}
