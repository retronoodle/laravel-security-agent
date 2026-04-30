<?php

namespace Timmonaghan\SecurityAgent\Services;

use InvalidArgumentException;

class EnvWriter
{
    private const ALLOWED_KEYS = ['LSA_MODEL', 'LSA_ANTHROPIC_API_KEY'];

    public function set(string $key, string $value): void
    {
        if (! in_array($key, self::ALLOWED_KEYS, true)) {
            throw new InvalidArgumentException("EnvWriter: unknown key '{$key}'");
        }

        $envPath = base_path('.env');
        $contents = file_exists($envPath) ? file_get_contents($envPath) : '';

        $line = "{$key}={$value}";

        if (preg_match("/^{$key}=.*/m", $contents)) {
            $updated = preg_replace("/^{$key}=.*/m", $line, $contents);
        } else {
            $updated = rtrim($contents) . "\n{$line}\n";
        }

        $tmp = $envPath . '.tmp.' . uniqid('', true);
        if (file_put_contents($tmp, $updated) === false) {
            @unlink($tmp);
            throw new \RuntimeException("EnvWriter: failed to write temp file for key '{$key}'");
        }
        rename($tmp, $envPath);
    }
}
