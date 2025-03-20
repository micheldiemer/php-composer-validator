<?php

namespace Md\Validator;

use Md\Validator\W3C_NU_Message;

class Validate
{
    public const BASE_DOCKER_URL_EXTERNAL = 'http://localhost:8003';
    public const BASE_DOCKER_URL_PATH = 'http://rendu';
    public const BASE_DOCKER_FILE_PATH = '/var/www/rendu';

    public static $filePath = self::BASE_DOCKER_FILE_PATH;
    public static $urlPath = self::BASE_DOCKER_URL_PATH;
    public static $urlExternal = self::BASE_DOCKER_URL_EXTERNAL;


    public const DEFAULT_HTML_TEMPLATE = <<<HTML
                <script>
                    window.addEventListener('load', function() {
                        const jsonview = window.jsonview;
                        const data = {jsonEncodedData};
                        const tree = jsonview.create(data);
                        jsonview.render(tree, document.getElementById('w3cmessages'));
                        jsonview.expand(tree);
                    });
                </script>
        HTML;

    public const STANDARD_EXCLUDE = ['bootstrap', '/vendor/', '/node_mobules/', '/.vscode/'];

    public static function setPathLocalToUrlPath($filePath, $urlPath, $urlExternal)
    {
        self::$filePath = $filePath;
        self::$urlPath = $urlPath;
        self::$urlExternal = $urlExternal;
    }


    public static function exclude(\SplFileInfo $file, array $exclusions = self::STANDARD_EXCLUDE): bool
    {
        $lower_file =  mb_strtolower($file);

        foreach ($exclusions as $exclude):
            if (mb_str_contains($lower_file, $exclude)):
                return true;
            endif;
        endforeach;
        return false;
    }

    private static function getContentType($file): string|false
    {
        $lower_file =  mb_strtolower($file);
        if (mb_str_ends_with($lower_file, '.scss')):
            return false;
        elseif (mb_str_ends_with($lower_file, 'html')):
            $type = 'text/html; charset=utf-8';
        elseif (mb_str_ends_with($lower_file, 'css')):
            $type = 'text/css';
        elseif (mb_str_ends_with($lower_file, 'php')):
            $type = 'text/html; charset=utf-8';
        elseif (mb_str_ends_with($lower_file, 'svg')):
            $type = 'image/svg+xml';
        else:
            return false;
        endif;

        return $type;
    }

    public static function localPathToUrl(\SplFileInfo $file, $localPath, $urlPath): string|false
    {
        if (!file_exists($file)):
            return false;
        endif;

        return mb_str_replace($localPath, $urlPath, $file->getPath() . '/' . $file->getFileName());
    }


    public static function code(string $code, string $contentType, IValidator $iValidator)
    {
        $validatorResult = $iValidator->validateCode($code, $contentType);
        return $validatorResult;
    }

    public static function localFile(\SplFileInfo $file, IValidator $validator, array $exclusions = self::STANDARD_EXCLUDE, string $HtmlOrJsTemplate = self::DEFAULT_HTML_TEMPLATE): bool
    {
        if (self::exclude($file, $exclusions)):
            return false;
        endif;

        $contentType = self::getContentType($file);
        if ($contentType === false):
            return false;
        endif;

        // $lower_file =  mb_strtolower($file);
        // if (str_ends_with($lower_file, '.php')):
        //     $useRemote = false;
        // endif;

        $localUrl = self::localPathToUrl($file, self::$filePath, self::$urlPath);

        $validatorResult = $validator->validateUrl($localUrl);

        self::processValidatorResult($validatorResult, $file, $localUrl, $HtmlOrJsTemplate);

        return true;
    }

    private static function processValidatorResult($validatorResult, \SplFileInfo $file, string $localUrl, string $HtmlOrJsTemplate = self::DEFAULT_HTML_TEMPLATE): void
    {
        if (!mb_str_contains($HtmlOrJsTemplate, '{jsonEncodedData}')):
            throw new \Exception('HtmlOrJsTemplate must contain {jsonEncodedData}');
        endif;

        $url = $localUrl;
        $data = $validatorResult;
        foreach ($validatorResult as $k => $w3curlRet):
            $data['httpCode'] = $w3curlRet['httpCode'];
            $ok = $w3curlRet['httpCode'] == 200 && (($w3curlRet['rawdata'] ?? false) !== false);
            $data['file'] = $k . ' ' . ($k == 'remote' ? mb_substr($file, mb_strlen(self::BASE_DOCKER_FILE_PATH) + 1) : mb_str_replace(self::$urlPath, self::$urlExternal, $url));
            if (!$ok):
                $data['message'] = 'W3CValidation null ' . ($w3curlRet['rawdata'] ?? '');
                continue;
            endif;

            $w3cdata = $w3curlRet['json'];

            /**
             * @var W3C_NU_Message $message
             */
            foreach ($w3cdata->messages as $message):
                $data[$message->type] ??= [];
                $lc = isset($message->firstLine) && isset($message->firstColumn) ?
                    $message->firstLine . ':' . $message->firstColumn . ' ' : '';

                $data[$message->type][] = $lc . ' ' . $message->message;

                echo \mb_str_replace('{jsonEncodedData}', json_encode($data), $HtmlOrJsTemplate);
            endforeach;
        endforeach;
    }
}
