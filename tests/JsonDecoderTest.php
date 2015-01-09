<?php

/*
 * This file is part of the Webmozart JSON package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Json\Tests;

use Webmozart\Json\JsonDecoder;

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
     * @expectedException \Webmozart\Json\ValidationFailedException
     */
    public function testDecodeFailsIfValidationFailsWithSchemaFile()
    {
        $this->decoder->decode('"foobar"', $this->schemaFile);
    }

    /**
     * @expectedException \Webmozart\Json\ValidationFailedException
     */
    public function testDecodeFailsIfValidationFailsWithSchemaObject()
    {
        $this->decoder->decode('"foobar"', $this->schemaObject);
    }

    public function testDecodeUtf8()
    {
        $data = $this->decoder->decode('{"name":"B\u00e9rnhard"}');

        $this->assertEquals((object) array('name' => 'Bérnhard'), $data);
    }

    /**
     * JSON_ERROR_UTF8
     *
     * @expectedException \Webmozart\Json\DecodingFailedException
     * @expectedExceptionCode 5
     */
    public function testDecodeFailsIfNotUtf8()
    {
        if (false !== strpos(PHP_VERSION, 'ubuntu')) {
            $this->markTestSkipped('This error is not reported on PHP versions compiled for Ubuntu.');

            return;
        }

        $win1258 = file_get_contents($this->fixturesDir.'/win-1258.json');

        $this->decoder->decode($win1258);
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
     * JSON_ERROR_DEPTH
     *
     * @expectedException \Webmozart\Json\DecodingFailedException
     * @expectedExceptionCode 1
     */
    public function testMaxDepth1Exceeded()
    {
        $this->decoder->setMaxDepth(1);

        $this->decoder->decode('{ "name": "Bernhard" }');
    }

    public function testMaxDepth1NotExceeded()
    {
        $this->decoder->setMaxDepth(1);

        $this->assertSame('Bernhard', $this->decoder->decode('"Bernhard"'));
    }

    /**
     * JSON_ERROR_DEPTH
     *
     * @expectedException \Webmozart\Json\DecodingFailedException
     * @expectedExceptionCode 1
     */
    public function testMaxDepth2Exceeded()
    {
        $this->decoder->setMaxDepth(2);

        $this->decoder->decode('{ "key": { "name": "Bernhard" } }');
    }

    public function testMaxDepth2NotExceeded()
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

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testMaxDepthMustBeOneOrGreater()
    {
        $this->decoder->setMaxDepth(0);
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

    public function testDecodeFile()
    {
        $data = $this->decoder->decodeFile($this->fixturesDir.'/valid.json');

        $this->assertInstanceOf('\stdClass', $data);
        $this->assertObjectHasAttribute('name', $data);
        $this->assertSame('Bernhard', $data->name);
    }

    /**
     * Test that the file name is present in the output.
     *
     * @expectedException \Webmozart\Json\FileNotFoundException
     * @expectedExceptionMessage bogus.json
     */
    public function testDecodeFileFailsIfNotFound()
    {
        $this->decoder->decodeFile($this->fixturesDir.'/bogus.json');
    }

    /**
     * Test that the file name is present in the output.
     *
     * @expectedException \Webmozart\Json\ValidationFailedException
     * @expectedExceptionMessage invalid.json
     */
    public function testDecodeFileFailsIfValidationFailsWithSchemaFile()
    {
        $this->decoder->decodeFile($this->fixturesDir.'/invalid.json', $this->schemaFile);
    }

    /**
     * Test that the file name is present in the output.
     *
     * @expectedException \Webmozart\Json\ValidationFailedException
     * @expectedExceptionMessage invalid.json
     */
    public function testDecodeFileFailsIfValidationFailsWithSchemaObject()
    {
        $this->decoder->decodeFile($this->fixturesDir.'/invalid.json', $this->schemaObject);
    }

    /**
     * Test that the file name is present in the output.
     *
     * @expectedException \Webmozart\Json\DecodingFailedException
     * @expectedExceptionMessage win-1258.json
     * @expectedExceptionCode 5
     */
    public function testDecodeFileFailsIfNotUtf8()
    {
        if (false !== strpos(PHP_VERSION, 'ubuntu')) {
            $this->markTestSkipped('This error is not reported on PHP versions compiled for Ubuntu.');

            return;
        }

        $this->decoder->decodeFile($this->fixturesDir.'/win-1258.json');
    }

    /**
     * Test that the file name is present in the output.
     *
     * @expectedException \Webmozart\Json\SchemaException
     * @expectedExceptionMessage valid.json
     */
    public function testDecodeFileFailsIfSchemaInvalid()
    {
        $this->decoder->decodeFile($this->fixturesDir.'/valid.json', 'bogus.json');
    }
}
