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
use PHPUnit_Framework_MockObject_MockObject;
use stdClass;
use Webmozart\Json\Migration\JsonMigration;
use Webmozart\Json\Migration\MigrationManager;
use Webmozart\Json\Versioning\JsonVersioner;

/**
 * @since  1.3
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class MigrationManagerTest extends \PHPUnit\Framework\TestCase
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
     * @var PHPUnit_Framework_MockObject_MockObject|JsonVersioner
     */
    private $versioner;

    /**
     * @var MigrationManager
     */
    private $manager;

    protected function setUp()
    {
        $this->migration1 = $this->createMigrationMock('0.8', '0.10');
        $this->migration2 = $this->createMigrationMock('0.10', '1.0');
        $this->migration3 = $this->createMigrationMock('1.0', '2.0');
        $this->versioner = $this->createMock('Webmozart\Json\Versioning\JsonVersioner');
        $this->manager = new MigrationManager(array(
            $this->migration1,
            $this->migration2,
            $this->migration3,
        ), $this->versioner);
    }

    public function testMigrateUp()
    {
        $data = (object) array('calls' => 0);

        $this->versioner->expects($this->once())
            ->method('parseVersion')
            ->with($data)
            ->willReturn('0.8');

        $this->versioner->expects($this->exactly(3))
            ->method('updateVersion')
            ->withConsecutive(
                array($data, '0.10'),
                array($data, '1.0'),
                array($data, '2.0')
            );

        $this->migration1->expects($this->once())
            ->method('up')
            ->with($data)
            ->willReturnCallback(function (stdClass $data) {
                Assert::assertSame(0, $data->calls);
                ++$data->calls;
            });
        $this->migration2->expects($this->once())
            ->method('up')
            ->with($data)
            ->willReturnCallback(function (stdClass $data) {
                Assert::assertSame(1, $data->calls);
                ++$data->calls;
            });
        $this->migration3->expects($this->once())
            ->method('up')
            ->with($data)
            ->willReturnCallback(function (stdClass $data) {
                Assert::assertSame(2, $data->calls);
                ++$data->calls;
            });

        $this->manager->migrate($data, '2.0');

        $this->assertSame(3, $data->calls);
    }

    public function testMigrateUpPartial()
    {
        $data = (object) array('calls' => 0);

        $this->versioner->expects($this->once())
            ->method('parseVersion')
            ->with($data)
            ->willReturn('0.10');

        $this->versioner->expects($this->once())
            ->method('updateVersion')
            ->with($data, '1.0');

        $this->migration1->expects($this->never())
            ->method('up');
        $this->migration2->expects($this->once())
            ->method('up')
            ->with($data)
            ->willReturnCallback(function (stdClass $data) {
                Assert::assertSame(0, $data->calls);
                ++$data->calls;
            });
        $this->migration3->expects($this->never())
            ->method('up');

        $this->manager->migrate($data, '1.0');

        $this->assertSame(1, $data->calls);
    }

    /**
     * @expectedException \Webmozart\Json\Migration\MigrationFailedException
     * @expectedExceptionMessage 0.5
     */
    public function testMigrateUpFailsIfNoMigrationForSourceVersion()
    {
        $data = (object) array();

        $this->versioner->expects($this->once())
            ->method('parseVersion')
            ->with($data)
            ->willReturn('0.5');

        $this->versioner->expects($this->never())
            ->method('updateVersion');

        $this->migration1->expects($this->never())
            ->method('up');
        $this->migration2->expects($this->never())
            ->method('up');
        $this->migration3->expects($this->never())
            ->method('up');

        $this->manager->migrate($data, '0.10');
    }

    /**
     * @expectedException \Webmozart\Json\Migration\MigrationFailedException
     * @expectedExceptionMessage 1.2
     */
    public function testMigrateUpFailsIfNoMigrationForTargetVersion()
    {
        $data = (object) array('calls' => 0);

        $this->versioner->expects($this->once())
            ->method('parseVersion')
            ->with($data)
            ->willReturn('0.10');

        $this->versioner->expects($this->once())
            ->method('updateVersion')
            ->with($data, '1.0');

        $this->migration1->expects($this->never())
            ->method('up');
        $this->migration2->expects($this->once())
            ->method('up')
            ->willReturnCallback(function (stdClass $data) {
                Assert::assertSame(0, $data->calls);
                ++$data->calls;
            });
        $this->migration3->expects($this->never())
            ->method('up');

        $this->manager->migrate($data, '1.2');
    }

    public function testMigrateDown()
    {
        $data = (object) array('calls' => 0);

        $this->versioner->expects($this->once())
            ->method('parseVersion')
            ->with($data)
            ->willReturn('2.0');

        $this->versioner->expects($this->exactly(3))
            ->method('updateVersion')
            ->withConsecutive(
                array($data, '1.0'),
                array($data, '0.10'),
                array($data, '0.8')
            );

        $this->migration3->expects($this->once())
            ->method('down')
            ->with($data)
            ->willReturnCallback(function (stdClass $data) {
                Assert::assertSame(0, $data->calls);
                ++$data->calls;
            });
        $this->migration2->expects($this->once())
            ->method('down')
            ->with($data)
            ->willReturnCallback(function (stdClass $data) {
                Assert::assertSame(1, $data->calls);
                ++$data->calls;
            });
        $this->migration1->expects($this->once())
            ->method('down')
            ->with($data)
            ->willReturnCallback(function (stdClass $data) {
                Assert::assertSame(2, $data->calls);
                ++$data->calls;
            });

        $this->manager->migrate($data, '0.8');

        $this->assertSame(3, $data->calls);
    }

    public function testMigrateDownPartial()
    {
        $data = (object) array('calls' => 0);

        $this->versioner->expects($this->once())
            ->method('parseVersion')
            ->with($data)
            ->willReturn('1.0');

        $this->versioner->expects($this->once())
            ->method('updateVersion')
            ->with($data, '0.10');

        $this->migration3->expects($this->never())
            ->method('down');
        $this->migration2->expects($this->once())
            ->method('down')
            ->with($data)
            ->willReturnCallback(function (stdClass $data) {
                Assert::assertSame(0, $data->calls);
                ++$data->calls;
            });
        $this->migration1->expects($this->never())
            ->method('down');

        $this->manager->migrate($data, '0.10');

        $this->assertSame(1, $data->calls);
    }

    /**
     * @expectedException \Webmozart\Json\Migration\MigrationFailedException
     * @expectedExceptionMessage 1.2
     */
    public function testMigrateDownFailsIfNoMigrationForSourceVersion()
    {
        $data = (object) array();

        $this->versioner->expects($this->once())
            ->method('parseVersion')
            ->with($data)
            ->willReturn('1.2');

        $this->versioner->expects($this->never())
            ->method('updateVersion');

        $this->migration3->expects($this->never())
            ->method('down');
        $this->migration2->expects($this->never())
            ->method('down');
        $this->migration1->expects($this->never())
            ->method('down');

        $this->manager->migrate($data, '1.0');
    }

    /**
     * @expectedException \Webmozart\Json\Migration\MigrationFailedException
     * @expectedExceptionMessage 0.9
     */
    public function testMigrateDownFailsIfNoMigrationForTargetVersion()
    {
        $data = (object) array('calls' => 0);

        $this->versioner->expects($this->once())
            ->method('parseVersion')
            ->with($data)
            ->willReturn('1.0');

        $this->versioner->expects($this->once())
            ->method('updateVersion')
            ->with($data, '0.10');

        $this->migration3->expects($this->never())
            ->method('down');
        $this->migration2->expects($this->once())
            ->method('down')
            ->willReturnCallback(function (stdClass $data) {
                Assert::assertSame(0, $data->calls);
                ++$data->calls;
            });
        $this->migration1->expects($this->never())
            ->method('down');

        $this->manager->migrate($data, '0.9');
    }

    public function testMigrateDoesNothingIfAlreadyCorrectVersion()
    {
        $data = (object) array();

        $this->versioner->expects($this->once())
            ->method('parseVersion')
            ->with($data)
            ->willReturn('0.10');

        $this->versioner->expects($this->never())
            ->method('updateVersion');

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
    }

    public function testGetKnownVersions()
    {
        $this->assertSame(array('0.8', '0.10', '1.0', '2.0'), $this->manager->getKnownVersions());
    }

    public function testGetKnownVersionsWithoutMigrations()
    {
        $this->manager = new MigrationManager(array(), $this->versioner);

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
        $mock = $this->createMock('Webmozart\Json\Migration\JsonMigration');

        $mock->expects($this->any())
            ->method('getSourceVersion')
            ->willReturn($sourceVersion);

        $mock->expects($this->any())
            ->method('getTargetVersion')
            ->willReturn($targetVersion);

        return $mock;
    }
}
