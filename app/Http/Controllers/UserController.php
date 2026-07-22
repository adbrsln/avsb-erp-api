<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use PaginatedResponse;

    public function index(Request $request): JsonResponse
    {
        $params = $request->query();
        $query = User::with('roles');

        if (! empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        return $this->paginate($query, $params);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->all();

        if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
            return response()->json(['errors' => ['name, email, and password are required']], 422);
        }

        $existing = User::where('email', $data['email'])->first();
        if ($existing) {
            return response()->json(['errors' => ['Email already in use']], 422);
        }

        $roles = $data['roles'] ?? ['staff'];
        if (is_string($roles)) {
            $roles = [$roles];
        }

        $allowedRoles = ['staff', 'pm', 'hr', 'finance', 'admin', 'super_admin'];
        foreach ($roles as $r) {
            if (! in_array($r, $allowedRoles)) {
                return response()->json(['errors' => ["Invalid role: {$r}"]], 422);
            }
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_BCRYPT),
        ]);

        $user->syncRoles($roles);

        $user->load('roles');

        return response()->json($user, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = User::with('roles')->findOrFail($id);

        return response()->json($user);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $data = $request->all();

        $updates = [];
        if (isset($data['name'])) {
            $updates['name'] = $data['name'];
        }
        if (isset($data['email'])) {
            $existing = User::where('email', $data['email'])->where('id', '!=', $user->id)->first();
            if ($existing) {
                return response()->json(['errors' => ['Email already in use']], 422);
            }
            $updates['email'] = $data['email'];
        }

        if (! empty($updates)) {
            $user->update($updates);
        }

        $user->load('roles');

        return response()->json($user);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->roles()->delete();
        $user->delete();

        return response()->json(['message' => 'User deleted']);
    }

    public function updateRoles(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $data = $request->all();

        $roles = $data['roles'] ?? ['staff'];
        if (is_string($roles)) {
            $roles = [$roles];
        }

        $allowedRoles = ['staff', 'pm', 'hr', 'finance', 'admin', 'super_admin'];
        foreach ($roles as $r) {
            if (! in_array($r, $allowedRoles)) {
                return response()->json(['errors' => ["Invalid role: {$r}"]], 422);
            }
        }

        $user->syncRoles($roles);
        $user->load('roles');

        return response()->json($user);
    }

    public function resetPassword(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $password = bin2hex(random_bytes(4));
        $user->update(['password' => password_hash($password, PASSWORD_BCRYPT)]);

        return response()->json([
            'message' => 'Password reset successful',
            'generated_password' => $password,
        ]);
    }
}
