<?php

namespace App\Clients;

use App\Clients\Parsers\JsonParser;

class ExampleClient extends AbstractClient
{
    /**
     * Getter for `base_uri`
     *
     * @return string
     */
    protected function getBaseUri()
    {
        return config('example.url');
    }

    /**
     * Parse json data
     *
     * @return JsonParser
     */
    protected function parser()
    {
        return new JsonParser();
    }

    /**
     * Get Default Options
     *
     * @return array
     */
    protected function defaultOptions()
    {
        return [
            'headers' => [
                'Accept' => 'application/json',
            ],
            'auth'    => [
                config('example.username'),
                config('example.password'),
            ],
        ];
    }

    /**
     * Example call api
     *
     * @param mixed $image
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getDataExample($image)
    {
        $options = [
            'multipart' => [
                [
                    'name'     => 'file',
                    'contents' => $image,
                ],
            ],
        ];

        return $this->request('POST', 'example', $options);
    }
}
