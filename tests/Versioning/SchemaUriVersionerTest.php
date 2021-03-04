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
use Webmozart\Json\Versioning\CannotUpdateVersionException;
use Webmozart\Json\Versioning\SchemaUriVersioner;

/**
 * @since  1.3
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class SchemaUriVersionerTest extends TestCase
{
    /**
     * @var SchemaUriVersioner
     */
    private $versioner;

    protected function setUp(): void
    {
        $this->versioner = new SchemaUriVersioner();
    }

    public function testParseVersion(): void
    {
        $data = (object) array('$schema' => 'http://example.com/schemas/1.0/schema');

        self::assertSame('1.0', $this->versioner->parseVersion($data));
    }

    public function testParseVersionFailsIfNotFound(): void
    {
        $this->expectException(CannotParseVersionException::class);

        $data = (object) array('$schema' => 'http://example.com/schemas/1.0-schema');

        $this->versioner->parseVersion($data);
    }

    public function testParseVersionWithCustomPattern(): void
    {
        $this->versioner = new SchemaUriVersioner('~(?<=/)\d+\.\d+(?=-)~');

        $data = (object) array('$schema' => 'http://example.com/schemas/1.0-schema');

        self::assertSame('1.0', $this->versioner->parseVersion($data));
    }

    public function testParseVersionFailsIfNoSchemaField(): void
    {
        $this->expectException(CannotParseVersionException::class);

        $data = (object) array('foo' => 'bar');

        $this->versioner->parseVersion($data);
    }

    public function testUpdateVersion(): void
    {
        $data = (object) array('$schema' => 'http://example.com/schemas/1.0/schema');

        $this->versioner->updateVersion($data, '2.0');

        self::assertSame('http://example.com/schemas/2.0/schema', $data->{'$schema'});
    }

    public function testUpdateVersionIgnoresCurrentVersion(): void
    {
        $data = (object) array('$schema' => 'http://example.com/schemas/1.0/schema');

        $this->versioner->updateVersion($data, '1.0');

        self::assertSame('http://example.com/schemas/1.0/schema', $data->{'$schema'});
    }

    public function testUpdateVersionFailsIfNotFound(): void
    {
        $this->expectException(CannotUpdateVersionException::class);

        $data = (object) array('$schema' => 'http://example.com/schemas/1.0-schema');

        $this->versioner->updateVersion($data, '2.0');
    }

    public function testUpdateVersionFailsIfFoundMultipleTimes(): void
    {
        $this->expectException(CannotUpdateVersionException::class);

        $data = (object) array('$schema' => 'http://example.com/1.0/schemas/1.0/schema');

        $this->versioner->updateVersion($data, '2.0');
    }

    public function testUpdateVersionCustomPattern(): void
    {
        $this->versioner = new SchemaUriVersioner('~(?<=/)\d+\.\d+(?=-)~');

        $data = (object) array('$schema' => 'http://example.com/schemas/1.0-schema');

        $this->versioner->updateVersion($data, '2.0');

        self::assertSame('http://example.com/schemas/2.0-schema', $data->{'$schema'});
    }

    public function testUpdateVersionFailsIfNoSchemaField(): void
    {
        $this->expectException(CannotUpdateVersionException::class);

        $data = (object) array('foo' => 'bar');

        $this->versioner->updateVersion($data, '2.0');
    }
}
