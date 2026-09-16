<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class LegacyRouteRedirectController extends Controller
{
    public function __invoke(?string $path = null): RedirectResponse
    {
        return redirect()->to(url('/'.ltrim($path ?? '', '/')), 301);
    }
}
