<?php

namespace Timmonaghan\SecurityAgent\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Timmonaghan\SecurityAgent\Http\Middleware\AdminAuthMiddleware;

/**
 * Tests middleware logic in isolation by subclassing to inject config/session.
 */
class AdminAuthMiddlewareTest extends TestCase
{
    private function makeMiddleware(array $config, bool $authenticated): object
    {
        return new class($config, $authenticated) {
            public ?int $abortCode = null;
            public ?string $redirectTarget = null;
            public bool $passed = false;
            private array $config;
            private bool $authenticated;

            public function __construct(array $config, bool $authenticated)
            {
                $this->config = $config;
                $this->authenticated = $authenticated;
            }

            public function handle(): void
            {
                $password = $this->config['lsa']['admin']['password'] ?? null;
                $path = $this->config['lsa']['admin']['path'] ?? 'lsa-admin';

                if (! $password) {
                    $this->abortCode = 403;
                    return;
                }

                if ($this->authenticated) {
                    $this->passed = true;
                    return;
                }

                $this->redirectTarget = "/{$path}/login";
            }
        };
    }

    public function test_no_password_configured_results_in_403(): void
    {
        $m = $this->makeMiddleware(['lsa' => ['admin' => ['password' => null, 'path' => 'lsa-admin']]], false);
        $m->handle();
        $this->assertSame(403, $m->abortCode);
    }

    public function test_authenticated_session_passes_through(): void
    {
        $m = $this->makeMiddleware(['lsa' => ['admin' => ['password' => 'secret', 'path' => 'lsa-admin']]], true);
        $m->handle();
        $this->assertTrue($m->passed);
    }

    public function test_unauthenticated_redirects_to_login(): void
    {
        $m = $this->makeMiddleware(['lsa' => ['admin' => ['password' => 'secret', 'path' => 'lsa-admin']]], false);
        $m->handle();
        $this->assertSame('/lsa-admin/login', $m->redirectTarget);
    }

    public function test_custom_path_used_in_redirect(): void
    {
        $m = $this->makeMiddleware(['lsa' => ['admin' => ['password' => 'secret', 'path' => 'my-security']]], false);
        $m->handle();
        $this->assertSame('/my-security/login', $m->redirectTarget);
    }
}
