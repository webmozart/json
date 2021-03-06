<?php

/*
 * This file is part of the webmozart/json package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Json\Tests\Migration;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Webmozart\Json\Conversion\ConversionFailedException;
use Webmozart\Json\Conversion\JsonConverter;
use Webmozart\Json\Migration\MigratingConverter;
use Webmozart\Json\Migration\MigrationManager;
use Webmozart\Json\Migration\UnsupportedVersionException;

/**
 * @since  1.3
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class MigratingConverterTest extends TestCase
{
    /**
     * @var MockObject|JsonConverter
     */
    private $innerConverter;

    /**
     * @var MockObject|MigrationManager
     */
    private $migrationManager;

    /**
     * @var MigratingConverter
     */
    private $converter;

    protected function setUp(): void
    {
        $this->migrationManager = $this->getMockBuilder(MigrationManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->migrationManager->expects(self::any())
            ->method('getKnownVersions')
            ->willReturn(array('0.9', '1.0'));
        $this->innerConverter = $this->createMock(JsonConverter::class);
        $this->converter = new MigratingConverter($this->innerConverter, '1.0', $this->migrationManager);
    }

    public function testToJsonDowngradesIfLowerVersion(): void
    {
        $options = array(
            'inner_option' => 'value',
            'targetVersion' => '0.9',
        );

        $beforeMigration = (object) array(
            'version' => '1.0',
        );

        $afterMigration = (object) array(
            'version' => '0.9',
            'downgraded' => true,
        );

        $this->innerConverter->expects(self::once())
            ->method('toJson')
            ->with('DATA', $options)
            ->willReturn($beforeMigration);

        $this->migrationManager->expects(self::once())
            ->method('migrate')
            ->willReturnCallback(function (stdClass $jsonData, $targetVersion) use ($beforeMigration) {
                // with() in combination with argument cloning doesn't work,
                // since we *want* to modify the original data (not the clone) below
                Assert::assertEquals($beforeMigration, $jsonData);

                $jsonData->version = $targetVersion;
                $jsonData->downgraded = true;
            });

        self::assertEquals($afterMigration, $this->converter->toJson('DATA', $options));
    }

    public function testToJsonDoesNotMigrateCurrentVersion(): void
    {
        $options = array(
            'inner_option' => 'value',
            'targetVersion' => '1.0',
        );

        $jsonData = (object) array(
            'version' => '1.0',
        );

        $this->innerConverter->expects(self::once())
            ->method('toJson')
            ->with('DATA', $options)
            ->willReturn($jsonData);

        $this->migrationManager->expects(self::never())
            ->method('migrate');

        self::assertEquals($jsonData, $this->converter->toJson('DATA', $options));
    }

    public function testToJsonDoesNotMigrateIfNoTargetVersion(): void
    {
        $options = array(
            'inner_option' => 'value',
        );

        $jsonData = (object) array(
            'version' => '1.0',
        );

        $this->innerConverter->expects(self::once())
            ->method('toJson')
            ->with('DATA', $options)
            ->willReturn($jsonData);

        $this->migrationManager->expects(self::never())
            ->method('migrate');

        self::assertEquals($jsonData, $this->converter->toJson('DATA', $options));
    }

    public function testToJsonFailsIfTargetVersionTooHigh(): void
    {
        $this->expectException(UnsupportedVersionException::class);

        $this->converter->toJson('DATA', array('targetVersion' => '1.1'));
    }

    public function testToJsonFailsIfNotAnObject(): void
    {
        $this->expectException(ConversionFailedException::class);

        $options = array(
            'inner_option' => 'value',
            'targetVersion' => '1.0',
        );

        $this->innerConverter->expects(self::once())
            ->method('toJson')
            ->with('DATA', $options)
            ->willReturn('foobar');

        $this->migrationManager->expects(self::never())
            ->method('migrate');

        $this->converter->toJson('DATA', $options);
    }

    public function testFromJsonUpgradesIfVersionTooLow(): void
    {
        $options = array(
            'inner_option' => 'value',
        );

        $beforeMigration = (object) array(
            'version' => '0.9',
        );

        $afterMigration = (object) array(
            'version' => '1.0',
            'upgraded' => true,
        );

        $this->migrationManager->expects(self::once())
            ->method('migrate')
            ->willReturnCallback(function (stdClass $jsonData, $targetVersion) use ($beforeMigration) {
                Assert::assertEquals($beforeMigration, $jsonData);

                $jsonData->version = $targetVersion;
                $jsonData->upgraded = true;
            });

        $this->innerConverter->expects(self::once())
            ->method('fromJson')
            ->with($afterMigration, $options)
            ->willReturn('DATA');

        $result = $this->converter->fromJson(clone $beforeMigration, $options);

        self::assertSame('DATA', $result);
    }

    public function testFromJsonDoesNotMigrateCurrentVersion(): void
    {
        $options = array(
            'inner_option' => 'value',
        );

        $jsonData = (object) array(
            'version' => '1.0',
        );

        $this->migrationManager->expects(self::never())
            ->method('migrate');

        $this->innerConverter->expects(self::once())
            ->method('fromJson')
            ->with($jsonData, $options)
            ->willReturn('DATA');

        $result = $this->converter->fromJson(clone $jsonData, $options);

        self::assertSame('DATA', $result);
    }

    public function testFromJsonFailsIfSourceVersionTooHigh(): void
    {
        $this->expectException(UnsupportedVersionException::class);

        $jsonData = (object) array(
            'version' => '1.1',
        );

        $this->converter->fromJson($jsonData);
    }

    public function testFromJsonFailsIfNotAnObject(): void
    {
        $this->expectException(ConversionFailedException::class);

        $this->migrationManager->expects(self::never())
            ->method('migrate');

        $this->innerConverter->expects(self::never())
            ->method('fromJson');

        $this->converter->fromJson('foobar');
    }

    public function testFromJsonFailsIfVersionIsMissing(): void
    {
        $this->expectException(ConversionFailedException::class);

        $this->migrationManager->expects(self::never())
            ->method('migrate');

        $this->innerConverter->expects(self::never())
            ->method('fromJson');

        $this->converter->fromJson((object) array());
    }
}
