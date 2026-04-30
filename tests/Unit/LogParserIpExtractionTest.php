<?php

namespace Timmonaghan\SecurityAgent\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Timmonaghan\SecurityAgent\Services\LogParser;

class LogParserIpExtractionTest extends TestCase
{
    private LogParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new LogParser();
    }

    public function test_extracts_valid_ipv4(): void
    {
        $line = '192.168.1.100 - GET /admin HTTP/1.1 200';
        $result = $this->callExtractIp($line);
        $this->assertSame('192.168.1.100', $result);
    }

    public function test_does_not_extract_version_string(): void
    {
        $line = 'User-Agent: GuzzleHttp/guzzlehttp/guzzle 7.0.1';
        $result = $this->callExtractIp($line);
        $this->assertNull($result);
    }

    public function test_does_not_extract_version_after_slash(): void
    {
        $line = 'guzzlehttp/guzzle/7.0.1 request failed';
        $result = $this->callExtractIp($line);
        $this->assertNull($result);
    }

    public function test_does_not_match_invalid_octet(): void
    {
        $line = '999.999.999.999 attempted connection';
        $result = $this->callExtractIp($line);
        $this->assertNull($result);
    }

    private function callExtractIp(string $line): ?string
    {
        $ref = new \ReflectionMethod(LogParser::class, 'extractIp');
        $ref->setAccessible(true);
        return $ref->invoke($this->parser, $line);
    }
}
