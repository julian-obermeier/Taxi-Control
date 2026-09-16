<?php

namespace Tests\Unit;

use App\Services\TotpService;
use PHPUnit\Framework\TestCase;

class TotpServiceTest extends TestCase
{
    public function test_it_verifies_rfc_6238_compatible_six_digit_code(): void
    {
        $service = new TotpService();
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

        $this->assertTrue($service->verify($secret, '287082', 0, 59));
        $this->assertFalse($service->verify($secret, '000000', 0, 59));
    }
}
