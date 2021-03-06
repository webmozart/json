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

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;
use Webmozart\Json\DecodingFailedException;
use Webmozart\Json\FileNotFoundException;
use Webmozart\Json\IOException;
use Webmozart\Json\JsonDecoder;
use Webmozart\Json\ValidationFailedException;

/**
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class JsonDecoderTest extends TestCase
{
    /**
     * @var JsonDecoder
     */
    private $decoder;

    private $fixturesDir;

    private $schemaFile;

    private $schemaObject;

    protected function setUp(): void
    {
        $this->decoder = new JsonDecoder();
        $this->fixturesDir = __DIR__.'/Fixtures';
        $this->schemaFile = $this->fixturesDir.'/schema.json';
        $this->schemaObject = json_decode(file_get_contents($this->schemaFile));
    }

    public function testDecode(): void
    {
        $data = $this->decoder->decode('{ "name": "Bernhard" }');

        self::assertInstanceOf(stdClass::class, $data);
        self::assertObjectHasAttribute('name', $data);
        self::assertSame('Bernhard', $data->name);
    }

    public function testDecodeEmptyObject(): void
    {
        $data = $this->decoder->decode('{}');

        self::assertEquals((object) array(), $data);
        self::assertInstanceOf(stdClass::class, $data);
    }

    public function testDecodeNull(): void
    {
        $data = $this->decoder->decode('null');

        self::assertNull($data);
    }

    public function testDecodeNestedEmptyObject(): void
    {
        $data = $this->decoder->decode('{ "empty": {} }');

        self::assertEquals((object) array('empty' => (object) array()), $data);
        self::assertInstanceOf(stdClass::class, $data);
        self::assertInstanceOf(stdClass::class, $data->empty);
    }

    public function testDecodeWithSchemaFile(): void
    {
        $data = $this->decoder->decode('{ "name": "Bernhard" }', $this->schemaFile);

        self::assertInstanceOf(stdClass::class, $data);
        self::assertObjectHasAttribute('name', $data);
        self::assertSame('Bernhard', $data->name);
    }

    public function testDecodeWithSchemaObject(): void
    {
        $data = $this->decoder->decode('{ "name": "Bernhard" }', $this->schemaObject);

        self::assertInstanceOf(stdClass::class, $data);
        self::assertObjectHasAttribute('name', $data);
        self::assertSame('Bernhard', $data->name);
    }

    public function testDecodeFailsIfValidationFailsWithSchemaObject(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->decoder->decode('"foobar"', $this->schemaObject);
    }

    public function testDecodeUtf8(): void
    {
        $data = $this->decoder->decode('{"name":"B\u00e9rnhard"}');

        self::assertEquals((object) array('name' => 'Bérnhard'), $data);
    }

    /**
     * JSON_ERROR_UTF8.
     */
    public function testDecodeFailsIfNotUtf8(): void
    {
        if (defined('JSON_C_VERSION')) {
            self::markTestSkipped('This error is not reported when using JSONC.');
        }

        $this->expectException(DecodingFailedException::class);
        $this->expectExceptionCode(5);

        $win1258 = file_get_contents($this->fixturesDir.'/win-1258.json');

        $this->decoder->decode($win1258);
    }

    public function testDecodeObjectAsObject(): void
    {
        $this->decoder->setObjectDecoding(JsonDecoder::OBJECT);

        $decoded = $this->decoder->decode('{ "name": "Bernhard" }');

        self::assertEquals((object) array('name' => 'Bernhard'), $decoded);
    }

    public function testDecodeObjectAsArray(): void
    {
        $this->decoder->setObjectDecoding(JsonDecoder::ASSOC_ARRAY);

        $decoded = $this->decoder->decode('{ "name": "Bernhard" }');

        self::assertEquals(array('name' => 'Bernhard'), $decoded);
    }

    public function testDecodeEmptyArrayKey(): void
    {
        $data = array('' => 'Bernhard');

        $this->decoder->setObjectDecoding(JsonDecoder::ASSOC_ARRAY);

        self::assertEquals($data, $this->decoder->decode('{"":"Bernhard"}'));
    }

    public function testDecodeEmptyProperty(): void
    {
        $data = (object) array('' => 'Bernhard');

        self::assertEquals($data, $this->decoder->decode('{"":"Bernhard"}'));
    }

    public function testDecodeMagicEmptyPropertyAfter71(): void
    {
        $data = (object) array('_empty_' => 'Bernhard');

        self::assertEquals($data, $this->decoder->decode('{"_empty_":"Bernhard"}'));
    }

    public function provideInvalidObjectDecoding(): array
    {
        return array(
            array(JsonDecoder::STRING),
            array(JsonDecoder::FLOAT),
            array(1234),
        );
    }

    /**
     * @dataProvider provideInvalidObjectDecoding
     */
    public function testFailIfInvalidObjectDecoding($invalidDecoding): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->decoder->setObjectDecoding($invalidDecoding);
    }

    public function testSchemaNotSupportedForArrays(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->decoder->setObjectDecoding(JsonDecoder::ASSOC_ARRAY);

        $this->decoder->decode('{ "name": "Bernhard" }', $this->schemaObject);
    }

    /**
     * JSON_ERROR_DEPTH.
     */
    public function testMaxDepth1Exceeded(): void
    {
        $this->expectException(DecodingFailedException::class);
        $this->expectExceptionCode(1);

        $this->decoder->setMaxDepth(1);

        $this->decoder->decode('{ "name": "Bernhard" }');
    }

    public function testMaxDepth1NotExceeded(): void
    {
        $this->decoder->setMaxDepth(1);

        self::assertSame('Bernhard', $this->decoder->decode('"Bernhard"'));
    }

    /**
     * JSON_ERROR_DEPTH.
     */
    public function testMaxDepth2Exceeded(): void
    {
        $this->expectException(DecodingFailedException::class);
        $this->expectExceptionCode(1);

        $this->decoder->setMaxDepth(2);

        $this->decoder->decode('{ "key": { "name": "Bernhard" } }');
    }

    public function testMaxDepth2NotExceeded(): void
    {
        $this->decoder->setMaxDepth(2);

        $decoded = $this->decoder->decode('{ "name": "Bernhard" }');

        self::assertEquals((object) array('name' => 'Bernhard'), $decoded);
    }

    public function testMaxDepthMustBeInteger(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->decoder->setMaxDepth('foo');
    }

    public function testMaxDepthMustBeOneOrGreater(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->decoder->setMaxDepth(0);
    }

    public function testDecodeBigIntAsFloat(): void
    {
        $this->decoder->setBigIntDecoding(JsonDecoder::FLOAT);

        $decoded = $this->decoder->decode('12312512423531123');

        self::assertEquals(12312512423531123.0, $decoded);
    }

    public function testDecodeBigIntAsString(): void
    {
        $this->decoder->setBigIntDecoding(JsonDecoder::STRING);

        $decoded = $this->decoder->decode('12312512423531123');

        self::assertEquals('12312512423531123', $decoded);
    }

    public function provideInvalidBigIntDecoding(): array
    {
        return array(
            array(JsonDecoder::OBJECT),
            array(JsonDecoder::ASSOC_ARRAY),
            array(1234),
        );
    }

    /**
     * @dataProvider provideInvalidBigIntDecoding
     */
    public function testFailIfInvalidBigIntDecoding($invalidDecoding): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->decoder->setBigIntDecoding($invalidDecoding);
    }

    public function testDecodeFile(): void
    {
        $data = $this->decoder->decodeFile($this->fixturesDir.'/valid.json');

        self::assertInstanceOf(stdClass::class, $data);
        self::assertObjectHasAttribute('name', $data);
        self::assertSame('Bernhard', $data->name);
    }

    /**
     * This test fails on my docker php:7.3-cli docker container
     * The chmod sets 0000 on the file but `file_get_contents` still reads the content.
     * However this test is successful on Travis.
     */
    public function testDecodeFileFailsIfNotReadable(): void
    {
        if ('\\' === DIRECTORY_SEPARATOR) {
            self::markTestSkipped('Cannot deny read access on Windows.');
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'JsonDecoderTest');
        file_put_contents($tempFile, file_get_contents($this->fixturesDir.'/valid.json'));

        chmod($tempFile, 0000);

        // Test that the file name is present in the output.
        $this->expectException(IOException::class);
        $this->expectExceptionMessage($tempFile);

        $this->decoder->decodeFile($tempFile);
    }

    /**
     * Test that the file name is present in the output.
     */
    public function testDecodeFileFailsIfNotFound(): void
    {
        $this->expectException(FileNotFoundException::class);
        $this->expectExceptionMessage('bogus.json');

        $this->decoder->decodeFile($this->fixturesDir.'/bogus.json');
    }

    /**
     * Test that the file name is present in the output.
     */
    public function testDecodeFileFailsIfValidationFailsWithSchemaObject(): void
    {
        $this->expectException(ValidationFailedException::class);
        $this->expectExceptionMessage('invalid.json');

        $this->decoder->decodeFile($this->fixturesDir.'/invalid.json', $this->schemaObject);
    }

    /**
     * Test that the file name is present in the output.
     */
    public function testDecodeFileFailsIfNotUtf8(): void
    {
        if (defined('JSON_C_VERSION')) {
            self::markTestSkipped('This error is not reported when using JSONC.');
        }

        $this->expectException(DecodingFailedException::class);
        $this->expectExceptionMessage('win-1258.json');
        $this->expectExceptionCode(5);

        $this->decoder->decodeFile($this->fixturesDir.'/win-1258.json');
    }
}
