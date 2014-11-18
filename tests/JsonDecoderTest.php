<?php

/*
 * This file is part of the Puli JSON package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Json\Tests;

use Puli\Json\JsonDecoder;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class JsonDecoderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var JsonDecoder
     */
    private $decoder;

    private $fixturesDir;

    private $schemaFile;

    private $schemaObject;

    protected function setUp()
    {
        $this->decoder = new JsonDecoder();
        $this->fixturesDir = __DIR__.'/Fixtures';
        $this->schemaFile = $this->fixturesDir.'/schema.json';
        $this->schemaObject = json_decode(file_get_contents($this->schemaFile));
    }

    public function testDecode()
    {
        $data = $this->decoder->decode('{ "name": "Bernhard" }');

        $this->assertInstanceOf('\stdClass', $data);
        $this->assertObjectHasAttribute('name', $data);
        $this->assertSame('Bernhard', $data->name);
    }

    public function testDecodeWithSchemaFile()
    {
        $data = $this->decoder->decode('{ "name": "Bernhard" }', $this->schemaFile);

        $this->assertInstanceOf('\stdClass', $data);
        $this->assertObjectHasAttribute('name', $data);
        $this->assertSame('Bernhard', $data->name);
    }

    public function testDecodeWithSchemaObject()
    {
        $data = $this->decoder->decode('{ "name": "Bernhard" }', $this->schemaObject);

        $this->assertInstanceOf('\stdClass', $data);
        $this->assertObjectHasAttribute('name', $data);
        $this->assertSame('Bernhard', $data->name);
    }

    /**
     * @expectedException \Puli\Json\InvalidJsonException
     */
    public function testDecodeFailsIfValidationFailsWithSchemaFile()
    {
        $this->decoder->decode('"foobar"', $this->schemaFile);
    }

    /**
     * @expectedException \Puli\Json\InvalidJsonException
     */
    public function testDecodeFailsIfValidationFailsWithSchemaObject()
    {
        $this->decoder->decode('"foobar"', $this->schemaObject);
    }

    /**
     * @expectedException \Puli\Json\SchemaException
     */
    public function testDecodeFailsIfSchemaFileNotFound()
    {
        $this->decoder->decode('{ "name": "Bernhard" }', __DIR__.'/bogus.json');
    }

    /**
     * @expectedException \Puli\Json\SchemaException
     */
    public function testDecodeFailsIfSchemaNeitherStringNorObject()
    {
        $this->decoder->decode('{ "name": "Bernhard" }', 12345);
    }

    /**
     * @expectedException \Puli\Json\SchemaException
     */
    public function testDecodeFailsIfSchemaFileContainsNoObject()
    {
        $this->decoder->decode('{ "name": "Bernhard" }', $this->fixturesDir.'/schema-no-object.json');
    }

    /**
     * @expectedException \Puli\Json\SchemaException
     */
    public function testDecodeFailsIfSchemaFileInvalid()
    {
        $this->decoder->decode('{ "name": "Bernhard" }', $this->fixturesDir.'/schema-invalid.json');
    }

    /**
     * @expectedException \Puli\Json\SchemaException
     */
    public function testDecodeFailsIfSchemaObjectInvalid()
    {
        $this->decoder->decode('{ "name": "Bernhard" }', (object) array('id' => 12345));
    }

    /**
     * @expectedException \Puli\Json\SchemaException
     */
    public function testDecodeFailsIfInvalidSchemaNotRecognized()
    {
        // justinrainbow/json-schema cannot validate "anyOf", so the following
        // will load the schema successfully and fail when the file is validated
        // against the schema
        $this->decoder->decode('{ "name": "Bernhard" }', (object) array('type' => 12345));
    }

    /**
     * @expectedException \Puli\Json\InvalidJsonException
     */
    public function testDecodeFailsIfNotUtf8()
    {
        $win1258 = file_get_contents($this->fixturesDir.'/win-1258.json');

        $this->decoder->decode($win1258);
    }

    public function testDecodeFile()
    {
        $data = $this->decoder->decodeFile($this->fixturesDir.'/valid.json', $this->schemaFile);

        $this->assertInstanceOf('\stdClass', $data);
        $this->assertObjectHasAttribute('name', $data);
        $this->assertSame('Bernhard', $data->name);
    }

    /**
     * @expectedException \Puli\Json\FileNotFoundException
     */
    public function testDecodeFileFailsIfNotFound()
    {
        $this->decoder->decodeFile(__DIR__.'/bogus.json', $this->schemaFile);
    }

    public function testDecodeObjectAsObject()
    {
        $this->decoder->setObjectDecoding(JsonDecoder::OBJECT);

        $decoded = $this->decoder->decode('{ "name": "Bernhard" }');

        $this->assertEquals((object) array('name' => 'Bernhard'), $decoded);
    }

    public function testDecodeObjectAsArray()
    {
        $this->decoder->setObjectDecoding(JsonDecoder::ASSOC_ARRAY);

        $decoded = $this->decoder->decode('{ "name": "Bernhard" }');

        $this->assertEquals(array('name' => 'Bernhard'), $decoded);
    }

    public function provideInvalidObjectDecoding()
    {
        return array(
            array(JsonDecoder::STRING),
            array(JsonDecoder::FLOAT),
            array(1234),
        );
    }

    /**
     * @dataProvider provideInvalidObjectDecoding
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfInvalidObjectDecoding($invalidDecoding)
    {
        $this->decoder->setObjectDecoding($invalidDecoding);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSchemaNotSupportedForArrays()
    {
        $this->decoder->setObjectDecoding(JsonDecoder::ASSOC_ARRAY);

        $this->decoder->decode('{ "name": "Bernhard" }', $this->schemaObject);
    }

    /**
     * @expectedException \Puli\Json\InvalidJsonException
     */
    public function testMaxDepthExceeded()
    {
        $this->decoder->setMaxDepth(1);

        $this->decoder->decode('{ "name": "Bernhard" }');
    }

    public function testMaxDepthNotExceeded()
    {
        $this->decoder->setMaxDepth(2);

        $decoded = $this->decoder->decode('{ "name": "Bernhard" }');

        $this->assertEquals((object) array('name' => 'Bernhard'), $decoded);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testMaxDepthMustBeInteger()
    {
        $this->decoder->setMaxDepth('foo');
    }

    public function testDecodeBigIntAsFloat()
    {
        $this->decoder->setBigIntDecoding(JsonDecoder::FLOAT);

        $decoded = $this->decoder->decode('12312512423531123');

        $this->assertEquals(12312512423531123.0, $decoded);
    }

    public function testDecodeBigIntAsString()
    {
        $this->decoder->setBigIntDecoding(JsonDecoder::STRING);

        $decoded = $this->decoder->decode('12312512423531123');

        $this->assertEquals('12312512423531123', $decoded);
    }

    public function provideInvalidBigIntDecoding()
    {
        return array(
            array(JsonDecoder::OBJECT),
            array(JsonDecoder::ASSOC_ARRAY),
            array(1234),
        );
    }

    /**
     * @dataProvider provideInvalidBigIntDecoding
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfInvalidBigIntDecoding($invalidDecoding)
    {
        $this->decoder->setBigIntDecoding($invalidDecoding);
    }
}
