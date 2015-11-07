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

use Symfony\Component\Filesystem\Filesystem;
use Webmozart\Json\JsonEncoder;

/**
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class JsonEncoderTest extends \PHPUnit_Framework_TestCase
{
    const BINARY_INPUT = "\xff\xf0";

    /**
     * @var JsonEncoder
     */
    private $encoder;

    private $fixturesDir;

    private $schemaFile;

    private $schemaObject;

    private $tempDir;

    private $tempFile;

    public function provideValues()
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

    protected function setUp()
    {
        while (false === @mkdir($this->tempDir = sys_get_temp_dir().'/webmozart-JsonEncoderTest'.rand(10000, 99999), 0777, true)) {
        }

        $this->encoder = new JsonEncoder();
        $this->fixturesDir = __DIR__.'/Fixtures';
        $this->schemaFile = $this->fixturesDir.'/schema.json';
        $this->schemaObject = json_decode(file_get_contents($this->schemaFile));
        $this->tempFile = $this->tempDir.'/data.json';
    }

    protected function tearDown()
    {
        $filesystem = new Filesystem();

        // Ensure all files in the directory are writable before removing
        $filesystem->chmod($this->tempDir, 0755, 0000, true);
        $filesystem->remove($this->tempDir);
    }

    /**
     * @dataProvider provideValues
     */
    public function testEncode($value, $json)
    {
        $this->assertSame($json, $this->encoder->encode($value));
    }

    public function testEncodeWithSchemaFile()
    {
        $data = (object) array('name' => 'Bernhard');

        $this->assertSame('{"name":"Bernhard"}', $this->encoder->encode($data, $this->schemaFile));
    }

    public function testEncodeWithSchemaObject()
    {
        $data = (object) array('name' => 'Bernhard');

        $this->assertSame('{"name":"Bernhard"}', $this->encoder->encode($data, $this->schemaObject));
    }

    /**
     * @expectedException \Webmozart\Json\ValidationFailedException
     */
    public function testEncodeFailsIfValidationFailsWithSchemaFile()
    {
        $this->encoder->encode('foobar', $this->schemaFile);
    }

    /**
     * @expectedException \Webmozart\Json\ValidationFailedException
     */
    public function testEncodeFailsIfValidationFailsWithSchemaObject()
    {
        $this->encoder->encode('foobar', $this->schemaObject);
    }

    public function testEncodeUtf8()
    {
        $data = (object) array('name' => 'Bérnhard');

        $this->assertSame('{"name":"B\u00e9rnhard"}', $this->encoder->encode($data));
    }

    /**
     * JSON_ERROR_UTF8.
     *
     * @expectedException \Webmozart\Json\EncodingFailedException
     * @expectedExceptionCode 5
     */
    public function testEncodeFailsIfNonUtf8()
    {
        if (version_compare(PHP_VERSION, '5.5.0', '<')) {
            $this->markTestSkipped('PHP >= 5.5.0 only');

            return;
        }

        $this->encoder->encode(file_get_contents($this->fixturesDir.'/win-1258.json'));
    }

    /**
     * JSON_ERROR_UTF8.
     *
     * @expectedException \Webmozart\Json\EncodingFailedException
     * @expectedExceptionCode 5
     */
    public function testEncodeFailsForBinaryValue()
    {
        $this->encoder->encode(self::BINARY_INPUT);
    }

    public function testEncodeArrayAsArray()
    {
        $data = array('one', 'two');

        $this->encoder->setArrayEncoding(JsonEncoder::JSON_ARRAY);

        $this->assertSame('["one","two"]', $this->encoder->encode($data));
    }

    public function testEncodeArrayAsObject()
    {
        $data = array('one', 'two');

        $this->encoder->setArrayEncoding(JsonEncoder::JSON_OBJECT);

        $this->assertSame('{"0":"one","1":"two"}', $this->encoder->encode($data));
    }

    public function provideInvalidArrayEncoding()
    {
        return array(
            array(JsonEncoder::JSON_NUMBER),
            array(JsonEncoder::JSON_STRING),
            array(1234),
        );
    }

    /**
     * @dataProvider provideInvalidArrayEncoding
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfInvalidArrayEncoding($invalidEncoding)
    {
        $this->encoder->setArrayEncoding($invalidEncoding);
    }

    public function testEncodeNumericAsString()
    {
        $data = '12345';

        $this->encoder->setNumericEncoding(JsonEncoder::JSON_STRING);

        $this->assertSame('"12345"', $this->encoder->encode($data));
    }

    public function testEncodeIntegerStringAsInteger()
    {
        $data = '12345';

        $this->encoder->setNumericEncoding(JsonEncoder::JSON_NUMBER);

        $this->assertSame('12345', $this->encoder->encode($data));
    }

    public function testEncodeIntegerFloatAsFloat()
    {
        $data = '123.45';

        $this->encoder->setNumericEncoding(JsonEncoder::JSON_NUMBER);

        $this->assertSame('123.45', $this->encoder->encode($data));
    }

    public function provideInvalidNumericEncoding()
    {
        return array(
            array(JsonEncoder::JSON_ARRAY),
            array(JsonEncoder::JSON_OBJECT),
            array(1234),
        );
    }

    /**
     * @dataProvider provideInvalidNumericEncoding
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfInvalidNumericEncoding($invalidEncoding)
    {
        $this->encoder->setNumericEncoding($invalidEncoding);
    }

    public function testGtLtEscaoed()
    {
        $this->encoder->setEscapeGtLt(true);

        $this->assertSame('"\u003C\u003E"', $this->encoder->encode('<>'));
    }

    public function testGtLtNotEscaoed()
    {
        $this->encoder->setEscapeGtLt(false);

        $this->assertSame('"<>"', $this->encoder->encode('<>'));
    }

    public function testAmpersandEscaped()
    {
        $this->encoder->setEscapeAmpersand(true);

        $this->assertSame('"\u0026"', $this->encoder->encode('&'));
    }

    public function testAmpersandNotEscaped()
    {
        $this->encoder->setEscapeAmpersand(false);

        $this->assertSame('"&"', $this->encoder->encode('&'));
    }

    public function testSingleQuoteEscaped()
    {
        $this->encoder->setEscapeSingleQuote(true);

        $this->assertSame('"\u0027"', $this->encoder->encode("'"));
    }

    public function testSingleQuoteNotEscaped()
    {
        $this->encoder->setEscapeSingleQuote(false);

        $this->assertSame('"\'"', $this->encoder->encode("'"));
    }

    public function testDoubleQuoteEscaped()
    {
        $this->encoder->setEscapeDoubleQuote(true);

        $this->assertSame('"\u0022"', $this->encoder->encode('"'));
    }

    public function testDoubleQuoteNotEscaped()
    {
        $this->encoder->setEscapeDoubleQuote(false);

        $this->assertSame('"\""', $this->encoder->encode('"'));
    }

    public function testSlashEscaped()
    {
        $this->encoder->setEscapeSlash(true);

        $this->assertSame('"\\/"', $this->encoder->encode('/'));
    }

    public function testSlashNotEscaped()
    {
        $this->encoder->setEscapeSlash(false);

        $this->assertSame('"/"', $this->encoder->encode('/'));
    }

    public function testUnicodeEscaped()
    {
        $this->encoder->setEscapeUnicode(true);

        $this->assertSame('"\u00fc"', $this->encoder->encode('ü'));
    }

    public function testUnicodeNotEscaped()
    {
        if (version_compare(PHP_VERSION, '5.4.0', '<')) {
            $this->markTestSkipped('PHP >= 5.4.0 only');

            return;
        }

        $this->encoder->setEscapeUnicode(false);

        $this->assertSame('"ü"', $this->encoder->encode('ü'));
    }

    /**
     * JSON_ERROR_DEPTH.
     *
     * @expectedException \Webmozart\Json\EncodingFailedException
     * @expectedExceptionCode 1
     */
    public function testMaxDepth1Exceeded()
    {
        if (version_compare(PHP_VERSION, '5.5.0', '<')) {
            $this->markTestSkipped('PHP >= 5.5.0 only');

            return;
        }

        $this->encoder->setMaxDepth(1);

        $this->encoder->encode((object) array('name' => 'Bernhard'));
    }

    public function testMaxDepth1NotExceeded()
    {
        $this->encoder->setMaxDepth(1);

        $this->assertSame('"Bernhard"', $this->encoder->encode('Bernhard'));
    }

    /**
     * JSON_ERROR_DEPTH.
     *
     * @expectedException \Webmozart\Json\EncodingFailedException
     * @expectedExceptionCode 1
     */
    public function testMaxDepth2Exceeded()
    {
        if (version_compare(PHP_VERSION, '5.5.0', '<')) {
            $this->markTestSkipped('PHP >= 5.5.0 only');

            return;
        }

        $this->encoder->setMaxDepth(2);

        $this->encoder->encode((object) array('key' => (object) array('name' => 'Bernhard')));
    }

    public function testMaxDepth2NotExceeded()
    {
        $this->encoder->setMaxDepth(2);

        $this->assertSame('{"name":"Bernhard"}', $this->encoder->encode((object) array('name' => 'Bernhard')));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testMaxDepthMustBeInteger()
    {
        $this->encoder->setMaxDepth('foo');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testMaxDepthMustBeOneOrGreater()
    {
        $this->encoder->setMaxDepth(0);
    }

    public function testPrettyPrinting()
    {
        if (version_compare(PHP_VERSION, '5.4.0', '<')) {
            $this->markTestSkipped('PHP >= 5.4.0 only');

            return;
        }

        $this->encoder->setPrettyPrinting(true);

        $this->assertSame("{\n    \"name\": \"Bernhard\"\n}", $this->encoder->encode((object) array('name' => 'Bernhard')));
    }

    public function testNoPrettyPrinting()
    {
        $this->encoder->setPrettyPrinting(false);

        $this->assertSame('{"name":"Bernhard"}', $this->encoder->encode((object) array('name' => 'Bernhard')));
    }

    public function testTerminateWithLineFeed()
    {
        $this->encoder->setTerminateWithLineFeed(true);

        $this->assertSame('{"name":"Bernhard"}'."\n", $this->encoder->encode((object) array('name' => 'Bernhard')));
    }

    public function testDoNotTerminateWithLineFeed()
    {
        $this->encoder->setTerminateWithLineFeed(false);

        $this->assertSame('{"name":"Bernhard"}', $this->encoder->encode((object) array('name' => 'Bernhard')));
    }

    public function testEncodeFile()
    {
        $data = (object) array('name' => 'Bernhard');

        $this->encoder->encodeFile($data, $this->tempFile);

        $this->assertFileExists($this->tempFile);
        $this->assertSame('{"name":"Bernhard"}', file_get_contents($this->tempFile));
    }

    public function testEncodeFileCreatesMissingDirectories()
    {
        $data = (object) array('name' => 'Bernhard');

        $this->encoder->encodeFile($data, $this->tempDir.'/sub/data.json');

        $this->assertFileExists($this->tempDir.'/sub/data.json');
        $this->assertSame('{"name":"Bernhard"}', file_get_contents($this->tempDir.'/sub/data.json'));
    }

    public function testEncodeFileFailsIfNotWritable()
    {
        $data = (object) array('name' => 'Bernhard');

        touch($this->tempFile);
        chmod($this->tempFile, 0400);

        // Test that the file name is present in the output.
        $this->setExpectedException(
            '\Webmozart\Json\IOException',
            $this->tempFile
        );

        $this->encoder->encodeFile($data, $this->tempFile);
    }

    public function testEncodeFileFailsIfValidationFailsWithSchemaFile()
    {
        // Test that the file name is present in the output.
        $this->setExpectedException(
            '\Webmozart\Json\ValidationFailedException',
            $this->tempFile
        );

        $this->encoder->encodeFile('foobar', $this->tempFile, $this->schemaFile);
    }

    public function testEncodeFileFailsIfValidationFailsWithSchemaObject()
    {
        // Test that the file name is present in the output.
        $this->setExpectedException(
            '\Webmozart\Json\ValidationFailedException',
            $this->tempFile
        );

        $this->encoder->encodeFile('foobar', $this->tempFile, $this->schemaObject);
    }

    public function testEncodeFileFailsIfNonUtf8()
    {
        if (version_compare(PHP_VERSION, '5.5.0', '<')) {
            $this->markTestSkipped('PHP >= 5.5.0 only');

            return;
        }

        // Test that the file name is present in the output.
        $this->setExpectedException(
            '\Webmozart\Json\EncodingFailedException',
            $this->tempFile,
            5
        );

        $this->encoder->encodeFile(file_get_contents($this->fixturesDir.'/win-1258.json'),
            $this->tempFile);
    }
}
