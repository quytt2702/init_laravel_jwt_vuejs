<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\CustomException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\User\UserResource;
use App\Services\Auth\ChangePasswordService;
use App\Services\Auth\LoginService;

class AuthController extends Controller
{
    /**
     * Get authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return $this->responseSuccess(
            UserResource::make(current_user()),
            __('messages.request.get_info_success')
        );
    }

    /**
     * API login
     *
     * @param LoginRequest $request
     * @param LoginService $service
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request, LoginService $service)
    {
        $email      = $request->input('email');
        $password   = $request->input('password');
        $isRemember = $request->input('remember', false);
        $data       = $service->handle($email, $password, $isRemember);

        return $this->responseSuccess(
            $data,
            __('auth.login_success')
        );
    }

    /**
     * API logout
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return $this->responseSuccess(
            null,
            __('auth.logout_success')
        );
    }

    /**
     * API change password
     *
     * @param ChangePasswordRequest $request
     * @param ChangePasswordService $service
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws CustomException
     */
    public function changePassword(ChangePasswordRequest $request, ChangePasswordService $service)
    {
        $service->setUser(auth()->user())
            ->handle($request->validated());

        return $this->responseSuccess(
            null,
            __('auth.change_password_success')
        );
    }
}
