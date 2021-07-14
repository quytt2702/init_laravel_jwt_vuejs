<?php

namespace App\Services\Auth;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Class RefreshTokenService
 *
 * @package App\Services\Auth
 */
class RefreshTokenService
{
    /**
     * Handle refresh token
     *
     * @return void
     */
    public function handle()
    {
        try {
            auth()->setToken(
                auth()->refresh()
            );
        } catch (\Throwable $e) {
            throw new UnauthorizedHttpException('jwt-auth');
        }
    }
}
