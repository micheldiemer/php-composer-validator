<?php

namespace Md\Validator;



class VNUValidator implements IValidator
{
    public const VNU_LAN_HOST = null;
    public const VNU_LAN_HOST_NAME = 'vnu';
    public const VNU_LAN_SECURE = false;
    public const VNU_LAN_PORT = 8888;

    public const VNU_LAN_DEFAULT_RAWURL = 'http://vnu:8888';
    public const VNU_DEFAULT_PARAMS = 'out=json';
    public static $VNU_lan_rawurl = self::VNU_LAN_DEFAULT_RAWURL;
    public static $VNU_lan_params = self::VNU_DEFAULT_PARAMS;
    public static $VNU_lan_url = self::VNU_LAN_DEFAULT_RAWURL .
        '?' . self::VNU_DEFAULT_PARAMS;


    public function validatorIsAvailable(): bool
    {
        $r = HttpCurl::http_curl(self::$VNU_lan_rawurl, HttpMethods::HEAD);
        return $r['httpCode'] === 200;
    }

    public static function setValidatorUrl(string|null $fullUrl = null, string|null $host = self::VNU_LAN_HOST, string|null $hostName = self::VNU_LAN_HOST_NAME, int|null $port = self::VNU_LAN_PORT, $secure = self::VNU_LAN_SECURE, string $params = self::VNU_DEFAULT_PARAMS): string
    {
        if (!is_null($fullUrl)) {
            self::$VNU_lan_url = $fullUrl;
            return self::$VNU_lan_url;
        }
        $_scheme = 'http' . ($secure ? 's' : '');
        $_host = (is_null($host)) ? gethostbyname($hostName) : $host;
        $_port = $port > 0 ? ":{$port}" : '';
        self::$VNU_lan_rawurl = $_scheme . '://' . $_host . ':' . $_port;
        self::$VNU_lan_params = $params;
        self::$VNU_lan_url = self::$VNU_lan_rawurl . '?' . self::$VNU_lan_params;
        return self::$VNU_lan_url;
    }

    public function validateCode(string $code, string $contentType  = 'text/html; charset=utf-8')
    {
        $url = "http://vnu:8888/?out=json";
        //self::$VNU_lan_url;
        echo $url . PHP_EOL;
        $r = HttpCurl::http_curl($url, HttpMethods::POST, $contentType, ['text' => $code]);

        return [
            'local_url' => $url,
            'httpCode' => $r['httpCode'],
            'rawdata' => $r['rawdata'],
            'json' => $r['json']
        ];
    }

    public function validateUrl(string $urlToValidate)
    {
        if (!$this->testUrl($urlToValidate, $result = []))
            return $result;

        # Vérification de l’adresse locale
        $encodedLocalUrl = urlencode(trim($urlToValidate));

        # Construction de l'URL
        $url = self::$VNU_lan_url . '&doc=' . $encodedLocalUrl;

        # Interrogation du validateur avec curl
        $r = HttpCurl::http_curl($url, HttpMethods::GET);
        $rawdata = $r['rawdata'];
        $httpcode = $r['httpCode'];

        if ($rawdata === false || $rawdata === '' || $httpcode === false):
            return ['httpCode' => $httpcode, 'rawdata' => null, 'json' => ['error' => "curl $url"]];
        endif;

        return ['httpCode' => $httpcode, 'rawdata' => $rawdata, 'json' => $r['json']];
    }

    private function testUrl(string $url, array &$result): bool
    {
        $url_data_res = fopen($url, 'r');
        if ($url_data_res === false):
            $result = ['httpCode' => 404, 'rawdata' => null, 'json' => ['error' => "fopen $url"]];
            return false;
        endif;
        fclose($url_data_res);
        return true;
    }


    public function validateLocalUrl(string $localUrl)
    {
        return $this->validateUrl($localUrl);
    }

    public function validateRemoteUrl(string $remoteUrl)
    {
        return $this->validateUrl($remoteUrl);
    }
}
