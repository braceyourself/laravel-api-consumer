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
    private $basePath;
    private $shapeResolver;
    private $collectionCallbacks = [];

    protected $headers = [];
    protected $options = [];
    protected $auth = [];
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
     * @param $basePath
     * @param ShapeResolver $shapeResolver
     */
    public function __construct($basePath, ShapeResolver $shapeResolver)
    {
        $this->basePath = $basePath;
        $this->shapeResolver = $shapeResolver;
        $this->client = new PendingZttpRequest();
    }

    /**
     * @return string
     */
    private function uri()
    {
        return $this->basePath . "/" . ltrim($this->path, "/");
    }

    /**
     * @return string
     */
    private function getCacheKey()
    {
        $key = $this->method . "-" . $this->uri();

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
    private function request($method = 'GET', $data)
    {
        $method = strtolower($method);

        $this->options = array_merge_recursive($this->options, $data);

        $this->prepareRequest();
        

        return $this->handleResponse(
            $this->client->$method($this->uri(), $this->options)->body()
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

    final public function get($id){

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
     * @return \Illuminate\Support\Collection|\Tightenco\Collect\Support\Collection
     * @throws \Exception
     */
    public function resolveRequest($request)
    {
        $collection = $this->shapeResolver->resolve($request);

        /** @var CollectionCallbackContract $callback */
        foreach ($this->collectionCallbacks as $callback) {
            $collection = $callback->applyTo($collection);
        }

        return $collection;
    }

    private function prepareRequest(){
        
        return $this->setHeaders()->setBodyFormat()->buildAuthentication();
        
    }
    
    private function buildAuthentication(){
        if (!empty($this->auth['basic'])) {
            $auth = $this->auth['basic'];
            
            $username = $auth['username'] ?? $auth[0];
            $password = $auth['password'] ?? $auth[1];
            
            $this->client = $this->client->withBasicAuth($username, $password);
        }
    }
    
    private function setBodyFormat(){
        
        $this->client->bodyFormat = $this->body_format;
        
        return $this;
    }
    
    private function setHeaders(){
        
        $this->client = $this->client->withHeaders($this->headers);
        
        return $this;
        
    }

    protected function handleResponse($response){

        if ($this->shouldCache) {
            return Cache::remember($this->getCacheKey(), $this->cacheDurationInMinutes, function ()use($response) {
                return $response;
            });
        }

        return $response;
    }
}
