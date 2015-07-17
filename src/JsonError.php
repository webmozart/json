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
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class JsonError
{
    /**
     * User-land implementation of `json_last_error_msg()` for PHP < 5.5.
     *
     * @return string The last JSON error message.
     */
    public static function getLastErrorMessage()
    {
        return self::getErrorMessage(json_last_error());
    }

    /**
     * Returns the error message of a JSON error code.
     *
     * @param int $error The error code.
     *
     * @return string The error message.
     */
    public static function getErrorMessage($error)
    {
        switch ($error) {
            case JSON_ERROR_NONE:
                return 'JSON_ERROR_NONE';
            case JSON_ERROR_DEPTH:
                return 'JSON_ERROR_DEPTH';
            case JSON_ERROR_STATE_MISMATCH:
                return 'JSON_ERROR_STATE_MISMATCH';
            case JSON_ERROR_CTRL_CHAR:
                return 'JSON_ERROR_CTRL_CHAR';
            case JSON_ERROR_SYNTAX:
                return 'JSON_ERROR_SYNTAX';
            case JSON_ERROR_UTF8:
                return 'JSON_ERROR_UTF8';
        }

        if (version_compare(PHP_VERSION, '5.5.0', '>=')) {
            switch ($error) {
                case JSON_ERROR_RECURSION:
                    return 'JSON_ERROR_RECURSION';
                case JSON_ERROR_INF_OR_NAN:
                    return 'JSON_ERROR_INF_OR_NAN';
                case JSON_ERROR_UNSUPPORTED_TYPE:
                    return 'JSON_ERROR_UNSUPPORTED_TYPE';
            }
        }

        return 'JSON_ERROR_UNKNOWN';
    }

    private function __construct()
    {
    }
}
