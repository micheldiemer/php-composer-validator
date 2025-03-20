<?php

declare(strict_types=1);

define('PHPUNIT_RUNNING', 1);
define('PHPUNIT_RUNNING_TEST_ERRORS', 0);

require_once __DIR__ . '/../../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Md\Validator\Validate;
use Md\Validator\VNUValidator;
use Md\Validator\W3CValidator;

final class ValidateTest extends TestCase
{

    public function testCodeVNU(): void
    {

        $this->expectException(\Exception::class);
        Validate::code("<html></html>", "text/html; charset=utf-8", new VNUValidator());
    }

    public function testCodeW3C(): void
    {
        $r = Validate::code("<html></html>", "text/html; charset=utf-8", new W3CValidator());
        $this->assertIsArray($r);
    }
}
