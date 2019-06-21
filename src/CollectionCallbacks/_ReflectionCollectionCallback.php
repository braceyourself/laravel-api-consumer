<?php

namespace BlackBits\ApiConsumer\CollectionCallbacks;

use BlackBits\ApiConsumer\Support\BaseResponseCallback;
use Illuminate\Support\Collection;
use Zttp\ZttpResponse;

class _ReflectionCollectionCallback extends BaseResponseCallback
{
    /**
     * @var array
     */
    private $args;

    private $method;

    public function __construct()
    {
        $this->args = func_get_args();
    }

    /**
     * @param $method
     * @return $this
     */
    public function setMethod($method)
    {
        $this->method = $method;
        return $this;
    }

    function applyTo(ZttpResponse &$response) : ZttpResponse
    {
        $method = $this->method;
        if (!method_exists($response, $method)) {
            throw new \Exception("Method {$method} does not exist.");
        }
        return $response->$method(... $this->args);
    }
}
