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

/**
 * Encodes data as JSON.
 *
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class JsonEncoder
{
    /**
     * Encode a value as JSON array.
     */
    const JSON_ARRAY = 1;

    /**
     * Encode a value as JSON object.
     */
    const JSON_OBJECT = 2;

    /**
     * Encode a value as JSON string.
     */
    const JSON_STRING = 3;

    /**
     * Encode a value as JSON integer or float.
     */
    const JSON_NUMBER = 4;

    /**
     * @var JsonValidator
     */
    private $validator;

    /**
     * @var int
     */
    private $arrayEncoding = self::JSON_ARRAY;

    /**
     * @var int
     */
    private $numericEncoding = self::JSON_STRING;

    /**
     * @var bool
     */
    private $gtLtEscaped = false;

    /**
     * @var bool
     */
    private $ampersandEscaped = false;

    /**
     * @var bool
     */
    private $singleQuoteEscaped = false;

    /**
     * @var bool
     */
    private $doubleQuoteEscaped = false;

    /**
     * @var bool
     */
    private $slashEscaped = true;

    /**
     * @var bool
     */
    private $unicodeEscaped = true;

    /**
     * @var bool
     */
    private $prettyPrinting = false;

    /**
     * @var bool
     */
    private $terminatedWithLineFeed = false;

    /**
     * @var int
     */
    private $maxDepth = 512;

    /**
     * Creates a new encoder.
     *
     * @param null|JsonValidator $validator
     */
    public function __construct(JsonValidator $validator = null)
    {
        $this->validator = $validator ?: new JsonValidator();
    }

    /**
     * Encodes data as JSON.
     *
     * If a schema is passed, the value is validated against that schema before
     * encoding. The schema may be passed as file path or as object returned
     * from `JsonDecoder::decodeFile($schemaFile)`.
     *
     * You can adjust the decoding with the various setters in this class.
     *
     * @param mixed         $data   The data to encode.
     * @param string|object $schema The schema file or object.
     *
     * @return string The JSON string.
     *
     * @throws EncodingFailedException   If the data could not be encoded.
     * @throws ValidationFailedException If the data fails schema validation.
     * @throws InvalidSchemaException    If the schema is invalid.
     */
    public function encode($data, $schema = null)
    {
        if (null !== $schema) {
            $errors = $this->validator->validate($data, $schema);

            if (count($errors) > 0) {
                throw ValidationFailedException::fromErrors($errors);
            }
        }

        $options = 0;

        if (self::JSON_OBJECT === $this->arrayEncoding) {
            $options |= JSON_FORCE_OBJECT;
        }

        if (self::JSON_NUMBER === $this->numericEncoding) {
            $options |= JSON_NUMERIC_CHECK;
        }

        if ($this->gtLtEscaped) {
            $options |= JSON_HEX_TAG;
        }

        if ($this->ampersandEscaped) {
            $options |= JSON_HEX_AMP;
        }

        if ($this->singleQuoteEscaped) {
            $options |= JSON_HEX_APOS;
        }

        if ($this->doubleQuoteEscaped) {
            $options |= JSON_HEX_QUOT;
        }

        if (PHP_VERSION_ID >= 50400) {
            if (!$this->slashEscaped) {
                $options |= JSON_UNESCAPED_SLASHES;
            }

            if (!$this->unicodeEscaped) {
                $options |= JSON_UNESCAPED_UNICODE;
            }

            if ($this->prettyPrinting) {
                $options |= JSON_PRETTY_PRINT;
            }
        }

        if (PHP_VERSION_ID >= 50500) {
            $maxDepth = $this->maxDepth;

            // We subtract 1 from the max depth to make JsonDecoder and
            // JsonEncoder consistent. json_encode() and json_decode() behave
            // differently for their depth values. See the test cases for
            // examples.
            // HHVM does not have this inconsistency.
            if (!defined('HHVM_VERSION')) {
                --$maxDepth;
            }

            $encoded = json_encode($data, $options, $maxDepth);
        } else {
            $encoded = json_encode($data, $options);
        }

        if (PHP_VERSION_ID < 50400 && !$this->slashEscaped) {
            // PHP below 5.4 does not allow to turn off slash escaping. Let's
            // unescape slashes manually.
            $encoded = str_replace('\\/', '/', $encoded);
        }

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new EncodingFailedException(sprintf(
                'The data could not be encoded as JSON: %s',
                JsonError::getLastErrorMessage()
            ), json_last_error());
        }

        if ($this->terminatedWithLineFeed) {
            $encoded .= "\n";
        }

        return $encoded;
    }

    /**
     * Encodes data into a JSON file.
     *
     * @param mixed         $data   The data to encode.
     * @param string        $path   The path where the JSON file will be stored.
     * @param string|object $schema The schema file or object.
     *
     * @throws EncodingFailedException   If the data could not be encoded.
     * @throws ValidationFailedException If the data fails schema validation.
     * @throws InvalidSchemaException    If the schema is invalid.
     *
     * @see encode
     */
    public function encodeFile($data, $path, $schema = null)
    {
        if (!file_exists($dir = dirname($path))) {
            mkdir($dir, 0777, true);
        }

        try {
            // Right now, it's sufficient to just write the file. In the future,
            // this will diff existing files with the given data and only do
            // in-place modifications where necessary.
            $content = $this->encode($data, $schema);
        } catch (EncodingFailedException $e) {
            // Add the file name to the exception
            throw new EncodingFailedException(sprintf(
                'An error happened while encoding %s: %s',
                $path,
                $e->getMessage()
            ), $e->getCode(), $e);
        } catch (ValidationFailedException $e) {
            // Add the file name to the exception
            throw new ValidationFailedException(sprintf(
                "Validation failed while encoding %s:\n%s",
                $path,
                $e->getErrorsAsString()
            ), $e->getErrors(), $e->getCode(), $e);
        } catch (InvalidSchemaException $e) {
            // Add the file name to the exception
            throw new InvalidSchemaException(sprintf(
                'An error happened while encoding %s: %s',
                $path,
                $e->getMessage()
            ), $e->getCode(), $e);
        }

        $errorMessage = null;
        $errorCode = 0;

        set_error_handler(function ($errno, $errstr) use (&$errorMessage, &$errorCode) {
            $errorMessage = $errstr;
            $errorCode = $errno;
        });

        file_put_contents($path, $content);

        restore_error_handler();

        if (null !== $errorMessage) {
            if (false !== $pos = strpos($errorMessage, '): ')) {
                // cut "file_put_contents(%path%):" to make message more readable
                $errorMessage = substr($errorMessage, $pos + 3);
            }

            throw new IOException(sprintf(
                'Could not write %s: %s (%s)',
                $path,
                $errorMessage,
                $errorCode
            ), $errorCode);
        }
    }

    /**
     * Returns the encoding of non-associative arrays.
     *
     * @return int One of the constants {@link JSON_OBJECT} and {@link JSON_ARRAY}.
     */
    public function getArrayEncoding()
    {
        return $this->arrayEncoding;
    }

    /**
     * Sets the encoding of non-associative arrays.
     *
     * By default, non-associative arrays are decoded as JSON arrays.
     *
     * @param int $encoding One of the constants {@link JSON_OBJECT} and {@link JSON_ARRAY}.
     *
     * @throws \InvalidArgumentException If the passed encoding is invalid.
     */
    public function setArrayEncoding($encoding)
    {
        if (self::JSON_ARRAY !== $encoding && self::JSON_OBJECT !== $encoding) {
            throw new \InvalidArgumentException(sprintf(
                'Expected JsonEncoder::JSON_ARRAY or JsonEncoder::JSON_OBJECT. '.
                'Got: %s',
                $encoding
            ));
        }

        $this->arrayEncoding = $encoding;
    }

    /**
     * Returns the encoding of numeric strings.
     *
     * @return int One of the constants {@link JSON_STRING} and {@link JSON_NUMBER}.
     */
    public function getNumericEncoding()
    {
        return $this->numericEncoding;
    }

    /**
     * Sets the encoding of numeric strings.
     *
     * By default, non-associative arrays are decoded as JSON strings.
     *
     * @param int $encoding One of the constants {@link JSON_STRING} and {@link JSON_NUMBER}.
     *
     * @throws \InvalidArgumentException If the passed encoding is invalid.
     */
    public function setNumericEncoding($encoding)
    {
        if (self::JSON_NUMBER !== $encoding && self::JSON_STRING !== $encoding) {
            throw new \InvalidArgumentException(sprintf(
                'Expected JsonEncoder::JSON_NUMBER or JsonEncoder::JSON_STRING. '.
                'Got: %s',
                $encoding
            ));
        }

        $this->numericEncoding = $encoding;
    }

    /**
     * Returns whether ampersands (&) are escaped.
     *
     * If `true`, ampersands will be escaped as "\u0026".
     *
     * By default, ampersands are not escaped.
     *
     * @return bool Whether ampersands are escaped.
     */
    public function isAmpersandEscaped()
    {
        return $this->ampersandEscaped;
    }

    /**
     * Sets whether ampersands (&) should be escaped.
     *
     * If `true`, ampersands will be escaped as "\u0026".
     *
     * By default, ampersands are not escaped.
     *
     * @param bool $enabled Whether ampersands should be escaped.
     */
    public function setEscapeAmpersand($enabled)
    {
        $this->ampersandEscaped = $enabled;
    }

    /**
     * Returns whether double quotes (") are escaped.
     *
     * If `true`, double quotes will be escaped as "\u0022".
     *
     * By default, double quotes are not escaped.
     *
     * @return bool Whether double quotes are escaped.
     */
    public function isDoubleQuoteEscaped()
    {
        return $this->doubleQuoteEscaped;
    }

    /**
     * Sets whether double quotes (") should be escaped.
     *
     * If `true`, double quotes will be escaped as "\u0022".
     *
     * By default, double quotes are not escaped.
     *
     * @param bool $enabled Whether double quotes should be escaped.
     */
    public function setEscapeDoubleQuote($enabled)
    {
        $this->doubleQuoteEscaped = $enabled;
    }

    /**
     * Returns whether single quotes (') are escaped.
     *
     * If `true`, single quotes will be escaped as "\u0027".
     *
     * By default, single quotes are not escaped.
     *
     * @return bool Whether single quotes are escaped.
     */
    public function isSingleQuoteEscaped()
    {
        return $this->singleQuoteEscaped;
    }

    /**
     * Sets whether single quotes (") should be escaped.
     *
     * If `true`, single quotes will be escaped as "\u0027".
     *
     * By default, single quotes are not escaped.
     *
     * @param bool $enabled Whether single quotes should be escaped.
     */
    public function setEscapeSingleQuote($enabled)
    {
        $this->singleQuoteEscaped = $enabled;
    }

    /**
     * Returns whether forward slashes (/) are escaped.
     *
     * If `true`, forward slashes will be escaped as "\/".
     *
     * By default, forward slashes are not escaped.
     *
     * @return bool Whether forward slashes are escaped.
     */
    public function isSlashEscaped()
    {
        return $this->slashEscaped;
    }

    /**
     * Sets whether forward slashes (") should be escaped.
     *
     * If `true`, forward slashes will be escaped as "\/".
     *
     * By default, forward slashes are not escaped.
     *
     * @param bool $enabled Whether forward slashes should be escaped.
     */
    public function setEscapeSlash($enabled)
    {
        $this->slashEscaped = $enabled;
    }

    /**
     * Returns whether greater than/less than symbols (>, <) are escaped.
     *
     * If `true`, greater than will be escaped as "\u003E" and less than as
     * "\u003C".
     *
     * By default, greater than/less than symbols are not escaped.
     *
     * @return bool Whether greater than/less than symbols are escaped.
     */
    public function isGtLtEscaped()
    {
        return $this->gtLtEscaped;
    }

    /**
     * Sets whether greater than/less than symbols (>, <) should be escaped.
     *
     * If `true`, greater than will be escaped as "\u003E" and less than as
     * "\u003C".
     *
     * By default, greater than/less than symbols are not escaped.
     *
     * @param bool $enabled Whether greater than/less than should be escaped.
     */
    public function setEscapeGtLt($enabled)
    {
        $this->gtLtEscaped = $enabled;
    }

    /**
     * Returns whether unicode characters are escaped.
     *
     * If `true`, unicode characters will be escaped as hexadecimals strings.
     * For example, "ü" will be escaped as "\u00fc".
     *
     * By default, unicode characters are escaped.
     *
     * @return bool Whether unicode characters are escaped.
     */
    public function isUnicodeEscaped()
    {
        return $this->unicodeEscaped;
    }

    /**
     * Sets whether unicode characters should be escaped.
     *
     * If `true`, unicode characters will be escaped as hexadecimals strings.
     * For example, "ü" will be escaped as "\u00fc".
     *
     * By default, unicode characters are escaped.
     *
     * @param bool $enabled Whether unicode characters should be escaped.
     */
    public function setEscapeUnicode($enabled)
    {
        $this->unicodeEscaped = $enabled;
    }

    /**
     * Returns whether JSON strings are formatted for better readability.
     *
     * If `true`, line breaks will be added after object properties and array
     * entries. Each new nesting level will be indented by four spaces.
     *
     * By default, pretty printing is not enabled.
     *
     * @return bool Whether JSON strings are formatted.
     */
    public function isPrettyPrinting()
    {
        return $this->prettyPrinting;
    }

    /**
     * Sets whether JSON strings should be formatted for better readability.
     *
     * If `true`, line breaks will be added after object properties and array
     * entries. Each new nesting level will be indented by four spaces.
     *
     * By default, pretty printing is not enabled.
     *
     * @param bool $prettyPrinting Whether JSON strings should be formatted.
     */
    public function setPrettyPrinting($prettyPrinting)
    {
        $this->prettyPrinting = $prettyPrinting;
    }

    /**
     * Returns whether JSON strings are terminated with a line feed.
     *
     * By default, JSON strings are not terminated with a line feed.
     *
     * @return bool Whether JSON strings are terminated with a line feed.
     */
    public function isTerminatedWithLineFeed()
    {
        return $this->terminatedWithLineFeed;
    }

    /**
     * Sets whether JSON strings should be terminated with a line feed.
     *
     * By default, JSON strings are not terminated with a line feed.
     *
     * @param bool $enabled Whether JSON strings should be terminated with a
     *                      line feed.
     */
    public function setTerminateWithLineFeed($enabled)
    {
        $this->terminatedWithLineFeed = $enabled;
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
     * If the depth is exceeded during encoding, an {@link EncodingFailedException}
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
}
