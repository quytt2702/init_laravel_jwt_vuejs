<?php

namespace App\Clients;

use App\Traits\WithUser;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\HandlerStack;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface;

/**
 * Support for json API
 *
 * Class AbstractClient
 *
 * @package App\Clients
 */
abstract class AbstractClient
{
    use WithUser;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var static
     */
    static $instance;

    /**
     * Response instance
     *
     * @var ResponseInterface
     */
    protected $response;

    /**
     * CallAPIBusinessService constructor.
     */
    public function __construct()
    {
        $this->initialize();
    }

    /**
     * Get static instance
     *
     * @return static
     */
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Refresh static instance exist
     */
    public static function refreshInstance()
    {
        self::$instance = null;
    }

    /**
     * Initialize Client Instance
     *
     * @return void
     */
    protected function initialize()
    {
        // Handler Setup
        $stack = HandlerStack::create();

        foreach ($this->middlewares() as $middleware) {
            $stack->push($middleware);
        }

        // Merge with given $options
        $options = array_merge_recursive($this->defaultOptions(), [
            'base_uri' => $this->getBaseUri(),
            'handler'  => $stack,
        ]);

        // Initialize client instance
        $this->client = new Client($options);
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
                'Accept'        => 'application/json',
                'content-type'  => 'application/json',
            ]
        ];
    }

    /**
     * Middleware
     *
     * @return mixed[]
     */
    protected function middlewares()
    {
        return [];
    }

    /**
     * @param string $method
     * @param string $endpoint
     * @param array $optionsAttach
     *
     * @return array
     */
    protected function request($method = 'get', $endpoint = '', $optionsAttach = [])
    {
        $code = null;

        try {
            $this->response = $this->client->request($method, $this->beautifyEndpoint($endpoint), $optionsAttach);
            $jsonResponse   = json_decode($this->response->getBody()->getContents(), true) ?? [];
            $code           = $this->response->getStatusCode();
        } catch (BadResponseException $e) {
            $this->response = $e->getResponse();
            $jsonResponse   = json_decode($this->response->getBody()->getContents(), true) ?? [];
            $code           = $this->response->getStatusCode();
        } catch (\Exception $e) {
            $code         = Response::HTTP_INTERNAL_SERVER_ERROR;
            $jsonResponse = [];
        }

        return $this->parse($code, $jsonResponse);
    }

    /**
     * Parse json data
     *
     * @param int $code
     * @param array|null $jsonResponse
     *
     * @return array
     */
    protected function parse($code, array $jsonResponse)
    {
        $success = ($code < 400);

        return [$this->beatifyJsonResponse($jsonResponse, $code, $success), $code, $success];
    }

    /**
     * Beatify json response
     *
     * @param array $jsonResponse
     * @param int   $code
     * @param bool  $success
     *
     * @return mixed
     */
    protected function beatifyJsonResponse($jsonResponse, $code, $success)
    {
        return $jsonResponse;
    }

    /**
     * Getter response
     *
     * @return ResponseInterface|null
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Getter for `base_uri`
     *
     * @return string
     */
    abstract protected function getBaseUri();

    /**
     * @param $endpoint
     *
     * @return string
     */
    protected function beautifyEndpoint($endpoint)
    {
        return ltrim($endpoint, '/');
    }
}