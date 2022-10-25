<?php

use App\Exceptions\CustomException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

if (!function_exists('throw_custom_exception')) {
    /**
     * throw error custom
     *
     * @param       $errorCode
     * @param array $replaceString
     * @param array $errors
     *
     * @throws CustomException
     */
    function throw_custom_exception($errorCode, array $replaceString = [], array $errors = [])
    {
        throw new CustomException($errorCode, $replaceString, $errors);
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

if (!function_exists('get_class_name')) {
    /**
     * @param $object
     *
     * @return bool|string
     */
    function get_class_name($object)
    {
        $classname = get_class($object);
        if ($pos = strrpos($classname, '\\')) {
            return substr($classname, $pos + 1);
        }

        return $classname;
    }
}

if (!function_exists('strposa')) {
    /** strpos that takes an array of values to match against a string note the stupid argument order (to match strpos)
     *
     * @param string $haystack
     * @param mixed  $needle
     * @param int    $offset
     *
     * @return false|int
     */
    function strposa(string $haystack, $needle, int $offset = 0)
    {
        foreach ((array)$needle as $what) {
            if (($pos = strpos($haystack, $what, $offset)) !== false) {
                return $pos;
            };
        }

        return false;
    }
}

if (!function_exists('strrposa')) {
    /** strrpos that takes an array of values to match against a string note the stupid argument order (to match
     * strpos) from right
     *
     * @param string $haystack
     * @param mixed  $needle
     * @param int    $offset
     *
     * @return false|int
     */
    function strrposa(string $haystack, $needle, int $offset = 0)
    {
        foreach ((array)$needle as $what) {
            if (($pos = strrpos($haystack, $what, $offset)) !== false) {
                return $pos;
            };
        }

        return false;
    }
}

if (!function_exists('nested_to_single')) {
    /**
     * Convert multidimensional array into single array
     *
     * @param array $array
     *
     * @return array
     */
    function nested_to_single(array $array)
    {
        $singleDimArray = [];

        foreach ($array as $item) {
            if (is_array($item)) {
                $singleDimArray = array_merge($singleDimArray, nested_to_single($item));

            } else {
                $singleDimArray[] = $item;
            }
        }

        return $singleDimArray;
    }
}

if (!function_exists('array_except_value')) {
    /**
     * @param array        $array
     * @param string|array $keys
     *
     * @return array
     */
    function array_except_value(array $array, $keys)
    {
        $keys = (array)$keys;

        return array_values(
            array_filter($array, function ($item) use ($keys) {
                return !in_array($item, $keys);
            })
        );
    }
}

if (!function_exists('is_secure_schema')) {
    /**
     * @param $url
     *
     * @return bool
     */
    function is_secure_schema(string $url)
    {
        return Str::startsWith($url, 'https');
    }
}

if (!function_exists('parse_exception')) {
    /**
     * Parse Exception into Array
     *
     * @param Exception $exception
     *
     * @return array
     */
    function parse_exception(Throwable $exception)
    {
        $exceptionData = [
            'message' => is_json($exception->getMessage()) ? json_decode($exception->getMessage()) : $exception->getMessage(),
            //            'trace'   => $exception->getTraceAsString(),
            'file'    => $exception->getFile(),
            'line'    => $exception->getLine(),
        ];

        if ($exception instanceof \GuzzleHttp\Exception\RequestException && $exception->hasResponse()) {
            $exceptionData['response'] = json_decode((string)$exception->getResponse()->getBody(), true);
        }

        return $exceptionData;
    }
}

if (!function_exists('is_json')) {
    /**
     * Json check
     *
     * @param $str
     *
     * @return bool
     */
    function is_json($str)
    {
        return json_decode($str) != null;
    }
}

if (!function_exists('get_query_by_chunk')) {
    /**
     * Build query and chunk by items
     *
     * @param int                                                                      $count
     * @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder $query
     * @param callable                                                                 $callback
     * @param string                                                                   $column
     *
     * @return bool
     */
    function get_query_by_chunk(int $count, $query, callable $callback, string $column = 'id')
    {
        $lastId = null;

        $index = 1;

        do {
            $columnTemp    = 'id_temp';
            $cloneSubQuery = clone $query;
            $clone         = clone $query;
            $mainQuery     = clone $query;

            $mainQuery->forPageAfterId($count, $lastId, $column);

            $cloneSubQuery->selectRaw("{$column} as {$columnTemp}")
                ->orderBy($columnTemp, 'asc')
                ->limit($count);

            if (!is_null($lastId)) {
                $cloneSubQuery->where($column, '>', $lastId);
            }

            $lastItem = $clone
                ->joinSub($cloneSubQuery, 'temporary_table', "temporary_table.{$columnTemp}", '=', $column)
                ->orderBy($column, 'desc')
                ->first();

            if (is_null($lastItem)) {
                break;
            }

            if ($callback($mainQuery, $lastItem, $index) === false) {
                return false;
            }

            $lastId = $lastItem->{$column};

            if ($lastId === null) {
                throw new RuntimeException("The get_query_by_chunk operation was aborted because the [{$column}] column is not present in the query result.");
            }

            unset($lastItem);

            $index++;
        } while (true);

        return true;
    }
}

if (!function_exists('convert_kana')) {
    /**
     * Convert string to kana
     *
     * @param string|null $str
     *
     * @return string|null
     */
    function convert_kana(?string $str)
    {
        return is_null($str) ? null : mb_convert_kana(
            strtoupper(
                convert_alphabet_to_half_size($str)
            ),
            'CKVA'
        );
    }
}

if (!function_exists('convert_alphabet_to_half_size')) {
    /**
     * Convert string to kana
     *
     * @param string|null $str
     *
     * @return string|null
     */
    function convert_alphabet_to_half_size(?string $str)
    {
        return is_null($str) ? null : mb_convert_kana($str, 'a');
    }
}

if (!function_exists('create_pagination')) {
    /**
     * Create custom pagination
     *
     * @param array|Collection $items
     * @param int              $total
     * @param int              $limit
     * @param int              $page
     * @param string           $pageName
     *
     * @return Application|mixed
     */
    function create_pagination(
        $items,
        int $total,
        int $limit = 10,
        int $page = 1,
        string $pageName = 'page'
    ) {
        return app(LengthAwarePaginator::class,
            [
                'items'       => $items,
                'total'       => $total,
                'perPage'     => $limit,
                'currentPage' => $page,
                'options'     => [
                    'path'     => Paginator::resolveCurrentPath(),
                    'pageName' => $pageName,
                ],
            ]
        );
    }
}

if ('get_public_ip') {
    /**
     * Get ip public of client
     *
     * @return string|null
     */
    function get_public_ip()
    {
        foreach (['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED'] as $key) {
            if (request()->server($key)) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip); // just to be safe

                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }

        return request()->ip(); // it will return server ip when no client ip found
    }
}
