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
 * Expects the version to be set in the "$schema" field of a JSON object.
 *
 * @since  1.3
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class SchemaFieldPatternVersioner implements JsonVersioner
{
    /**
     * The default pattern used to extract the version of a schema ID.
     */
    const DEFAULT_PATTERN = '~(?<=/)\d+\.\d+(?=/)~';

    /**
     * @var string
     */
    private $pattern;

    public function __construct($pattern = self::DEFAULT_PATTERN)
    {
        $this->pattern = $pattern;
    }

    /**
     * {@inheritdoc}
     */
    public function parseVersion(stdClass $jsonData)
    {
        if (!isset($jsonData->{'$schema'})) {
            throw new CannotParseVersionException('Cannot find "$schema" property in JSON object.');
        }

        $schema = $jsonData->{'$schema'};

        if (!preg_match($this->pattern, $schema, $matches)) {
            throw new CannotParseVersionException(sprintf(
                'Cannot find version of schema "%s" (pattern: "%s")',
                $schema,
                $this->pattern
            ));
        }

        return $matches[0];
    }

    /**
     * {@inheritdoc}
     */
    public function updateVersion(stdClass $jsonData, $version)
    {
        if (!isset($jsonData->{'$schema'})) {
            throw new CannotUpdateVersionException('Cannot find "$schema" property in JSON object.');
        }

        $previousSchema = $jsonData->{'$schema'};
        $newSchema = preg_replace($this->pattern, $version, $previousSchema, -1, $count);

        if (1 !== $count) {
            throw new CannotUpdateVersionException(sprintf(
                'Cannot update version of schema "%s" (pattern: "%s"): %s',
                $previousSchema,
                $this->pattern,
                $count < 1 ? 'Not found' : 'Found more than once'
            ));
        }

        $jsonData->{'$schema'} = $newSchema;
    }
}
