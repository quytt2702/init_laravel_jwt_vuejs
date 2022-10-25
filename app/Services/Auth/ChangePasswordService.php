<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Repositories\Contracts\UserRepository;
use App\Traits\WithUser;
use App\Exceptions\CustomException;
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
     * @param UserRepository $userRepository
     */
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
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
            throw_custom_exception('ERROR-PASSWORD-0001');
        }

        $currentUser = $this->userRepository->update([
            'password' => Hash::make($attribute['password'])
        ], $this->user);

        return $currentUser;
    }
}
