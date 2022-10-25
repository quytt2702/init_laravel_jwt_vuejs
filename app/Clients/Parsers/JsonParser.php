<?php

namespace App\Clients\Parsers;

use App\Clients\Contracts\ParserInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class JsonParser
 *
 * @package App\Clients\Parsers
 */
class JsonParser implements ParserInterface
{
    /**
     * JsonParser
     *
     * @param ResponseInterface|null $response Response
     *
     * @return array|bool|float|int|mixed|null|\object|string
     */
    public function parse($response)
    {
        if (! $response) {
            return null;
        }

        $content = (string) $response->getBody();

        return json_decode($content, true);
    }
}
