<?php

namespace BlackBits\ApiConsumer\Support;

use BlackBits\ApiConsumer\CollectionCallbacks\_ReflectionCollectionCallback;
use BlackBits\ApiConsumer\Contracts\CollectionCallbackContract;
use BlackBits\ApiConsumer\Support\ShapeResolver;
use Zttp\PendingZttpRequest;
use Zttp\Zttp;
use Illuminate\Support\Facades\Cache;


abstract class Endpoint
{
    private $shapeResolver;
    private $collectionCallbacks = [];

    protected $headers = [];
    protected $options = [];
    protected $params = [];
    protected $body_format = 'json';
    protected $path;
    protected $method;

    protected $shouldCache = false;
    protected $cacheDurationInMinutes = 5;
    /**
     * @var PendingZttpRequest
     */
    private $client;


    /**
     * Endpoint constructor.
     * @param ShapeResolver $shapeResolver
     * @param array $options
     * @param array $args
     */
    public function __construct(ShapeResolver $shapeResolver, array $options = [], $args = [])
    {
        $this->shapeResolver = $shapeResolver;
        $this->client = new PendingZttpRequest();
        $this->options = array_merge_recursive($options, $args);
        $this->params = $this->options['params'] ?? [];
    }

    /**
     * @return string
     */
    private function buildUri()
    {
        $base_url = rtrim($this->options('url'),'/');
        $endpoint_uri = ltrim($this->path, '/');
        $full_rui = "$base_url/$endpoint_uri" ;

        foreach ($this->params as $key => $value) {
            if ($key === array_key_first($this->params))
                $full_rui .= '?';

            $full_rui .= "$key=$value";

            if ($key !== array_key_first($this->params))
                $full_rui .= '&';

        }

        return $full_rui;
    }

    /**
     * @return string
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
     * @return mixed
     */
    private function request($method = 'GET', $data = [])
    {
        $method = strtolower($method);

        $this->prepareRequest($data);

        return $this->handleResponse(
            $this->client->$method($this->buildUri(), $this->options)->body()
        );

    }

    /**
     * @param CollectionCallbackContract $collectionCallback
     */
    private function registerCollectionCallback(CollectionCallbackContract $collectionCallback)
    {
        $this->collectionCallbacks[] = $collectionCallback;
    }

    /**
     * @return \Illuminate\Support\Collection|\Tightenco\Collect\Support\Collection
     * @throws \Exception
     */
    final public function all()
    {
        return $this->resolveRequest(
            $this->request('get')
        );
    }

    final public function get($id)
    {

        return $this->all()->where('transaction_id', $id);

    }

    final public function post(array $data)
    {
        return $this->resolveRequest(
            $this->request('POST', $data)
        );
    }

    /**
     * @return \Illuminate\Support\Collection|\Tightenco\Collect\Support\Collection
     * @throws \Exception
     */
    final public function first()
    {
        return $this->all()->first();
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
     * @return array
     * @throws \Exception
     */
    public function resolveRequest($request)
    {
        $collection = $this->shapeResolver->resolve($request);

        /** @var CollectionCallbackContract $callback */
        foreach ($this->collectionCallbacks as $callback) {
            $collection = $callback->applyTo($collection);
        }

        return [
            'data' => $collection
        ];
    }

    private function prepareRequest($data = [])
    {

        $this->options = array_merge_recursive($this->options, $data);

        return $this->setHeaders()->setBodyFormat()->buildAuthentication();

    }

    private function buildAuthentication()
    {
        $auth = $this->options('auth');

        if (!empty($auth['basic']) && count($auth) > 2) {
            $auth = $auth['basic'];

            $this->client = $this->client->withBasicAuth(
                $auth['username'] ?? $auth[0],
                $auth['password'] ?? $auth[1]
            );

        } else if (isset($auth['key'])) {

            $this->params['key'] = $auth['key'];
        }
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

    protected function handleResponse($response)
    {

        if ($this->shouldCache) {
            return Cache::remember($this->getCacheKey(), $this->cacheDurationInMinutes, function () use ($response) {
                return $response;
            });
        }

        return $response;
    }

    private function options($key)
    {

        try {
            $val = $this->options[$key];
        } catch (\ErrorException $e) {
            $val = null;
        }
        return $val;
    }
}
