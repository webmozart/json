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

use Webmozart\Json\Versioning\SchemaUriVersioner;

/**
 * @since  1.3
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class SchemaUriVersionerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var SchemaUriVersioner
     */
    private $versioner;

    protected function setUp()
    {
        $this->versioner = new SchemaUriVersioner();
    }

    public function testParseVersion()
    {
        $data = (object) array('$schema' => 'http://example.com/schemas/1.0/schema');

        $this->assertSame('1.0', $this->versioner->parseVersion($data));
    }

    /**
     * @expectedException \Webmozart\Json\Versioning\CannotParseVersionException
     */
    public function testParseVersionFailsIfNotFound()
    {
        $data = (object) array('$schema' => 'http://example.com/schemas/1.0-schema');

        $this->versioner->parseVersion($data);
    }

    public function testParseVersionWithCustomPattern()
    {
        $this->versioner = new SchemaUriVersioner('~(?<=/)\d+\.\d+(?=-)~');

        $data = (object) array('$schema' => 'http://example.com/schemas/1.0-schema');

        $this->assertSame('1.0', $this->versioner->parseVersion($data));
    }

    /**
     * @expectedException \Webmozart\Json\Versioning\CannotParseVersionException
     */
    public function testParseVersionFailsIfNoSchemaField()
    {
        $data = (object) array('foo' => 'bar');

        $this->versioner->parseVersion($data);
    }

    public function testUpdateVersion()
    {
        $data = (object) array('$schema' => 'http://example.com/schemas/1.0/schema');

        $this->versioner->updateVersion($data, '2.0');

        $this->assertSame('http://example.com/schemas/2.0/schema', $data->{'$schema'});
    }

    public function testUpdateVersionIgnoresCurrentVersion()
    {
        $data = (object) array('$schema' => 'http://example.com/schemas/1.0/schema');

        $this->versioner->updateVersion($data, '1.0');

        $this->assertSame('http://example.com/schemas/1.0/schema', $data->{'$schema'});
    }

    /**
     * @expectedException \Webmozart\Json\Versioning\CannotUpdateVersionException
     */
    public function testUpdateVersionFailsIfNotFound()
    {
        $data = (object) array('$schema' => 'http://example.com/schemas/1.0-schema');

        $this->versioner->updateVersion($data, '2.0');
    }

    /**
     * @expectedException \Webmozart\Json\Versioning\CannotUpdateVersionException
     */
    public function testUpdateVersionFailsIfFoundMultipleTimes()
    {
        $data = (object) array('$schema' => 'http://example.com/1.0/schemas/1.0/schema');

        $this->versioner->updateVersion($data, '2.0');
    }

    public function testUpdateVersionCustomPattern()
    {
        $this->versioner = new SchemaUriVersioner('~(?<=/)\d+\.\d+(?=-)~');

        $data = (object) array('$schema' => 'http://example.com/schemas/1.0-schema');

        $this->versioner->updateVersion($data, '2.0');

        $this->assertSame('http://example.com/schemas/2.0-schema', $data->{'$schema'});
    }

    /**
     * @expectedException \Webmozart\Json\Versioning\CannotUpdateVersionException
     */
    public function testUpdateVersionFailsIfNoSchemaField()
    {
        $data = (object) array('foo' => 'bar');

        $this->versioner->updateVersion($data, '2.0');
    }
}
