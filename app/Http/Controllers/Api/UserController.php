<?php

namespace App\Http\Controllers\Api;

use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProfileResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function show(Request $request): UserResource
    {
        return new UserResource($request->user()->load('roles'));
    }

    public function profile(Request $request): ProfileResource
    {
        return new ProfileResource($request->user()->load('kycProfile'));
    }

    public function update(Request $request, UpdateUserProfileInformation $updater): ProfileResource
    {
        $updater->update($request->user(), $request->only(['name', 'email', 'phone']));

        return new ProfileResource($request->user()->fresh()->load('kycProfile'));
    }
}
