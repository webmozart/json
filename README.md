Webmozart JSON
==============

[![Build Status](https://travis-ci.org/webmozart/json.svg?branch=1.2.2)](https://travis-ci.org/webmozart/json)
[![Build status](https://ci.appveyor.com/api/projects/status/icccqc0aq1molo96/branch/master?svg=true)](https://ci.appveyor.com/project/webmozart/json/branch/master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/webmozart/json/badges/quality-score.png?b=1.2.2)](https://scrutinizer-ci.com/g/webmozart/json/?branch=1.2.2)
[![Latest Stable Version](https://poser.pugx.org/webmozart/json/v/stable.svg)](https://packagist.org/packages/webmozart/json)
[![Total Downloads](https://poser.pugx.org/webmozart/json/downloads.svg)](https://packagist.org/packages/webmozart/json)
[![Dependency Status](https://www.versioneye.com/php/webmozart:json/1.2.2/badge.svg)](https://www.versioneye.com/php/webmozart:json/1.2.2)

Latest release: [1.2.2](https://packagist.org/packages/webmozart/json#1.2.2)

A robust wrapper for `json_encode()`/`json_decode()` that normalizes their
behavior across PHP versions, throws meaningful exceptions and supports schema
validation by default.

Installation
------------

Use [Composer] to install the package:

```
$ composer require webmozart/json
```

Encoding
--------

Use the [`JsonEncoder`] to encode data as JSON:

```php
use Webmozart\Json\JsonEncoder;

$encoder = new JsonEncoder();

// Store JSON in string
$string = $encoder->encode($data);

// Store JSON in file
$encoder->encodeFile($data, '/path/to/file.json');
```

You can pass the path to a [JSON schema] in the last optional argument of
both methods:

```php
use Webmozart\Json\ValidationFailedException;

try {
    $string = $encoder->encode($data, '/path/to/schema.json');
} catch (ValidationFailedException $e) {
    // data did not match schema 
}
```

Decoding
--------

Use the [`JsonDecoder`] to decode a JSON string/file:

```php
use Webmozart\Json\JsonDecoder;

$decoder = new JsonDecoder();

// Read JSON string
$data = $decoder->decode($string);

// Read JSON file
$data = $decoder->decodeFile('/path/to/file.json');
```

Like [`JsonEncoder`], the decoder accepts the path to a JSON schema in the last
optional argument of its methods:

```php
use Webmozart\Json\ValidationFailedException;

try {
    $data = $decoder->decodeFile('/path/to/file.json', '/path/to/schema.json');
} catch (ValidationFailedException $e) {
    // data did not match schema 
}
```

Validation
----------

Sometimes it is necessary to separate the steps of encoding/decoding JSON data
and validating it against a schema. In this case, you can omit the schema
argument during encoding/decoding and use the [`JsonValidator`] to validate the
data manually later on:

```php
use Webmozart\Json\JsonDecoder;
use Webmozart\Json\JsonValidator;
use Webmozart\Json\ValidationFailedException;

$decoder = new JsonDecoder();
$validator = new JsonValidator();

$data = $decoder->decodeFile('/path/to/file.json');

// process $data...

$errors = $validator->validate($data, '/path/to/schema.json');

if (count($errors) > 0) {
    // data did not match schema 
}
```

Authors
-------

* [Bernhard Schussek] a.k.a. [@webmozart]
* [The Community Contributors]

Contribute
----------

Contributions to the package are always welcome!

* Report any bugs or issues you find on the [issue tracker].
* You can grab the source code at the package's [Git repository].

Support
-------

If you are having problems, send a mail to bschussek@gmail.com or shout out to
[@webmozart] on Twitter.

License
-------

All contents of this package are licensed under the [MIT license].

[Composer]: https://getcomposer.org
[Bernhard Schussek]: http://webmozarts.com
[The Community Contributors]: https://github.com/webmozart/json/graphs/contributors
[issue tracker]: https://github.com/webmozart/json/issues
[Git repository]: https://github.com/webmozart/json
[@webmozart]: https://twitter.com/webmozart
[MIT license]: LICENSE
[JSON schema]: http://json-schema.org
[`JsonEncoder`]: src/JsonEncoder.php
[`JsonDecoder`]: src/JsonDecoder.php
[`JsonValidator`]: src/JsonValidator.php
