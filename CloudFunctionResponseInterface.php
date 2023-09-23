<?php

declare(strict_types=1);

namespace CloudFunction;

use Psr\Http\Message\ResponseInterface;

interface CloudFunctionResponseInterface extends ResponseInterface
{
    public const ERROR_JSON_ENCODING = 'Problem encoding JSON in response';
    public const HEADER_CONTENT_TYPE_VALUE_JSON = 'application/json; charset=utf-8';
    public const HEADER_KEY_ALLOW_METHODS = 'Access-Control-Allow-Methods';
    public const HEADER_KEY_ALLOW_ORIGIN = 'Access-Control-Allow-Origin';
    public const HEADER_KEY_CACHE_CONTROL = 'Cache-Control';
    public const HEADER_KEY_CONTENT_TYPE = 'Content-Type';
    public const HEADER_KEY_SURROGATE_CONTROL = 'Surrogate-Control';
    public const HEADER_KEY_VARY = 'Vary';
    public const HEADER_VARY_ACCEPT_ENCODING = 'Accept-Encoding';
    public const HEADER_VARY_ORIGIN = 'Origin';
    public const HEADERS = [
        self::HEADER_KEY_ALLOW_ORIGIN => '*',
        self::HEADER_KEY_ALLOW_METHODS => 'GET, OPTIONS',
        self::HEADER_KEY_CONTENT_TYPE => self::HEADER_CONTENT_TYPE_VALUE_JSON,
    ];
    public const RESPONSE_API_KEY_DATA = 'data';
    public const RESPONSE_API_KEY_ERROR = 'error';
    public const RESPONSE_API_KEY_SUCCESS = 'success';
    public const RESPONSE_API_KEY_TIMESTAMP_ISO8601 = 'timestamp_iso8601';
    public const RESPONSE_API_KEY_TIMESTAMP_UNIX = 'timestamp_unix';
    public const RESPONSE_API_KEY_VERSION = 'version';
}
