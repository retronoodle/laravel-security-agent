<?php

namespace Timmonaghan\SecurityAgent\Console\Commands;

use Illuminate\Console\Command;
use Timmonaghan\SecurityAgent\Jobs\AnalyzeThreat;
use Timmonaghan\SecurityAgent\Services\LogParser;

class MonitorLogs extends Command
{
    protected $signature = 'security-agent:monitor';

    protected $description = 'Poll the Laravel log for suspicious activity and dispatch threat analysis jobs';

    public function handle(LogParser $parser): int
    {
        $logPath = config('security-agent.log_path');

        $lines = $parser->getNewLines($logPath);

        if (empty($lines)) {
            return Command::SUCCESS;
        }

        $threats = $parser->detectThreats($lines);

        foreach ($threats as $threat) {
            AnalyzeThreat::dispatch($threat['ip'], $threat['pattern_type'], $threat['raw_excerpt']);
        }

        return Command::SUCCESS;
    }
}
