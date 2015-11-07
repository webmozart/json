<?php

/*
 * This file is part of the webmozart/json package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Json;

use RuntimeException;

/**
 * Thrown when read/write errors on the filesystem occur.
 *
 * @since  1.1
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class IOException extends RuntimeException
{
}
