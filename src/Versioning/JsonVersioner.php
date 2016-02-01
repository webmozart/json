<?php

/*
 * This file is part of the webmozart/json package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Json\Versioning;

use stdClass;

/**
 * Parses and updates the version of a JSON object.
 *
 * @since  1.3
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface JsonVersioner
{
    /**
     * Parses and returns the version of a JSON object.
     *
     * @param stdClass $jsonData The JSON object.
     *
     * @return string The version.
     *
     * @throws CannotParseVersionException If the version cannot be parsed.
     */
    public function parseVersion(stdClass $jsonData);

    /**
     * Updates the version of a JSON object.
     *
     * @param stdClass $jsonData The JSON object.
     * @param string   $version  The version to set.
     *
     * @throws CannotUpdateVersionException If the version cannot be updated.
     */
    public function updateVersion(stdClass $jsonData, $version);
}
