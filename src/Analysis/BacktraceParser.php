<?php

declare(strict_types=1);

namespace BlissJaspis\QueryDetector\Analysis;

use Illuminate\Support\Collection;

class BacktraceParser
{
    /**
     * @return list<array{index: int, name: string|null, line: int|string}>
     */
    public function findSources(Collection $stack): array
    {
        $sources = [];

        foreach ($stack as $index => $trace) {
            $sources[] = $this->parseTrace($index, $trace);
        }

        return array_values(array_filter($sources));
    }

    /**
     * @param  array<string, mixed>  $trace
     * @return array{index: int, name: string|null, line: int|string}|false
     */
    public function parseTrace(int $index, array $trace): array|false
    {
        if (isset($trace['class'], $trace['file']) &&
            ! $this->fileIsInExcludedPath($trace['file'])
        ) {
            return [
                'index' => $index,
                'name' => $this->normalizeFilename($trace['file']),
                'line' => $trace['line'] ?? '?',
            ];
        }

        return false;
    }

    protected function fileIsInExcludedPath(string $file): bool
    {
        $excludedPaths = array_merge(
            [
                '/vendor/laravel/framework/src/Illuminate/Database',
                '/vendor/laravel/framework/src/Illuminate/Events',
            ],
            config('querydetector.excluded_paths', [])
        );

        $normalizedPath = str_replace('\\', '/', $file);

        foreach ($excludedPaths as $excludedPath) {
            if (str_contains($normalizedPath, $excludedPath)) {
                return true;
            }
        }

        return false;
    }

    protected function normalizeFilename(string $path): string
    {
        if (file_exists($path)) {
            $path = realpath($path) ?: $path;
        }

        return str_replace(base_path(), '', $path);
    }
}
