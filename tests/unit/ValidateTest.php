<?php

declare(strict_types=1);

define('PHPUNIT_RUNNING', 1);
define('PHPUNIT_RUNNING_TEST_ERRORS', 0);

require_once __DIR__ . '/../../vendor/autoload.php';

use Md\Validator\IValidator;
use PHPUnit\Framework\TestCase;
use Md\Validator\Validate;
use Md\Validator\VNUValidator;
use Md\Validator\W3CValidator;

final class ValidateTest extends TestCase
{
    const BASIC_HTML_OK = "<!DOCTYPE html><html lang='fr'><head><title>test</title><meta charset='utf-8'></head></html>";
    const BASIC_HTML_ERR = "<!DOCTYPE html><html><head><title>test</title><meta charset='utf-8'></head></html>";
    const BASIC_HTML_ERRMSG = 'Consider adding a “lang” attribute to the “html” start tag to declare the language of this document.';

    public function testCodeVNU(): void
    {
        $validator = new VNUValidator();

        $validatorAvailable = $validator->validatorIsAvailable();
        $this->assertTrue($validatorAvailable);
        if (!$validatorAvailable) {
            echo "VNUValidator is not available\n";
            return;
        }

        $r = Validate::code($validator, self::BASIC_HTML_ERR);
        $this->assertEquals($r['httpCode'], 200);
        $this->assertIsObject($r['json']);
        $this->assertIsObject($r['json']->messages[0]);
        $message = $r['json']->messages[0];
        $this->assertEquals($message->type, 'info');
        $this->assertEquals($message->message, self::BASIC_HTML_ERRMSG);

        $r = Validate::code($validator, self::BASIC_HTML_OK);
        $this->assertIsObject($r['json']);
        $this->assertEmpty($r['json']->messages);
    }

    public function testCodeW3C(): void
    {

        $validator = new W3CValidator();
        $this->assertTrue($validator->validatorIsAvailable());
        //      $this->testValidator($validator);

        $r = Validate::code($validator, self::BASIC_HTML_ERR);

        $this->assertEquals($r['httpCode'], 200);
        $this->assertIsObject($r['json']);
        $this->assertIsObject($r['json']->messages[0]);
        $message = $r['json']->messages[0];
        $this->assertEquals($message->type, 'info');
        $this->assertEquals($message->message, self::BASIC_HTML_ERRMSG);

        $r = Validate::code($validator, self::BASIC_HTML_OK);
        $this->assertIsObject($r['json']);
        $this->assertEmpty($r['json']->messages);
    }

}
