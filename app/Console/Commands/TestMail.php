<?php

namespace App\Console\Commands;

use App\Services\Notification\MailService;
use Illuminate\Console\Command;

class TestMail extends Command
{
    protected $signature = 'cron:test-mail {email? : Optional test recipient}';

    protected $description = 'Test SMTP connection, auth, and optionally send a test email';

    public function handle(MailService $service): int
    {
        $testEmail = $this->argument('email');

        $config = config('mail');

        $this->newLine();
        $this->line('  '."\033[1;36m═══ AVSB ERP — SMTP Test \033[0m".str_repeat('═', 26));
        $this->newLine();

        $this->line('  Configuration:');
        $this->line("    Host:        {$config['host']}");
        $this->line("    Port:        {$config['port']}");
        $this->line("    Encryption:  {$config['encryption']}");
        $this->line("    Username:    {$config['username']}");
        $this->line('    Password:    '.($config['password'] ? '******' : '(none)'));
        $this->line("    From:        {$config['from']['address']} ({$config['from']['name']})");
        $this->newLine();

        try {
            $result = $service->testConnection($testEmail);
        } catch (\Throwable $e) {
            $this->error("Script error: {$e->getMessage()}");

            return 1;
        }

        // Connection phase
        $this->line('  ─── Connection '.str_repeat('─', 35));
        if ($result['connection']) {
            $this->line("  \033[32m✓\033[0m Connected in {$result['latency']}s");
            if ($result['server_info']) {
                $this->line('    Server: '.mb_substr(trim($result['server_info']), 0, 60));
            }
        } else {
            $this->line("  \033[31m✗\033[0m Failed after {$result['latency']}s");
        }
        $this->newLine();

        // Auth phase
        $this->line('  ─── Authentication '.str_repeat('─', 30));
        if ($result['auth']) {
            $this->line("  \033[32m✓\033[0m Authenticated as ".($config['username'] ?: '(no credentials needed)'));
        } else {
            $this->line("  \033[31m✗\033[0m Authentication failed");
            if ($result['error']) {
                $this->line("    Error: {$result['error']}");
            }
            if ($result['error_detail']) {
                $this->line("    Detail: {$result['error_detail']}");
            }
        }
        $this->newLine();

        // Send phase
        if ($testEmail) {
            $this->line('  ─── Test Send '.str_repeat('─', 35));
            $this->line("    To: {$testEmail}");
            if ($result['send_success']) {
                $this->line("  \033[32m✓\033[0m Sent successfully");
            } elseif ($result['send_success'] === false) {
                $this->line("  \033[31m✗\033[0m Send failed");
                if ($result['error']) {
                    $this->line("    Error: {$result['error']}");
                }
                if ($result['error_detail']) {
                    $this->line("    Detail: {$result['error_detail']}");
                }
            } else {
                $this->line("  \033[33m✗\033[0m Skipped (no connection/auth)");
            }
            $this->newLine();
        }

        // SMTP transcript
        if (! empty($result['debug'])) {
            $this->line('  ─── SMTP Transcript '.str_repeat('─', 30));
            $lines = explode("\n", trim($result['debug']));
            foreach ($lines as $line) {
                $clean = trim($line);
                if ($clean !== '') {
                    $this->line("  {$clean}");
                }
            }
            $this->newLine();
        }

        // Summary
        $allOk = $result['connection'] && $result['auth'] && ($testEmail === null || $result['send_success'] === true);

        if ($allOk) {
            $this->line('  '.str_repeat('═', 46));
            $this->line('  '."\033[1;32m  PASS\033[0m");
        } else {
            $this->line('  '.str_repeat('═', 46));
            if (! $result['connection']) {
                $this->line('  '."\033[1;31m  FAIL\033[0m — Connection failed");
            } elseif (! $result['auth']) {
                $this->line('  '."\033[1;31m  FAIL\033[0m — Authentication failed");
            } elseif ($result['send_success'] === false) {
                $this->line('  '."\033[1;33m  WARN\033[0m — Connected but send failed");
            }
        }
        $this->newLine();

        $exitCode = 0;
        if (! $result['connection'] || ! $result['auth']) {
            $exitCode = 1;
        } elseif ($result['send_success'] === false) {
            $exitCode = 2;
        }

        return $exitCode;
    }
}
