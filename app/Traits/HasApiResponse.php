<?php

namespace App\Traits;

use Illuminate\Http\Response;

/**
 * Trait HasApiResponse
 *
 * @package App\Http\Concerns
 */
trait HasApiResponse
{
    /**
     * Meta response fields
     *
     * @var array
     */
    private static $meta = [];

    /**
     * Add meta response with $key
     *
     * @param mixed $key     Key
     * @param mixed $value   Value
     * @param bool  $refresh Refresh key and add value
     *
     * @return void
     */
    protected final static function addMetaResponse($key, $value, $refresh = false)
    {
        if ($refresh) {
            self::$meta[$key] = [];
        }

        self::$meta[$key] = $value;
    }

    /**
     * Reset meta
     */
    protected final static function refreshMeta()
    {
        self::$meta = [];
    }

    /**
     * Build response Success
     *
     * @param mixed  $data    Data
     * @param string $message Message response
     * @param mixed  $status  Code response
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected final function responseSuccess($data = [], $message = null, $status = Response::HTTP_OK)
    {
        $message = empty($message) ? __('messages.request.request_success') : $message;

        return $this->buildResponse(true, $data, $message, $status);
    }

    /**
     * Build response Error
     *
     * @param string $message Message
     * @param mixed  $errors  Errors
     * @param mixed  $status  Code response
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected final function responseError($message = null, $errors = [], $status = null)
    {
        $message = empty($message) ? __('exception.bad_request') : $message;

        if ($message instanceof \Throwable) {
            $exception = $message;
            $message   = $exception->getMessage();

            // Append detail error in json format
            if (config('app.debug') && (config('app.env') != 'production')) {
                $errors      = array_merge((array) $errors, [
                    'exception' => [
                        'file'  => $exception->getFile(),
                        'line'  => $exception->getLine(),
                        'trace' => explode(PHP_EOL, $exception->getTraceAsString())
                    ],
                ]);
            }
        }

        return $this->buildResponse(false, [], $message, $status, $errors);
    }

    /**
     * Build response
     *
     * @param bool        $success Request is success
     * @param array       $data    Data
     * @param string|null $message Message
     * @param mixed       $status  Code response
     * @param array       $errors  Error of response
     *
     * @return \Illuminate\Http\JsonResponse
     */
    private function buildResponse($success, $data = [], $message = null, $status = Response::HTTP_OK, $errors = [])
    {
        if (empty($status) && !$success) {
            $status = Response::HTTP_BAD_REQUEST;
        }

        return response()->json([
            'status'  => $status,
            'success' => $success,
            'message' => $message,
            'data'    => $data,
            'errors'  => $errors,
            'meta'    => self::$meta,
        ], $status);
    }
}
