<?php

namespace App\Traits;

use App\Exceptions\CustomException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Http\Exceptions\MaintenanceModeException;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Throwable;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

/**
 * Trait HasExceptionApiResponse
 *
 * @package App\Http\Concerns
 */
trait HasExceptionApiResponse
{
    use HasApiResponse;

    /**
     * Response Exception
     *
     * @param Request   $request   Request
     * @param Throwable $exception Throwable
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected final function responseException($request, Throwable $exception)
    {
        switch (true) {
            case $exception instanceof UnauthorizedException:
            case $exception instanceof UnauthorizedHttpException:
            case $exception instanceof AuthenticationException:
            case $exception instanceof TokenInvalidException:
                return $this->responseError(
                    'ERROR-0401',
                    __('error_codes.ERROR-0401'),
                    [],
                    Response::HTTP_UNAUTHORIZED
                );

            case $exception instanceof ValidationException:
                return $this->responseError(
                    'ERROR-0422',
                    __('error_codes.ERROR-0422'),
                    $this->beautifyErrorValidate($exception->validator->errors()->toArray()),
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );

            case $exception instanceof NotFoundHttpException:
            case $exception instanceof ModelNotFoundException:
                return $this->responseError(
                    'ERROR-0404',
                    __('error_codes.ERROR-0404'),
                    [],
                    Response::HTTP_NOT_FOUND
                );

            case $exception instanceof CustomException:
                return $this->responseError(
                    $exception->getErrorCode(),
                    $exception->getMessage(),
                    $exception->getErrors(),
                    $exception->getCode()
                );

            case $exception instanceof ServiceUnavailableHttpException:
            case $exception instanceof MaintenanceModeException:
                return $this->responseError(
                    'ERROR-0503',
                    __('error_codes.ERROR-0503'),
                    [],
                    Response::HTTP_SERVICE_UNAVAILABLE
                );

            case $exception instanceof ThrottleRequestsException:
                return $this->responseError(
                    'ERROR-0429',
                    __('error_codes.ERROR-0429'),
                    [],
                    Response::HTTP_TOO_MANY_REQUESTS
                );

            case $exception instanceof PostTooLargeException:
                return $this->responseError(
                    'ERROR-0413',
                    __('error_codes.ERROR-0413'),
                    [],
                    Response::HTTP_REQUEST_ENTITY_TOO_LARGE
                );

            case $exception instanceof HttpException:
                return $this->responseError(
                    'ERROR-0' . $exception->getStatusCode(),
                    $exception->getMessage(),
                    [],
                    $exception->getStatusCode()
                );

            case $exception instanceof AuthorizationException:
                return $this->responseError(
                    'ERROR-0403',
                    __('error_codes.ERROR-0403'),
                    [],
                    Response::HTTP_FORBIDDEN
                );

            case $exception instanceof MethodNotAllowedHttpException:
                return $this->responseError(
                    'ERROR-0405',
                    __('error_codes.ERROR-0405'),
                    [],
                    Response::HTTP_METHOD_NOT_ALLOWED
                );
        }

        return $this->responseError(
            'ERROR-0500',
            __('error_codes.ERROR-0500'),
            [],
            Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }

    /**
     * Beautiful Error validate
     *
     * @param array $errors
     *
     * @return array
     */
    private function beautifyErrorValidate(array $errors)
    {
        $errorResponses = [];
        foreach ($errors as $key => $error) {
            foreach ($error as $detail) {
                $errorResponses[] = [
                    'title'   => __('exception.field_error', ['field' => $key]),
                    'detail'  => $detail,
                    'pointer' => $key,
                ];
            }
        }

        return $errorResponses;
    }
}
