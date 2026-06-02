<?php

use App\Console\Commands\SendRenewalReminders;
use App\Console\Commands\TransitionSubscriptions;
use Illuminate\Support\Facades\Schedule;

// Subscription lifecycle automation (§2).
Schedule::command(TransitionSubscriptions::class)->dailyAt('00:15');
Schedule::command(SendRenewalReminders::class)->dailyAt('08:00')->timezone('Asia/Kuwait');

// Purge raw identity-capture images past their retention window (§5/§9).
Schedule::command('identity:purge-raw-captures')->everyTenMinutes();
