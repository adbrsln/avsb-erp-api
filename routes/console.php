<?php

use Illuminate\Support\Facades\Schedule;

// ── Process notification queue every minute ──
Schedule::command('cron:process-notifications')->everyMinute();

// ── Mark overdue invoices daily at 2am ──
Schedule::command('cron:mark-overdue')->dailyAt('02:00');

// ── Pre-generate document PDFs daily at 3am ──
Schedule::command('cron:generate-document-pdfs')->dailyAt('03:00');

// ── Notification housekeeping daily at 4am ──
Schedule::command('cron:housekeep-notifications')->dailyAt('04:00');
