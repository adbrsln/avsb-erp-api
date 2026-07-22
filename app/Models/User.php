<?php

namespace App\Models;

use App\Services\ActivityLogger;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use Auditable, HasApiTokens, HasFactory;

    protected $table = 'users';

    protected $fillable = [
        'name', 'email', 'password',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'login_attempts' => 'integer',
        'locked_until' => 'datetime',
    ];

    public function roles()
    {
        return $this->hasMany(UserRole::class, 'user_id');
    }

    public function getRoleNames(): array
    {
        $rows = $this->roles()->pluck('role')->toArray();

        return ! empty($rows) ? $rows : ['staff'];
    }

    public function hasRole(string $role): bool
    {
        return $this->roles()->where('role', $role)->exists();
    }

    public function syncRoles(array $roles): void
    {
        if (empty($roles)) {
            $roles = ['staff'];
        }

        $oldRoles = $this->getRoleNames();

        $this->roles()->delete();
        foreach ($roles as $role) {
            UserRole::create(['user_id' => $this->id, 'role' => $role]);
        }

        $newRoles = $this->getRoleNames();
        if ($oldRoles !== $newRoles) {
            ActivityLogger::on($this)
                ->withProperties([
                    'old' => $oldRoles,
                    'new' => $newRoles,
                    'email' => $this->email,
                ])
                ->inLog('default')
                ->log('Roles updated', 'updated');
        }
    }
}
