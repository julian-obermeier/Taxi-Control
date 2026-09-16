<?php

/**
 * Allows Taxi-Control to boot before the web installer has created .env.
 *
 * Laravel's cookie/session encryption requires APP_KEY on the very first
 * request. For a fresh shared-hosting installation we create a private,
 * installation-local temporary key under storage/. The installer replaces
 * it with the final random APP_KEY in .env and removes this temporary file.
 */
$projectRoot = dirname(__DIR__);
$envPath = $projectRoot.'/.env';
$configuredKey = null;

if (is_file($envPath)) {
    $envContents = @file_get_contents($envPath);

    if (is_string($envContents) && preg_match('/^\s*APP_KEY\s*=\s*(.*)$/m', $envContents, $matches) === 1) {
        $configuredKey = trim($matches[1]);
        $configuredKey = trim($configuredKey, "\"'");
    }
}

if (($configuredKey === null || $configuredKey === '') && ! getenv('APP_KEY')) {
    $keyFile = $projectRoot.'/storage/app/.preinstall-key';
    $keyDirectory = dirname($keyFile);

    if (! is_dir($keyDirectory)) {
        @mkdir($keyDirectory, 0775, true);
    }

    $temporaryKey = is_file($keyFile) ? trim((string) @file_get_contents($keyFile)) : '';

    if ($temporaryKey === '') {
        $temporaryKey = 'base64:'.base64_encode(random_bytes(32));

        if (@file_put_contents($keyFile, $temporaryKey, LOCK_EX) === false) {
            throw new RuntimeException('Temporärer Installationsschlüssel konnte nicht gespeichert werden. Prüfen Sie die Schreibrechte von storage/app.');
        }

        @chmod($keyFile, 0600);
    }

    putenv('APP_KEY='.$temporaryKey);
    $_ENV['APP_KEY'] = $temporaryKey;
    $_SERVER['APP_KEY'] = $temporaryKey;
}
