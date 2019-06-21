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
     * @return \Illuminate\Support\Collection|\Tightenco\Collect\Support\Collection
     * @throws \Exception
     */
    public function resolve(ZttpResponse $response)
    {
        $results = $response->json();

        if (! $this->isJSON($results))
            throw new \Exception("Api result data is not a valid json!");


        $results = json_decode($results);


        if (!is_array($results)) $results = [$results];

        return count($results) > 1 ?
            collect($results)->map(function ($result) {
                return $this->shape::create($result);
            }):
            $this->shape::create($results);
    }

    private function isJSON($json_string)
    {
        return is_string($json_string) && is_array(json_decode($json_string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
    }
}
