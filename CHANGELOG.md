Changelog
=========

* 1.2.2 (2016-01-14)

 * fixed loading of schemas from PHARs

* 1.2.1 (2016-01-14)

 * bumped justinrainbow/json-schema to 1.6 to fix "pattern-properties" with 
   slashes

* 1.2.0 (2015-01-02)

 * added support for `$ref` in schemas

* 1.1.1 (2015-12-28)

 * fixed PHP 7 compatibility

* 1.1.0 (2015-12-11)

 * added `IOException` and better error handling in `JsonEncoder::encodeFile()`
   and `JsonDecoder::decodeFile()`
 * `JsonEncoder::encodeFile()` now creates missing directories on demand
 * `JsonEncoder` now throws an exception on all PHP versions when binary values 
   are passed
 * added support for disabled slash escaping on PHP below 5.4

* 1.0.2 (2015-08-11)

 * fixed decoding of `null`

* 1.0.1 (2015-06-04)

 * fixed detection of the JSONC library in `JsonDecoder::decodeJson()`

* 1.0.0 (2015-03-19)

 * flipped `$data` and `$file` arguments of `JsonEncoder::encodeFile()`

* 1.0.0-beta (2015-01-12)

 * renamed `SchemaException` to `InvalidSchemaException`
 * changed `JsonValidator::validate()` to return the discovered errors instead
   of throwing an exception

* 1.0.0-alpha1 (2014-12-03)

 * first alpha release
