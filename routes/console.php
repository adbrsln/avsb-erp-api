<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// ── Process notification queue every minute ──
Schedule::command('cron:process-notifications')->everyMinute();

// ── Mark overdue invoices daily at 2am ──
Schedule::command('cron:mark-overdue')->dailyAt('02:00');

// ── Pre-generate document PDFs daily at 3am ──
Schedule::command('cron:generate-document-pdfs')->dailyAt('03:00');

// ── Notification housekeeping daily at 4am ──
Schedule::command('cron:housekeep-notifications')->dailyAt('04:00');

// ── Auto clock-out open attendance sessions (self-gated on company setting; close time is deterministic end+grace) ──
Schedule::command('attendance:auto-clock-out')->everyThirtyMinutes();

// ── Fetch next year's Malaysian public holidays on 31 Dec (14:00 UTC = 22:00 MY) ──
Schedule::call(fn () => Artisan::call('holidays:fetch', ['--year' => now()->addYear()->year]))
    ->yearlyOn(12, 31, '14:00');
