<?php

/*
 * This file is part of the Webmozart JSON package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Json\Symfony\Tests;

use Webmozart\Json\Symfony\JsonEncoder;

/**
 * @since  1.0
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class JsonEncoderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var JsonEncoder
     */
    private $encoder;

    /**
     * @var string
     */
    private $fixturesDir;

    /**
     * @var string
     */
    private $schemaFile;

    /**
     * @var \stdClass
     */
    private $schemaObject;

    protected function setUp()
    {
        $this->encoder = new JsonEncoder();
        $this->fixturesDir = __DIR__.'/../Fixtures';
        $this->schemaFile = $this->fixturesDir.'/schema.json';
        $this->schemaObject = json_decode(file_get_contents($this->schemaFile));
    }

    public function testEncode()
    {
        $data = (object) array('name' => 'Kévin');

        $this->assertSame('{"name":"K\u00e9vin"}', $this->encoder->encode($data, 'json'));
    }

    public function testEncodeWithSchemaFile()
    {
        $data = (object) array('name' => 'Kévin');

        $this->assertSame('{"name":"K\u00e9vin"}', $this->encoder->encode($data, 'json', array('json_schema' => $this->schemaFile)));
    }

    public function testEncodeWithSchemaObject()
    {
        $data = (object) array('name' => 'Kévin');

        $this->assertSame('{"name":"K\u00e9vin"}', $this->encoder->encode($data, 'json', array('json_schema' => $this->schemaObject)));
    }

    public function testDecode()
    {
        $data = $this->encoder->decode('{ "name": "K\u00e9vin" }', 'json');

        $this->assertInstanceOf('\stdClass', $data);
        $this->assertObjectHasAttribute('name', $data);
        $this->assertSame('Kévin', $data->name);
    }

    public function testDecodeWithSchemaFile()
    {
        $data = $this->encoder->decode('{ "name": "K\u00e9vin" }', 'json', array('json_schema' => $this->schemaFile));

        $this->assertInstanceOf('\stdClass', $data);
        $this->assertObjectHasAttribute('name', $data);
        $this->assertSame('Kévin', $data->name);
    }

    public function testDecodeWithSchemaObject()
    {
        $data = $this->encoder->decode('{ "name": "K\u00e9vin" }', 'json', array('json_schema' => $this->schemaObject));

        $this->assertInstanceOf('\stdClass', $data);
        $this->assertObjectHasAttribute('name', $data);
        $this->assertSame('Kévin', $data->name);
    }
}
