<?php

/*
 * This file is part of the webmozart/json package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Json\Migration\Versioner;

use stdClass;

/**
 * Expects the version to be set in the "version" field of a JSON object.
 *
 * @since  1.3
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class VersionFieldVersioner implements JsonVersioner
{
    /**
     * {@inheritdoc}
     */
    public function parseVersion(stdClass $jsonData)
    {
        if (!isset($jsonData->version)) {
            throw new CannotParseVersionException('Cannot find "version" property in JSON object.');
        }

        return $jsonData->version;
    }

    /**
     * {@inheritdoc}
     */
    public function updateVersion(stdClass $jsonData, $version)
    {
        $jsonData->version = $version;
    }
}
