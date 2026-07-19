<?php

declare(strict_types=1);

namespace ChristianBrown\GcpFunction;

interface CacheHeaderBuilderInterface
{
    public const string DIRECTIVE_MAX_AGE_SPRINTF = 'max-age=%d';
    public const string DIRECTIVE_S_MAXAGE_SPRINTF = 's-maxage=%d';
    public const string DIRECTIVE_STALE_IF_ERROR_SPRINTF = 'stale-if-error=%d';
    public const string DIRECTIVE_STALE_WHILE_REVALIDATE_SPRINTF = 'stale-while-revalidate=%d';

    /**
     * @param array<string, string> $headers
     *
     * @return array<string, string>
     */
    public function build(array $headers, ?FunctionConfigInterface $functionConfig, bool $success): array;
}
