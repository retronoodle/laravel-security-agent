<?php

namespace Timmonaghan\SecurityAgent\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Timmonaghan\SecurityAgent\Mail\SecurityAlert;

class ThreatAgent
{
    private const MAX_TURNS = 10;

    private const TOOLS = [
        [
            'name'        => 'get_ip_history',
            'description' => 'Returns the count and most recent timestamp of prior security events for a given IP address.',
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'ip' => ['type' => 'string', 'description' => 'IPv4 address to look up'],
                ],
                'required' => ['ip'],
            ],
        ],
        [
            'name'        => 'get_recent_events',
            'description' => 'Returns the last N security events across all IPs for campaign-level context.',
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'limit' => ['type' => 'integer', 'description' => 'Maximum number of events to return (max 50)'],
                ],
                'required' => ['limit'],
            ],
        ],
        [
            'name'        => 'block_ip',
            'description' => 'Blocks an IP address. Only call this when confidence is at or above the configured threshold.',
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'ip'         => ['type' => 'string'],
                    'reason'     => ['type' => 'string'],
                    'confidence' => ['type' => 'number', 'description' => 'Confidence score 0.0–1.0'],
                ],
                'required' => ['ip', 'reason', 'confidence'],
            ],
        ],
        [
            'name'        => 'send_alert',
            'description' => 'Sends an alert email to administrators for threats that need human review.',
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'ip'          => ['type' => 'string'],
                    'pattern_type'=> ['type' => 'string'],
                    'summary'     => ['type' => 'string'],
                    'confidence'  => ['type' => 'number'],
                ],
                'required' => ['ip', 'pattern_type', 'summary', 'confidence'],
            ],
        ],
    ];

    public function __construct(
        private readonly IpBlocklist $blocklist,
        private readonly Client $http,
    ) {}

    /**
     * @return array{summary: string, confidence: float|null, outcome: string}
     */
    public function analyze(string $ip, string $patternType, string $rawExcerpt): array
    {
        $apiKey = config('security-agent.anthropic_api_key');
        $model  = config('security-agent.anthropic_model', 'claude-sonnet-4-6');

        $systemPrompt = <<<PROMPT
You are a security analyst agent. You have been given a suspicious log excerpt from a Laravel application.
Your job is to:
1. Research the threat using available tools.
2. Decide whether to block the IP or send an admin alert.
3. Always end with a concise plain-text summary and a confidence score (0.0–1.0) in the format:
   SUMMARY: <your summary>
   CONFIDENCE: <score>
PROMPT;

        $userMessage = "Suspicious activity detected.\nIP: {$ip}\nPattern: {$patternType}\n\nLog excerpt:\n{$rawExcerpt}";

        $messages = [['role' => 'user', 'content' => $userMessage]];

        $outcome    = 'ignored';
        $summary    = null;
        $confidence = null;

        for ($turn = 0; $turn < self::MAX_TURNS; $turn++) {
            $response = $this->callClaude($apiKey, $model, $systemPrompt, $messages);

            $messages[] = ['role' => 'assistant', 'content' => $response['content']];

            // Check for tool use blocks
            $toolUseBlocks = array_filter($response['content'], fn($b) => $b['type'] === 'tool_use');

            if (empty($toolUseBlocks)) {
                // Text completion — extract summary and confidence
                $textBlock = collect($response['content'])->firstWhere('type', 'text');
                if ($textBlock) {
                    [$summary, $confidence] = $this->parseCompletion($textBlock['text']);
                }
                break;
            }

            // Execute tools and feed results back
            $toolResults = [];
            foreach ($toolUseBlocks as $block) {
                $result        = $this->executeTool($block['name'], $block['input'], $ip, $patternType, $outcome);
                $outcome       = $result['outcome'] ?? $outcome;
                $toolResults[] = [
                    'type'        => 'tool_result',
                    'tool_use_id' => $block['id'],
                    'content'     => json_encode($result['data']),
                ];
            }

            $messages[] = ['role' => 'user', 'content' => $toolResults];
        }

        if ($turn >= self::MAX_TURNS) {
            Log::warning("SecurityAgent: max turns reached for IP {$ip}");
            $summary = $summary ?? 'Analysis incomplete: max turns reached.';
        }

        return [
            'summary'    => $summary ?? 'No summary produced.',
            'confidence' => $confidence,
            'outcome'    => $outcome,
        ];
    }

    private function callClaude(string $apiKey, string $model, string $system, array $messages): array
    {
        $response = $this->http->post('https://api.anthropic.com/v1/messages', [
            'headers' => [
                'x-api-key'         => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ],
            'json' => [
                'model'      => $model,
                'max_tokens' => 1024,
                'system'     => $system,
                'tools'      => self::TOOLS,
                'messages'   => $messages,
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    private function executeTool(string $name, array $input, string $currentIp, string $patternType, string &$outcome): array
    {
        return match ($name) {
            'get_ip_history'   => $this->toolGetIpHistory($input),
            'get_recent_events'=> $this->toolGetRecentEvents($input),
            'block_ip'         => $this->toolBlockIp($input, $outcome),
            'send_alert'       => $this->toolSendAlert($input, $patternType, $outcome),
            default            => ['data' => ['error' => "Unknown tool: {$name}"], 'outcome' => $outcome],
        };
    }

    // Task 4.3
    private function toolGetIpHistory(array $input): array
    {
        $ip  = $input['ip'];
        $row = DB::table('lsa_security_events')
            ->selectRaw('COUNT(*) as event_count, MAX(created_at) as last_seen')
            ->where('ip_address', $ip)
            ->first();

        return [
            'data' => [
                'ip'          => $ip,
                'event_count' => (int) ($row->event_count ?? 0),
                'last_seen'   => $row->last_seen ?? null,
            ],
        ];
    }

    // Task 4.4
    private function toolGetRecentEvents(array $input): array
    {
        $limit  = min((int) ($input['limit'] ?? 20), 50);
        $events = DB::table('lsa_security_events')
            ->select(['ip_address', 'pattern_type', 'confidence', 'created_at'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->toArray();

        return ['data' => $events];
    }

    // Task 4.5
    private function toolBlockIp(array $input, string &$outcome): array
    {
        $ip         = $input['ip'];
        $reason     = $input['reason'];
        $confidence = (float) $input['confidence'];
        $threshold  = (float) config('security-agent.confidence_threshold', 0.85);
        $ttl        = (int) config('security-agent.block_ttl_minutes', 60);

        if ($confidence < $threshold) {
            return [
                'data' => [
                    'blocked' => false,
                    'reason'  => "Confidence {$confidence} is below threshold {$threshold}. Block rejected.",
                ],
            ];
        }

        $this->blocklist->block($ip, $reason, $confidence, $ttl);
        $outcome = 'blocked';

        return [
            'data' => [
                'blocked'    => true,
                'ip'         => $ip,
                'expires_in' => "{$ttl} minutes",
            ],
        ];
    }

    // Task 4.6
    private function toolSendAlert(array $input, string $patternType, string &$outcome): array
    {
        $adminEmails = config('security-agent.admin_emails', []);

        if (empty($adminEmails)) {
            Log::warning('SecurityAgent: admin_emails not configured, skipping alert.');
            return ['data' => ['sent' => false, 'reason' => 'No admin_emails configured.']];
        }

        $mailable = new SecurityAlert(
            ip: $input['ip'],
            patternType: $input['pattern_type'] ?? $patternType,
            confidence: (float) ($input['confidence'] ?? 0),
            summary: $input['summary'],
            timestamp: now(),
        );

        foreach ($adminEmails as $email) {
            Mail::to(trim($email))->queue($mailable);
        }

        $outcome = 'alerted';

        return ['data' => ['sent' => true, 'recipients' => $adminEmails]];
    }

    private function parseCompletion(string $text): array
    {
        $summary    = null;
        $confidence = null;

        if (preg_match('/SUMMARY:\s*(.+?)(?:CONFIDENCE:|$)/si', $text, $m)) {
            $summary = trim($m[1]);
        }
        if (preg_match('/CONFIDENCE:\s*([0-9.]+)/i', $text, $m)) {
            $confidence = (float) $m[1];
        }

        return [$summary ?? $text, $confidence];
    }
}
