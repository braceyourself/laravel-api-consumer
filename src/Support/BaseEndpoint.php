<?php

namespace BlackBits\ApiConsumer\Support;

use BlackBits\ApiConsumer\CollectionCallbacks\_ReflectionCollectionCallback;
use BlackBits\ApiConsumer\Contracts\ResponseCallbackContract;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Zttp\PendingZttpRequest;
use Illuminate\Support\Facades\Cache;
use Zttp\ZttpResponse;


abstract class BaseEndpoint
{
    private $responseCallbacks = [];

    protected $headers = [];
    protected $options = [];
    protected $params = [];
    protected $body_format = 'json';
    protected $path;
    protected $method;
    protected $responseRules;
    protected $validation = [];
    protected $client;


    /**
     * Endpoint constructor.
     * @param array $config
     * @param array $args
     */
    public function __construct($args = [], array $config = [])
    {
        $this->client = new PendingZttpRequest();
        $this->optiogsns = array_merge_recursive($config['options'], $args);
//        $this->params = $this->options['params'] ?? [];
    }

    /**
     * @param array $appendPath
     * @return string
     */
    private function buildUri(...$appendPath)
    {
        $base_url = rtrim($this->options['url'], '/');
        $endpoint_uri = trim($this->path, '/');

        foreach($appendPath as $path){
            $path = trim($path, '/');
            $endpoint_uri .= "/$path";
        }


        $full_uri = trim("$base_url/$endpoint_uri", '/');
        foreach ($this->params as $key => $value) {
            if ($key === array_key_first($this->params))
                $full_uri .= '?';

            $full_uri .= "$key=$value";

            if ($key !== array_key_first($this->params))
                $full_uri .= '&';

        }

        dump($full_uri);
        return $full_uri;
    }

    /**
     * @return string    private function request($method = 'GET', $data = [])
     */
    private function getCacheKey()
    {
        $key = $this->method . "-" . $this->buildUri();

        if (!empty($this->options)) {
            $value = $this->options;
            if (is_array($value)) {
                $value = http_build_query($value, null, '&', PHP_QUERY_RFC3986);
            }
            if (is_string($value)) {
                $key .= "-" . $value;
            }
        }

        return $key;
    }

    /**
     * @param string $method
     * @param null $id
     * @param array $data
     * @return ApiResponse
     */
    public function sendRequest($method = 'GET', $data = [])
    {
        $method = strtolower($method);

        $this->prepareRequest($data);

        dump($this->options);
        $response = new ApiResponse(
            $this->client->$method($this->buildUri(), $this->options)
        );

        $this->validate($method, $response);

        return $response;
    }

    /**
     * @param ResponseCallbackContract $collectionCallback
     */
    private function registerCollectionCallback(ResponseCallbackContract $collectionCallback)
    {
        $this->responseCallbacks[] = $collectionCallback;
    }


    /**
     * @param $id
     * @return ApiResponse
     */
    final public function find($id)
    {
        $response = $this->sendRequest('get', compact('id'));

        return $response->withErrors();
    }

    /**
     * @param array $data
     * @return ApiResponse
     */
    final public function get($data = [])
    {
        $response = $this->sendRequest('get', $data);

        return $response->withErrors();
    }

    /**
     * @param array $data
     * @return ApiResponse
     * @throws \Exception
     */
    final public function post(array $data)
    {
        $response = $this->sendRequest('POST', $data);

        return $response->withErrors();
    }


    /**
     * @param $name
     * @param $arguments
     * @return $this
     * @throws \Exception
     */
    public function __call($name, $arguments)
    {
        $collectionCallback = "\App\CollectionCallbacks\\" . ucfirst($name) . "CollectionCallback";

        if (!class_exists($collectionCallback)) {
            $collectionCallback = "\BlackBits\ApiConsumer\CollectionCallbacks\\" . ucfirst($name) . "CollectionCallback";
        }

        if (!class_exists($collectionCallback)) {
            $this->registerCollectionCallback(
                (new _ReflectionCollectionCallback(... $arguments))->setMethod($name)
            );
            return $this;
        }

        $this->registerCollectionCallback(new $collectionCallback(... $arguments));
        return $this;
    }

    /**
     * @param ZttpResponse $response
     * @return \Illuminate\Support\Collection|ZttpResponse
     * @throws \Exception
     */
    public function applyCallbacks(ZttpResponse $response)
    {
        /** @var ResponseCallbackContract $callback */
        foreach ($this->responseCallbacks as $callback) {
            $response = $callback->applyTo($response);
        }

        return $response;
    }


    private function prepareRequest($data = [])
    {

        $this->options = array_merge_recursive($this->options, $data);

        return $this->setHeaders()->setBodyFormat()->buildAuthentication();

    }

    private function buildAuthentication()
    {
        $auth = $this->options['auth'];

        if (!empty($authBasic = $auth['basic'])) {

            $this->client = $this->client->withBasicAuth(
                $authBasic['username'] ?? $authBasic[0],
                $authBasic['password'] ?? $authBasic[1]
            );

        } else if (isset($auth['key'])) {

            $this->params['key'] = $auth['key'];
        }

        return $this;
    }

    private function setBodyFormat()
    {

        $this->client->bodyFormat = $this->body_format;

        return $this;
    }

    private function setHeaders()
    {

        $this->client = $this->client->withHeaders($this->headers);

        return $this;

    }


    public function config(...$keys)
    {
        $config_key = 'api-consumers.' . get_class($this);
        foreach ($keys as $key) {
            $config_key .= ".$key";
        }

        return config($config_key);
    }

    private function validate($method, ApiResponse $response)
    {
        try {
            if (isset($this->validation[$method])) {
                $response->validate(
                    $this->validation[$method]['rules'] ?? [],
                    $this->validation[$method]['messages'] ?? [],
                    $this->validation[$method]['customAttributes'] ?? []
                );
            }
        } catch (ValidationException $e) {
            $response->addErrors($e->errors());
        }


    }


}
