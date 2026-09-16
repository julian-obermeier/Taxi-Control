<?php

namespace Tests\Unit;

use App\Services\OutboundUrlGuard;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OutboundUrlGuardTest extends TestCase
{
    public function test_rejects_private_ip_targets(): void
    {
        $this->expectException(ValidationException::class);
        app(OutboundUrlGuard::class)->validateHttps('https://127.0.0.1/hook');
    }

    public function test_accepts_public_literal_ip_target(): void
    {
        $result = app(OutboundUrlGuard::class)->validateHttps('https://8.8.8.8/hook');
        $this->assertSame('8.8.8.8', $result['host']);
    }

    public function test_rejects_localhost_names(): void
    {
        $this->expectException(ValidationException::class);
        app(OutboundUrlGuard::class)->validateHttps('https://localhost/hook');
    }
}
