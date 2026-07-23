<?php

declare(strict_types=1);

namespace ChristianBrown\GcpFunction;

use JsonException;

use function gmdate;
use function json_encode;
use function ksort;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;

final class ResponseBodyBuilder implements ResponseBodyBuilderInterface
{
    /**
     * @param mixed[] $data
     *
     * @return mixed[]
     */
    public function build(array $data, ?FunctionConfigInterface $functionConfig, bool $success, ?string $error, int $time): array
    {
        $bodyJson = [
            ResponseInterface::RESPONSE_API_KEY_SUCCESS => $success,
            ResponseInterface::RESPONSE_API_KEY_TIMESTAMP_UNIX => $time,
            ResponseInterface::RESPONSE_API_KEY_TIMESTAMP_ISO8601 => gmdate('c', $time),
        ];

        $bodyJson = self::appendData($bodyJson, $data);
        $bodyJson = self::appendVersion($bodyJson, $functionConfig);
        $bodyJson = self::appendError($bodyJson, $error);

        ksort($bodyJson);

        return $bodyJson;
    }

    /**
     * @param mixed[] $bodyJson
     *
     * @return array{0: string, 1: bool, 2: int}
     */
    public function encode(array $bodyJson, bool $success, int $statusCode): array
    {
        try {
            $body = json_encode($bodyJson, JSON_THROW_ON_ERROR + JSON_PRETTY_PRINT);
        } catch (JsonException $exception) {
            $success = false;
            $statusCode = ResponseInterface::STATUS_INTERNAL_SERVER_ERROR;
            $bodyJson[ResponseInterface::RESPONSE_API_KEY_SUCCESS] = false;
            unset($bodyJson[ResponseInterface::RESPONSE_API_KEY_DATA]);
            $bodyJson[ResponseInterface::RESPONSE_API_KEY_ERROR] = ResponseInterface::ERROR_JSON_ENCODING;
            /**
             * @noinspection PhpUsageOfSilenceOperatorInspection
             */
            $body = (string) @json_encode($bodyJson, JSON_PRETTY_PRINT);
        }

        return [$body, $success, $statusCode];
    }

    /**
     * @param mixed[] $bodyJson
     * @param mixed[] $data
     *
     * @return mixed[]
     */
    private static function appendData(array $bodyJson, array $data): array
    {
        if ($data) {
            $bodyJson[ResponseInterface::RESPONSE_API_KEY_DATA] = $data;
        }

        return $bodyJson;
    }

    /**
     * @param mixed[] $bodyJson
     *
     * @return mixed[]
     */
    private static function appendError(array $bodyJson, ?string $error): array
    {
        if (null !== $error) {
            $bodyJson[ResponseInterface::RESPONSE_API_KEY_ERROR] = $error;
        }

        return $bodyJson;
    }

    /**
     * @param mixed[] $bodyJson
     *
     * @return mixed[]
     */
    private static function appendVersion(array $bodyJson, ?FunctionConfigInterface $functionConfig): array
    {
        if ($functionConfig instanceof FunctionConfigInterface) {
            $bodyJson[ResponseInterface::RESPONSE_API_KEY_VERSION] = $functionConfig->getKrevision();
        }

        return $bodyJson;
    }
}
