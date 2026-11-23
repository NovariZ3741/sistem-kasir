<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Perbarui aturan asosiasi (Apriori) setiap malam agar rekomendasi kasir selalu mengikuti data terbaru
Schedule::command('apriori:run')->dailyAt('01:00');
