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

use stdClass;
use Webmozart\Assert\Assert;

/**
 * Migrates a JSON object between different versions.
 *
 * The JSON object is expected to have the property "version" set.
 *
 * @since  1.3
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class MigrationManager
{
    /**
     * @var JsonMigration[]
     */
    private $migrationsBySourceVersion = array();

    /**
     * @var JsonMigration[]
     */
    private $migrationsByTargetVersion = array();

    /**
     * @var string[]
     */
    private $knownVersions;

    /**
     * Creates a new migration manager.
     *
     * @param JsonMigration[] $migrations The migrations migrating a JSON object
     *                                    between individual versions.
     */
    public function __construct(array $migrations)
    {
        Assert::allIsInstanceOf($migrations, __NAMESPACE__.'\JsonMigration');

        foreach ($migrations as $migration) {
            $this->migrationsBySourceVersion[$migration->getSourceVersion()] = $migration;
            $this->migrationsByTargetVersion[$migration->getTargetVersion()] = $migration;
            $this->knownVersions[] = $migration->getSourceVersion();
            $this->knownVersions[] = $migration->getTargetVersion();
        }

        $this->knownVersions = array_unique($this->knownVersions);

        uksort($this->migrationsBySourceVersion, 'version_compare');
        uksort($this->migrationsByTargetVersion, 'version_compare');
        usort($this->knownVersions, 'version_compare');
    }

    /**
     * Migrates a JSON object to the given version.
     *
     * @param stdClass $data          The JSON object.
     * @param string   $targetVersion The version string.
     *
     */
    public function migrate(stdClass $data, $targetVersion)
    {
        if (version_compare($targetVersion, $data->version, '>')) {
            $this->up($data, $targetVersion);
        } elseif (version_compare($targetVersion, $data->version, '<')) {
            $this->down($data, $targetVersion);
        }
    }

    /**
     * Returns all versions known to the manager.
     *
     * @return string[] The known version strings.
     */
    public function getKnownVersions()
    {
        return $this->knownVersions;
    }

    private function up($data, $targetVersion)
    {
        while (version_compare($data->version, $targetVersion, '<')) {
            if (!isset($this->migrationsBySourceVersion[$data->version])) {
                throw new MigrationException(sprintf(
                    'No migration found to upgrade from version %s to %s.',
                    $data->version,
                    $targetVersion
                ));
            }

            $migration = $this->migrationsBySourceVersion[$data->version];

            // Final version too high
            if (version_compare($migration->getTargetVersion(), $targetVersion, '>')) {
                throw new MigrationException(sprintf(
                    'No migration found to upgrade from version %s to %s.',
                    $data->version,
                    $targetVersion
                ));
            }

            $migration->up($data);

            $data->version = $migration->getTargetVersion();
        }
    }

    private function down($data, $targetVersion)
    {
        while (version_compare($data->version, $targetVersion, '>')) {
            if (!isset($this->migrationsByTargetVersion[$data->version])) {
                throw new MigrationException(sprintf(
                    'No migration found to downgrade from version %s to %s.',
                    $data->version,
                    $targetVersion
                ));
            }

            $migration = $this->migrationsByTargetVersion[$data->version];

            // Final version too low
            if (version_compare($migration->getSourceVersion(), $targetVersion, '<')) {
                throw new MigrationException(sprintf(
                    'No migration found to downgrade from version %s to %s.',
                    $data->version,
                    $targetVersion
                ));
            }

            $migration->down($data);

            $data->version = $migration->getSourceVersion();
        }
    }
}
