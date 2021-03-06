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
use JsonSchema\Uri\Retrievers\UriRetrieverInterface;

/**
 * @author Bernhard Schussek <hello@webmozart.io>
 */
class LocalUriRetrieverTest extends TestCase
{
    private const GITHUB_SCHEMA_URL = 'https://raw.githubusercontent.com/webmozart/json/be0e18a01f2ef720008a91d047f16de1dc30030c/tests/Fixtures/schema.json';

    private const GITHUB_SCHEMA_BODY = <<<'BODY'
{
    "id": "http://webmozart.io/fixtures/schema#",
    "type": "object"
}

BODY;

    private const GITHUB_SCHEMA_CONTENT_TYPE = 'text/plain; charset=utf-8';

    public function testRetrieve(): void
    {
        $retriever = new LocalUriRetriever(__DIR__.'/Fixtures', array(
            'http://my/schema/1.0' => 'schema-1.0.json',
            'http://my/schema/2.0' => self::GITHUB_SCHEMA_URL,
        ));

        $schema1Body = file_get_contents(__DIR__.'/Fixtures/schema-1.0.json');

        self::assertSame($schema1Body, $retriever->retrieve('http://my/schema/1.0'));
        self::assertNull($retriever->getContentType());

        self::assertSame(self::GITHUB_SCHEMA_BODY, $retriever->retrieve('http://my/schema/2.0'));
        self::assertSame(self::GITHUB_SCHEMA_CONTENT_TYPE, $retriever->getContentType());
    }

    public function testRetrieveLoadsUnmappedUrisFromFilesystemByDefault(): void
    {
        $retriever = new LocalUriRetriever();

        $schema1Body = file_get_contents(__DIR__.'/Fixtures/schema-1.0.json');

        self::assertSame($schema1Body, $retriever->retrieve('file://'.__DIR__.'/Fixtures/schema-1.0.json'));
        self::assertNull($retriever->getContentType());
    }

    public function testRetrieveLoadsUnmappedUrisFromWebByDefault(): void
    {
        $retriever = new LocalUriRetriever();

        self::assertSame(self::GITHUB_SCHEMA_BODY, $retriever->retrieve(self::GITHUB_SCHEMA_URL));
        self::assertSame(self::GITHUB_SCHEMA_CONTENT_TYPE, $retriever->getContentType());
    }

    public function testRetrievePassesUnmappedUrisToFallbackRetriever(): void
    {
        $fallbackRetriever = $this->createMock(UriRetrieverInterface::class);

        $fallbackRetriever->expects(self::once())
            ->method('retrieve')
            ->with('http://my/schema/1.0')
            ->willReturn('FOOBAR');

        $fallbackRetriever->expects(self::once())
            ->method('getContentType')
            ->willReturn('content/type');

        $retriever = new LocalUriRetriever(null, array(), $fallbackRetriever);

        self::assertSame('FOOBAR', $retriever->retrieve('http://my/schema/1.0'));
        self::assertSame('content/type', $retriever->getContentType());
    }

    public function testGetContentTypeInitiallyReturnsNull(): void
    {
        $retriever = new LocalUriRetriever();

        self::assertNull($retriever->getContentType());
    }
}
