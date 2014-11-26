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

use Webmozart\Json\JsonEncoder;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class JsonEncoderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var JsonEncoder
     */
    private $encoder;

    private $fixturesDir;

    private $schemaFile;

    private $schemaObject;

    private $tempFile;

    protected function setUp()
    {
        $this->encoder = new JsonEncoder();
        $this->fixturesDir = __DIR__.'/Fixtures';
        $this->schemaFile = $this->fixturesDir.'/schema.json';
        $this->schemaObject = json_decode(file_get_contents($this->schemaFile));
        $this->tempFile = tempnam(sys_get_temp_dir(), 'JsonEncoderTest');
    }

    protected function tearDown()
    {
        unlink($this->tempFile);
    }

    public function testEncode()
    {
        $data = (object) array('name' => 'Bernhard');

        $this->assertSame('{"name":"Bernhard"}', $this->encoder->encode($data));
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

    /**
     * JSON_ERROR_UTF8
     *
     * @expectedException \Webmozart\Json\EncodingFailedException
     * @expectedExceptionCode 5
     */
    public function testEncodeFailsIfNonUtf8()
    {
        $this->encoder->encode(file_get_contents($this->fixturesDir.'/win-1258.json'));
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
        $this->encoder->setEscapeUnicode(false);

        $this->assertSame('"ü"', $this->encoder->encode('ü'));
    }

    /**
     * JSON_ERROR_DEPTH
     *
     * @expectedException \Webmozart\Json\EncodingFailedException
     * @expectedExceptionCode 1
     */
    public function testMaxDepth0Exceeded()
    {
        $this->encoder->setMaxDepth(0);

        $this->encoder->encode((object) array('name' => 'Bernhard'));
    }

    public function testMaxDepth0NotExceeded()
    {
        $this->encoder->setMaxDepth(10);

        $this->assertSame('"Bernhard"', $this->encoder->encode('Bernhard'));
    }

    /**
     * JSON_ERROR_DEPTH
     *
     * @expectedException \Webmozart\Json\EncodingFailedException
     * @expectedExceptionCode 1
     */
    public function testMaxDepth1Exceeded()
    {
        $this->encoder->setMaxDepth(1);

        $this->encoder->encode((object) array('key' => (object) array('name' => 'Bernhard')));
    }

    public function testMaxDepth1NotExceeded()
    {
        $this->encoder->setMaxDepth(1);

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
    public function testMaxDepthMustBeZeroOrGreater()
    {
        $this->encoder->setMaxDepth(-1);
    }

    public function testPrettyPrinting()
    {
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

        $this->encoder->encodeFile($this->tempFile, $data);

        $this->assertFileExists($this->tempFile);
        $this->assertSame('{"name":"Bernhard"}', file_get_contents($this->tempFile));
    }

    /**
     * Test that the file name is present in the output.
     *
     * @expectedException \Webmozart\Json\ValidationFailedException
     * @expectedExceptionMessage JsonEncoderTest
     */
    public function testEncodeFileFailsIfValidationFailsWithSchemaFile()
    {
        $this->encoder->encodeFile($this->tempFile, 'foobar', $this->schemaFile);
    }

    /**
     * Test that the file name is present in the output.
     *
     * @expectedException \Webmozart\Json\ValidationFailedException
     * @expectedExceptionMessage JsonEncoderTest
     */
    public function testEncodeFileFailsIfValidationFailsWithSchemaObject()
    {
        $this->encoder->encodeFile($this->tempFile, 'foobar', $this->schemaObject);
    }

    /**
     * Test that the file name is present in the output.
     *
     * @expectedException \Webmozart\Json\EncodingFailedException
     * @expectedExceptionMessage JsonEncoderTest
     * @expectedExceptionCode 5
     */
    public function testEncodeFileFailsIfNonUtf8()
    {
        $this->encoder->encodeFile($this->tempFile, file_get_contents($this->fixturesDir.'/win-1258.json'));
    }
}
