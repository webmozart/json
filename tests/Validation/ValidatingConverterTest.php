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

use JsonSchema\Exception\InvalidSchemaException;
use JsonSchema\Validator;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Webmozart\Json\Conversion\ConversionFailedException;
use Webmozart\Json\Conversion\JsonConverter;
use Webmozart\Json\Validation\ValidatingConverter;

/**
 * @since  1.3
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ValidatingConverterTest extends TestCase
{
    /**
     * @var MockObject|JsonConverter
     */
    private $innerConverter;

    /**
     * @var MockObject|Validator
     */
    private $jsonValidator;

    /**
     * @var ValidatingConverter
     */
    private $converter;

    protected function setUp(): void
    {
        $this->innerConverter = $this->createMock(JsonConverter::class);
        $this->jsonValidator = $this->getMockBuilder(Validator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->converter = new ValidatingConverter(
            $this->innerConverter,
            '/path/to/schema',
            $this->jsonValidator
        );
    }

    public function testToJson(): void
    {
        $options = array('option' => 'value');

        $jsonData = (object) array(
            'foo' => 'bar',
        );

        $this->innerConverter->expects(self::once())
            ->method('toJson')
            ->with('DATA', $options)
            ->willReturn($jsonData);

        $this->jsonValidator->expects(self::once())
            ->method('validate')
            ->with($jsonData, '/path/to/schema');

        self::assertSame($jsonData, $this->converter->toJson('DATA', $options));
    }

    public function testToJsonWithoutSchema(): void
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

        $this->innerConverter->expects(self::once())
            ->method('toJson')
            ->with('DATA', $options)
            ->willReturn($jsonData);

        $this->jsonValidator->expects(self::once())
            ->method('validate')
            ->with($jsonData, null);

        self::assertSame($jsonData, $this->converter->toJson('DATA', $options));
    }

    public function testToJsonRunsSchemaCallable(): void
    {
        $options = array('option' => 'value');

        $jsonData = (object) array(
            'foo' => 'bar',
        );

        $this->innerConverter->expects(self::once())
            ->method('toJson')
            ->with('DATA', $options)
            ->willReturn($jsonData);

        $this->jsonValidator->expects(self::once())
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

        self::assertSame($jsonData, $this->converter->toJson('DATA', $options));
    }

    public function testFromJson(): void
    {
        $options = array('option' => 'value');

        $jsonData = (object) array(
            'foo' => 'bar',
        );

        $this->jsonValidator->expects(self::once())
            ->method('validate')
            ->with($jsonData, '/path/to/schema');

        $this->innerConverter->expects(self::once())
            ->method('fromJson')
            ->with($jsonData, $options)
            ->willReturn('DATA');

        self::assertSame('DATA', $this->converter->fromJson($jsonData, $options));
    }

    public function testFromJsonWithoutSchema(): void
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

        $this->jsonValidator->expects(self::once())
            ->method('validate')
            ->with($jsonData, null);

        $this->innerConverter->expects(self::once())
            ->method('fromJson')
            ->with($jsonData, $options)
            ->willReturn('DATA');

        self::assertSame('DATA', $this->converter->fromJson($jsonData, $options));
    }

    public function testFromJsonRunsSchemaCallable(): void
    {
        $options = array('option' => 'value');

        $jsonData = (object) array(
            'foo' => 'bar',
        );

        $this->jsonValidator->expects(self::once())
            ->method('validate')
            ->with($jsonData, '/dynamic/schema');

        $this->innerConverter->expects(self::once())
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

        self::assertSame('DATA', $this->converter->fromJson($jsonData, $options));
    }

    public function testConvertValidationErrorsToConversionException(): void
    {
        $this->expectException(ConversionFailedException::class);

        $options = array('option' => 'value');

        $jsonData = (object) array(
            'foo' => 'bar',
        );

        $this->innerConverter->expects(self::once())
            ->method('toJson')
            ->with('DATA', $options)
            ->willReturn($jsonData);

        $this->jsonValidator->expects(self::once())
            ->method('validate');

        $this->jsonValidator->expects(self::once())
            ->method('getErrors')->willReturn(array(
                'First error',
                ['Second error'],
            ));

        $this->converter->toJson('DATA', $options);
    }
}
