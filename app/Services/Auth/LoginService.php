<?php

namespace App\Services\Auth;

use App\Repositories\V1\UserRepository;
use Carbon\Carbon;
use Illuminate\Http\Response;

/**
 * Class LoginService
 * @package App\Services\Auth
 */
class LoginService
{
    /**
     * @var UserRepository|null
     */
    protected $userRepository;

    /**
     * LoginService constructor.
     *
     * @param null $userRepository
     */
    public function __construct($userRepository = null)
    {
        $this->userRepository = $userRepository instanceof UserRepository
            ? $userRepository
            : (new UserRepository());
    }

    /**
     * Handle login
     *
     * @param $email
     * @param $password
     * @param null $remember
     *
     * @return array
     */
    public function handle($email, $password, $remember = null)
    {
        $token = $this->attemptLogin($email, $password, $remember);

        return $this->responseWithToken($token);
    }

    /**
     * Attempt to authenticate a user using the given credentials.
     *
     * @param $email
     * @param $password
     * @param null $remember
     *
     * @return bool
     */
    protected function attemptLogin($email, $password, $remember = null)
    {
        $credentials = [
            'email' => $email,
            'password' => $password,
        ];

        if (empty($remember)) {
            $token = auth()->attempt($credentials);
        } else {
            $token = auth()->setTTL(config('jwt.ttl_remember'))->attempt($credentials);
        }

        if (!$token) {
            abort(Response::HTTP_UNAUTHORIZED,__('auth.failed'));
        }

        return $token;
    }

    /**
     * Build response from token
     *
     * @param $token
     *
     * @return array
     */
    public function responseWithToken($token)
    {
        $payload = auth()->payload();
        $tokenExpire = $payload->get('exp');

        return [
            'access_token'               => $token,
            'token_type'                 => 'bearer',
            'token_expired_at'           => Carbon::parse($tokenExpire)->toDateTimeString(),
            'token_expired_at_timestamp' => $tokenExpire * 1000,
        ];
    }
}
