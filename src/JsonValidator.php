<?php

/*
 * This file is part of the Puli JSON package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Json;

use JsonSchema\Exception\InvalidArgumentException;
use JsonSchema\Validator;

/**
 * Validates decoded JSON values against a JSON schema.
 *
 * This class is a wrapper for {@link Validator} that adds exceptions and
 * validation of schema files. A few edge cases that are not handled by
 * {@link Validator} are handled by this class.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class JsonValidator
{
    /**
     * The schema used for validating schemas.
     *
     * @var \stdClass|null
     */
    private $metaSchema;

    /**
     * Validates JSON data against a schema.
     *
     * The schema may be passed as file path or as object returned from
     * `json_decode($schemaFile)`.
     *
     * @param mixed         $data   The decoded JSON data.
     * @param string|object $schema The schema file or object.
     *
     * @throws ValidationFailedException If the data does not comply with the schema.
     * @throws SchemaException If the schema is invalid.
     */
    public function validate($data, $schema)
    {
        if (is_string($schema)) {
            $schema = $this->loadSchema($schema);
        } else {
            $this->validateSchema($schema);
        }

        $validator = new Validator();

        try {
            $validator->check($data, $schema);
        } catch (InvalidArgumentException $e) {
            throw new SchemaException(sprintf(
                'The schema is invalid: %s',
                $e->getMessage()
            ), 0, $e);
        }

        if (!$validator->isValid()) {
            $errors = (array) $validator->getErrors();

            foreach ($errors as $key => $error) {
                $prefix = $error['property'] ? $error['property'].': ' : '';
                $errors[$key] = $prefix.$error['message'];
            }

            throw ValidationFailedException::fromErrors($errors);
        }
    }

    private function validateSchema($schema)
    {
        if (null === $this->metaSchema) {
            // The meta schema is obviously not validated. If we
            // validate it against itself, we have an endless recursion
            $this->metaSchema = json_decode(file_get_contents(__DIR__.'/../res/meta-schema.json'));
        }

        if ($schema === $this->metaSchema) {
            return;
        }

        try {
            $this->validate($schema, $this->metaSchema);
        } catch (ValidationFailedException $e) {
            throw new SchemaException(sprintf(
                "The schema is invalid:\n%s",
                $e->getErrorsAsString()
            ), 0, $e);
        }

        // not caught by justinrainbow/json-schema
        if (!is_object($schema)) {
            throw new SchemaException(sprintf(
                'The schema must be an object. Got: %s',
                $schema,
                gettype($schema)
            ));
        }
    }

    private function loadSchema($file)
    {
        if (!file_exists($file)) {
            throw new SchemaException(sprintf(
                'The schema file %s does not exist.',
                $file
            ));
        }

        $schema = json_decode(file_get_contents($file));

        try {
            $this->validateSchema($schema);
        } catch (SchemaException $e) {
            throw new SchemaException(sprintf(
                'An error occurred while loading the schema %s: %s',
                $file,
                $e->getMessage()
            ), 0, $e);
        }

        return $schema;
    }
}
