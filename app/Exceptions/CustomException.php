<?php

namespace App\Exceptions;

use Exception;

/**
 * Class CustomException
 *
 * @package App\Exceptions
 */
class CustomException extends Exception
{
    /**
     * @var mixed|string
     */
    protected $errorCode;

    /**
     * @var array
     */
    protected $errors;

    /**
     * CustomException constructor.
     *
     * @param string $errorCode
     * @param array  $replaceString
     */
    public function __construct($errorCode = "ERROR-0400", array $replaceString = [], array $errors = [])
    {
        $this->errorCode = $errorCode;
        $this->errors    = $errors;

        $message = __(sprintf('error_codes.%s', $errorCode), $replaceString);

        parent::__construct($message, \Symfony\Component\HttpFoundation\Response::HTTP_BAD_REQUEST);
    }

    /**
     * @return mixed|string
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
