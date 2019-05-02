<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\VarDumper\Tests;

use PHPUnit\Framework\TestCase;
use StdClass;
use Yiisoft\VarDumper\VarDumperHelper;

/**
 * @group helpers
 */
class VarDumperHelperTest extends TestCase
{

    public function testDumpIncompleteObject()
    {
        $serializedObj = 'O:16:"nonExistingClass":0:{}';
        $incompleteObj = unserialize($serializedObj);
        $dumpResult = VarDumperHelper::dumpAsString($incompleteObj);
        $this->assertContains("__PHP_Incomplete_Class#1\n(", $dumpResult);
        $this->assertContains('nonExistingClass', $dumpResult);
    }

    public function testExportIncompleteObject()
    {
        $serializedObj = 'O:16:"nonExistingClass":0:{}';
        $incompleteObj = unserialize($serializedObj);
        $exportResult = VarDumperHelper::export($incompleteObj);
        $this->assertContains('nonExistingClass', $exportResult);
    }

    public function testDumpObject()
    {
        $obj = new StdClass();
        $this->assertEquals("stdClass#1\n(\n)", VarDumperHelper::dumpAsString($obj));

        $obj = new StdClass();
        $obj->name = 'test-name';
        $obj->price = 19;
        $dumpResult = VarDumperHelper::dumpAsString($obj);
        $this->assertContains("stdClass#1\n(", $dumpResult);
        $this->assertContains("[name] => 'test-name'", $dumpResult);
        $this->assertContains('[price] => 19', $dumpResult);
    }

    /**
     * Data provider for [[testExport()]].
     * @return array test data
     */
    public function dataProviderExport(): array
    {
        // Regular :

        $data = [
            [
                'test string',
                var_export('test string', true),
            ],
            [
                75,
                var_export(75, true),
            ],
            [
                7.5,
                var_export(7.5, true),
            ],
            [
                null,
                'null',
            ],
            [
                true,
                'true',
            ],
            [
                false,
                'false',
            ],
            [
                [],
                '[]',
            ],
        ];

        // Arrays :

        $var = [
            'key1' => 'value1',
            'key2' => 'value2',
        ];
        $expectedResult = <<<'RESULT'
[
    'key1' => 'value1',
    'key2' => 'value2',
]
RESULT;
        $data[] = [$var, $expectedResult];

        $var = [
            'value1',
            'value2',
        ];
        $expectedResult = <<<'RESULT'
[
    'value1',
    'value2',
]
RESULT;
        $data[] = [$var, $expectedResult];

        $var = [
            'key1' => [
                'subkey1' => 'value2',
            ],
            'key2' => [
                'subkey2' => 'value3',
            ],
        ];
        $expectedResult = <<<'RESULT'
[
    'key1' => [
        'subkey1' => 'value2',
    ],
    'key2' => [
        'subkey2' => 'value3',
    ],
]
RESULT;
        $data[] = [$var, $expectedResult];

        // Objects :

        $var = new StdClass();
        $var->testField = 'Test Value';
        $expectedResult = "unserialize('" . serialize($var) . "')";
        $data[] = [$var, $expectedResult];

        // @formatter:off
        $var = static function () {return 2;};
        // @formatter:on
        $expectedResult = 'function () {return 2;}';
        $data[] = [$var, $expectedResult];

        return $data;
    }

    /**
     * @dataProvider dataProviderExport
     *
     * @param mixed $var
     * @param string $expectedResult
     */
    public function testExport($var, $expectedResult)
    {
        $exportResult = VarDumperHelper::export($var);
        $this->assertEqualsWithoutLE($expectedResult, $exportResult);
        //$this->assertEquals($var, eval('return ' . $exportResult . ';'));
    }

    /**
     * @depends testExport
     */
    public function testExportObjectFallback()
    {
        $var = new StdClass();
        $var->testFunction = static function () {
            return 2;
        };
        $exportResult = VarDumperHelper::export($var);
        $this->assertNotEmpty($exportResult);

        $master = new StdClass();
        $slave = new StdClass();
        $master->slave = $slave;
        $slave->master = $master;
        $master->function = static function () {
            return true;
        };

        $exportResult = VarDumperHelper::export($master);
        $this->assertNotEmpty($exportResult);
    }

    /**
     * @depends testDumpObject
     */
    public function testDumpClassWithCustomDebugInfo()
    {
        $object = new CustomDebugInfo();
        $object->volume = 10;
        $object->unitPrice = 15;

        $dumpResult = VarDumperHelper::dumpAsString($object);
        $this->assertContains('totalPrice', $dumpResult);
        $this->assertNotContains('unitPrice', $dumpResult);
    }

    /**
     * Asserting two strings equality ignoring line endings.
     * @param string $expected
     * @param string $actual
     * @param string $message
     */
    protected function assertEqualsWithoutLE(string $expected, string $actual, string $message = '')
    {
        $expected = str_replace("\r\n", "\n", $expected);
        $actual = str_replace("\r\n", "\n", $actual);
        $this->assertEquals($expected, $actual, $message);
    }
}