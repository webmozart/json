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
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\Json\EncodingFailedException;
use Webmozart\Json\IOException;
use Webmozart\Json\JsonEncoder;
use Webmozart\Json\ValidationFailedException;

/**
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class JsonEncoderTest extends TestCase
{
    private const BINARY_INPUT = "\xff\xf0";

    /**
     * @var JsonEncoder
     */
    private $encoder;

    private $fixturesDir;

    private $schemaFile;

    private $schemaObject;

    private $tempDir;

    private $tempFile;

    public function provideValues(): array
    {
        return array(
            array(0, '0'),
            array(1, '1'),
            array(1234, '1234'),
            array('a', '"a"'),
            array('b', '"b"'),
            array('a/b', '"a\/b"'),
            array(12.34, '12.34'),
            array(true, 'true'),
            array(false, 'false'),
            array(null, 'null'),
            array(array(1, 2, 3, 4), '[1,2,3,4]'),
            array(array('foo' => 'bar', 'baz' => 'bam'), '{"foo":"bar","baz":"bam"}'),
            array((object) array('foo' => 'bar', 'baz' => 'bam'), '{"foo":"bar","baz":"bam"}'),
        );
    }

    protected function setUp(): void
    {
        while (false === @mkdir($this->tempDir = sys_get_temp_dir().'/webmozart-JsonEncoderTest'.random_int(10000, 99999), 0777, true)) {
        }

        $this->encoder = new JsonEncoder();
        $this->fixturesDir = __DIR__.'/Fixtures';
        $this->schemaFile = $this->fixturesDir.'/schema.json';
        $this->schemaObject = json_decode(file_get_contents($this->schemaFile));
        $this->tempFile = $this->tempDir.'/data.json';
    }

    protected function tearDown(): void
    {
        $filesystem = new Filesystem();

        // Ensure all files in the directory are writable before removing
        $filesystem->chmod($this->tempDir, 0755, 0000, true);
        $filesystem->remove($this->tempDir);
    }

    /**
     * @dataProvider provideValues
     */
    public function testEncode($value, $json): void
    {
        self::assertSame($json, $this->encoder->encode($value));
    }

    public function testEncodeWithSchemaFile(): void
    {
        $data = (object) array('name' => 'Bernhard');

        self::assertSame('{"name":"Bernhard"}', $this->encoder->encode($data, $this->schemaFile));
    }

    public function testEncodeWithSchemaObject(): void
    {
        $data = (object) array('name' => 'Bernhard');

        self::assertSame('{"name":"Bernhard"}', $this->encoder->encode($data, $this->schemaObject));
    }

    public function testEncodeFailsIfValidationFailsWithSchemaObject(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->encoder->encode('foobar', $this->schemaObject);
    }

    public function testEncodeUtf8(): void
    {
        $data = (object) array('name' => 'Bérnhard');

        self::assertSame('{"name":"B\u00e9rnhard"}', $this->encoder->encode($data));
    }

    /**
     * JSON_ERROR_UTF8.
     */
    public function testEncodeFailsIfNonUtf8(): void
    {
        $this->expectException(EncodingFailedException::class);
        $this->expectExceptionCode(5);

        $this->encoder->encode(file_get_contents($this->fixturesDir.'/win-1258.json'));
    }

    /**
     * JSON_ERROR_UTF8.
     */
    public function testEncodeFailsForBinaryValue(): void
    {
        $this->expectException(EncodingFailedException::class);
        $this->expectExceptionCode(5);

        $this->encoder->encode(self::BINARY_INPUT);
    }

    public function testEncodeEmptyArrayKey(): void
    {
        $data = array('' => 'Bernhard');

        self::assertSame('{"":"Bernhard"}', $this->encoder->encode($data));
    }

    public function testEncodeEmptyProperty(): void
    {
        $data = (object) array('' => 'Bernhard');

        self::assertSame('{"":"Bernhard"}', $this->encoder->encode($data));
    }

    public function testEncodeMagicEmptyPropertyAfter71(): void
    {
        $data = (object) array('_empty_' => 'Bernhard');

        self::assertSame('{"_empty_":"Bernhard"}', $this->encoder->encode($data));
    }

    public function testEncodeArrayAsArray(): void
    {
        $data = array('one', 'two');

        $this->encoder->setArrayEncoding(JsonEncoder::JSON_ARRAY);

        self::assertSame('["one","two"]', $this->encoder->encode($data));
    }

    public function testEncodeArrayAsObject(): void
    {
        $data = array('one', 'two');

        $this->encoder->setArrayEncoding(JsonEncoder::JSON_OBJECT);

        self::assertSame('{"0":"one","1":"two"}', $this->encoder->encode($data));
    }

    public function provideInvalidArrayEncoding(): array
    {
        return array(
            array(JsonEncoder::JSON_NUMBER),
            array(JsonEncoder::JSON_STRING),
            array(1234),
        );
    }

    /**
     * @dataProvider provideInvalidArrayEncoding
     */
    public function testFailIfInvalidArrayEncoding($invalidEncoding): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->encoder->setArrayEncoding($invalidEncoding);
    }

    public function testEncodeNumericAsString(): void
    {
        $data = '12345';

        $this->encoder->setNumericEncoding(JsonEncoder::JSON_STRING);

        self::assertSame('"12345"', $this->encoder->encode($data));
    }

    public function testEncodeIntegerStringAsInteger(): void
    {
        $data = '12345';

        $this->encoder->setNumericEncoding(JsonEncoder::JSON_NUMBER);

        self::assertSame('12345', $this->encoder->encode($data));
    }

    public function testEncodeIntegerFloatAsFloat(): void
    {
        $data = '123.45';

        $this->encoder->setNumericEncoding(JsonEncoder::JSON_NUMBER);

        self::assertSame('123.45', $this->encoder->encode($data));
    }

    public function provideInvalidNumericEncoding(): array
    {
        return array(
            array(JsonEncoder::JSON_ARRAY),
            array(JsonEncoder::JSON_OBJECT),
            array(1234),
        );
    }

    /**
     * @dataProvider provideInvalidNumericEncoding
     */
    public function testFailIfInvalidNumericEncoding($invalidEncoding): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->encoder->setNumericEncoding($invalidEncoding);
    }

    public function testGtLtEscaped(): void
    {
        $this->encoder->setEscapeGtLt(true);

        self::assertSame('"\u003C\u003E"', $this->encoder->encode('<>'));
    }

    public function testGtLtNotEscaped(): void
    {
        $this->encoder->setEscapeGtLt(false);

        self::assertSame('"<>"', $this->encoder->encode('<>'));
    }

    public function testAmpersandEscaped(): void
    {
        $this->encoder->setEscapeAmpersand(true);

        self::assertSame('"\u0026"', $this->encoder->encode('&'));
    }

    public function testAmpersandNotEscaped(): void
    {
        $this->encoder->setEscapeAmpersand(false);

        self::assertSame('"&"', $this->encoder->encode('&'));
    }

    public function testSingleQuoteEscaped(): void
    {
        $this->encoder->setEscapeSingleQuote(true);

        self::assertSame('"\u0027"', $this->encoder->encode("'"));
    }

    public function testSingleQuoteNotEscaped(): void
    {
        $this->encoder->setEscapeSingleQuote(false);

        self::assertSame('"\'"', $this->encoder->encode("'"));
    }

    public function testDoubleQuoteEscaped(): void
    {
        $this->encoder->setEscapeDoubleQuote(true);

        self::assertSame('"\u0022"', $this->encoder->encode('"'));
    }

    public function testDoubleQuoteNotEscaped(): void
    {
        $this->encoder->setEscapeDoubleQuote(false);

        self::assertSame('"\""', $this->encoder->encode('"'));
    }

    public function testSlashEscaped(): void
    {
        $this->encoder->setEscapeSlash(true);

        self::assertSame('"\\/"', $this->encoder->encode('/'));
    }

    public function testSlashNotEscaped(): void
    {
        $this->encoder->setEscapeSlash(false);

        self::assertSame('"/"', $this->encoder->encode('/'));
    }

    public function testUnicodeEscaped(): void
    {
        $this->encoder->setEscapeUnicode(true);

        self::assertSame('"\u00fc"', $this->encoder->encode('ü'));
    }

    public function testUnicodeNotEscaped(): void
    {
        $this->encoder->setEscapeUnicode(false);

        self::assertSame('"ü"', $this->encoder->encode('ü'));
    }

    /**
     * JSON_ERROR_DEPTH.
     */
    public function testMaxDepth1Exceeded(): void
    {
        $this->expectException(EncodingFailedException::class);
        $this->expectExceptionCode(1);

        $this->encoder->setMaxDepth(1);

        $this->encoder->encode((object) array('name' => 'Bernhard'));
    }

    public function testMaxDepth1NotExceeded(): void
    {
        $this->encoder->setMaxDepth(1);

        self::assertSame('"Bernhard"', $this->encoder->encode('Bernhard'));
    }

    /**
     * JSON_ERROR_DEPTH.
     */
    public function testMaxDepth2Exceeded(): void
    {
        $this->expectException(EncodingFailedException::class);
        $this->expectExceptionCode(1);

        $this->encoder->setMaxDepth(2);

        $this->encoder->encode((object) array('key' => (object) array('name' => 'Bernhard')));
    }

    public function testMaxDepth2NotExceeded(): void
    {
        $this->encoder->setMaxDepth(2);

        self::assertSame('{"name":"Bernhard"}', $this->encoder->encode((object) array('name' => 'Bernhard')));
    }

    public function testMaxDepthMustBeInteger(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->encoder->setMaxDepth('foo');
    }

    public function testMaxDepthMustBeOneOrGreater(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->encoder->setMaxDepth(0);
    }

    public function testPrettyPrinting(): void
    {
        $this->encoder->setPrettyPrinting(true);

        self::assertSame("{\n    \"name\": \"Bernhard\"\n}", $this->encoder->encode((object) array('name' => 'Bernhard')));
    }

    public function testNoPrettyPrinting(): void
    {
        $this->encoder->setPrettyPrinting(false);

        self::assertSame('{"name":"Bernhard"}', $this->encoder->encode((object) array('name' => 'Bernhard')));
    }

    public function testTerminateWithLineFeed(): void
    {
        $this->encoder->setTerminateWithLineFeed(true);

        self::assertSame('{"name":"Bernhard"}'."\n", $this->encoder->encode((object) array('name' => 'Bernhard')));
    }

    public function testDoNotTerminateWithLineFeed(): void
    {
        $this->encoder->setTerminateWithLineFeed(false);

        self::assertSame('{"name":"Bernhard"}', $this->encoder->encode((object) array('name' => 'Bernhard')));
    }

    public function testEncodeFile(): void
    {
        $data = (object) array('name' => 'Bernhard');

        $this->encoder->encodeFile($data, $this->tempFile);

        self::assertFileExists($this->tempFile);
        self::assertSame('{"name":"Bernhard"}', file_get_contents($this->tempFile));
    }

    public function testEncodeFileCreatesMissingDirectories(): void
    {
        $data = (object) array('name' => 'Bernhard');

        $this->encoder->encodeFile($data, $this->tempDir.'/sub/data.json');

        self::assertFileExists($this->tempDir.'/sub/data.json');
        self::assertSame('{"name":"Bernhard"}', file_get_contents($this->tempDir.'/sub/data.json'));
    }

    /**
     * This test fails on my docker php:7.3-cli docker container
     * The chmod sets 0000 on the file but `file_get_contents` still reads the content.
     * However this test is successful on Travis.
     */
    public function testEncodeFileFailsIfNotWritable(): void
    {
        $data = (object) array('name' => 'Bernhard');

        touch($this->tempFile);
        chmod($this->tempFile, 0400);

        // Test that the file name is present in the output.
        $this->expectException(IOException::class);
        $this->expectExceptionMessage($this->tempFile);

        $this->encoder->encodeFile($data, $this->tempFile);
    }

    public function testEncodeFileFailsIfValidationFailsWithSchemaObject(): void
    {
        // Test that the file name is present in the output.
        $this->expectException(ValidationFailedException::class);
        $this->expectExceptionMessage($this->tempFile);

        $this->encoder->encodeFile('foobar', $this->tempFile, $this->schemaObject);
    }

    public function testEncodeFileFailsIfNonUtf8(): void
    {
        // Test that the file name is present in the output.
        $this->expectException(EncodingFailedException::class);
        $this->expectExceptionMessage($this->tempFile);
        $this->expectExceptionCode(5);

        $this->encoder->encodeFile(file_get_contents($this->fixturesDir.'/win-1258.json'),
            $this->tempFile);
    }
}
