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
 * Thrown when a JSON file contains invalid JSON.
 *
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ValidationFailedException extends \Exception
{
    private $errors;

    public static function fromErrors(array $errors = array(), $code = 0, \Exception $previous = null)
    {
        return new static(sprintf(
            "Validation of the JSON data failed:\n%s",
            implode("\n", $errors)
        ), $errors, $code, $previous);
    }

    public function __construct($message = '', array $errors = array(), $code = 0, \Exception $previous = null)
    {
        $this->errors = $errors;

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return array
     */
    public function getErrorsAsString()
    {
        return implode("\n", $this->errors);
    }
}
