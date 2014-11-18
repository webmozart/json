<?php

/*
 * This file is part of the Puli CLI package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Cli\Tests\Json;

use Puli\Json\JsonReader;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class JsonReaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var JsonReader
     */
    private $reader;

    private $fixturesDir;

    private $schema;

    protected function setUp()
    {
        $this->reader = new JsonReader();
        $this->fixturesDir = __DIR__.'/Fixtures';
        $this->schema = $this->fixturesDir.'/schema.json';
    }

    public function testReadJson()
    {
        $data = $this->reader->readJson($this->fixturesDir.'/valid.json', $this->schema);

        $this->assertInstanceOf('\stdClass', $data);
        $this->assertObjectHasAttribute('name', $data);
        $this->assertSame('webmozart', $data->name);
    }

    /**
     * @expectedException \Puli\Json\InvalidJsonException
     */
    public function testReadJsonFailsIfInvalidJson()
    {
        $this->reader->readJson($this->fixturesDir.'/invalid.json', $this->schema);
    }

    /**
     * @expectedException \Puli\Json\InvalidJsonException
     */
    public function testReadJsonFailsIfNotUtf8()
    {
        $this->reader->readJson($this->fixturesDir.'/win-1258.json', $this->schema);
    }

    /**
     * @expectedException \Puli\Json\FileNotFoundException
     */
    public function testReadJsonFailsIfNotFound()
    {
        $this->reader->readJson(__DIR__.'/bogus.json', $this->schema);
    }

    /**
     * @expectedException \Puli\Json\SchemaLoadingException
     */
    public function testReadJsonFailsIfSchemaNotFound()
    {
        $this->reader->readJson($this->fixturesDir.'/valid.json', __DIR__.'/bogus.json');
    }

    /**
     * @expectedException \Puli\Json\SchemaLoadingException
     */
    public function testReadJsonFailsIfSchemaInvalid()
    {
        $this->reader->readJson($this->fixturesDir.'/valid.json', $this->fixturesDir.'/invalid.json');
    }
}
