<?php

namespace Md\Validator;


class HttpCurl
{
    private static function http_curl_get(&$ch)
    {
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
    }

    private static function http_curl_head(&$ch)
    {
        curl_setopt($ch, CURLOPT_NOBODY, true);
    }

    private static function http_curl_post_form_data(&$ch, array $post_data)
    {
        curl_setopt($ch, CURLOPT_POST, true);
        self::http_curl_data($ch, $post_data);
    }

    private static function http_curl_post_form_urlencoded(&$ch, array|string $data)
    {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data != '' && $data != []) {
            $post_data = is_array($data) ? \http_build_query($data) : $data;
            self::http_curl_data($ch, $post_data);
        }
    }

    private static function http_curl_data(&$ch, array|string $data)
    {
        if ($data != '' && $data != []) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } else {
            curl_setopt($ch, CURLOPT_NOBODY, true);
        }
    }

    public static function http_curl($url, HttpMethods $method = HttpMethods::GET, $contentType = 'text/html; charset=utf-8', string|array $body = '', bool $w3c = true): array
    {
        $ch = curl_init();
        curl_setopt_array(
            $ch,
            [
                CURLOPT_URL => $url,
                CURLOPT_USERAGENT => 'cURL/' . PHP_VERSION,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => $method->value,
                CURLINFO_HEADER_OUT => true,
                CURLOPT_FORBID_REUSE => true,
                CURLOPT_RETURNTRANSFER => true,
            ]
        );

        if ($method === HttpMethods::POST) {
            if ($w3c && strlen($body['text'] ?? '') > 0) {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: ' . $contentType]);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body['text']);
            } else {
                self::http_curl_post_form_data($ch, $body);
            }
        } elseif ($method === HttpMethods::GET) {
            self::http_curl_get($ch);
        } elseif ($method === HttpMethods::HEAD) {
            self::http_curl_head($ch);
        } else {
            self::http_curl_data($ch, $body);
        }

        return self::http_curl_exec($ch);
    }



    public static function http_curl_exec(&$ch)
    {
        $rawdata = curl_exec($ch);

        // $info = curl_getinfo($ch);
        // $method = $info['effective_method'];
        // $rqHd  = $info['request_header'];
        // $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        // echo "------------------ request ------------------" . PHP_EOL;
        // echo $method . PHP_EOL;
        // echo $rqHd . PHP_EOL;

        // echo "------------------ response ------------------" . PHP_EOL;
        // echo $rawdata . PHP_EOL;
        // echo "-------------------------------------" . PHP_EOL;

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        $json = str_contains($contentType, 'json')
            ? json_decode($rawdata, null, 512, JSON_THROW_ON_ERROR)
            : null;

        return ['httpCode' => $httpcode, 'rawdata' => $rawdata, 'contentType' => $contentType, 'json' => $json];
    }

    private static function build_data_files($boundary, $fields, $files)
    {
        $data = '';
        $eol = "\r\n";

        $delimiter = '-------------' . $boundary;

        foreach ($fields as $name => $content) {
            $data .= "--" . $delimiter . $eol
                . 'Content-Disposition: form-data; name="' . $name . "\"" . $eol . $eol
                . $content . $eol;
        }


        foreach ($files as $name => $content) {
            $data .= "--" . $delimiter . $eol
                . 'Content-Disposition: form-data; name="' . $name . '"; filename="' . $name . '"' . $eol
                //. 'Content-Type: image/png'.$eol
                . 'Content-Transfer-Encoding: binary' . $eol;

            $data .= $eol;
            $data .= $content . $eol;
        }
        $data .= "--" . $delimiter . "--" . $eol;


        return $data;
    }
}
