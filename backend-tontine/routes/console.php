<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Schedule;
use App\Jobs\MarquerSeancesManquantes;

// S'exécute chaque jour à 6h du matin
Schedule::job(new MarquerSeancesManquantes)->dailyAt('06:00');
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
