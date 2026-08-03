<?php

use App\Models\User;
use App\Models\UserNotification;

use function Pest\Laravel\post;

beforeEach(function () {
    $this->user = User::where('email', 'superadmin@azamventures.com')->first();
    $this->token = $this->user->createToken('test')->plainTextToken;
    $this->headers = ['Authorization' => 'Bearer '.$this->token];
});

describe('Notification mark-all-read', function () {

    it('marks all unread notifications as read via POST read-all', function () {
        UserNotification::create(['user_id' => $this->user->id, 'title' => 'A', 'is_read' => false]);
        UserNotification::create(['user_id' => $this->user->id, 'title' => 'B', 'is_read' => false]);
        UserNotification::create(['user_id' => $this->user->id, 'title' => 'C', 'is_read' => true]);

        post('/api/v1/notifications/read-all', [], $this->headers)
            ->assertStatus(204);

        expect(UserNotification::where('user_id', $this->user->id)->where('is_read', false)->count())->toBe(0)
            ->and(UserNotification::where('user_id', $this->user->id)->count())->toBe(3);
    });

    it('keeps GET read as a working alias', function () {
        UserNotification::create(['user_id' => $this->user->id, 'title' => 'A', 'is_read' => false]);

        $this->get('/api/v1/notifications/read', $this->headers)
            ->assertStatus(204);

        expect(UserNotification::where('user_id', $this->user->id)->where('is_read', false)->count())->toBe(0);
    });

});
