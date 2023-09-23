<?php

declare(strict_types=1);

namespace ChristianBrown\CloudFunction;

use GuzzleHttp\Psr7\Response;
use JsonException;

use const JSON_THROW_ON_ERROR;

abstract class AbstractJsonResponse extends Response implements ResponseInterface
{
    /**
     * @noinspection PhpUnusedLocalVariableInspection
     */
    public function __construct(?FunctionConfigInterface $functionConfig, array $data = [], bool $success = true, ?string $error = null, int $statusCode = 200)
    {
        $time = time();

        $bodyJson = [
            self::RESPONSE_API_KEY_SUCCESS => $success,
            self::RESPONSE_API_KEY_TIMESTAMP_UNIX => $time,
            self::RESPONSE_API_KEY_TIMESTAMP_ISO8601 => date('c', $time),
        ];
        if ($data) {
            $bodyJson[self::RESPONSE_API_KEY_DATA] = $data;
        }
        if ($functionConfig instanceof FunctionConfigInterface) {
            $bodyJson[self::RESPONSE_API_KEY_VERSION] = $functionConfig->getKrevision();
        }
        if (null !== $error) {
            $bodyJson[self::RESPONSE_API_KEY_ERROR] = $error;
        }

        ksort($bodyJson);
        try {
            $body = json_encode($bodyJson, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            $success = false;
            $statusCode = 500;
            $bodyJson[self::RESPONSE_API_KEY_ERROR] = self::ERROR_JSON_ENCODING;
            /**
             * @noinspection PhpUsageOfSilenceOperatorInspection
             */
            $body = @json_encode($bodyJson);
        }

        $headers = self::HEADERS;
        if ($functionConfig instanceof FunctionConfigInterface) {
            $requiredOrigin = $functionConfig->getRequiredOrigin();
            if (!empty($requiredOrigin)) {
                $headers[self::HEADER_KEY_ALLOW_ORIGIN] = $requiredOrigin;
                $varyList = [self::HEADER_VARY_ACCEPT_ENCODING, self::HEADER_VARY_ORIGIN];
                $requiredHeaderKey = $functionConfig->getRequiredHeaderKey();
                if ($requiredHeaderKey) {
                    $varyList[] = $requiredHeaderKey;
                }
                $headers[self::HEADER_KEY_VARY] = implode(',', $varyList);
            }
        }

        if ($success) {
            // @todo Get these values from config, from env vars
            $headers[self::HEADER_KEY_SURROGATE_CONTROL] = 'max-age=3600, stale-while-revalidate=259200, stale-if-error=259200';
            $headers[self::HEADER_KEY_CACHE_CONTROL] = 's-maxage=3600, max-age=3600, stale-while-revalidate=259200, stale-if-error=259200';
        }

        parent::__construct($statusCode, $headers, $body);
    }
}
