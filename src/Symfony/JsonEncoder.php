<?php

/*
 * This file is part of the Webmozart JSON package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Json\Symfony;

use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Webmozart\Json\JsonEncoder as BaseJsonEncoder;
use Webmozart\Json\JsonDecoder as BaseJsonDecoder;
use Webmozart\Json\EncodingFailedException;
use Webmozart\Json\ValidationFailedException;
use Webmozart\Json\InvalidSchemaException;

/**
 * Bridge with the Symfony Serializer Component.
 *
 * @since  1.0
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class JsonEncoder implements EncoderInterface, DecoderInterface
{
    /**
     * @var BaseJsonEncoder
     */
    private $encoder;
    /**
     * @var BaseJsonDecoder
     */
    private $decoder;

    public function __construct(BaseJsonEncoder $encoder = null, BaseJsonDecoder $decoder = null)
    {
        $this->encoder = $encoder ?: new BaseJsonEncoder();
        $this->decoder = $decoder ?: new BaseJsonDecoder();
    }

    /**
     * {@inheritdoc}
     *
     * @throws EncodingFailedException If the data could not be encoded.
     * @throws ValidationFailedException If the data fails schema validation.
     * @throws InvalidSchemaException If the schema is invalid.
     */
    public function encode($data, $format, array $context = array())
    {
        $schema = isset($context['json_schema']) ? $context['json_schema'] : null;

        return $this->encoder->encode($data, $schema);
    }

    /**
     * {@inheritdoc}
     */
    public function decode($data, $format, array $context = array())
    {
        $schema = isset($context['json_schema']) ? $context['json_schema'] : null;

        return $this->decoder->decode($data, $schema);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsEncoding($format)
    {
        return 'json';
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDecoding($format)
    {
        return 'json';
    }
}
