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

use Seld\JsonLint\JsonParser;
use Seld\JsonLint\ParsingException;

/**
 * Decodes JSON strings/files and validates against a JSON schema.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class JsonDecoder
{
    /**
     * Decode a JSON value as PHP object.
     */
    const OBJECT = 0;

    /**
     * Decode a JSON value as associative array.
     */
    const ASSOC_ARRAY = 1;

    /**
     * Decode a JSON value as float.
     */
    const FLOAT = 2;

    /**
     * Decode a JSON value as string.
     */
    const STRING = 3;

    /**
     * @var JsonValidator
     */
    private $validator;

    /**
     * @var int
     */
    private $objectDecoding = self::OBJECT;

    /**
     * @var int
     */
    private $maxDepth = 512;

    /**
     * @var int
     */
    private $bigIntDecoding = self::FLOAT;

    /**
     * Creates a new decoder;
     */
    public function __construct()
    {
        $this->validator = new JsonValidator();
    }

    /**
     * Decodes and validates a JSON string.
     *
     * If a schema is passed, the decoded object is validated against that
     * schema. The schema may be passed as file path or as object returned from
     * `JsonDecoder::decodeFile($schemaFile)`.
     *
     * You can adjust the decoding with {@link setObjectDecoding()},
     * {@link setBigIntDecoding()} and {@link setMaxDepth()}.
     *
     * Schema validation is not supported when objects are decoded as
     * associative arrays.
     *
     * @param string        $json   The JSON string.
     * @param string|object $schema The schema file or object.
     *
     * @return mixed The decoded value.
     *
     * @throws InvalidJsonException If the JSON string is invalid.
     * @throws SchemaException If the schema is invalid.
     */
    public function decode($json, $schema = null)
    {
        if (self::ASSOC_ARRAY === $this->objectDecoding && null !== $schema) {
            throw new \InvalidArgumentException(
                'Schema validation is not supported when objects are decoded '.
                'as associative arrays. Call '.
                'JsonDecoder::setDecodeObjectsAs(JsonDecoder::OBJECT) to fix.'
            );
        }
        $decoded = $this->decodeJson($json);

        if (null !== $schema) {
            $this->validator->validate($decoded, $schema);
        }

        return $decoded;
    }

    /**
     * Decodes and validates a JSON file.
     *
     * @param string        $file   The path to the JSON file.
     * @param string|object $schema The schema file or object.
     *
     * @return mixed The decoded file.
     *
     * @throws FileNotFoundException If the file was not found.
     * @throws InvalidJsonException If the JSON file is invalid.
     * @throws SchemaException If the schema is invalid.
     *
     * @see decode
     */
    public function decodeFile($file, $schema = null)
    {
        if (!file_exists($file)) {
            throw new FileNotFoundException(sprintf(
                'The file "%s" does not exist.',
                $file
            ));
        }

        return $this->decode(file_get_contents($file), $schema);
    }

    /**
     * Returns the maximum recursion depth.
     *
     * @return int The maximum recursion depth.
     */
    public function getMaxDepth()
    {
        return $this->maxDepth;
    }

    /**
     * Sets the maximum recursion depth.
     *
     * If the depth is exceeded during decoding, an {@link InvalidJsonException}
     * will be thrown.
     *
     * @param int $maxDepth The maximum recursion depth.
     */
    public function setMaxDepth($maxDepth)
    {
        if (!is_int($maxDepth)) {
            throw new \InvalidArgumentException(sprintf(
                'The maximum depth should be an integer. Got: %s',
                is_object($maxDepth) ? get_class($maxDepth) : gettype($maxDepth)
            ));
        }

        $this->maxDepth = $maxDepth;
    }

    /**
     * Returns the decoding of JSON objects.
     *
     * @return int One of the constants {@link OBJECT} and {@link ASSOC_ARRAY}.
     */
    public function getObjectDecoding()
    {
        return $this->objectDecoding;
    }

    /**
     * Sets the decoding of JSON objects.
     *
     * By default, JSON objects are decoded as instances of {@link \stdClass}.
     *
     * @param int $decoding One of the constants {@link OBJECT} and {@link ASSOC_ARRAY}.
     *
     * @throws \InvalidArgumentException If the passed decoding is invalid.
     */
    public function setObjectDecoding($decoding)
    {
        if (self::OBJECT !== $decoding && self::ASSOC_ARRAY !== $decoding) {
            throw new \InvalidArgumentException(sprintf(
                'Expected JsonDecoder::OBJECT or JsonDecoder::ASSOC_ARRAY. '.
                'Got: %s',
                $decoding
            ));
        }

        $this->objectDecoding = $decoding;
    }

    /**
     * Returns the decoding of big integers.
     *
     * @return int One of the constants {@link FLOAT} and {@link STRING}.
     */
    public function getBigIntDecoding()
    {
        return $this->bigIntDecoding;
    }

    /**
     * Sets the decoding of big integers.
     *
     * By default, big integers are decoded as floats.
     *
     * @param int $decoding One of the constants {@link FLOAT} and {@link STRING}.
     *
     * @throws \InvalidArgumentException If the passed decoding is invalid.
     */
    public function setBigIntDecoding($decoding)
    {
        if (self::FLOAT !== $decoding && self::STRING !== $decoding) {
            throw new \InvalidArgumentException(sprintf(
                'Expected JsonDecoder::FLOAT or JsonDecoder::STRING. '.
                'Got: %s',
                $decoding
            ));
        }

        $this->bigIntDecoding = $decoding;
    }

    private function decodeJson($json)
    {
        $assoc = self::ASSOC_ARRAY === $this->objectDecoding;
        $options = self::STRING === $this->bigIntDecoding ? JSON_BIGINT_AS_STRING : 0;

        $decoded = json_decode($json, $assoc, $this->maxDepth, $options);

        // Data could not be decoded
        if (null === $decoded && null !== $json) {
            $parser = new JsonParser();
            $e = $parser->lint($json);

            // Happens for example when the max depth is exceeded
            if (!$e instanceof ParsingException) {
                throw new InvalidJsonException(sprintf(
                    'The JSON data could not be decoded: %s.',
                    json_last_error_msg()
                ));
            }

            throw InvalidJsonException::fromErrors(array($e->getMessage()), 0, $e);
        }

        return $decoded;
    }
}
