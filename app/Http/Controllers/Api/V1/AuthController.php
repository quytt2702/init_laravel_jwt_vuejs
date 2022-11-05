<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\CustomException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\User\UserResource;
use App\Services\Auth\ChangePasswordService;
use App\Services\Auth\LoginService;
use App\Services\Auth\RefreshTokenService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\Token;

class AuthController extends Controller
{
    /**
     * Get authenticated user.
     *
     * @return JsonResponse
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
     * @return JsonResponse
     */
    public function login(LoginRequest $request, LoginService $service)
    {
        $email      = $request->input('email');
        $password   = $request->input('password');
        $isRemember = $request->input('remember', false);
        $service->handle($email, $password, $isRemember);

        return $this->responseSuccess(
            $this->getToken(),
            __('auth.login_success')
        );
    }

    /**
     * API logout
     *
     * @return JsonResponse
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
     * Api refresh token
     *
     * @return JsonResponse
     */
    public function refresh(RefreshTokenService $service)
    {
        $service->handle();

        return $this->responseSuccess(
            $this->getToken(),
            __('auth.request_success')
        );
    }

    /**
     * API change password
     *
     * @param ChangePasswordRequest $request
     * @param ChangePasswordService $service
     *
     * @return JsonResponse
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

    /**
     * Get token
     *
     * @return Token
     */
    private function getToken()
    {
        $token       = auth()->getToken();
        $payload     = auth()->payload();
        $tokenExpire = $payload->get('exp');

        $token->id                   = auth()->id();
        $token->access_token         = $token->get();
        $token->token_type           = 'Bearer';
        $token->expires_in           = auth()->factory()->getTTL() * 60;
        $token->expired_at           = Carbon::parse($tokenExpire)->toDateTimeString();
        $token->expired_at_timestamp = $tokenExpire * 1000;

        return $token;
    }
}
