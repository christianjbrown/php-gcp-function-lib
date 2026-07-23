<?php

declare(strict_types=1);

namespace ChristianBrown\GcpFunction;

use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

#[OA\Schema(
    schema: 'ErrorEnvelope',
    description: 'The complete JSON error envelope returned by every consuming function for a failed request (an authorization failure or an unhandled error). Identical for every function.',
    required: [
        self::RESPONSE_API_KEY_SUCCESS,
        self::RESPONSE_API_KEY_TIMESTAMP_UNIX,
        self::RESPONSE_API_KEY_TIMESTAMP_ISO8601,
        self::RESPONSE_API_KEY_VERSION,
        self::RESPONSE_API_KEY_ERROR,
    ],
    properties: [
        new OA\Property(property: self::RESPONSE_API_KEY_SUCCESS, description: 'Always false for an error response.', type: 'boolean', enum: [false]),
        new OA\Property(property: self::RESPONSE_API_KEY_TIMESTAMP_UNIX, description: 'When the response was generated (Unix seconds).', type: 'integer'),
        new OA\Property(property: self::RESPONSE_API_KEY_TIMESTAMP_ISO8601, description: 'When the response was generated (ISO 8601).', type: 'string', format: 'date-time'),
        new OA\Property(property: self::RESPONSE_API_KEY_VERSION, description: 'The Cloud Run revision that produced the response.', type: 'string'),
        new OA\Property(property: self::RESPONSE_API_KEY_ERROR, description: 'A human-readable description of what went wrong.', type: 'string'),
    ],
    type: 'object',
    additionalProperties: false,
)]
#[OA\Schema(
    schema: 'SuccessEnvelope',
    description: 'The shared base of the JSON success envelope returned by every consuming function. The `data` property is a generic, untyped payload placeholder — a function tightens it to its own object or array schema by composing this base with `allOf`, e.g. `allOf: [{$ref: "#/components/schemas/SuccessEnvelope"}, {properties: {data: {$ref: "#/components/schemas/YourData"}}}]`. It is omitted from `required` because a function with an empty payload emits no `data` key at all.',
    required: [
        self::RESPONSE_API_KEY_SUCCESS,
        self::RESPONSE_API_KEY_TIMESTAMP_UNIX,
        self::RESPONSE_API_KEY_TIMESTAMP_ISO8601,
        self::RESPONSE_API_KEY_VERSION,
    ],
    properties: [
        new OA\Property(property: self::RESPONSE_API_KEY_SUCCESS, description: 'Always true for a success response.', type: 'boolean', enum: [true]),
        new OA\Property(property: self::RESPONSE_API_KEY_TIMESTAMP_UNIX, description: 'When the response was generated (Unix seconds).', type: 'integer'),
        new OA\Property(property: self::RESPONSE_API_KEY_TIMESTAMP_ISO8601, description: 'When the response was generated (ISO 8601).', type: 'string', format: 'date-time'),
        new OA\Property(property: self::RESPONSE_API_KEY_VERSION, description: 'The Cloud Run revision that produced the response.', type: 'string'),
        new OA\Property(property: self::RESPONSE_API_KEY_DATA, description: 'The function-specific payload (object or array). Each function overrides this via `allOf` with its own data schema.'),
    ],
    type: 'object',
    additionalProperties: false,
)]
interface ResponseInterface extends PsrResponseInterface
{
    public const string ERROR_JSON_ENCODING = 'Problem encoding JSON in response';
    public const string HEADER_CONTENT_TYPE_VALUE_JSON = 'application/json; charset=utf-8';
    public const string HEADER_KEY_ALLOW_METHODS = 'Access-Control-Allow-Methods';
    public const string HEADER_KEY_ALLOW_ORIGIN = 'Access-Control-Allow-Origin';
    public const string HEADER_KEY_CACHE_CONTROL = 'Cache-Control';
    public const string HEADER_KEY_CONTENT_TYPE = 'Content-Type';
    public const string HEADER_KEY_ORIGIN = 'Origin';
    public const string HEADER_KEY_SURROGATE_CONTROL = 'Surrogate-Control';
    public const string HEADER_KEY_SURROGATE_KEY = 'Surrogate-Key';
    public const string HEADER_KEY_VARY = 'Vary';
    public const string HEADER_VARY_ACCEPT_ENCODING = 'Accept-Encoding';
    public const string HEADER_VARY_ORIGIN = 'Origin';
    public const array HEADERS = [
        self::HEADER_KEY_ALLOW_METHODS => 'GET, OPTIONS',
        self::HEADER_KEY_CONTENT_TYPE => self::HEADER_CONTENT_TYPE_VALUE_JSON,
    ];
    public const array HOSTS_LOCAL = ['localhost', '127.0.0.1', '0.0.0.0', '::1'];
    public const string RESPONSE_API_KEY_DATA = 'data';
    public const string RESPONSE_API_KEY_ERROR = 'error';
    public const string RESPONSE_API_KEY_SUCCESS = 'success';
    public const string RESPONSE_API_KEY_TIMESTAMP_ISO8601 = 'timestamp_iso8601';
    public const string RESPONSE_API_KEY_TIMESTAMP_UNIX = 'timestamp_unix';
    public const string RESPONSE_API_KEY_VERSION = 'version';
    public const int STATUS_INTERNAL_SERVER_ERROR = 500;
    public const int STATUS_OK = 200;
    public const int STATUS_UNAUTHORIZED = 401;
}
