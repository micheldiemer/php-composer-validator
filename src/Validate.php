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


        $fullPath = $file->getPath() . '/' . $file->getFileName();

        if (!mb_str_starts_with($fullPath, $localPath)):
            return false;
        endif;
        $filePart = mb_substr($fullPath, mb_strlen($localPath));

        $parts = \preg_split('/[\/\\\\]/', $filePart);
        $urlParts = [];
        foreach ($parts as $k => $part):
            $urlParts[] = rawurlencode($part);
        endforeach;

        return $urlPath . implode('/', $urlParts);
    }


    public static function code(IValidator $iValidator, string $code, string $contentType = "text/html; charset=utf-8"): array
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

    private static function processValidatorResult($validatorResult, \SplFileInfo|null $file, string $localUrl, string $HtmlOrJsTemplate = self::DEFAULT_HTML_TEMPLATE): void
    {
        if (!mb_str_contains($HtmlOrJsTemplate, '{jsonEncodedData}')):
            throw new \Exception('HtmlOrJsTemplate must contain {jsonEncodedData}');
        endif;

        $HtmlOrJsTemplate = mb_trim($HtmlOrJsTemplate);

        $url = $localUrl;
        $data = [];


        $data['httpCode'] = $validatorResult['httpCode'];

        $ok = $validatorResult['httpCode'] == 200 && (($validatorResult['rawdata'] ?? false) !== false);
        if (!is_null($file)):
            $data['file'] = $file->getPath() . '/' . $file->getFileName();
        else:
            $data['file'] = __FILE__ . ':' . __LINE__;
        endif;
        if ($localUrl != ''):
            $data['url'] = $localUrl;
        else:
            $data['url'] = $validatorResult['json']['url'];
        endif;
        $data['url2'] = mb_str_replace(self::$urlPath, self::$urlExternal, $data['url']);

        /*$k . ' ' . ($k == 'remote' ? mb_substr($file, mb_strlen(self::BASE_DOCKER_FILE_PATH) + 1) : mb_str_replace(self::$urlPath, self::$urlExternal, $url));*/

        if (!$ok):
            if (isset($validatorResult['error'])) {
                $data['message'] = 'Erreur validation ' . $validatorResult['error'];
            } else {
                $data['message'] = 'W3CValidation not ok ' . ($w3curlRet['rawdata'] ?? '');
            }
        endif;

        if ($ok && !\is_object($validatorResult['json'])):
            $data['message'] = 'W3CValidation no json ' . ($w3curlRet['rawdata'] ?? '');
            $ok = false;
        endif;

        $w3cdata = $validatorResult['json'];
        if ($ok && !\is_array($w3cdata->messages)):
            $data['message'] = 'Messages pas un tableau ' . ($w3curlRet['rawdata'] ?? '');
            $ok = false;
        endif;
        if ($ok && count($w3cdata->messages) === 0):
            $data['message'] = 'Aucun message';
        endif;

        if (!$ok) {
            echo \mb_str_replace('{jsonEncodedData}', json_encode($data), $HtmlOrJsTemplate);
            return;
        }


        /**
         * @var W3C_NU_Message $message
         */
        foreach ($w3cdata->messages as $message):
            $data[$message->type] ??= [];
            $lc = isset($message->firstLine) && isset($message->firstColumn) ?
                $message->firstLine . ':' . $message->firstColumn . ' ' : '';

            $data[$message->type][] = $lc . ' ' . $message->message;
        endforeach;
        echo \mb_str_replace('{jsonEncodedData}', json_encode($data), $HtmlOrJsTemplate);
    }
}
