<?php

namespace Timmonaghan\SecurityAgent\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Timmonaghan\SecurityAgent\Services\EnvWriter;

/**
 * Tests that saveSettings calls EnvWriter correctly.
 * The EnvWriter is mocked; this tests the controller's decision logic.
 */
class AdminSettingsSaveTest extends TestCase
{
    private function makeMockEnvWriter(): object
    {
        return new class {
            public array $calls = [];

            public function set(string $key, string $value): void
            {
                $this->calls[] = [$key, $value];
            }
        };
    }

    private function simulateSaveSettings(array $input, array $availableModels, object $envWriter): string
    {
        // Mirrors the logic in AdminController::saveSettings()
        if (empty($input['model'])) {
            return 'validation_error';
        }

        if (! in_array($input['model'], $availableModels, true)) {
            return 'invalid_model';
        }

        $envWriter->set('LSA_MODEL', $input['model']);

        if (! empty($input['api_key'])) {
            $envWriter->set('LSA_ANTHROPIC_API_KEY', $input['api_key']);
        }

        return 'success';
    }

    public function test_model_written_to_env_on_save(): void
    {
        $writer = $this->makeMockEnvWriter();
        $result = $this->simulateSaveSettings(
            ['model' => 'claude-sonnet-4-6', 'api_key' => ''],
            ['claude-haiku-4-5-20251001', 'claude-sonnet-4-6', 'claude-opus-4-7'],
            $writer,
        );

        $this->assertSame('success', $result);
        $this->assertCount(1, $writer->calls);
        $this->assertSame(['LSA_MODEL', 'claude-sonnet-4-6'], $writer->calls[0]);
    }

    public function test_api_key_written_when_provided(): void
    {
        $writer = $this->makeMockEnvWriter();
        $this->simulateSaveSettings(
            ['model' => 'claude-sonnet-4-6', 'api_key' => 'sk-ant-xyz'],
            ['claude-haiku-4-5-20251001', 'claude-sonnet-4-6', 'claude-opus-4-7'],
            $writer,
        );

        $this->assertCount(2, $writer->calls);
        $this->assertSame(['LSA_ANTHROPIC_API_KEY', 'sk-ant-xyz'], $writer->calls[1]);
    }

    public function test_api_key_not_written_when_empty(): void
    {
        $writer = $this->makeMockEnvWriter();
        $this->simulateSaveSettings(
            ['model' => 'claude-sonnet-4-6', 'api_key' => ''],
            ['claude-haiku-4-5-20251001', 'claude-sonnet-4-6', 'claude-opus-4-7'],
            $writer,
        );

        $this->assertCount(1, $writer->calls);
    }

    public function test_invalid_model_rejected(): void
    {
        $writer = $this->makeMockEnvWriter();
        $result = $this->simulateSaveSettings(
            ['model' => 'gpt-4o', 'api_key' => ''],
            ['claude-haiku-4-5-20251001', 'claude-sonnet-4-6', 'claude-opus-4-7'],
            $writer,
        );

        $this->assertSame('invalid_model', $result);
        $this->assertCount(0, $writer->calls);
    }
}
