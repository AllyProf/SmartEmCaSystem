<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule daily follow-up SMS reminders at 7:00 AM (Africa/Dar_es_Salaam)
Schedule::command('followups:send-reminders')
    ->dailyAt('07:00')
    ->timezone('Africa/Dar_es_Salaam')
    ->name('send-followup-reminders')
    ->withoutOverlapping();

// Schedule sending scheduled SMS every minute
Schedule::command('sms:send-scheduled')
    ->everyMinute()
    ->timezone('Africa/Dar_es_Salaam')
    ->name('send-scheduled-sms')
    ->withoutOverlapping();

Schedule::command('attendance:auto-sign-out')
    ->dailyAt('00:05')
    ->timezone('Africa/Dar_es_Salaam')
    ->name('attendance-auto-sign-out')
    ->withoutOverlapping();

Schedule::command('attendance:send-reminders')
    ->dailyAt('08:30')
    ->timezone('Africa/Dar_es_Salaam')
    ->name('attendance-send-reminders')
    ->withoutOverlapping();
