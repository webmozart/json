<?php

/*
 * This file is part of the webmozart/json package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Json\Tests\Validation;

use PHPUnit\Framework\Assert;
use PHPUnit_Framework_MockObject_MockObject;
use Webmozart\Json\Conversion\JsonConverter;
use Webmozart\Json\InvalidSchemaException;
use Webmozart\Json\JsonValidator;
use Webmozart\Json\Validation\ValidatingConverter;

/**
 * @since  1.3
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ValidatingConverterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PHPUnit_Framework_MockObject_MockObject|JsonConverter
     */
    private $innerConverter;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|JsonValidator
     */
    private $jsonValidator;

    /**
     * @var ValidatingConverter
     */
    private $converter;

    protected function setUp()
    {
        $this->innerConverter = $this->createMock('Webmozart\Json\Conversion\JsonConverter');
        $this->jsonValidator = $this->getMockBuilder('Webmozart\Json\JsonValidator')
            ->disableOriginalConstructor()
            ->getMock();
        $this->converter = new ValidatingConverter(
            $this->innerConverter,
            '/path/to/schema',
            $this->jsonValidator
        );
    }

    public function testToJson()
    {
        $options = array('option' => 'value');

        $jsonData = (object) array(
            'foo' => 'bar',
        );

        $this->innerConverter->expects($this->once())
            ->method('toJson')
            ->with('DATA', $options)
            ->willReturn($jsonData);

        $this->jsonValidator->expects($this->once())
            ->method('validate')
            ->with($jsonData, '/path/to/schema');

        $this->assertSame($jsonData, $this->converter->toJson('DATA', $options));
    }

    public function testToJsonWithoutSchema()
    {
        $options = array('option' => 'value');

        $jsonData = (object) array(
            'foo' => 'bar',
        );

        $this->converter = new ValidatingConverter(
            $this->innerConverter,
            null,
            $this->jsonValidator
        );

        $this->innerConverter->expects($this->once())
            ->method('toJson')
            ->with('DATA', $options)
            ->willReturn($jsonData);

        $this->jsonValidator->expects($this->once())
            ->method('validate')
            ->with($jsonData, null);

        $this->assertSame($jsonData, $this->converter->toJson('DATA', $options));
    }

    public function testToJsonRunsSchemaCallable()
    {
        $options = array('option' => 'value');

        $jsonData = (object) array(
            'foo' => 'bar',
        );

        $this->innerConverter->expects($this->once())
            ->method('toJson')
            ->with('DATA', $options)
            ->willReturn($jsonData);

        $this->jsonValidator->expects($this->once())
            ->method('validate')
            ->with($jsonData, '/dynamic/schema');

        $this->converter = new ValidatingConverter(
            $this->innerConverter,
            function ($data) use ($jsonData) {
                Assert::assertSame($jsonData, $data);

                return '/dynamic/schema';
            },
            $this->jsonValidator
        );

        $this->assertSame($jsonData, $this->converter->toJson('DATA', $options));
    }

    public function testFromJson()
    {
        $options = array('option' => 'value');

        $jsonData = (object) array(
            'foo' => 'bar',
        );

        $this->jsonValidator->expects($this->once())
            ->method('validate')
            ->with($jsonData, '/path/to/schema');

        $this->innerConverter->expects($this->once())
            ->method('fromJson')
            ->with($jsonData, $options)
            ->willReturn('DATA');

        $this->assertSame('DATA', $this->converter->fromJson($jsonData, $options));
    }

    public function testFromJsonWithoutSchema()
    {
        $options = array('option' => 'value');

        $jsonData = (object) array(
            'foo' => 'bar',
        );

        $this->converter = new ValidatingConverter(
            $this->innerConverter,
            null,
            $this->jsonValidator
        );

        $this->jsonValidator->expects($this->once())
            ->method('validate')
            ->with($jsonData, null);

        $this->innerConverter->expects($this->once())
            ->method('fromJson')
            ->with($jsonData, $options)
            ->willReturn('DATA');

        $this->assertSame('DATA', $this->converter->fromJson($jsonData, $options));
    }

    public function testFromJsonRunsSchemaCallable()
    {
        $options = array('option' => 'value');

        $jsonData = (object) array(
            'foo' => 'bar',
        );

        $this->jsonValidator->expects($this->once())
            ->method('validate')
            ->with($jsonData, '/dynamic/schema');

        $this->innerConverter->expects($this->once())
            ->method('fromJson')
            ->with($jsonData, $options)
            ->willReturn('DATA');

        $this->converter = new ValidatingConverter(
            $this->innerConverter,
            function ($data) use ($jsonData) {
                Assert::assertSame($jsonData, $data);

                return '/dynamic/schema';
            },
            $this->jsonValidator
        );

        $this->assertSame('DATA', $this->converter->fromJson($jsonData, $options));
    }

    /**
     * @expectedException \Webmozart\Json\Conversion\ConversionFailedException
     */
    public function testConvertSchemaExceptionToConversionException()
    {
        $options = array('option' => 'value');

        $jsonData = (object) array(
            'foo' => 'bar',
        );

        $this->innerConverter->expects($this->once())
            ->method('toJson')
            ->with('DATA', $options)
            ->willReturn($jsonData);

        $this->jsonValidator->expects($this->once())
            ->method('validate')
            ->willThrowException(new InvalidSchemaException());

        $this->converter->toJson('DATA', $options);
    }

    /**
     * @expectedException \Webmozart\Json\Conversion\ConversionFailedException
     */
    public function testConvertValidationErrorsToConversionException()
    {
        $options = array('option' => 'value');

        $jsonData = (object) array(
            'foo' => 'bar',
        );

        $this->innerConverter->expects($this->once())
            ->method('toJson')
            ->with('DATA', $options)
            ->willReturn($jsonData);

        $this->jsonValidator->expects($this->once())
            ->method('validate')
            ->willReturn(array(
                'First error',
                'Second error',
            ));

        $this->converter->toJson('DATA', $options);
    }
}
