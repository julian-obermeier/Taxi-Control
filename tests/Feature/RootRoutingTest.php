<?php

namespace Tests\Feature;

use Tests\TestCase;

class RootRoutingTest extends TestCase
{
    public function test_application_routes_use_domain_root(): void
    {
        $this->assertSame('/login', route('taxi-control.login', absolute: false));
        $this->assertSame('/superadmin', route('taxi-control.superadmin.dashboard', absolute: false));
        $this->assertSame('/taxi-mueller', route('taxi-control.tenant.dashboard', ['tenant' => 'taxi-mueller'], false));
    }

    public function test_legacy_prefix_redirects_to_root(): void
    {
        $this->get('/taxi-control/login')
            ->assertStatus(301)
            ->assertRedirect(url('/login'));

        $this->get('/taxi-control/superadmin')
            ->assertStatus(301)
            ->assertRedirect(url('/superadmin'));
    }
}
