<?php

namespace App\Services\Notification;

use App\Models\NotificationLog;
use App\Models\NotificationPreference;
use App\Models\NotificationQueue;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Models\UserNotification;

class NotificationService
{
    private MailService $mailer;

    public function __construct(?MailService $mailer = null)
    {
        $this->mailer = $mailer ?? new MailService;
    }

    public static function queue(
        string $eventType,
        string $recipientEmail,
        string $recipientName,
        array $context = [],
        ?string $modelType = null,
        ?int $modelId = null,
        ?string $subject = null,
        ?string $body = null,
        ?string $url = null,
        ?array $attachments = null
    ): ?NotificationQueue {
        if (empty($recipientEmail)) {
            return null;
        }
        if (! $subject || ! $body) {
            $template = NotificationTemplate::where('event_type', $eventType)->first();
            if (! $template) {
                return null;
            }

            $rendered = self::render($template, $context);
            if (! $subject) {
                $subject = $rendered['subject'];
            }
            if (! $body) {
                $body = $rendered['body'];
            }
        }

        // Check notification preferences
        $user = User::where('email', $recipientEmail)->first();
        $prefs = null;
        if ($user) {
            $prefs = NotificationPreference::where('user_id', $user->id)
                ->where('event_type', $eventType)
                ->first();
        }

        // Create in-app notification (check preference)
        if ($user && (! $prefs || $prefs->in_app)) {
            $cleanBody = preg_replace('/<p>\s*<a\s+[^>]*href="[^"]*"[^>]*>.*?<\/a>\s*<\/p>/i', '', $body);
            $cleanBody = preg_replace('/<a\s+[^>]*href="[^"]*"[^>]*>.*?<\/a>/i', '', $cleanBody);
            $cleanBody = trim(strip_tags($cleanBody));
            $inAppTitle = preg_replace('/\s*[—–-]\s+.*$/u', '', $subject);
            UserNotification::create([
                'user_id' => $user->id,
                'title' => $inAppTitle ?: $subject,
                'body' => $cleanBody,
                'url' => $url ?? ($context['url'] ?? null),
                'event_type' => $eventType,
            ]);
        }

        // Send push immediately (only if user has active subscription)
        if ($user && (! $prefs || $prefs->push)) {
            try {
                $pushBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</div>'], "\n", $body));
                (new PushNotificationService)->sendToEmail(
                    $recipientEmail,
                    $subject,
                    $pushBody,
                    $url ?? ($context['url'] ?? null)
                );
            } catch (\Throwable $e) {
                writeErrorLog('Push notification failed in queue()', ['email' => $recipientEmail, 'error' => $e->getMessage()]);
            }
        }

        // Queue email (check global env toggle + user preference)
        $mailEnabled = ($_ENV['MAIL_ENABLED'] ?? 'true') !== 'false';
        if ($mailEnabled && (! $prefs || $prefs->email)) {
            $queueData = [
                'recipient_name' => $recipientName,
                'subject' => $subject,
                'body' => $body,
                'context' => $context,
                'status' => 'pending',
            ];
            if ($attachments !== null) {
                $queueData['attachments'] = $attachments;
            }

            return NotificationQueue::firstOrCreate(
                [
                    'event_type' => $eventType,
                    'recipient_email' => $recipientEmail,
                    'model_type' => $modelType,
                    'model_id' => $modelId,
                ],
                $queueData
            );
        }

        return null;
    }

    public static function queueToMany(
        string $eventType,
        array $recipients,
        array $context = [],
        ?string $modelType = null,
        ?int $modelId = null,
        ?string $subject = null,
        ?string $body = null,
        ?string $url = null,
        ?array $attachments = null
    ): void {
        foreach ($recipients as $r) {
            $email = $r['email'] ?? '';
            $name = $r['name'] ?? '';
            if (empty($email)) {
                continue;
            }
            self::queue($eventType, $email, $name, $context, $modelType, $modelId, $subject, $body, $url, $attachments);
        }
    }

    private static function render(NotificationTemplate $template, array $context): array
    {
        $replace = [];
        foreach ($context as $key => $value) {
            $replace['{{'.$key.'}}'] = htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }

        $subject = str_replace(array_keys($replace), array_values($replace), $template->subject_template);
        $body = str_replace(array_keys($replace), array_values($replace), $template->body_template);

        return ['subject' => $subject, 'body' => $body];
    }

    public function sendFromQueue(int $limit = 50): array
    {
        $this->reap();

        $items = NotificationQueue::where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', date('Y-m-d H:i:s'));
            })
            ->limit($limit)
            ->lockForUpdate()
            ->get();

        $sent = 0;
        $failed = 0;

        foreach ($items as $item) {
            $updated = NotificationQueue::where('id', $item->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'processing',
                    'processing_since' => date('Y-m-d H:i:s'),
                    'attempts' => $item->attempts + 1,
                ]);

            if ($updated === 0) {
                continue;
            }

            $item->refresh();

            try {
                $attachments = $item->attachments ? (is_string($item->attachments) ? json_decode($item->attachments, true) : $item->attachments) : [];
                $ok = $this->mailer->send(
                    $item->recipient_email,
                    $item->recipient_name ?? '',
                    $item->subject,
                    $item->body,
                    $attachments
                );

                if ($ok) {
                    $item->update([
                        'status' => 'sent',
                        'sent_at' => date('Y-m-d H:i:s'),
                        'processing_since' => null,
                        'error' => null,
                    ]);

                    NotificationLog::create([
                        'queue_id' => $item->id,
                        'event_type' => $item->event_type,
                        'recipient_email' => $item->recipient_email,
                        'recipient_name' => $item->recipient_name,
                        'subject' => $item->subject,
                        'body' => $item->body,
                        'status' => 'sent',
                        'sent_at' => date('Y-m-d H:i:s'),
                    ]);

                    $sent++;
                } else {
                    throw new \RuntimeException($this->mailer->getLastError() ?? 'Unknown SMTP error');
                }
            } catch (\Throwable $e) {
                $backoffMinutes = min(pow($item->attempts, 2), 60);
                $scheduledAt = date('Y-m-d H:i:s', time() + $backoffMinutes * 60);

                $item->update([
                    'status' => $item->attempts >= $item->max_attempts ? 'failed' : 'pending',
                    'error' => $e->getMessage(),
                    'processing_since' => null,
                    'scheduled_at' => $item->attempts >= $item->max_attempts ? null : $scheduledAt,
                ]);
                $failed++;
            }
        }

        return ['sent' => $sent, 'failed' => $failed];
    }

    private function reap(): void
    {
        $threshold = date('Y-m-d H:i:s', time() - 300);

        NotificationQueue::where('status', 'processing')
            ->where('updated_at', '<', $threshold)
            ->whereColumn('attempts', '<', 'max_attempts')
            ->update([
                'status' => 'pending',
                'processing_since' => null,
            ]);

        NotificationQueue::where('status', 'processing')
            ->where('updated_at', '<', $threshold)
            ->whereColumn('attempts', '>=', 'max_attempts')
            ->update([
                'status' => 'failed',
                'error' => 'Max attempts exceeded / stuck in processing',
            ]);
    }
}
