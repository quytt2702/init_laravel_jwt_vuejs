<?php

namespace App\Clients;

use App\Traits\WithUser;
use App\Clients\Contracts\ParserInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\HandlerStack;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface;

abstract class AbstractClient
{
    use WithUser;

    /**
     * @var Client
     */
    protected $client;

    /**
     * Response instance
     *
     * @var ResponseInterface
     */
    protected $response;

    /**
     * @var \Exception
     */
    protected $exception;

    /**
     * CallAPIBusinessService constructor.
     */
    public function __construct()
    {
        $this->initialize();
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
        return [];
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
     * @param array  $optionsAttach
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function request($method = 'get', $endpoint = '', $optionsAttach = [])
    {
        $this->resetResponse();
        $this->resetException();

        try {
            $this->response = $this->client->request($method, $this->beautifyEndpoint($endpoint), $optionsAttach);
            $code           = $this->response->getStatusCode();
        } catch (BadResponseException $e) {
            $this->exception = $e;
            $this->response  = $e->getResponse();
            $code            = $this->response->getStatusCode();
        } catch (\Exception $e) {
            $this->exception = $e;
            $code            = Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        return $this->createResult($this->parser()->parse($this->response), $code);
    }

    /**
     * Parse html data
     *
     * @param mixed $data
     * @param int   $code
     *
     * @return array
     */
    protected function createResult($data, $code)
    {
        $code      = (int) $code;
        $isSuccess = ($code < Response::HTTP_BAD_REQUEST);

        $this->handleError($data, $code, $isSuccess);

        return [$data, $code, $isSuccess];
    }

    /**
     * Handle error from response
     *
     * @param $data
     * @param $code
     * @param $isSuccess
     */
    protected function handleError($data, $code, $isSuccess)
    {
        //
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
     * @return $this
     */
    public function resetResponse()
    {
        $this->response = null;

        return $this;
    }

    /**
     * Getter response
     *
     * @return \Exception|null
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * @return $this
     */
    public function resetException()
    {
        $this->exception = null;

        return $this;
    }

    /**
     * Get data from response
     *
     * @param array|null $jsonResponse
     *
     * @return mixed
     */
    protected function getDataResponse($jsonResponse)
    {
        return Arr::get($jsonResponse, 'data', []);
    }

    /**
     * @param $endpoint
     *
     * @return string
     */
    protected function beautifyEndpoint($endpoint)
    {
        return ltrim($endpoint, '/');
    }

    /**
     * Parse data, return parseHtml/parseJson/Custom
     *
     * @return ParserInterface
     */
    abstract protected function parser();

    /**
     * Getter for `base_uri`
     *
     * @return string
     */
    abstract protected function getBaseUri();
}
