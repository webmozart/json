Webmozart JSON
==============

[![Build Status](https://travis-ci.org/webmozart/json.svg?branch=master)](https://travis-ci.org/webmozart/json)
[![Build status](https://ci.appveyor.com/api/projects/status/icccqc0aq1molo96/branch/master?svg=true)](https://ci.appveyor.com/project/webmozart/json/branch/master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/webmozart/json/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/webmozart/json/?branch=master)
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

~~~
$ composer require webmozart/json
~~~

Encoding
--------

Use the [`JsonEncoder`] to encode data as JSON:

~~~php
use Webmozart\Json\JsonEncoder;

$encoder = new JsonEncoder();

// Store JSON in string
$string = $encoder->encode($data);

// Store JSON in file
$encoder->encodeFile($data, '/path/to/file.json');
~~~

You can pass the path to a [JSON schema] in the last optional argument of
both methods:

~~~php
use Webmozart\Json\ValidationFailedException;

try {
    $string = $encoder->encode($data, '/path/to/schema.json');
} catch (ValidationFailedException $e) {
    // data did not match schema 
}
~~~

Decoding
--------

Use the [`JsonDecoder`] to decode a JSON string/file:

~~~php
use Webmozart\Json\JsonDecoder;

$decoder = new JsonDecoder();

// Read JSON string
$data = $decoder->decode($string);

// Read JSON file
$data = $decoder->decodeFile('/path/to/file.json');
~~~

Like [`JsonEncoder`], the decoder accepts the path to a JSON schema in the last
optional argument of its methods:

~~~php
use Webmozart\Json\ValidationFailedException;

try {
    $data = $decoder->decodeFile('/path/to/file.json', '/path/to/schema.json');
} catch (ValidationFailedException $e) {
    // data did not match schema 
}
~~~

Validation
----------

Sometimes it is necessary to separate the steps of encoding/decoding JSON data
and validating it against a schema. In this case, you can omit the schema
argument during encoding/decoding and use the [`JsonValidator`] to validate the
data manually later on:

~~~php
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
~~~

Conversion
----------

You can implement [`JsonConverter`] to encapsulate the conversion of objects
from and to JSON structures in a single class:

~~~php
use stdClass;
use Webmozart\Json\Conversion\JsonConverter;

class ConfigFileJsonConverter implements JsonConverter
{
    public function toJson($configFile, array $options = array())
    {
        $jsonData = new stdClass();
         
        if (null !== $configFile->getApplicationName()) {
            $jsonData->application = $configFile->getApplicationName();
        }
        
        // ...
        
        return $jsonData;
    }
    
    public function fromJson($jsonData, array $options = array())
    {
        $configFile = new ConfigFile();
        
        if (isset($jsonData->application)) {
            $configFile->setApplicationName($jsonData->application);
        }
        
        // ...
        
        return $configFile;
    }
}
~~~

Loading and dumping `ConfigFile` objects is very simple now:

~~~php
$converter = new ConfigFileJsonConverter();

// Load config.json as ConfigFile object
$jsonData = $decoder->decodeFile('/path/to/config.json');
$configFile = $converter->fromJson($jsonData);

// Save ConfigFile object as config.json
$jsonData = $converter->toJson($configFile);
$encoder->encodeFile($jsonData, '/path/to/config.json');
~~~

You can automate the schema validation of your `ConfigFile` by wrapping the
converter in a `ValidatingConverter`:

~~~php
use Webmozart\Json\Validation\ValidatingConverter;

$converter = new ValidatingConverter(
    new ConfigFileJsonConverter(),
    __DIR__.'/../res/schema/config-schema.json'
);
~~~

Versioning and Migration
------------------------

When you continuously develop an application, you will enter the situation that
you need to change your JSON schemas. Updating JSON files to match their
changed schemas can be challenging and time consuming. This package supports a
versioning mechanism to automate this migration.

Imagine `config.json` files in three different versions: 1.0, 2.0 and 3.0.
The name of a key changed between those versions.

The JSON file must contain a key "version" that stores the version of the file:

config.json (version 1.0)

~~~json
{
    "version": "1.0",
    "application": "Hello world!"
}
~~~

config.json (version 2.0)

~~~json
{
    "version": "2.0",
    "application.name": "Hello world!"
}
~~~

config.json (version 3.0)

~~~json
{
    "version": "3.0",
    "application": {
        "name": "Hello world!"
    }
}
~~~

You can support files in any of these versions by implementing:

1. A converter compatible with the latest version (e.g. 3.0)

2. Migrations that migrate older versions to newer versions (e.g. 1.0  to
   2.0 and 2.0 to 3.0.
   
Let's look at an example of a `ConfigFileJsonConverter` for version 3.0:

~~~php
use stdClass;
use Webmozart\Json\Conversion\JsonConverter;

class ConfigFileJsonConverter implements JsonConverter
{
    public function toJson($configFile, array $options = array())
    {
        $jsonData = new stdClass();
         
        if (null !== $configFile->getApplicationName()) {
            $jsonData->application = new stdClass();
            $jsonData->application->name = $configFile->getApplicationName();
        }
        
        // ...
        
        return $jsonData;
    }
    
    public function fromJson($jsonData, array $options = array())
    {
        $configFile = new ConfigFile();
        
        if (isset($jsonData->application->name)) {
            $configFile->setApplicationName($jsonData->application->name);
        }
        
        // ...
        
        return $configFile;
    }
}
~~~

This converter can be used as described in the previous section. However,
it can only be used with `config.json` files in version 3.0.

We can add support for older files by implementing the [`JsonMigration`]
interface. This interface contains four methods:
 
* `getSourceVersion()`: returns the source version of the migration
* `getTargetVersion()`: returns the target version of the migration
* `up(stdClass $jsonData)`: migrates from the source to the target version
* `down(stdClass $jsonData)`: migrates from the target to the source version

~~~php
use Webmozart\Json\Migration\JsonMigration;

class ConfigFileJson20To30Migration implements JsonMigration
{
    public function getSourceVersion()
    {
        return '2.0';
    }
    
    public function getTargetVersion()
    {
        return '3.0';
    }
    
    public function up(stdClass $jsonData)
    {
        if (isset($jsonData->{'application.name'})) {
            $jsonData->application = new stdClass();
            $jsonData->application->name = $jsonData->{'application.name'};
            
            unset($jsonData->{'application.name'});
        )
    }
    
    public function down(stdClass $jsonData)
    {
        if (isset($jsonData->application->name)) {
            $jsonData->{'application.name'} = $jsonData->application->name;
            
            unset($jsonData->application);
        )
    }
}
~~~

With a list of such migrations, we can create a `MigratingConverter` that
decorates our `ConfigFileJsonConverter`:

~~~php
use Webmozart\Json\Migration\MigratingConverter;
use Webmozart\Json\Migration\MigrationManager;

// Written for version 3.0
$converter = new ConfigFileJsonConverter();

// Support for older versions. The order of migrations does not matter.
$migrationManager = new MigrationManager(array(
    new ConfigFileJson10To20Migration(),
    new ConfigFileJson20To30Migration(),
));

// Decorate the converter
$converter = new MigratingConverter($converter, $migrationManager);
~~~

The resulting converter is able to load and dump JSON files in any of the 
versions 1.0, 2.0 and 3.0.
 
~~~php
// Loads a file in version 1.0, 2.0 or 3.0
$jsonData = $decoder->decodeFile('/path/to/config.json');
$configFile = $converter->fromJson($jsonData);

// Writes the file in the latest version by default (3.0)
$jsonData = $converter->toJson($configFile);
$encoder->encodeFile($jsonData, '/path/to/config.json');

// Writes the file in a specific version
$jsonData = $converter->toJson($configFile, array(
    'target_version' => '2.0',
));
$encoder->encodeFile($jsonData, '/path/to/config.json');
~~~

If you want to add schema validation, wrap your encoder into a
`ValidatingConverter`. You can wrap both the inner and the outer converter
to make sure that both the JSON before and after running the migrations complies
to the corresponding schemas.

~~~php
// Written for version 3.0
$converter = new ConfigFileJsonConverter();

// Decorate to validate against a schema
$converter = new ValidatingConverter($converter, __DIR__.'/../res/schema/config-schema-3.0.json');

// Decorate to support different versions
$converter = new MigratingConverter($converter, $migrationManager);

// Decorate to validate against older schemas
$converter = new ValidatingConverter($converter, function ($jsonData) {
    return __DIR__.'/../res/schema/config-schema-'.$jsonData->version.'.json'
});
~~~

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
[`JsonConverter`]: src/Conversion/JsonConverter.php
[`JsonMigration`]: src/Migration/JsonMigration.php
