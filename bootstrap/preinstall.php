<?php

/**
 * Allows Taxi-Control to boot before the web installer has completed.
 *
 * Laravel loads .env during application bootstrap. A merely process-local
 * APP_KEY can therefore be overwritten again by an existing `APP_KEY=` line.
 * For a fresh installation we persist a temporary installation key directly
 * into .env before Laravel/Dotenv starts. The installer later replaces the
 * complete .env with a newly generated final production key.
 */
$projectRoot = dirname(__DIR__);
$envPath = $projectRoot.'/.env';
$examplePath = $projectRoot.'/.env.example';

if (! is_file($envPath) && is_file($examplePath)) {
    if (@copy($examplePath, $envPath) === false) {
        throw new RuntimeException('Die .env-Datei konnte für die Erstinstallation nicht angelegt werden. Prüfen Sie die Schreibrechte des Projektverzeichnisses.');
    }
}

$envContents = is_file($envPath) ? @file_get_contents($envPath) : false;
$configuredKey = null;

if (is_string($envContents) && preg_match('/^\s*APP_KEY\s*=\s*(.*)$/m', $envContents, $matches) === 1) {
    $configuredKey = trim($matches[1]);
    $configuredKey = trim($configuredKey, "\"'");
}

if ($configuredKey === null || $configuredKey === '') {
    $keyFile = $projectRoot.'/storage/app/.preinstall-key';
    $keyDirectory = dirname($keyFile);

    if (! is_dir($keyDirectory) && ! @mkdir($keyDirectory, 0775, true) && ! is_dir($keyDirectory)) {
        throw new RuntimeException('storage/app konnte für die Erstinstallation nicht angelegt werden.');
    }

    $temporaryKey = is_file($keyFile) ? trim((string) @file_get_contents($keyFile)) : '';

    if ($temporaryKey === '') {
        $temporaryKey = 'base64:'.base64_encode(random_bytes(32));

        if (@file_put_contents($keyFile, $temporaryKey, LOCK_EX) === false) {
            throw new RuntimeException('Temporärer Installationsschlüssel konnte nicht gespeichert werden. Prüfen Sie die Schreibrechte von storage/app.');
        }

        @chmod($keyFile, 0600);
    }

    if (! is_string($envContents)) {
        $envContents = "APP_KEY=\nSESSION_DRIVER=file\nCACHE_STORE=file\n";
    }

    if (preg_match('/^\s*APP_KEY\s*=.*$/m', $envContents) === 1) {
        $updatedEnv = preg_replace('/^\s*APP_KEY\s*=.*$/m', 'APP_KEY='.$temporaryKey, $envContents, 1);
    } else {
        $updatedEnv = "APP_KEY={$temporaryKey}\n".$envContents;
    }

    if (! is_string($updatedEnv) || @file_put_contents($envPath, $updatedEnv, LOCK_EX) === false) {
        throw new RuntimeException('Der temporäre Installationsschlüssel konnte nicht in .env geschrieben werden. Prüfen Sie die Schreibrechte des Projektverzeichnisses.');
    }

    $configuredKey = $temporaryKey;
}

if ($configuredKey !== null && $configuredKey !== '') {
    putenv('APP_KEY='.$configuredKey);
    $_ENV['APP_KEY'] = $configuredKey;
    $_SERVER['APP_KEY'] = $configuredKey;
}
