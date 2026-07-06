<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Alerte J-3 : rappels d'échéance des factures à crédit (workflow sécurisé, étape 4).
Schedule::command('send:invoice-due-reminders')->dailyAt('08:00');
