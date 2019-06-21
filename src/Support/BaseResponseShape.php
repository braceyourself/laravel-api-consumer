<?php

namespace BlackBits\ApiConsumer\Support;

use BlackBits\ApiConsumer\Contracts\ShapeContract;
use Zttp\ZttpResponse;

abstract class BaseResponseShape implements ShapeContract
{
    protected $require_shape_structure = false;

    protected $transformations = [];

    protected $fields = [];
    private $data = [];

    public static function createFromResponse(ZttpResponse $response)
    {
        $results = $response->json();
        $shape = new static();

        if (!is_array($results))
            throw new \Exception("Response was not valid. Please contact the vendor.");


        foreach ($results as $key => $value) {
            $shape->set($key, $value);
        }

        $shape->validateStructure();

        return $shape->data();

    }

    /**
     * @return bool
     */
    public function requireStructure(): bool
    {
        return $this->require_shape_structure;
    }

    /**
     * @return array
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @return array
     */
    public function getTransformations(): array
    {
        return $this->transformations;
    }

    /**
     * @param $key
     * @param $value
     */
    public function set($key, $value)
    {
        if (isset($this->transformations[$key])) {
            $key = $this->transformations[$key];
        }


        $this->data[$key] = $value;
    }

    /**
     * @throws \Exception
     */
    public function validateStructure()
    {
        if (!$this->require_shape_structure) {
            return;
        }

        foreach ($this->fields as $field) {
            if (!isset($this->data[$field]))  {
                throw new \Exception("Shape is missing data field: '{$field}'");
            }
        }

    }



    protected function hasOne($endpoint, $field)
    {
        $endpoint_name = explode("\\", $endpoint);
        $endpoint_name = array_pop($endpoint_name);
        $endpoint_name = str_replace("Endpoint", "", $endpoint_name);

        $consumer_name = explode("\\", $endpoint);
        $consumer_name = array_slice($consumer_name, 0, count($consumer_name) -2);
        $consumer_name = "\\" . implode("\\", $consumer_name) . "\\" . $consumer_name[count($consumer_name) -1];


        return $consumer_name::$endpoint_name()->find($this->$field);
    }

    protected function hasMany($endpoint, $field)
    {
        $endpoint_name = explode("\\", $endpoint);
        $endpoint_name = array_pop($endpoint_name);
        $endpoint_name = str_replace("Endpoint", "", $endpoint_name);

        $consumer_name = explode("\\", $endpoint);
        $consumer_name = array_slice($consumer_name, 0, count($consumer_name) -2);
        $consumer_name = "\\" . implode("\\", $consumer_name) . "\\" . $consumer_name[count($consumer_name) -1];


        return $consumer_name::$endpoint_name()->findMany($this->$field);
    }

    /**
     * @return \Illuminate\Support\Collection|\Tightenco\Collect\Support\Collection
     */
    private function data()
    {
        return collect($this->data);
    }
}
