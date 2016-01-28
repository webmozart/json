<?php

/*
 * This file is part of the webmozart/json package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Json\Conversion;

/**
 * Converts data to and from JSON.
 *
 * @since  1.3
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface JsonConverter
{
    /**
     * Converts an implementation-specific data structure to JSON.
     *
     * @param mixed $data    The data to convert.
     * @param array $options Additional implementation-specific conversion options.
     *
     * @return mixed The JSON data. Pass this data to a {@link JsonEncoder} to
     *               generate a JSON string.
     *
     * @throws ConversionFailedException If the conversion fails.
     */
    public function toJson($data, array $options = array());

    /**
     * Converts JSON to an implementation-specific data structure.
     *
     * @param mixed $jsonData The JSON data. Use a {@link JsonDecoder} to
     *                        convert a JSON string to this data structure.
     * @param array $options  Additional implementation-specific conversion options.
     *
     * @return mixed The converted data.
     *
     * @throws ConversionFailedException If the conversion fails.
     */
    public function fromJson($jsonData, array $options = array());
}
