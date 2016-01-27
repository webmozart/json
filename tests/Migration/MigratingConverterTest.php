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

use PHPUnit_Framework_Assert;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;
use stdClass;
use Webmozart\Json\Conversion\JsonConverter;
use Webmozart\Json\Migration\MigratingConverter;
use Webmozart\Json\Migration\MigrationManager;

/**
 * @since  1.3
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class MigratingConverterTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var PHPUnit_Framework_MockObject_MockObject|JsonConverter
     */
    private $innerConverter;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|MigrationManager
     */
    private $migrationManager;

    /**
     * @var MigratingConverter
     */
    private $converter;

    protected function setUp()
    {
        $this->migrationManager = $this->getMockBuilder('Webmozart\Json\Migration\MigrationManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->migrationManager->expects($this->any())
            ->method('getKnownVersions')
            ->willReturn(array('0.9', '1.0'));
        $this->innerConverter = $this->getMock('Webmozart\Json\Conversion\JsonConverter');
        $this->converter = new MigratingConverter($this->innerConverter, '1.0', $this->migrationManager);
    }

    public function testToJsonDowngradesIfLowerVersion()
    {
        $options = array(
            'inner_option' => 'value',
            'target_version' => '0.9',
        );

        $beforeMigration = (object) array(
            'version' => '1.0',
        );

        $afterMigration = (object) array(
            'version' => '0.9',
            'downgraded' => true,
        );

        $this->innerConverter->expects($this->once())
            ->method('toJson')
            ->with('DATA', $options)
            ->willReturn($beforeMigration);

        $this->migrationManager->expects($this->once())
            ->method('migrate')
            ->willReturnCallback(function (stdClass $jsonData, $targetVersion) use ($beforeMigration) {
                // with() in combination with argument cloning doesn't work,
                // since we *want* to modify the original data (not the clone) below
                PHPUnit_Framework_Assert::assertEquals($beforeMigration, $jsonData);

                $jsonData->version = $targetVersion;
                $jsonData->downgraded = true;
            });

        $this->assertEquals($afterMigration, $this->converter->toJson('DATA', $options));
    }

    public function testToJsonDoesNotMigrateCurrentVersion()
    {
        $options = array(
            'inner_option' => 'value',
            'target_version' => '1.0',
        );

        $jsonData = (object) array(
            'version' => '1.0',
        );

        $this->innerConverter->expects($this->once())
            ->method('toJson')
            ->with('DATA', $options)
            ->willReturn($jsonData);

        $this->migrationManager->expects($this->never())
            ->method('migrate');

        $this->assertEquals($jsonData, $this->converter->toJson('DATA', $options));
    }

    public function testToJsonDoesNotMigrateIfNoTargetVersion()
    {
        $options = array(
            'inner_option' => 'value',
        );

        $jsonData = (object) array(
            'version' => '1.0',
        );

        $this->innerConverter->expects($this->once())
            ->method('toJson')
            ->with('DATA', $options)
            ->willReturn($jsonData);

        $this->migrationManager->expects($this->never())
            ->method('migrate');

        $this->assertEquals($jsonData, $this->converter->toJson('DATA', $options));
    }

    /**
     * @expectedException \Webmozart\Json\Migration\UnsupportedVersionException
     */
    public function testToJsonFailsIfTargetVersionTooHigh()
    {
        $this->converter->toJson('DATA', array('target_version' => '1.1'));
    }

    /**
     * @expectedException \Webmozart\Json\Conversion\ConversionException
     */
    public function testToJsonFailsIfNotAnObject()
    {
        $options = array(
            'inner_option' => 'value',
            'target_version' => '1.0',
        );

        $this->innerConverter->expects($this->once())
            ->method('toJson')
            ->with('DATA', $options)
            ->willReturn('foobar');

        $this->migrationManager->expects($this->never())
            ->method('migrate');

        $this->converter->toJson('DATA', $options);
    }

    public function testFromJsonUpgradesIfVersionTooLow()
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

        $this->migrationManager->expects($this->once())
            ->method('migrate')
            ->willReturnCallback(function (stdClass $jsonData, $targetVersion) use ($beforeMigration) {
                PHPUnit_Framework_Assert::assertEquals($beforeMigration, $jsonData);

                $jsonData->version = $targetVersion;
                $jsonData->upgraded = true;
            });

        $this->innerConverter->expects($this->once())
            ->method('fromJson')
            ->with($afterMigration, $options)
            ->willReturn('DATA');

        $result = $this->converter->fromJson(clone $beforeMigration, $options);

        $this->assertSame('DATA', $result);
    }

    public function testFromJsonDoesNotMigrateCurrentVersion()
    {
        $options = array(
            'inner_option' => 'value',
        );

        $jsonData = (object) array(
            'version' => '1.0',
        );

        $this->migrationManager->expects($this->never())
            ->method('migrate');

        $this->innerConverter->expects($this->once())
            ->method('fromJson')
            ->with($jsonData, $options)
            ->willReturn('DATA');

        $result = $this->converter->fromJson(clone $jsonData, $options);

        $this->assertSame('DATA', $result);
    }

    /**
     * @expectedException \Webmozart\Json\Migration\UnsupportedVersionException
     */
    public function testFromJsonFailsIfSourceVersionTooHigh()
    {
        $jsonData = (object) array(
            'version' => '1.1',
        );

        $this->converter->fromJson($jsonData);
    }

    /**
     * @expectedException \Webmozart\Json\Conversion\ConversionException
     */
    public function testFromJsonFailsIfNotAnObject()
    {
        $this->migrationManager->expects($this->never())
            ->method('migrate');

        $this->innerConverter->expects($this->never())
            ->method('fromJson');

        $this->converter->fromJson('foobar');
    }

    /**
     * @expectedException \Webmozart\Json\Conversion\ConversionException
     */
    public function testFromJsonFailsIfVersionIsMissing()
    {
        $this->migrationManager->expects($this->never())
            ->method('migrate');

        $this->innerConverter->expects($this->never())
            ->method('fromJson');

        $this->converter->fromJson((object) array());
    }
}
