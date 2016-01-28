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
use Webmozart\Json\Migration\JsonMigration;
use Webmozart\Json\Migration\MigrationManager;

/**
 * @since  1.3
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class MigrationManagerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var PHPUnit_Framework_MockObject_MockObject|JsonMigration
     */
    private $migration1;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|JsonMigration
     */
    private $migration2;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|JsonMigration
     */
    private $migration3;

    /**
     * @var MigrationManager
     */
    private $manager;

    protected function setUp()
    {
        $this->migration1 = $this->createMigrationMock('0.8', '0.10');
        $this->migration2 = $this->createMigrationMock('0.10', '1.0');
        $this->migration3 = $this->createMigrationMock('1.0', '2.0');
        $this->manager = new MigrationManager(array(
            $this->migration1,
            $this->migration2,
            $this->migration3,
        ));
    }

    public function testMigrateUp()
    {
        $data = (object) array('version' => '0.8', 'calls' => 0);

        $this->migration1->expects($this->once())
            ->method('up')
            ->with($data)
            ->willReturnCallback(function (stdClass $data) {
                PHPUnit_Framework_Assert::assertSame('0.8', $data->version);
                PHPUnit_Framework_Assert::assertSame(0, $data->calls);
                ++$data->calls;
            });
        $this->migration2->expects($this->once())
            ->method('up')
            ->with($data)
            ->willReturnCallback(function (stdClass $data) {
                PHPUnit_Framework_Assert::assertSame('0.10', $data->version);
                PHPUnit_Framework_Assert::assertSame(1, $data->calls);
                ++$data->calls;
            });
        $this->migration3->expects($this->once())
            ->method('up')
            ->with($data)
            ->willReturnCallback(function (stdClass $data) {
                PHPUnit_Framework_Assert::assertSame('1.0', $data->version);
                PHPUnit_Framework_Assert::assertSame(2, $data->calls);
                ++$data->calls;
            });

        $this->manager->migrate($data, '2.0');

        $this->assertSame('2.0', $data->version);
        $this->assertSame(3, $data->calls);
    }

    public function testMigrateUpPartial()
    {
        $data = (object) array('version' => '0.10', 'calls' => 0);

        $this->migration1->expects($this->never())
            ->method('up');
        $this->migration2->expects($this->once())
            ->method('up')
            ->with($data)
            ->willReturnCallback(function (stdClass $data) {
                PHPUnit_Framework_Assert::assertSame('0.10', $data->version);
                PHPUnit_Framework_Assert::assertSame(0, $data->calls);
                ++$data->calls;
            });
        $this->migration3->expects($this->never())
            ->method('up');

        $this->manager->migrate($data, '1.0');

        $this->assertSame('1.0', $data->version);
        $this->assertSame(1, $data->calls);
    }

    /**
     * @expectedException \Webmozart\Json\Migration\MigrationException
     * @expectedExceptionMessage 0.5
     */
    public function testMigrateUpFailsIfNoMigrationForOriginVersion()
    {
        $data = (object) array('version' => '0.5');

        $this->migration1->expects($this->never())
            ->method('up');
        $this->migration2->expects($this->never())
            ->method('up');
        $this->migration3->expects($this->never())
            ->method('up');

        $this->manager->migrate($data, '0.10');
    }

    /**
     * @expectedException \Webmozart\Json\Migration\MigrationException
     * @expectedExceptionMessage 1.2
     */
    public function testMigrateUpFailsIfNoMigrationForTargetVersion()
    {
        $data = (object) array('version' => '0.10', 'calls' => 0);

        $this->migration1->expects($this->never())
            ->method('up');
        $this->migration2->expects($this->once())
            ->method('up')
            ->willReturnCallback(function (stdClass $data) {
                PHPUnit_Framework_Assert::assertSame('0.10', $data->version);
                PHPUnit_Framework_Assert::assertSame(0, $data->calls);
                ++$data->calls;
            });
        $this->migration3->expects($this->never())
            ->method('up');

        $this->manager->migrate($data, '1.2');
    }

    public function testMigrateDown()
    {
        $data = (object) array('version' => '2.0', 'calls' => 0);

        $this->migration3->expects($this->once())
            ->method('down')
            ->with($data)
            ->willReturnCallback(function (stdClass $data) {
                PHPUnit_Framework_Assert::assertSame('2.0', $data->version);
                PHPUnit_Framework_Assert::assertSame(0, $data->calls);
                ++$data->calls;
            });
        $this->migration2->expects($this->once())
            ->method('down')
            ->with($data)
            ->willReturnCallback(function (stdClass $data) {
                PHPUnit_Framework_Assert::assertSame('1.0', $data->version);
                PHPUnit_Framework_Assert::assertSame(1, $data->calls);
                ++$data->calls;
            });
        $this->migration1->expects($this->once())
            ->method('down')
            ->with($data)
            ->willReturnCallback(function (stdClass $data) {
                PHPUnit_Framework_Assert::assertSame('0.10', $data->version);
                PHPUnit_Framework_Assert::assertSame(2, $data->calls);
                ++$data->calls;
            });

        $this->manager->migrate($data, '0.8');

        $this->assertSame('0.8', $data->version);
        $this->assertSame(3, $data->calls);
    }

    public function testMigrateDownPartial()
    {
        $data = (object) array('version' => '1.0', 'calls' => 0);

        $this->migration3->expects($this->never())
            ->method('down');
        $this->migration2->expects($this->once())
            ->method('down')
            ->with($data)
            ->willReturnCallback(function (stdClass $data) {
                PHPUnit_Framework_Assert::assertSame('1.0', $data->version);
                PHPUnit_Framework_Assert::assertSame(0, $data->calls);
                ++$data->calls;
            });
        $this->migration1->expects($this->never())
            ->method('down');

        $this->manager->migrate($data, '0.10');

        $this->assertSame('0.10', $data->version);
        $this->assertSame(1, $data->calls);
    }

    /**
     * @expectedException \Webmozart\Json\Migration\MigrationException
     * @expectedExceptionMessage 1.2
     */
    public function testMigrateDownFailsIfNoMigrationForOriginVersion()
    {
        $data = (object) array('version' => '1.2');

        $this->migration3->expects($this->never())
            ->method('down');
        $this->migration2->expects($this->never())
            ->method('down');
        $this->migration1->expects($this->never())
            ->method('down');

        $this->manager->migrate($data, '1.0');
    }

    /**
     * @expectedException \Webmozart\Json\Migration\MigrationException
     * @expectedExceptionMessage 0.9
     */
    public function testMigrateDownFailsIfNoMigrationForTargetVersion()
    {
        $data = (object) array('version' => '1.0', 'calls' => 0);

        $this->migration3->expects($this->never())
            ->method('down');
        $this->migration2->expects($this->once())
            ->method('down')
            ->willReturnCallback(function (stdClass $data) {
                PHPUnit_Framework_Assert::assertSame('1.0', $data->version);
                PHPUnit_Framework_Assert::assertSame(0, $data->calls);
                ++$data->calls;
            });
        $this->migration1->expects($this->never())
            ->method('down');

        $this->manager->migrate($data, '0.9');
    }

    public function testMigrateDoesNothingIfAlreadyCorrectVersion()
    {
        $data = (object) array('version' => '0.10');

        $this->migration1->expects($this->never())
            ->method('up');
        $this->migration2->expects($this->never())
            ->method('up');
        $this->migration3->expects($this->never())
            ->method('up');
        $this->migration1->expects($this->never())
            ->method('down');
        $this->migration2->expects($this->never())
            ->method('down');
        $this->migration3->expects($this->never())
            ->method('down');

        $this->manager->migrate($data, '0.10');

        $this->assertSame('0.10', $data->version);
    }

    public function testGetKnownVersions()
    {
        $this->assertSame(array('0.8', '0.10', '1.0', '2.0'), $this->manager->getKnownVersions());
    }

    public function testGetKnownVersionsWithoutMigrations()
    {
        $this->manager = new MigrationManager(array());

        $this->assertSame(array(), $this->manager->getKnownVersions());
    }

    /**
     * @param string $sourceVersion
     * @param string $targetVersion
     *
     * @return PHPUnit_Framework_MockObject_MockObject|JsonMigration
     */
    private function createMigrationMock($sourceVersion, $targetVersion)
    {
        $mock = $this->getMock('Webmozart\Json\Migration\JsonMigration');

        $mock->expects($this->any())
            ->method('getSourceVersion')
            ->willReturn($sourceVersion);

        $mock->expects($this->any())
            ->method('getTargetVersion')
            ->willReturn($targetVersion);

        return $mock;
    }
}
