<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Response;

/**
 * Class CustomException
 *
 * @package App\Exceptions
 */
class CustomException extends Exception
{
    /**
     * @var array|mixed $errors Error
     */
    private $errors;

    /**
     * CustomException constructor.
     *
     * @param string $message
     * @param array $errors
     * @param $code
     * @param \Throwable|null $previous
     */
    public function __construct($message, array $errors = [], $code = Response::HTTP_BAD_REQUEST, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->setErrors($errors);
    }

    /**
     * Get errors
     *
     * @return mixed
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Set errors
     *
     * @param $errors
     *
     * @return $this
     */
    public function setErrors($errors)
    {
        $this->errors = $errors;

        return $this;
    }
}
