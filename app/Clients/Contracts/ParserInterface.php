<?php

namespace App\Clients\Contracts;

use Psr\Http\Message\ResponseInterface;

/**
 * Interface ParserInterface
 *
 * @package App\Clients\Contracts
 */
interface ParserInterface
{
    /**
     * Parse Response
     *
     * @param ResponseInterface|null $response Response
     *
     * @return array|bool|float|int|mixed|null|\object|string
     */
    public function parse($response);
}
