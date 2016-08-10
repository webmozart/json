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

/**
 * Migrates a JSON object between versions.
 *
 * The JSON object is expected to have the property "version" set.
 *
 * @since  1.3
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface JsonMigration
{
    /**
     * Returns the version of the JSON object that this migration expects.
     *
     * @return string The version string
     */
    public function getSourceVersion();

    /**
     * Returns the version of the JSON object that this migration upgrades to.
     *
     * @return string The version string
     */
    public function getTargetVersion();

    /**
     * Upgrades a JSON object from the source to the target version.
     *
     * @param stdClass $data The JSON object of the package file
     */
    public function up(stdClass $data);

    /**
     * Reverts a JSON object from the target to the source version.
     *
     * @param stdClass $data The JSON object of the package file
     */
    public function down(stdClass $data);
}
