<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('taxi-control:status', function () {
    $this->info('Taxi-Control ist erreichbar.');
})->purpose('Prüft die Taxi-Control-Anwendung.');
