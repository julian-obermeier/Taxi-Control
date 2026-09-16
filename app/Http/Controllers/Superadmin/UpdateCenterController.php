<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;
use ZipArchive;

class UpdateCenterController extends Controller
{
    public function __construct(private readonly AuditService $audit)
    {
    }

    public function index(): View
    {
        $version = SystemSetting::query()->where('key', 'app.version')->value('value') ?: '0.1.0-dev';
        return view('superadmin.update.index', compact('version'));
    }

    public function apply(Request $request): RedirectResponse
    {
        $request->validate([
            'package' => ['required', 'file', 'mimes:zip', 'max:204800'],
            'backup_confirmed' => ['accepted'],
        ]);
        abort_unless(class_exists(ZipArchive::class), 500, 'PHP-Erweiterung zip ist für das Update-Center erforderlich.');

        $workDir = storage_path('app/private/updates/'.Str::uuid());
        File::ensureDirectoryExists($workDir);
        $zipPath = $request->file('package')->getRealPath();
        $zip = new ZipArchive();
        abort_unless($zip->open($zipPath) === true, 422, 'Updatepaket konnte nicht geöffnet werden.');

        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = str_replace('\\', '/', (string) $zip->getNameIndex($i));
                abort_if(Str::startsWith($name, '/') || str_contains($name, '../'), 422, 'Unsicherer Pfad im Updatepaket.');
            }
            abort_unless($zip->extractTo($workDir), 422, 'Updatepaket konnte nicht entpackt werden.');
        } finally {
            $zip->close();
        }

        $manifestPath = $workDir.'/manifest.json';
        abort_unless(File::isFile($manifestPath), 422, 'manifest.json fehlt im Updatepaket.');
        $manifest = json_decode(File::get($manifestPath), true);
        abort_unless(is_array($manifest) && filled($manifest['version'] ?? null) && is_array($manifest['files'] ?? null), 422, 'Update-Manifest ist ungültig.');

        $validatedFiles = [];
        foreach ($manifest['files'] as $entry) {
            abort_unless(is_array($entry) && filled($entry['source'] ?? null) && filled($entry['target'] ?? null) && filled($entry['sha256'] ?? null), 422, 'Dateieintrag im Manifest ist ungültig.');
            $source = $workDir.'/'.ltrim(str_replace('\\', '/', $entry['source']), '/');
            $target = str_replace('\\', '/', ltrim($entry['target'], '/'));
            abort_if(str_contains($target, '../') || in_array($target, ['.env', '.htaccess'], true), 422, 'Nicht erlaubtes Updateziel.');
            abort_unless(Str::startsWith($target, ['app/', 'bootstrap/', 'config/', 'database/', 'public/', 'resources/', 'routes/', 'vendor/', 'composer.lock']), 422, 'Updateziel liegt außerhalb erlaubter Verzeichnisse.');
            abort_unless(File::isFile($source), 422, 'Quelldatei aus Manifest fehlt: '.$entry['source']);
            abort_unless(hash_equals(strtolower($entry['sha256']), hash_file('sha256', $source)), 422, 'Prüfsumme stimmt nicht: '.$entry['source']);
            $validatedFiles[] = [$source, base_path($target)];
        }

        foreach ($validatedFiles as [$source, $target]) {
            File::ensureDirectoryExists(dirname($target));
            $tmp = $target.'.taxi-control-update';
            File::copy($source, $tmp);
            rename($tmp, $target);
        }

        Artisan::call('migrate', ['--force' => true]);
        Artisan::call('optimize:clear');
        SystemSetting::query()->updateOrCreate(['key' => 'app.version'], ['value' => $manifest['version'], 'is_secret' => false]);
        $this->audit->log('superadmin.update.applied', null, [], ['version' => $manifest['version'], 'files' => count($validatedFiles)]);
        File::deleteDirectory($workDir);

        return back()->with('status', 'Update auf Version '.$manifest['version'].' wurde angewendet. Migrationen und Cache-Bereinigung wurden ausgeführt.');
    }
}
