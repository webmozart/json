<?php

/*
 * This file is part of the Webmozart JSON package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Json;

use Seld\JsonLint\JsonParser;
use Seld\JsonLint\ParsingException;

/**
 * Decodes JSON strings/files and validates against a JSON schema.
 *
 * @since  1.0
 *
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
    private $bigIntDecoding = self::FLOAT;

    /**
     * @var int
     */
    private $maxDepth = 512;

    /**
     * Creates a new decoder.
     *
     * @param null|JsonValidator $validator
     */
    public function __construct(JsonValidator $validator = null)
    {
        $this->validator = $validator ?: new JsonValidator();
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
     * @throws DecodingFailedException   If the JSON string could not be decoded.
     * @throws ValidationFailedException If the decoded string fails schema
     *                                   validation.
     * @throws InvalidSchemaException    If the schema is invalid.
     */
    public function decode($json, $schema = null)
    {
        if (self::ASSOC_ARRAY === $this->objectDecoding && null !== $schema) {
            throw new \InvalidArgumentException(
                'Schema validation is not supported when objects are decoded '.
                'as associative arrays. Call '.
                'JsonDecoder::setObjectDecoding(JsonDecoder::JSON_OBJECT) to fix.'
            );
        }

        $decoded = $this->decodeJson($json);

        if (null !== $schema) {
            $errors = $this->validator->validate($decoded, $schema);

            if (count($errors) > 0) {
                throw ValidationFailedException::fromErrors($errors);
            }
        }

        return $decoded;
    }

    /**
     * Decodes and validates a JSON file.
     *
     * @param string        $path   The path to the JSON file.
     * @param string|object $schema The schema file or object.
     *
     * @return mixed The decoded file.
     *
     * @throws FileNotFoundException     If the file was not found.
     * @throws DecodingFailedException   If the file could not be decoded.
     * @throws ValidationFailedException If the decoded file fails schema
     *                                   validation.
     * @throws InvalidSchemaException    If the schema is invalid.
     *
     * @see decode
     */
    public function decodeFile($path, $schema = null)
    {
        if (!file_exists($path)) {
            throw new FileNotFoundException(sprintf(
                'The file %s does not exist.',
                $path
            ));
        }

        $errorMessage = null;
        $errorCode = 0;

        set_error_handler(function ($errno, $errstr) use (&$errorMessage, &$errorCode) {
            $errorMessage = $errstr;
            $errorCode = $errno;
        });

        $content = file_get_contents($path);

        restore_error_handler();

        if (null !== $errorMessage) {
            if (false !== $pos = strpos($errorMessage, '): ')) {
                // cut "file_get_contents(%path%):" to make message more readable
                $errorMessage = substr($errorMessage, $pos + 3);
            }

            throw new IOException(sprintf(
                'Could not read %s: %s (%s)',
                $path,
                $errorMessage,
                $errorCode
            ), $errorCode);
        }

        try {
            return $this->decode($content, $schema);
        } catch (DecodingFailedException $e) {
            // Add the file name to the exception
            throw new DecodingFailedException(sprintf(
                'An error happened while decoding %s: %s',
                $path,
                $e->getMessage()
            ), $e->getCode(), $e);
        } catch (ValidationFailedException $e) {
            // Add the file name to the exception
            throw new ValidationFailedException(sprintf(
                "Validation of %s failed:\n%s",
                $path,
                $e->getErrorsAsString()
            ), $e->getErrors(), $e->getCode(), $e);
        } catch (InvalidSchemaException $e) {
            // Add the file name to the exception
            throw new InvalidSchemaException(sprintf(
                'An error happened while decoding %s: %s',
                $path,
                $e->getMessage()
            ), $e->getCode(), $e);
        }
    }

    /**
     * Returns the maximum recursion depth.
     *
     * A depth of zero means that objects are not allowed. A depth of one means
     * only one level of objects or arrays is allowed.
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
     * If the depth is exceeded during decoding, an {@link DecodingnFailedException}
     * will be thrown.
     *
     * A depth of zero means that objects are not allowed. A depth of one means
     * only one level of objects or arrays is allowed.
     *
     * @param int $maxDepth The maximum recursion depth.
     *
     * @throws \InvalidArgumentException If the depth is not an integer greater
     *                                   than or equal to zero.
     */
    public function setMaxDepth($maxDepth)
    {
        if (!is_int($maxDepth)) {
            throw new \InvalidArgumentException(sprintf(
                'The maximum depth should be an integer. Got: %s',
                is_object($maxDepth) ? get_class($maxDepth) : gettype($maxDepth)
            ));
        }

        if ($maxDepth < 1) {
            throw new \InvalidArgumentException(sprintf(
                'The maximum depth should 1 or greater. Got: %s',
                $maxDepth
            ));
        }

        $this->maxDepth = $maxDepth;
    }

    /**
     * Returns the decoding of JSON objects.
     *
     * @return int One of the constants {@link JSON_OBJECT} and {@link ASSOC_ARRAY}.
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
     * @param int $decoding One of the constants {@link JSON_OBJECT} and {@link ASSOC_ARRAY}.
     *
     * @throws \InvalidArgumentException If the passed decoding is invalid.
     */
    public function setObjectDecoding($decoding)
    {
        if (self::OBJECT !== $decoding && self::ASSOC_ARRAY !== $decoding) {
            throw new \InvalidArgumentException(sprintf(
                'Expected JsonDecoder::JSON_OBJECT or JsonDecoder::ASSOC_ARRAY. '.
                'Got: %s',
                $decoding
            ));
        }

        $this->objectDecoding = $decoding;
    }

    /**
     * Returns the decoding of big integers.
     *
     * @return int One of the constants {@link FLOAT} and {@link JSON_STRING}.
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
     * @param int $decoding One of the constants {@link FLOAT} and {@link JSON_STRING}.
     *
     * @throws \InvalidArgumentException If the passed decoding is invalid.
     */
    public function setBigIntDecoding($decoding)
    {
        if (self::FLOAT !== $decoding && self::STRING !== $decoding) {
            throw new \InvalidArgumentException(sprintf(
                'Expected JsonDecoder::FLOAT or JsonDecoder::JSON_STRING. '.
                'Got: %s',
                $decoding
            ));
        }

        $this->bigIntDecoding = $decoding;
    }

    private function decodeJson($json)
    {
        $assoc = self::ASSOC_ARRAY === $this->objectDecoding;

        if (PHP_VERSION_ID >= 50400 && !defined('JSON_C_VERSION')) {
            $options = self::STRING === $this->bigIntDecoding ? JSON_BIGINT_AS_STRING : 0;

            $decoded = json_decode($json, $assoc, $this->maxDepth, $options);
        } else {
            $decoded = json_decode($json, $assoc, $this->maxDepth);
        }

        // Data could not be decoded
        if (null === $decoded && 'null' !== $json) {
            $parser = new JsonParser();
            $e = $parser->lint($json);

            if ($e instanceof ParsingException) {
                throw new DecodingFailedException(sprintf(
                    'The JSON data could not be decoded: %s.',
                    $e->getMessage()
                ), 0, $e);
            }

            // $e is null if json_decode() failed, but the linter did not find
            // any problems. Happens for example when the max depth is exceeded.
            throw new DecodingFailedException(sprintf(
                'The JSON data could not be decoded: %s.',
                JsonError::getLastErrorMessage()
            ), json_last_error());
        }

        return $decoded;
    }
}
