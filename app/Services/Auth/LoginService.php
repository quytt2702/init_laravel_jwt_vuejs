<?php

namespace App\Services\Auth;

use App\Repositories\Contracts\UserRepository;
use Carbon\Carbon;
use Illuminate\Http\Response;

/**
 * Class LoginService
 *
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
     * @param UserRepository $userRepository
     */
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Handle login
     *
     * @param      $email
     * @param      $password
     * @param null $remember
     *
     * @return string
     */
    public function handle($email, $password, $remember = null)
    {
        return $this->attemptLogin($email, $password, $remember);
    }

    /**
     * Attempt to authenticate a user using the given credentials.
     *
     * @param      $email
     * @param      $password
     * @param null $remember
     *
     * @return bool
     */
    protected function attemptLogin($email, $password, $remember = null)
    {
        $credentials = [
            'email'    => $email,
            'password' => $password,
        ];

        if (empty($remember)) {
            $token = auth()->attempt($credentials);
        } else {
            $token = auth()->setTTL(config('jwt.ttl_remember'))->attempt($credentials);
        }

        if (!$token) {
            abort(Response::HTTP_UNAUTHORIZED, __('auth.failed'));
        }

        return $token;
    }
}
