<?php

namespace App\Traits;

use App\Services\Notification\NotificationRecipientResolver;
use App\Services\Notification\NotificationService;

trait NotifiesProjectParticipants
{
    protected function notifyProject(string $eventType, array $context, int $projectId, ?string $modelType = null, ?int $modelId = null, ?string $subject = null, ?string $body = null, ?string $url = null): void
    {
        try {
            $recipients = NotificationRecipientResolver::getProjectParticipants($projectId);
            if (!empty($recipients)) {
                NotificationService::queueToMany(
                    $eventType,
                    $recipients,
                    $context,
                    $modelType,
                    $modelId,
                    $subject,
                    $body,
                    $url
                );
            }
        } catch (\Throwable $e) {
            writeErrorLog('Notification failed: ' . $eventType, ['project_id' => $projectId, 'error' => $e->getMessage()]);
        }
    }
}
