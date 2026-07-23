<?php

namespace App\Services\Notification;

use App\Services\FileStorageService;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class MailService
{
    private PHPMailer $mailer;

    private array $config;

    private ?string $lastError = null;

    public function __construct(?array $config = null)
    {
        $this->config = $config ?? [
            'host'       => config('mail.mailers.smtp.host', 'localhost'),
            'port'       => (int) config('mail.mailers.smtp.port', 587),
            'username'   => config('mail.mailers.smtp.username', ''),
            'password'   => config('mail.mailers.smtp.password', ''),
            'encryption' => config('mail.mailers.smtp.encryption', ''),
            'from_email' => config('mail.from.address', 'noreply@avsb.com'),
            'from_name'  => config('mail.from.name', 'AVSB ERP'),
        ];
        $this->mailer = new PHPMailer(true);
        $this->configure();
    }

    private function configure(): void
    {
        $this->mailer->isSMTP();
        $this->mailer->Host = $this->config['host'];
        $this->mailer->Port = $this->config['port'];
        $hasAuth = ! empty($this->config['username']) || ! empty($this->config['password']);
        $this->mailer->SMTPAuth = $hasAuth;
        $this->mailer->Username = $this->config['username'];
        $this->mailer->Password = $this->config['password'];

        $encryption = $this->config['encryption'] ?? '';
        if ($encryption === 'tls') {
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($encryption === 'ssl') {
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        }

        $this->mailer->SMTPKeepAlive = true;
        $this->mailer->CharSet = PHPMailer::CHARSET_UTF8;

        $this->mailer->setFrom(
            $this->config['from_email'],
            $this->config['from_name']
        );
    }

    public function send(string $toEmail, string $toName, string $subject, string $htmlBody, array $attachments = []): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();

            $this->mailer->addAddress($toEmail, $toName);
            $this->mailer->Subject = $subject;
            $this->mailer->isHTML(true);
            $this->mailer->Body = $this->wrapBody($htmlBody);
            $plainText = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</div>'], ["\n", "\n", "\n", "\n\n", "\n\n"], $htmlBody));
            $plainText = preg_replace('/\n{3,}/', "\n\n", $plainText);
            $this->mailer->AltBody = trim($plainText);

            foreach ($attachments as $att) {
                $attPath = $att['path'] ?? '';
                $attFilename = $att['filename'] ?? basename($attPath);
                $attMime = $att['mime'] ?? 'application/octet-stream';

                if (! empty($att['content'])) {
                    $this->mailer->addStringAttachment(
                        base64_decode($att['content']),
                        $attFilename,
                        'base64',
                        $attMime
                    );
                } elseif (! empty($attPath)) {
                    $storage = new FileStorageService;
                    try {
                        $fileContent = $storage->get($attPath);
                        $this->mailer->addStringAttachment($fileContent, $attFilename, 'base64', $attMime);
                    } catch (\Throwable $e) {
                        writeErrorLog('Mail attachment read failed', ['path' => $attPath, 'error' => $e->getMessage()]);
                    }
                }
            }

            $this->mailer->send();
            $this->lastError = null;

            return true;
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            writeErrorLog('Mail send failed', ['error' => $e->getMessage(), 'to' => $toEmail, 'subject' => $subject]);

            return false;
        }
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    private function wrapBody(string $body): string
    {
        // Clean up any unreplaced {{placeholders}}
        $body = preg_replace('/\{\{[^}]+\}\}/', '', $body);

        $appUrl = $_ENV['APP_URL'] ?? 'https://erp.azamventures.com';
        $year = date('Y');

        return <<<HTML
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>AZAM VENTURES ERP</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:14px;line-height:1.6;color:#18181b;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f5;">
    <tr>
      <td align="center" style="padding:24px 16px;">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

          <!-- Header -->
          <tr>
            <td style="background-color:#ca2316;padding:20px 32px;border-radius:8px 8px 0 0;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td>
                    <span style="color:#ffffff;font-size:20px;font-weight:700;letter-spacing:-0.5px;">AVSB</span>
                    <span style="color:rgba(255,255,255,0.6);font-size:16px;font-weight:400;margin-left:4px;">ERP</span>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Content -->
          <tr>
            <td style="background-color:#ffffff;padding:32px 32px;border-left:1px solid #e4e4e7;border-right:1px solid #e4e4e7;">
              {$body}
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background-color:#fafafa;padding:20px 32px;border:1px solid #e4e4e7;border-top:none;border-radius:0 0 8px 8px;">
              <p style="margin:0 0 4px;font-size:12px;color:#71717a;line-height:1.5;">&copy; {$year} Azam Ventures Sdn Bhd. All rights reserved.</p>
              <p style="margin:0;font-size:12px;color:#71717a;line-height:1.5;">This is an automated notification from <a href="{$appUrl}" style="color:#ca2316;text-decoration:none;">Azam Ventures ERP</a>. Please do not reply directly to this email.</p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
    }

    public function testConnection(?string $testEmail = null): array
    {
        $result = [
            'success' => false,
            'connection' => false,
            'auth' => false,
            'send_success' => null,
            'latency' => 0.0,
            'server_info' => '',
            'debug' => '',
            'error' => null,
            'error_detail' => null,
        ];

        $debugOutput = '';
        $origDebug = $this->mailer->SMTPDebug;
        $origDebugOutput = $this->mailer->Debugoutput;

        try {
            $this->mailer->SMTPDebug = SMTP::DEBUG_CONNECTION;
            $this->mailer->Debugoutput = function ($str, $level) use (&$debugOutput) {
                $debugOutput .= $str;
            };

            $start = microtime(true);
            $connected = $this->mailer->smtpConnect();
            $result['latency'] = round(microtime(true) - $start, 4);

            if ($connected) {
                $result['connection'] = true;
                $result['auth'] = true;

                if (preg_match('/SERVER\s*->\s*CLIENT:\s*220\s+(.+)\r?\n/', $debugOutput, $m)) {
                    $result['server_info'] = trim($m[1]);
                }

                if ($testEmail) {
                    try {
                        $this->mailer->clearAddresses();
                        $this->mailer->clearAttachments();
                        $this->mailer->addAddress($testEmail);
                        $this->mailer->Subject = 'AVSB ERP — SMTP Test';
                        $this->mailer->isHTML(true);
                        $testBody = '<p style="margin:0 0 16px;font-size:15px;">Your email configuration is working correctly.</p><p style="margin:0;font-size:13px;color:#71717a;">This is a test email sent from the AVSB ERP system diagnostics page.</p>';
                        $this->mailer->Body = $this->wrapBody($testBody);
                        $this->mailer->AltBody = strip_tags($testBody);
                        $this->mailer->send();
                        $result['send_success'] = true;
                    } catch (\Throwable $e) {
                        $result['send_success'] = false;
                        $result['error'] = 'Connected and authenticated, but send failed';
                        $result['error_detail'] = $e->getMessage().(! empty($this->mailer->ErrorInfo) ? ' | '.$this->mailer->ErrorInfo : '');
                    }
                }

                $this->mailer->smtpClose();
            } else {
                $result['error'] = 'Connection failed';
                $result['error_detail'] = $this->mailer->ErrorInfo ?? '';
            }
        } catch (\Throwable $e) {
            $result['error'] = $e->getMessage();
            $result['error_detail'] = $this->mailer->ErrorInfo ?? '';
        }

        $result['success'] = $result['connection'] && $result['auth'] && ($testEmail === null || $result['send_success'] === true);
        $result['debug'] = $debugOutput;

        try {
            $this->mailer->SMTPDebug = $origDebug;
            $this->mailer->Debugoutput = $origDebugOutput;
        } catch (\Throwable) {
        }

        return $result;
    }

    public function __destruct()
    {
        try {
            $this->mailer->SMTPClose();
        } catch (\Throwable) {
        }
    }
}
