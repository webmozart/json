<?php

/*
 * This file is part of the webmozart/json package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Json\Migration;

use Exception;
use Webmozart\Json\Conversion\ConversionFailedException;

/**
 * Thrown when a version is not supported.
 *
 * @since  1.3
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class UnsupportedVersionException extends ConversionFailedException
{
    /**
     * Creates an exception for an unknown version.
     *
     * @param string         $version       The version that caused the
     *                                      exception.
     * @param string[]       $knownVersions The known versions.
     * @param Exception|null $cause         The exception that caused this
     *                                      exception.
     *
     * @return static The created exception.
     */
    public static function forVersion($version, array $knownVersions, Exception $cause = null)
    {
        usort($knownVersions, 'version_compare');

        return new static(sprintf(
            'Cannot process JSON at version %s. The supported versions '.
            'are %s.',
            $version,
            implode(', ', $knownVersions)
        ), 0, $cause);
    }
}
