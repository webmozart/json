<?php

/*
 * This file is part of the Webmozart JSON package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Json\Tests;

use Webmozart\Json\JsonValidator;

/**
 * @since  1.0
 *
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
        $errors = $this->validator->validate(
            (object) array('name' => 'Bernhard'),
            $this->schemaFile
        );

        $this->assertCount(0, $errors);
    }

    public function testValidateWithSchemaFileInPhar()
    {
        // Work-around for https://bugs.php.net/bug.php?id=71368:
        // "format": "uri" validation removed for "id" field in meta-schema.json

        $errors = $this->validator->validate(
            (object) array('name' => 'Bernhard'),
            'phar://'.$this->fixturesDir.'/schema.phar/schema.json'
        );

        $this->assertCount(0, $errors);
    }

    public function testValidateWithSchemaObject()
    {
        $errors = $this->validator->validate(
            (object) array('name' => 'Bernhard'),
            $this->schemaObject
        );

        $this->assertCount(0, $errors);
    }

    public function testValidateWithReferences()
    {
        $errors = $this->validator->validate(
            (object) array('name' => 'Bernhard', 'has-coffee' => true),
            $this->fixturesDir.'/schema-refs.json'
        );

        $this->assertCount(0, $errors);
    }

    public function testValidateFailsIfValidationFailsWithSchemaFile()
    {
        $errors = $this->validator->validate('foobar', $this->schemaFile);

        $this->assertCount(1, $errors);
    }

    public function testValidateFailsIfValidationFailsWithSchemaObject()
    {
        $errors = $this->validator->validate('foobar', $this->schemaObject);

        $this->assertCount(1, $errors);
    }

    public function testValidateFailsIfValidationFailsWithReferences()
    {
        $errors = $this->validator->validate(
            (object) array('name' => 'Bernhard', 'has-coffee' => null),
            $this->fixturesDir.'/schema-refs.json'
        );

        $this->assertGreaterThan(1, count($errors));
    }

    /**
     * Test that the file name is mentioned in the output.
     *
     * @expectedException \Webmozart\Json\InvalidSchemaException
     * @expectedExceptionMessage bogus.json
     */
    public function testValidateFailsIfSchemaFileNotFound()
    {
        $this->validator->validate((object) array('name' => 'Bernhard'), __DIR__.'/bogus.json');
    }

    /**
     * @expectedException \Webmozart\Json\InvalidSchemaException
     */
    public function testValidateFailsIfSchemaNeitherStringNorObject()
    {
        $this->validator->validate((object) array('name' => 'Bernhard'), 12345);
    }

    /**
     * @expectedException \Webmozart\Json\InvalidSchemaException
     */
    public function testValidateFailsIfSchemaFileContainsNoObject()
    {
        $this->validator->validate(
            (object) array('name' => 'Bernhard'),
            $this->fixturesDir.'/schema-no-object.json'
        );
    }

    /**
     * @expectedException \Webmozart\Json\InvalidSchemaException
     */
    public function testValidateFailsIfSchemaFileInvalid()
    {
        $this->validator->validate(
            (object) array('name' => 'Bernhard'),
            $this->fixturesDir.'/schema-invalid.json'
        );
    }

    /**
     * @expectedException \Webmozart\Json\InvalidSchemaException
     */
    public function testValidateFailsIfSchemaObjectInvalid()
    {
        $this->validator->validate(
            (object) array('name' => 'Bernhard'),
            (object) array('id' => 12345)
        );
    }

    /**
     * @expectedException \Webmozart\Json\InvalidSchemaException
     */
    public function testValidateFailsIfInvalidSchemaNotRecognized()
    {
        // justinrainbow/json-schema cannot validate "anyOf", so the following
        // will load the schema successfully and fail when the file is validated
        // against the schema
        $this->validator->validate(
            (object) array('name' => 'Bernhard'),
            (object) array('type' => 12345)
        );
    }
}
