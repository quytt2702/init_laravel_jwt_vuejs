<?php

use App\Exceptions\CustomException;
use Illuminate\Validation\ValidationException;

if (!function_exists('throw_custom_exception')) {
    /**
     * throw error custom
     *
     * @param $message
     * @param array $errors
     *
     * @throws CustomException
     */
    function throw_custom_exception($message, $errors = [])
    {
        throw new CustomException($message, $errors);
    }
}

if (!function_exists('throw_validation_exception')) {
    /**
     * throw error validate
     *
     * @param array $messages
     *
     * @throws ValidationException
     */
    function throw_validation_exception(array $messages)
    {
        throw ValidationException::withMessages($messages);
    }
}

if (!function_exists('current_user')) {
    /**
     * Get user authenticate
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|\App\Models\User|null
     */
    function current_user()
    {
        return auth()->user();
    }
}
