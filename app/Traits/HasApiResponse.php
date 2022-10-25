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
    protected static function addMetaResponse($key, $value, $refresh = false)
    {
        if ($refresh) {
            self::$meta[$key] = [];
        }

        self::$meta[$key] = $value;
    }

    /**
     * Reset meta
     */
    protected static function refreshMeta()
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
    protected function responseSuccess($data = [], $message = null, $status = Response::HTTP_OK)
    {
        $message = empty($message) ? __('messages.request.request_success') : $message;

        return response()->json([
            'status'  => $status,
            'success' => true,
            'message' => $message,
            'data'    => $data,
            'meta'    => self::$meta,
        ], $status);
    }

    /**
     * @param       $errorId
     * @param null  $message
     * @param array $errors
     * @param int   $status
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function responseError($errorId, $message = null, $errors = [], $status = 500)
    {
        $message = empty($message) ? __('exception.bad_request') : $message;

        return response()->json([
            'status'  => $status,
            'success' => false,
            'error'   => [
                'error_id' => $errorId,
                'message' => $message,
                'errors'  => $errors,
            ]
        ], $status);
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
