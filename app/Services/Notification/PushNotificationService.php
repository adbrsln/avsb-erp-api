<?php

namespace App\Services\Notification;

use App\Models\PushSubscription;
use App\Models\User;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class PushNotificationService
{
    private ?WebPush $webPush = null;

    private function getWebPush(): WebPush
    {
        if ($this->webPush === null) {
            $auth = [
                'VAPID' => [
                    'subject' => config('services.vapid.subject', 'mailto:admin@azamventures.com'),
                    'publicKey' => config('services.vapid.public_key', ''),
                    'privateKey' => config('services.vapid.private_key', ''),
                ],
            ];
            $this->webPush = new WebPush($auth);
            $this->webPush->setAutomaticPadding(false);
        }

        return $this->webPush;
    }

    public function sendToUser(User $user, string $title, string $body, ?string $url = null): array
    {
        $publicKey = config('services.vapid.public_key');
        $privateKey = config('services.vapid.private_key');

        if (empty($publicKey) || empty($privateKey)) {
            return ['sent' => 0, 'failed' => 0, 'reason' => 'VAPID not configured'];
        }

        $subscriptions = PushSubscription::where('user_id', $user->id)->get();
        if ($subscriptions->isEmpty()) {
            return ['sent' => 0, 'failed' => 0, 'reason' => 'no subscriptions'];
        }

        $webPush = $this->getWebPush();
        $payload = json_encode([
            'title' => $title,
            'body' => mb_substr($body, 0, 200),
            'url' => $url ?? '/',
            'icon' => '/icon-192x192.png',
            'badge' => '/icon-192x192.png',
        ]);

        $sent = 0;
        $failed = 0;

        foreach ($subscriptions as $sub) {
            try {
                $webPush->queueNotification(
                    Subscription::create([
                        'endpoint' => $sub->endpoint,
                        'authToken' => $sub->auth_key,
                        'publicKey' => $sub->p256dh_key,
                    ]),
                    $payload
                );
            } catch (\Throwable $e) {
                $failed++;
            }
        }

        foreach ($webPush->flush() as $report) {
            if ($report->isSuccess()) {
                $sent++;
            } else {
                $failed++;
                if ($report->isSubscriptionExpired()) {
                    PushSubscription::where('endpoint', $report->getEndpoint())->delete();
                }
            }
        }

        return ['sent' => $sent, 'failed' => $failed];
    }

    public function sendToEmail(string $email, string $title, string $body, ?string $url = null): array
    {
        $user = User::where('email', $email)->first();
        if (! $user) {
            return ['sent' => 0, 'failed' => 0, 'reason' => 'user not found'];
        }

        return $this->sendToUser($user, $title, $body, $url);
    }
}
