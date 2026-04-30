<?php

namespace Timmonaghan\SecurityAgent\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Timmonaghan\SecurityAgent\Services\ThreatAgent;

class AnalyzeThreat implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly string $ip,
        private readonly string $patternType,
        private readonly string $rawExcerpt,
    ) {}

    public function handle(ThreatAgent $agent): void
    {
        $eventId = DB::table('security_events')->insertGetId([
            'ip_address'   => $this->ip,
            'pattern_type' => $this->patternType,
            'raw_excerpt'  => $this->rawExcerpt,
            'outcome'      => 'pending',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        try {
            $result = $agent->analyze($this->ip, $this->patternType, $this->rawExcerpt);
        } catch (\Throwable $e) {
            Log::error("SecurityAgent: analysis failed for IP {$this->ip}: " . $e->getMessage());
            DB::table('security_events')->where('id', $eventId)->update([
                'agent_summary' => 'Analysis failed: ' . $e->getMessage(),
                'outcome'       => 'ignored',
                'updated_at'    => now(),
            ]);
            return;
        }

        DB::table('security_events')->where('id', $eventId)->update([
            'agent_summary' => $result['summary'],
            'confidence'    => $result['confidence'],
            'outcome'       => $result['outcome'],
            'updated_at'    => now(),
        ]);
    }
}
