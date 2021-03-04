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
use Webmozart\Json\Migration\JsonMigration;
use Webmozart\Json\Migration\MigrationFailedException;
use Webmozart\Json\Migration\MigrationManager;
use Webmozart\Json\Versioning\JsonVersioner;

/**
 * @since  1.3
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class MigrationManagerTest extends TestCase
{
    /**
     * @var MockObject|JsonMigration
     */
    private $migration1;

    /**
     * @var MockObject|JsonMigration
     */
    private $migration2;

    /**
     * @var MockObject|JsonMigration
     */
    private $migration3;

    /**
     * @var MockObject|JsonVersioner
     */
    private $versioner;

    /**
     * @var MigrationManager
     */
    private $manager;

    protected function setUp(): void
    {
        $this->migration1 = $this->createMigrationMock('0.8', '0.10');
        $this->migration2 = $this->createMigrationMock('0.10', '1.0');
        $this->migration3 = $this->createMigrationMock('1.0', '2.0');
        $this->versioner = $this->createMock(JsonVersioner::class);
        $this->manager = new MigrationManager(array(
            $this->migration1,
            $this->migration2,
            $this->migration3,
        ), $this->versioner);
    }

    public function testMigrateUp(): void
    {
        $data = (object) array('calls' => 0);

        $this->versioner->expects(self::once())
            ->method('parseVersion')
            ->with($data)
            ->willReturn('0.8');

        $this->versioner->expects(self::exactly(3))
            ->method('updateVersion')
            ->withConsecutive(
                array($data, '0.10'),
                array($data, '1.0'),
                array($data, '2.0')
            );

        $this->migration1->expects(self::once())
            ->method('up')
            ->with($data)
            ->willReturnCallback(function (stdClass $data) {
                Assert::assertSame(0, $data->calls);
                ++$data->calls;
            });
        $this->migration2->expects(self::once())
            ->method('up')
            ->with($data)
            ->willReturnCallback(function (stdClass $data) {
                Assert::assertSame(1, $data->calls);
                ++$data->calls;
            });
        $this->migration3->expects(self::once())
            ->method('up')
            ->with($data)
            ->willReturnCallback(function (stdClass $data) {
                Assert::assertSame(2, $data->calls);
                ++$data->calls;
            });

        $this->manager->migrate($data, '2.0');

        self::assertSame(3, $data->calls);
    }

    public function testMigrateUpPartial(): void
    {
        $data = (object) array('calls' => 0);

        $this->versioner->expects(self::once())
            ->method('parseVersion')
            ->with($data)
            ->willReturn('0.10');

        $this->versioner->expects(self::once())
            ->method('updateVersion')
            ->with($data, '1.0');

        $this->migration1->expects(self::never())
            ->method('up');
        $this->migration2->expects(self::once())
            ->method('up')
            ->with($data)
            ->willReturnCallback(function (stdClass $data) {
                Assert::assertSame(0, $data->calls);
                ++$data->calls;
            });
        $this->migration3->expects(self::never())
            ->method('up');

        $this->manager->migrate($data, '1.0');

        self::assertSame(1, $data->calls);
    }

    public function testMigrateUpFailsIfNoMigrationForSourceVersion(): void
    {
        $this->expectException(MigrationFailedException::class);
        $this->expectExceptionMessage('0.5');

        $data = (object) array();

        $this->versioner->expects(self::once())
            ->method('parseVersion')
            ->with($data)
            ->willReturn('0.5');

        $this->versioner->expects(self::never())
            ->method('updateVersion');

        $this->migration1->expects(self::never())
            ->method('up');
        $this->migration2->expects(self::never())
            ->method('up');
        $this->migration3->expects(self::never())
            ->method('up');

        $this->manager->migrate($data, '0.10');
    }

    public function testMigrateUpFailsIfNoMigrationForTargetVersion(): void
    {
        $this->expectException(MigrationFailedException::class);
        $this->expectExceptionMessage('1.2');

        $data = (object) array('calls' => 0);

        $this->versioner->expects(self::once())
            ->method('parseVersion')
            ->with($data)
            ->willReturn('0.10');

        $this->versioner->expects(self::once())
            ->method('updateVersion')
            ->with($data, '1.0');

        $this->migration1->expects(self::never())
            ->method('up');
        $this->migration2->expects(self::once())
            ->method('up')
            ->willReturnCallback(function (stdClass $data) {
                Assert::assertSame(0, $data->calls);
                ++$data->calls;
            });
        $this->migration3->expects(self::never())
            ->method('up');

        $this->manager->migrate($data, '1.2');
    }

    public function testMigrateDown(): void
    {
        $data = (object) array('calls' => 0);

        $this->versioner->expects(self::once())
            ->method('parseVersion')
            ->with($data)
            ->willReturn('2.0');

        $this->versioner->expects(self::exactly(3))
            ->method('updateVersion')
            ->withConsecutive(
                array($data, '1.0'),
                array($data, '0.10'),
                array($data, '0.8')
            );

        $this->migration3->expects(self::once())
            ->method('down')
            ->with($data)
            ->willReturnCallback(function (stdClass $data) {
                Assert::assertSame(0, $data->calls);
                ++$data->calls;
            });
        $this->migration2->expects(self::once())
            ->method('down')
            ->with($data)
            ->willReturnCallback(function (stdClass $data) {
                Assert::assertSame(1, $data->calls);
                ++$data->calls;
            });
        $this->migration1->expects(self::once())
            ->method('down')
            ->with($data)
            ->willReturnCallback(function (stdClass $data) {
                Assert::assertSame(2, $data->calls);
                ++$data->calls;
            });

        $this->manager->migrate($data, '0.8');

        self::assertSame(3, $data->calls);
    }

    public function testMigrateDownPartial(): void
    {
        $data = (object) array('calls' => 0);

        $this->versioner->expects(self::once())
            ->method('parseVersion')
            ->with($data)
            ->willReturn('1.0');

        $this->versioner->expects(self::once())
            ->method('updateVersion')
            ->with($data, '0.10');

        $this->migration3->expects(self::never())
            ->method('down');
        $this->migration2->expects(self::once())
            ->method('down')
            ->with($data)
            ->willReturnCallback(function (stdClass $data) {
                Assert::assertSame(0, $data->calls);
                ++$data->calls;
            });
        $this->migration1->expects(self::never())
            ->method('down');

        $this->manager->migrate($data, '0.10');

        self::assertSame(1, $data->calls);
    }

    public function testMigrateDownFailsIfNoMigrationForSourceVersion(): void
    {
        $this->expectException(MigrationFailedException::class);
        $this->expectExceptionMessage('1.2');

        $data = (object) array();

        $this->versioner->expects(self::once())
            ->method('parseVersion')
            ->with($data)
            ->willReturn('1.2');

        $this->versioner->expects(self::never())
            ->method('updateVersion');

        $this->migration3->expects(self::never())
            ->method('down');
        $this->migration2->expects(self::never())
            ->method('down');
        $this->migration1->expects(self::never())
            ->method('down');

        $this->manager->migrate($data, '1.0');
    }

    public function testMigrateDownFailsIfNoMigrationForTargetVersion(): void
    {
        $this->expectException(MigrationFailedException::class);
        $this->expectExceptionMessage('0.9');

        $data = (object) array('calls' => 0);

        $this->versioner->expects(self::once())
            ->method('parseVersion')
            ->with($data)
            ->willReturn('1.0');

        $this->versioner->expects(self::once())
            ->method('updateVersion')
            ->with($data, '0.10');

        $this->migration3->expects(self::never())
            ->method('down');
        $this->migration2->expects(self::once())
            ->method('down')
            ->willReturnCallback(function (stdClass $data) {
                Assert::assertSame(0, $data->calls);
                ++$data->calls;
            });
        $this->migration1->expects(self::never())
            ->method('down');

        $this->manager->migrate($data, '0.9');
    }

    public function testMigrateDoesNothingIfAlreadyCorrectVersion(): void
    {
        $data = (object) array();

        $this->versioner->expects(self::once())
            ->method('parseVersion')
            ->with($data)
            ->willReturn('0.10');

        $this->versioner->expects(self::never())
            ->method('updateVersion');

        $this->migration1->expects(self::never())
            ->method('up');
        $this->migration2->expects(self::never())
            ->method('up');
        $this->migration3->expects(self::never())
            ->method('up');
        $this->migration1->expects(self::never())
            ->method('down');
        $this->migration2->expects(self::never())
            ->method('down');
        $this->migration3->expects(self::never())
            ->method('down');

        $this->manager->migrate($data, '0.10');
    }

    public function testGetKnownVersions(): void
    {
        self::assertSame(array('0.8', '0.10', '1.0', '2.0'), $this->manager->getKnownVersions());
    }

    public function testGetKnownVersionsWithoutMigrations(): void
    {
        $this->manager = new MigrationManager(array(), $this->versioner);

        self::assertSame(array(), $this->manager->getKnownVersions());
    }

    /**
     * @param string $sourceVersion
     * @param string $targetVersion
     *
     * @return MockObject|JsonMigration
     */
    private function createMigrationMock($sourceVersion, $targetVersion): MockObject
    {
        $mock = $this->createMock(JsonMigration::class);

        $mock->expects(self::any())
            ->method('getSourceVersion')
            ->willReturn($sourceVersion);

        $mock->expects(self::any())
            ->method('getTargetVersion')
            ->willReturn($targetVersion);

        return $mock;
    }
}
