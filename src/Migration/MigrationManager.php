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
use Webmozart\Json\Versioning\JsonVersioner;
use Webmozart\Json\Versioning\SchemaUriVersioner;

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
     * @var JsonVersioner
     */
    private $versioner;

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
    private $knownVersions = array();

    /**
     * Creates a new migration manager.
     *
     * @param JsonMigration[]    $migrations The migrations migrating a JSON
     *                                       object between individual versions.
     * @param JsonVersioner|null $versioner  The versioner that should be used.
     */
    public function __construct(array $migrations, JsonVersioner $versioner = null)
    {
        Assert::allIsInstanceOf($migrations, __NAMESPACE__.'\JsonMigration');

        $this->versioner = $versioner ?: new SchemaUriVersioner();

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
     */
    public function migrate(stdClass $data, $targetVersion)
    {
        $sourceVersion = $this->versioner->parseVersion($data);

        if (version_compare($targetVersion, $sourceVersion, '>')) {
            $this->up($data, $sourceVersion, $targetVersion);
        } elseif (version_compare($targetVersion, $sourceVersion, '<')) {
            $this->down($data, $sourceVersion, $targetVersion);
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

    private function up($data, $sourceVersion, $targetVersion)
    {
        while (version_compare($sourceVersion, $targetVersion, '<')) {
            if (!isset($this->migrationsBySourceVersion[$sourceVersion])) {
                throw new MigrationFailedException(sprintf(
                    'No migration found to upgrade from version %s to %s.',
                    $sourceVersion,
                    $targetVersion
                ));
            }

            $migration = $this->migrationsBySourceVersion[$sourceVersion];

            // Final version too high
            if (version_compare($migration->getTargetVersion(), $targetVersion, '>')) {
                throw new MigrationFailedException(sprintf(
                    'No migration found to upgrade from version %s to %s.',
                    $sourceVersion,
                    $targetVersion
                ));
            }

            $migration->up($data);

            $this->versioner->updateVersion($data, $migration->getTargetVersion());

            $sourceVersion = $migration->getTargetVersion();
        }
    }

    private function down($data, $sourceVersion, $targetVersion)
    {
        while (version_compare($sourceVersion, $targetVersion, '>')) {
            if (!isset($this->migrationsByTargetVersion[$sourceVersion])) {
                throw new MigrationFailedException(sprintf(
                    'No migration found to downgrade from version %s to %s.',
                    $sourceVersion,
                    $targetVersion
                ));
            }

            $migration = $this->migrationsByTargetVersion[$sourceVersion];

            // Final version too low
            if (version_compare($migration->getSourceVersion(), $targetVersion, '<')) {
                throw new MigrationFailedException(sprintf(
                    'No migration found to downgrade from version %s to %s.',
                    $sourceVersion,
                    $targetVersion
                ));
            }

            $migration->down($data);

            $this->versioner->updateVersion($data, $migration->getSourceVersion());

            $sourceVersion = $migration->getSourceVersion();
        }
    }
}
