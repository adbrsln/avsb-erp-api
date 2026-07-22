<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    private function validatePassword(string $password): ?string
    {
        if (strlen($password) < 8) {
            return 'Password must be at least 8 characters';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            return 'Password must contain an uppercase letter';
        }
        if (!preg_match('/[a-z]/', $password)) {
            return 'Password must contain a lowercase letter';
        }
        if (!preg_match('/[0-9]/', $password)) {
            return 'Password must contain a digit';
        }
        return null;
    }

    private function verifyTurnstile(string $token): bool
    {
        $secret = config('services.turnstile.secret_key', '');
        if (empty($secret)) {
            return true;
        }
        $response = @file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query([
                    'secret' => $secret,
                    'response' => $token,
                ]),
            ],
        ]));
        if (!$response) return false;
        $result = json_decode($response, true);
        return ($result['success'] ?? false) === true;
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'turnstile_token' => 'nullable|string',
        ]);

        $user = User::where('email', $data['email'])->first();

        // Turnstile check
        if (!$this->verifyTurnstile($data['turnstile_token'] ?? '')) {
            return response()->json(['error' => 'Security check failed'], 422);
        }

        // Account lockout check
        if ($user && $user->locked_until) {
            $lockedUntil = $user->locked_until instanceof \Carbon\Carbon
                ? $user->locked_until
                : \Carbon\Carbon::parse($user->locked_until);
            if ($lockedUntil->isFuture()) {
                return response()->json(['error' => 'Account temporarily locked. Try again later.'], 423);
            }
        }

        $passwordValid = $user && password_verify($data['password'], $user->password);

        if (!$passwordValid) {
            $attempts = $user ? ($user->login_attempts ?? 0) : 0;
            $delay = (int) min(500000 * pow(2, min($attempts, 3)), 4000000);
            usleep($delay);

            if ($user) {
                $user->increment('login_attempts');
                if ($user->login_attempts >= 5) {
                    $user->update(['locked_until' => \Carbon\Carbon::now()->addMinutes(15)]);
                }
            }

            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        // Success — reset attempts
        $user->update(['login_attempts' => 0, 'locked_until' => null]);

        $roles = $user->getRoleNames();
        $token = $user->createToken('api')->plainTextToken;

        $userArr = $user->toArray();
        $userArr['roles'] = $roles;

        return response()->json([
            'token' => $token,
            'user' => $userArr,
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string',
        ]);

        $passwordError = $this->validatePassword($data['password']);
        if ($passwordError) {
            return response()->json(['error' => $passwordError], 422);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_BCRYPT),
        ]);
        $user->syncRoles(['staff']);

        $userArr = $user->toArray();
        $userArr['roles'] = $user->getRoleNames();

        return response()->json($userArr, 201);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $userArr = $user->toArray();
        $userArr['roles'] = $user->getRoleNames();
        return response()->json($userArr);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string',
            'new_password_confirm' => 'required|string',
        ]);

        $user = $request->user();

        if (!password_verify($data['current_password'], $user->password)) {
            return response()->json(['error' => 'Current password is incorrect'], 422);
        }

        $passwordError = $this->validatePassword($data['new_password']);
        if ($passwordError) {
            return response()->json(['error' => $passwordError], 422);
        }

        if ($data['new_password'] !== $data['new_password_confirm']) {
            return response()->json(['error' => 'New passwords do not match'], 422);
        }

        $user->update(['password' => password_hash($data['new_password'], PASSWORD_BCRYPT)]);

        return response()->json(['message' => 'Password changed successfully']);
    }

    public function verifyPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'password' => 'required|string',
        ]);

        $user = $request->user();

        if (!password_verify($data['password'], $user->password)) {
            return response()->json(['verified' => false, 'error' => 'Invalid password'], 422);
        }

        return response()->json(['verified' => true]);
    }
}
