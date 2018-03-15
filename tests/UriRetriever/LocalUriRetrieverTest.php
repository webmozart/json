<?php

/*
 * This file is part of the Webmozart JSON package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Json\Tests\UriRetriever;

use PHPUnit\Framework\TestCase;
use Webmozart\Json\UriRetriever\LocalUriRetriever;

/**
 * @author Bernhard Schussek <hello@webmozart.io>
 */
class LocalUriRetrieverTest extends TestCase
{
    const GITHUB_SCHEMA_URL = 'https://raw.githubusercontent.com/webmozart/json/be0e18a01f2ef720008a91d047f16de1dc30030c/tests/Fixtures/schema.json';

    const GITHUB_SCHEMA_BODY = <<<'BODY'
{
    "id": "http://webmozart.io/fixtures/schema#",
    "type": "object"
}

BODY;

    const GITHUB_SCHEMA_CONTENT_TYPE = 'text/plain; charset=utf-8';

    public function testRetrieve()
    {
        $retriever = new LocalUriRetriever(__DIR__.'/Fixtures', array(
            'http://my/schema/1.0' => 'schema-1.0.json',
            'http://my/schema/2.0' => self::GITHUB_SCHEMA_URL,
        ));

        $schema1Body = file_get_contents(__DIR__.'/Fixtures/schema-1.0.json');

        $this->assertSame($schema1Body, $retriever->retrieve('http://my/schema/1.0'));
        $this->assertNull($retriever->getContentType());

        $this->assertSame(self::GITHUB_SCHEMA_BODY, $retriever->retrieve('http://my/schema/2.0'));
        $this->assertSame(self::GITHUB_SCHEMA_CONTENT_TYPE, $retriever->getContentType());
    }

    public function testRetrieveLoadsUnmappedUrisFromFilesystemByDefault()
    {
        $retriever = new LocalUriRetriever();

        $schema1Body = file_get_contents(__DIR__.'/Fixtures/schema-1.0.json');

        $this->assertSame($schema1Body, $retriever->retrieve('file://'.__DIR__.'/Fixtures/schema-1.0.json'));
        $this->assertNull($retriever->getContentType());
    }

    public function testRetrieveLoadsUnmappedUrisFromWebByDefault()
    {
        $retriever = new LocalUriRetriever();

        $this->assertSame(self::GITHUB_SCHEMA_BODY, $retriever->retrieve(self::GITHUB_SCHEMA_URL));
        $this->assertSame(self::GITHUB_SCHEMA_CONTENT_TYPE, $retriever->getContentType());
    }

    public function testRetrievePassesUnmappedUrisToFallbackRetriever()
    {
        $fallbackRetriever = $this->createMock('JsonSchema\Uri\Retrievers\UriRetrieverInterface');

        $fallbackRetriever->expects($this->at(0))
            ->method('retrieve')
            ->with('http://my/schema/1.0')
            ->willReturn('FOOBAR');

        $fallbackRetriever->expects($this->at(1))
            ->method('getContentType')
            ->willReturn('content/type');

        $retriever = new LocalUriRetriever(null, array(), $fallbackRetriever);

        $this->assertSame('FOOBAR', $retriever->retrieve('http://my/schema/1.0'));
        $this->assertSame('content/type', $retriever->getContentType());
    }

    public function testGetContentTypeInitiallyReturnsNull()
    {
        $retriever = new LocalUriRetriever();

        $this->assertNull($retriever->getContentType());
    }
}
