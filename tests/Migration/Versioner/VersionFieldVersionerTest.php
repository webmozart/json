<?php

/*
 * This file is part of the webmozart/json package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Json\Tests\Migration\Versioner;

use PHPUnit_Framework_TestCase;
use Webmozart\Json\Migration\Versioner\VersionFieldVersioner;

/**
 * @since  1.3
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class VersionFieldVersionerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var VersionFieldVersioner
     */
    private $versioner;

    protected function setUp()
    {
        $this->versioner = new VersionFieldVersioner();
    }

    public function testParseVersion()
    {
        $data = (object) array('version' => '1.0');

        $this->assertSame('1.0', $this->versioner->parseVersion($data));
    }

    /**
     * @expectedException \Webmozart\Json\Migration\Versioner\CannotParseVersionException
     */
    public function testParseVersionFailsIfNotFound()
    {
        $data = (object) array('foo' => 'bar');

        $this->versioner->parseVersion($data);
    }

    public function testUpdateVersion()
    {
        $data = (object) array('version' => '1.0');

        $this->versioner->updateVersion($data, '2.0');

        $this->assertSame('2.0', $data->version);
    }

    public function testUpdateVersionCreatesFieldIfNotFound()
    {
        $data = (object) array('foo' => 'bar');

        $this->versioner->updateVersion($data, '2.0');

        $this->assertSame('2.0', $data->version);
    }
}
