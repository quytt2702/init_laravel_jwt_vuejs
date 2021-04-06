<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Traits\WithUser;
use App\Exceptions\CustomException;
use App\Repositories\V1\UserRepository;
use Illuminate\Support\Facades\Hash;

/**
 * Class ChangePasswordService
 * @package App\Services\Auth
 */
class ChangePasswordService
{
    use WithUser;

    protected $userRepository;

    /**
     * ChangePasswordService constructor.
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
     * Handle change password
     *
     * @param $attribute
     *
     * @return User
     *
     * @throws CustomException
     */
    public function handle($attribute)
    {
        if (!Hash::check($attribute['current_password'], $this->user->password)) {
            throw_custom_exception(__('auth.old_password_wrong'));
        }

        $currentUser = $this->userRepository->update([
            'password' => $attribute['password']
        ], $this->user);

        return $currentUser;
    }
}
