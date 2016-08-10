<?php

/*
 * This file is part of the Webmozart JSON package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Json\UriRetriever;

use JsonSchema\Uri\Retrievers\FileGetContents;
use JsonSchema\Uri\Retrievers\UriRetrieverInterface;
use Webmozart\PathUtil\Path;

/**
 * @since  1.3
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class LocalUriRetriever implements UriRetrieverInterface
{
    /**
     * @var string[]
     */
    private $mappings;

    /**
     * @var string
     */
    private $baseDir;

    /**
     * @var UriRetrieverInterface
     */
    private $filesystemRetriever;

    /**
     * @var UriRetrieverInterface
     */
    private $fallbackRetriever;

    /**
     * @var UriRetrieverInterface
     */
    private $lastUsedRetriever;

    public function __construct($baseDir = null, array $mappings = array(), UriRetrieverInterface $fallbackRetriever = null)
    {
        $this->baseDir = $baseDir ? Path::canonicalize($baseDir) : null;
        $this->mappings = $mappings;
        $this->filesystemRetriever = new FileGetContents();
        $this->fallbackRetriever = $fallbackRetriever ?: $this->filesystemRetriever;
    }

    /**
     * {@inheritdoc}
     */
    public function retrieve($uri)
    {
        if (isset($this->mappings[$uri])) {
            $uri = $this->mappings[$uri];

            if (Path::isLocal($uri)) {
                $uri = 'file://'.($this->baseDir ? Path::makeAbsolute($uri, $this->baseDir) : $uri);
            }

            $this->lastUsedRetriever = $this->filesystemRetriever;

            return $this->filesystemRetriever->retrieve($uri);
        }

        $this->lastUsedRetriever = $this->fallbackRetriever;

        return $this->fallbackRetriever->retrieve($uri);
    }

    /**
     * {@inheritdoc}
     */
    public function getContentType()
    {
        return $this->lastUsedRetriever ? $this->lastUsedRetriever->getContentType() : null;
    }
}
