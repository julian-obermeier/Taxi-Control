<?php

namespace Tests\Unit;

use App\Services\OutboundUrlGuard;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;

class OutboundUrlGuardTest extends TestCase
{
    public function test_rejects_private_ip_targets(): void
    {
        $this->expectException(ValidationException::class);
        (new OutboundUrlGuard())->validateHttps('https://127.0.0.1/hook');
    }

    public function test_accepts_public_literal_ip_target(): void
    {
        $result = (new OutboundUrlGuard())->validateHttps('https://8.8.8.8/hook');
        $this->assertSame('8.8.8.8', $result['host']);
    }
}
