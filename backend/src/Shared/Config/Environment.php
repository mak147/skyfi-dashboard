<?php

declare(strict_types=1);

namespace SkyFi\Shared\Config;

final class Environment
{
    /**
     * Loads simple KEY=VALUE pairs without overwriting values supplied by the host.
     *
     * @param string $path Path to the environment file.
     */
    public static function load(string $path): void
    {
        if (!is_file($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            $value = trim($value, "\"'");

            if ($key !== '' && getenv($key) === false) {
                putenv(sprintf('%s=%s', $key, $value));
            }
        }
    }
}
