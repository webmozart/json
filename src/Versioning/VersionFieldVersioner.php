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
 * Expects the version to be set in the "version" field of a JSON object.
 *
 * @since  1.3
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class VersionFieldVersioner implements JsonVersioner
{
    /**
     * @var string
     */
    private $fieldName;

    /**
     * Creates a new versioner.
     *
     * @param string $fieldName The name of the version field
     */
    public function __construct($fieldName = 'version')
    {
        $this->fieldName = (string) $fieldName;
    }

    /**
     * {@inheritdoc}
     */
    public function parseVersion(stdClass $jsonData)
    {
        if (!isset($jsonData->{$this->fieldName})) {
            throw new CannotParseVersionException(sprintf(
                'Cannot find "%s" property in JSON object.',
                $this->fieldName
            ));
        }

        return $jsonData->{$this->fieldName};
    }

    /**
     * {@inheritdoc}
     */
    public function updateVersion(stdClass $jsonData, $version)
    {
        $jsonData->{$this->fieldName} = $version;
    }
}
