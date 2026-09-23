<?php

namespace App\Http\Controllers\Api;

use App\Actions\Fortify\CreateNewUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ForgotPasswordRequest;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\ApiAuthenticationService;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{
    public function register(Request $request, CreateNewUser $creator): JsonResponse
    {
        $request->validate(['device_name' => ['required', 'string', 'max:100']]);

        $user = $creator->create($request->all());
        event(new Registered($user));

        return response()->json([
            'user' => new UserResource($user->load('roles')),
            'token' => $user->createToken($request->string('device_name'))->plainTextToken,
            'message' => __('Registration successful. Check your email to verify your address.'),
        ], Response::HTTP_CREATED);
    }

    public function login(LoginRequest $request, ApiAuthenticationService $auth): JsonResponse
    {
        $user = $auth->attempt($request->validated());

        return response()->json([
            'user' => new UserResource($user->load('roles')),
            'token' => $user->createToken($request->validated('device_name'))->plainTextToken,
        ]);
    }

    public function logout(Request $request): Response
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();
        event(new Logout('sanctum', $user));

        return response()->noContent();
    }

    /**
     * Always returns the same response so the endpoint cannot be used to
     * discover which emails are registered.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink($request->only('email'));

        return response()->json([
            'message' => __('If an account exists for that email, a password reset link has been sent.'),
        ]);
    }
}
