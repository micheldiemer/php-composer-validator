<?php

namespace Md\Validator;

use Md\Validator\HttpCurl;
use Md\Validator\HttpMethods;

class Url
{
    private string|null $host;
    private string $scheme;
    private string|null $hostName;
    private int|null $port;
    private bool $secure;
    private string|null $fullUrl = null;

    private array $params;

    public static function fromUrl(string $url): self
    {
        $parsed = parse_url($url);
        $host = $parsed['host'] ?? null;
        $hostName = $parsed['host'] ?? null;
        $port = $parsed['port'] ?? null;
        $secure = $parsed['scheme'] === 'https';
        $scheme = $parsed['scheme'] ?? 'http';

        if (is_null($host) && is_null($hostName)) {
            throw new \InvalidArgumentException('Url must have a host or a hostName');
        }
        if ($scheme === 'https') {
            $scheme = 'http';
        }


        return new self($host, $hostName, $secure, $port, $scheme);
    }

    public function __construct(
        string|null $host,
        string|null $hostName = null,
        bool $secure = false,
        int|null $port = null,
        string $scheme = 'http'
    ) {
        $this->host = $host;
        $this->hostName = $hostName;
        $this->port = $port;
        $this->secure = $secure;
        $this->scheme = $scheme;
        $this->params = [];
        $this->withoutParams();
    }

    public function addParams(array $params): self
    {
        $this->params = array_merge($params, $this->params);
        return $this;
    }

    public function setParam(string $name, $value): self
    {
        $this->params[$name] = $value;
        return $this;
    }

    public function removeParam(string $name): self
    {
        unset($this->params[$name]);
        return $this;
    }

    public function full(): string
    {
        return $this->withoutParams() . '?' . http_build_query($this->params);
    }

    public function withoutParams(): string
    {
        if (!is_null($this->fullUrl)) {
            return $this->fullUrl;
        }

        $this->fullUrl = $this->scheme . ($this->secure ? 's' : '') . '://';
        $this->fullUrl .= (is_null($this->host)) ? gethostbyname($this->hostName) : $this->host;
        $this->fullUrl .= $this->port > 0 ? ":{$this->port}" : '';
        return $this->fullUrl;
    }

    public function test()
    {
        return HttpCurl::http_curl_test($this->full(), HttpMethods::HEAD);
    }
}
