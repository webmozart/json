<?php

/*
 * This file is part of the Puli JSON package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Json\Tests;

use Puli\Json\JsonValidator;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class JsonValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var JsonValidator
     */
    private $validator;

    private $fixturesDir;

    private $schemaFile;

    private $schemaObject;

    protected function setUp()
    {
        $this->validator = new JsonValidator();
        $this->fixturesDir = __DIR__.'/Fixtures';
        $this->schemaFile = $this->fixturesDir.'/schema.json';
        $this->schemaObject = json_decode(file_get_contents($this->schemaFile));
    }

    public function testValidateWithSchemaFile()
    {
        $this->validator->validate((object) array('name' => 'Bernhard'), $this->schemaFile);

        // no exception thrown
        $this->assertTrue(true);
    }

    public function testValidateWithSchemaObject()
    {
        $this->validator->validate((object) array('name' => 'Bernhard'), $this->schemaObject);

        // no exception thrown
        $this->assertTrue(true);
    }

    /**
     * @expectedException \Puli\Json\InvalidJsonException
     */
    public function testValidateFailsIfValidationFailsWithSchemaFile()
    {
        $this->validator->validate('foobar', $this->schemaFile);
    }

    /**
     * @expectedException \Puli\Json\InvalidJsonException
     */
    public function testValidateFailsIfValidationFailsWithSchemaObject()
    {
        $this->validator->validate('foobar', $this->schemaObject);
    }

    /**
     * @expectedException \Puli\Json\SchemaException
     */
    public function testValidateFailsIfSchemaFileNotFound()
    {
        $this->validator->validate((object) array('name' => 'Bernhard'), __DIR__.'/bogus.json');
    }

    /**
     * @expectedException \Puli\Json\SchemaException
     */
    public function testValidateFailsIfSchemaNeitherStringNorObject()
    {
        $this->validator->validate((object) array('name' => 'Bernhard'), 12345);
    }

    /**
     * @expectedException \Puli\Json\SchemaException
     */
    public function testValidateFailsIfSchemaFileContainsNoObject()
    {
        $this->validator->validate((object) array('name' => 'Bernhard'), $this->fixturesDir.'/schema-no-object.json');
    }

    /**
     * @expectedException \Puli\Json\SchemaException
     */
    public function testValidateFailsIfSchemaFileInvalid()
    {
        $this->validator->validate((object) array('name' => 'Bernhard'), $this->fixturesDir.'/schema-invalid.json');
    }

    /**
     * @expectedException \Puli\Json\SchemaException
     */
    public function testValidateFailsIfSchemaObjectInvalid()
    {
        $this->validator->validate((object) array('name' => 'Bernhard'), (object) array('id' => 12345));
    }

}
