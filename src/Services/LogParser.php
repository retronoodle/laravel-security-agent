<?php

namespace Timmonaghan\SecurityAgent\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LogParser
{
    private const OFFSET_CACHE_KEY = 'security_agent.log_offset';

    private const PATTERNS = [
        'sqli' => '/union\s+select|\'\\s*or\\s*\'1\'\\s*=\\s*\'1|--\\s*$|\/\*.*\*\/|xp_cmdshell|exec\s*\(|drop\s+table|insert\s+into|select\s+.*\s+from/i',
        'auth_brute_force' => null, // handled by frequency count
        '404_flood' => null,        // handled by frequency count
    ];

    private const BRUTE_FORCE_THRESHOLD = 5;
    private const FLOOD_THRESHOLD = 10;

    public function getNewLines(string $logPath): array
    {
        if (! file_exists($logPath)) {
            Log::warning("SecurityAgent: log file not found at {$logPath}");
            return [];
        }

        $offset = Cache::get(self::OFFSET_CACHE_KEY, 0);
        $size   = filesize($logPath);

        if ($size < $offset) {
            // Log was rotated — reset
            $offset = 0;
        }

        if ($size === $offset) {
            return [];
        }

        $handle = fopen($logPath, 'r');
        fseek($handle, $offset);
        $buffer = '';
        while (! feof($handle)) {
            $buffer .= fread($handle, 8192);
        }
        $newOffset = ftell($handle);
        fclose($handle);

        $lines = array_map('rtrim', explode("\n", $buffer));

        Cache::forever(self::OFFSET_CACHE_KEY, $newOffset);

        return array_filter($lines);
    }

    public function detectThreats(array $lines): array
    {
        $batches = [];

        $ipCounts = ['auth_brute_force' => [], '404_flood' => []];

        foreach ($lines as $line) {
            $ip = $this->extractIp($line);

            // SQLi
            if ($ip && preg_match(self::PATTERNS['sqli'], $line)) {
                $batches[] = [
                    'ip'           => $ip,
                    'pattern_type' => 'sqli',
                    'raw_excerpt'  => $line,
                ];
            }

            // Count failed auth events per IP
            if (preg_match('/authentication.*failed|login.*failed|invalid.*credentials|unauthorized/i', $line) && $ip) {
                $ipCounts['auth_brute_force'][$ip][] = $line;
            }

            // Count 404 events per IP
            if (preg_match('/\s404\s/', $line) && $ip) {
                $ipCounts['404_flood'][$ip][] = $line;
            }
        }

        foreach ($ipCounts['auth_brute_force'] as $ip => $matchedLines) {
            if (count($matchedLines) >= self::BRUTE_FORCE_THRESHOLD) {
                $batches[] = [
                    'ip'           => $ip,
                    'pattern_type' => 'auth_brute_force',
                    'raw_excerpt'  => implode("\n", array_slice($matchedLines, 0, 10)),
                ];
            }
        }

        foreach ($ipCounts['404_flood'] as $ip => $matchedLines) {
            if (count($matchedLines) >= self::FLOOD_THRESHOLD) {
                $batches[] = [
                    'ip'           => $ip,
                    'pattern_type' => '404_flood',
                    'raw_excerpt'  => implode("\n", array_slice($matchedLines, 0, 10)),
                ];
            }
        }

        return $batches;
    }

    private function extractIp(string $line): ?string
    {
        $octet = '(?:25[0-5]|2[0-4]\d|[01]?\d\d?)';
        $pattern = '/(?<![a-zA-Z\/])(\b' . $octet . '(?:\.' . $octet . '){3}\b)/';
        if (preg_match($pattern, $line, $m)) {
            return $m[1];
        }
        return null;
    }
}
