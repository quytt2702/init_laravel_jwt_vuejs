<?php

namespace App\Clients\Parsers;

use App\Clients\Contracts\ParserInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class NullParser
 *
 * @package App\Clients\Parsers
 */
class NullParser implements ParserInterface
{
    /**
     * Parse Response. Just return original given $response
     *
     * @param ResponseInterface|null $response Response
     *
     * @return array|bool|float|int|mixed|null|\object|string
     */
    public function parse($response)
    {
        return null;
    }
}
