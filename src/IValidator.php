<?php

namespace Md\Validator;

interface IValidator
{
    public function validateCode(string $code, string $contentType);
    public function validateUrl(string $url);
    public function validatorIsAvailable(): bool;
}
