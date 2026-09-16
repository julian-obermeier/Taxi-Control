<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class InstallerController extends Controller
{
    public function show(): View
    {
        abort_if($this->installed(), 404);

        return view('installer.index', ['checks' => $this->checks()]);
    }

    public function install(Request $request): RedirectResponse
    {
        abort_if($this->installed(), 404);

        $data = $request->validate([
            'app_url' => ['required', 'url'],
            'db_host' => ['required', 'string', 'max:255'],
            'db_port' => ['required', 'integer', 'between:1,65535'],
            'db_database' => ['required', 'string', 'max:64'],
            'db_username' => ['required', 'string', 'max:64'],
            'db_password' => ['nullable', 'string', 'max:255'],
            'admin_name' => ['required', 'string', 'max:120'],
            'admin_email' => ['required', 'email', 'max:255'],
            'admin_password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        if (in_array(false, $this->checks(), true)) {
            return back()->withErrors(['installer' => 'Mindestens eine Systemvoraussetzung ist nicht erfüllt.'])->withInput();
        }

        $appKey = 'base64:'.base64_encode(random_bytes(32));
        $env = $this->buildEnv($data, $appKey);

        try {
            if (file_put_contents(base_path('.env'), $env, LOCK_EX) === false) {
                throw new \RuntimeException('Die .env-Datei konnte nicht geschrieben werden.');
            }

            config([
                'app.key' => $appKey,
                'app.url' => $data['app_url'],
                'database.default' => 'mysql',
                'database.connections.mysql.host' => $data['db_host'],
                'database.connections.mysql.port' => (string) $data['db_port'],
                'database.connections.mysql.database' => $data['db_database'],
                'database.connections.mysql.username' => $data['db_username'],
                'database.connections.mysql.password' => $data['db_password'] ?? '',
            ]);

            DB::purge('mysql');
            DB::connection('mysql')->getPdo();
            Artisan::call('migrate', ['--force' => true]);

            $admin = User::query()->create([
                'name' => $data['admin_name'],
                'email' => Str::lower($data['admin_email']),
                'password' => $data['admin_password'],
                'is_superadmin' => true,
                'email_verified_at' => now(),
            ]);

            $lockPath = storage_path('app/installed.lock');
            file_put_contents($lockPath, json_encode([
                'installed_at' => now()->toIso8601String(),
                'version' => '0.1.0-phase1',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);

            Auth::login($admin);
            $request->session()->regenerate();
            $request->session()->put('2fa_passed', true);

            return redirect()->route('taxi-control.superadmin.dashboard')->with('status', 'Taxi-Control wurde erfolgreich installiert. Bitte aktivieren Sie jetzt 2FA für den Superadministrator.');
        } catch (Throwable $e) {
            report($e);
            return back()->withErrors(['installer' => 'Installation fehlgeschlagen: '.$e->getMessage()])->withInput();
        }
    }

    private function installed(): bool
    {
        return file_exists(storage_path('app/installed.lock'));
    }

    private function checks(): array
    {
        return [
            'PHP >= 8.2' => version_compare(PHP_VERSION, '8.2.0', '>='),
            'PDO MySQL' => extension_loaded('pdo_mysql'),
            'OpenSSL' => extension_loaded('openssl'),
            'Mbstring' => extension_loaded('mbstring'),
            'Tokenizer' => extension_loaded('tokenizer'),
            'XML' => extension_loaded('xml'),
            'Ctype' => extension_loaded('ctype'),
            'Fileinfo' => extension_loaded('fileinfo'),
            'Storage beschreibbar' => is_writable(storage_path()),
            'Projektverzeichnis beschreibbar' => is_writable(base_path()),
        ];
    }

    private function buildEnv(array $data, string $appKey): string
    {
        $q = fn ($value) => '"'.str_replace(['\\', '"', "\n", "\r"], ['\\\\', '\\"', '', ''], (string) $value).'"';

        return implode("\n", [
            'APP_NAME="Taxi-Control"',
            'APP_ENV=production',
            'APP_KEY='.$appKey,
            'APP_DEBUG=false',
            'APP_URL='.$q($data['app_url']),
            'APP_TIMEZONE=Europe/Berlin',
            'APP_LOCALE=de',
            'APP_FALLBACK_LOCALE=de',
            '',
            'LOG_CHANNEL=stack',
            'LOG_LEVEL=warning',
            '',
            'DB_CONNECTION=mysql',
            'DB_HOST='.$q($data['db_host']),
            'DB_PORT='.$data['db_port'],
            'DB_DATABASE='.$q($data['db_database']),
            'DB_USERNAME='.$q($data['db_username']),
            'DB_PASSWORD='.$q($data['db_password'] ?? ''),
            '',
            'SESSION_DRIVER=database',
            'SESSION_LIFETIME=120',
            'SESSION_ENCRYPT=true',
            'SESSION_SECURE_COOKIE=true',
            'CACHE_STORE=database',
            'QUEUE_CONNECTION=database',
            '',
            'MAIL_MAILER=log',
            'MAIL_FROM_ADDRESS="noreply@example.de"',
            'MAIL_FROM_NAME="Taxi-Control"',
            '',
        ]);
    }
}
