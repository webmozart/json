<?php

/*
 * This file is part of the webmozart/json package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Json\Tests\Versioning;

use PHPUnit\Framework\TestCase;
use Webmozart\Json\Versioning\CannotParseVersionException;
use Webmozart\Json\Versioning\VersionFieldVersioner;

/**
 * @since  1.3
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class VersionFieldVersionerTest extends TestCase
{
    /**
     * @var VersionFieldVersioner
     */
    private $versioner;

    protected function setUp(): void
    {
        $this->versioner = new VersionFieldVersioner();
    }

    public function testParseVersion(): void
    {
        $data = (object) array('version' => '1.0');

        self::assertSame('1.0', $this->versioner->parseVersion($data));
    }

    public function testParseVersionCustomFieldName(): void
    {
        $this->versioner = new VersionFieldVersioner('foo');

        $data = (object) array('foo' => '1.0');

        self::assertSame('1.0', $this->versioner->parseVersion($data));
    }

    public function testParseVersionFailsIfNotFound(): void
    {
        $this->expectException(CannotParseVersionException::class);

        $data = (object) array('foo' => 'bar');

        $this->versioner->parseVersion($data);
    }

    public function testUpdateVersion(): void
    {
        $data = (object) array('version' => '1.0');

        $this->versioner->updateVersion($data, '2.0');

        self::assertSame('2.0', $data->version);
    }

    public function testUpdateVersionCustomFieldName(): void
    {
        $this->versioner = new VersionFieldVersioner('foo');

        $data = (object) array('foo' => '1.0');

        $this->versioner->updateVersion($data, '2.0');

        self::assertSame('2.0', $data->foo);
    }

    public function testUpdateVersionCreatesFieldIfNotFound(): void
    {
        $data = (object) array('foo' => 'bar');

        $this->versioner->updateVersion($data, '2.0');

        self::assertSame('2.0', $data->version);
    }
}
