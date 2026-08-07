<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(fn () => app(\App\Actions\CloseExpiredStays::class)->handle())
    ->name('close-expired-guest-stays')
    ->everyMinute()
    ->withoutOverlapping();
