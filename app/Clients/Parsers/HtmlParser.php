<?php

namespace App\Clients\Parsers;

use App\Clients\Contracts\ParserInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class HtmlParser
 *
 * @package App\Clients\Parsers
 */
class HtmlParser implements ParserInterface
{
    /**
     * Parse Response
     *
     * @param ResponseInterface|null $response  Response
     * @param \Exception             $exception Exception
     *
     * @return array|bool|float|int|mixed|null|\object|string
     */
    public function parse($response, $exception = null)
    {
        if (! $response) {
            return '';
        }

        return (string) $response->getBody();
    }
}
