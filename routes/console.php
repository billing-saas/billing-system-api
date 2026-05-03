<?php

use Illuminate\Support\Facades\Schedule;

// Check overdue bills — every night at midnight
Schedule::command('invoices:check-overdue')
    ->dailyAt('00:00')
    ->withoutOverlapping();

// Send reminders — every morning at 9 AM
Schedule::command('invoices:send-reminders')
    ->dailyAt('09:00')
    ->withoutOverlapping();
