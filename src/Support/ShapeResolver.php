<?php

namespace BlackBits\ApiConsumer\Support;

use BlackBits\ApiConsumer\Contracts\ShapeContract;
use Zttp\ZttpResponse;

class ShapeResolver
{
    /**
     * @var BaseEndpointShape $shape
     */
    private $shape;

    public function __construct(ShapeContract $shape)
    {
        $this->shape = $shape;
    }

    /**
     * @param ZttpResponse $response
     * @return BaseEndpointShape
     * @throws \Exception
     */
    public function resolve(ZttpResponse $response)
    {
        $results = $response->json();

        if (!is_array($results))
            throw new \Exception("Response was not valid. Please contact the vendor.");


        return $this->shape::build($results);
    }

    private function isJSON($json_string)
    {
        return is_string($json_string) && is_array(json_decode($json_string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
    }
}
