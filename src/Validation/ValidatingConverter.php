<?php

/*
 * This file is part of the webmozart/json package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Json\Validation;

use JsonSchema\Validator;
use Webmozart\Json\Conversion\ConversionFailedException;
use Webmozart\Json\Conversion\JsonConverter;

/**
 * A decorator for a {@link JsonCoverter} that validates the JSON data.
 *
 * Pass the path to the schema to the constructor:
 *
 * ~~~php
 * $converter = ConfigFileConverter();
 *
 * // Decorate the converter
 * $converter = new ValidatingConverter($converter, __DIR__.'/schema.json');
 * ~~~
 *
 * Whenever you load or dump data as JSON, the JSON structure is validated
 * against the schema:
 *
 * ~~~php
 * $jsonDecoder = new JsonDecoder();
 * $configFile = $converter->fromJson($jsonDecoder->decode($json));
 *
 * $jsonEncoder = new JsonEncoder();
 * $jsonEncoder->encode($converter->toJson($configFile));
 * ~~~
 *
 * If you want to dynamically determine the path to the schema file, pass a
 * callable instead of the string. This is especially useful when versioning
 * your JSON data:
 *
 * ~~~php
 * $converter = ConfigFileConverter();
 *
 * // Calculate the schema path based on the "version" key in the JSON object
 * $getSchemaPath = function ($jsonData) {
 *     return __DIR__.'/schema-'.$jsonData->version.'.json';
 * }
 *
 * // Decorate the converter
 * $converter = new ValidatingConverter($converter, $getSchemaPath);
 * ~~~
 *
 * @since  1.3
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ValidatingConverter implements JsonConverter
{
    /**
     * @var JsonConverter
     */
    private $innerConverter;

    /**
     * @var mixed
     */
    private $schema;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * Creates the converter.
     *
     * @param JsonConverter        $innerConverter The decorated converter
     * @param string|callable|null $schema         The path to the schema file
     *                                             or a callable for calculating
     *                                             the path dynamically for a
     *                                             given JSON data. If `null`,
     *                                             the schema is taken from the
     *                                             `$schema` property of the
     *                                             JSON data
     * @param null|Validator        $validator     The validator (optional)
     */
    public function __construct(JsonConverter $innerConverter, $schema = null, Validator $validator = null)
    {
        $this->innerConverter = $innerConverter;
        $this->schema = $schema;
        $this->validator = $validator ?? new Validator();
    }

    /**
     * {@inheritdoc}
     */
    public function toJson($data, array $options = array())
    {
        $jsonData = $this->innerConverter->toJson($data, $options);

        $this->validate($jsonData);

        return $jsonData;
    }

    /**
     * {@inheritdoc}
     */
    public function fromJson($jsonData, array $options = array())
    {
        $this->validate($jsonData);

        return $this->innerConverter->fromJson($jsonData, $options);
    }

    private function validate($jsonData)
    {
        $schema = $this->schema;

        if (is_callable($schema)) {
            $schema = $schema($jsonData);
        }

        $this->validator->validate($jsonData, $schema);

        $errors = $this->validator->getErrors();

        if (null === $errors) {
            return;
        }

        if (count($errors) > 0) {
            foreach ($errors as &$error) {
                if (!is_array($error)) {
                    continue;
                }

                $error = implode("\n", $error);
            }
            unset($error);

            throw new ConversionFailedException(sprintf(
                "The passed JSON did not match the schema:\n%s",
                implode("\n", $errors)
            ));
        }
    }
}
