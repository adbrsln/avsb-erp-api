<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\PushSubscription;
use App\Models\User;
use App\Services\Notification\MailService;
use App\Services\Notification\PushNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SystemController extends Controller
{
    public function health(Request $request): JsonResponse
    {
        $dbOk = false;
        $storageWritable = false;
        $errors = [];

        try {
            DB::connection()->getPdo();
            $dbOk = true;
        } catch (\Exception $e) {
            $errors[] = 'Database: '.$e->getMessage();
        }

        $storagePath = __DIR__.'/../../storage/logs';
        $storageWritable = is_writable($storagePath);

        $lastAudit = ActivityLog::orderBy('created_at', 'desc')->first();

        return response()->json([
            'status' => $dbOk && $storageWritable ? 'healthy' : 'degraded',
            'database' => $dbOk,
            'storage_writable' => $storageWritable,
            'php_version' => PHP_VERSION,
            'app_env' => config('app.env', 'production'),
            'app_url' => config('app.url', ''),
            'last_audit_log' => $lastAudit ? $lastAudit->created_at : null,
            'errors' => $errors,
        ]);
    }

    public function diagnostics(Request $request): JsonResponse
    {
        $vapidConfigured = ! empty(config('services.vapid.public_key')) && ! empty(config('services.vapid.private_key'));

        $totalSubscriptions = 0;
        $mySubscriptions = 0;
        try {
            $totalSubscriptions = PushSubscription::count();
            $user = $request->user();
            $email = is_object($user) ? ($user->email ?? '') : ($user['email'] ?? '');
            if ($email) {
                $userModel = User::where('email', $email)->first();
                if ($userModel) {
                    $mySubscriptions = PushSubscription::where('user_id', $userModel->id)->count();
                }
            }
        } catch (\Throwable $e) {
            // table may not exist
        }

        $mailConfig = config('mail');
        $mailConfigured = ! empty($mailConfig['host']);

        return response()->json([
            'push_configured' => $vapidConfigured,
            'push_public_key_short' => $vapidConfigured ? '...'.substr(config('services.vapid.public_key'), -8) : null,
            'push_total_subscriptions' => $totalSubscriptions,
            'push_my_subscriptions' => $mySubscriptions,
            'mail_configured' => $mailConfigured,
            'mail_host' => $mailConfig['host'] ?? null,
            'mail_port' => $mailConfig['port'] ?? null,
            'mail_encryption' => $mailConfig['encryption'] ?? null,
            'mail_from' => ($mailConfig['from']['address'] ?? '').' ('.($mailConfig['from']['name'] ?? '').')',
        ]);
    }

    public function testPush(Request $request): JsonResponse
    {
        $user = $request->user();
        $email = is_object($user) ? ($user->email ?? '') : ($user['email'] ?? '');

        $result = (new PushNotificationService)->sendToEmail(
            $email,
            'Test Notification',
            'Your push notification system is working correctly!',
            '/'
        );

        return response()->json($result);
    }

    public function testMail(Request $request): JsonResponse
    {
        $body = $request->all();
        $testEmail = trim($body['email'] ?? '');

        if (empty($testEmail)) {
            return response()->json(['error' => 'Email is required'], 422);
        }

        $result = (new MailService)->testConnection($testEmail);

        return response()->json($result);
    }
}
