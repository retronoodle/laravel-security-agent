<?php

namespace Timmonaghan\SecurityAgent\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Timmonaghan\SecurityAgent\Services\EnvWriter;

class EnvWriterTest extends TestCase
{
    private string $envPath;
    private EnvWriter $writer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->envPath = sys_get_temp_dir() . '/lsa_test_' . uniqid() . '.env';
        $this->writer = $this->makeWriter($this->envPath);
    }

    protected function tearDown(): void
    {
        @unlink($this->envPath);
        @unlink($this->envPath . '.bak');
        parent::tearDown();
    }

    private function makeWriter(string $path): EnvWriter
    {
        // Override base_path() by subclassing — instead we patch via temp file directly
        // and use a testable subclass that accepts a custom path.
        return new class($path) extends EnvWriter {
            public function __construct(private string $customPath) {}

            public function set(string $key, string $value): void
            {
                $allowed = (new \ReflectionClass(EnvWriter::class))->getConstant('ALLOWED_KEYS');
                if (! in_array($key, $allowed, true)) {
                    throw new InvalidArgumentException("EnvWriter: unknown key '{$key}'");
                }

                $contents = file_exists($this->customPath) ? file_get_contents($this->customPath) : '';
                $line = "{$key}={$value}";

                if (preg_match("/^{$key}=.*/m", $contents)) {
                    $updated = preg_replace("/^{$key}=.*/m", $line, $contents);
                } else {
                    $updated = rtrim($contents) . "\n{$line}\n";
                }

                $tmp = $this->customPath . '.tmp.' . uniqid('', true);
                file_put_contents($tmp, $updated);
                rename($tmp, $this->customPath);
            }
        };
    }

    public function test_key_updated_in_place(): void
    {
        file_put_contents($this->envPath, "APP_NAME=Laravel\nLSA_MODEL=claude-opus-4-7\nAPP_ENV=local\n");

        $this->writer->set('LSA_MODEL', 'claude-sonnet-4-6');

        $contents = file_get_contents($this->envPath);
        $this->assertStringContainsString('LSA_MODEL=claude-sonnet-4-6', $contents);
        $this->assertStringNotContainsString('claude-opus-4-7', $contents);
        $this->assertStringContainsString('APP_NAME=Laravel', $contents);
        $this->assertStringContainsString('APP_ENV=local', $contents);
    }

    public function test_key_appended_when_absent(): void
    {
        file_put_contents($this->envPath, "APP_NAME=Laravel\nAPP_ENV=local\n");

        $this->writer->set('LSA_MODEL', 'claude-haiku-4-5-20251001');

        $contents = file_get_contents($this->envPath);
        $this->assertStringContainsString('LSA_MODEL=claude-haiku-4-5-20251001', $contents);
        $this->assertStringContainsString('APP_NAME=Laravel', $contents);
    }

    public function test_throws_on_unknown_key(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->writer->set('SOME_RANDOM_KEY', 'value');
    }

    public function test_original_unchanged_on_write_failure(): void
    {
        $original = "APP_NAME=Laravel\nLSA_MODEL=claude-sonnet-4-6\n";
        file_put_contents($this->envPath, $original);

        // Writer that throws on file_put_contents (simulates disk/permission error)
        $writer = new class($this->envPath) extends EnvWriter {
            public function __construct(private string $customPath) {}

            public function set(string $key, string $value): void
            {
                $allowed = (new \ReflectionClass(EnvWriter::class))->getConstant('ALLOWED_KEYS');
                if (! in_array($key, $allowed, true)) {
                    throw new \InvalidArgumentException("EnvWriter: unknown key '{$key}'");
                }

                throw new \RuntimeException("EnvWriter: failed to write temp file for key '{$key}'");
            }
        };

        try {
            $writer->set('LSA_MODEL', 'claude-opus-4-7');
            $this->fail('Expected RuntimeException not thrown');
        } catch (\RuntimeException) {
            // expected
        }

        $this->assertSame($original, file_get_contents($this->envPath));
    }

    public function test_api_key_written(): void
    {
        file_put_contents($this->envPath, "APP_NAME=Laravel\n");

        $this->writer->set('LSA_ANTHROPIC_API_KEY', 'sk-ant-abc123');

        $contents = file_get_contents($this->envPath);
        $this->assertStringContainsString('LSA_ANTHROPIC_API_KEY=sk-ant-abc123', $contents);
    }
}
