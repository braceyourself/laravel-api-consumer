<?php

namespace BlackBits\ApiConsumer;

use BlackBits\ApiConsumer\Support\Endpoint;
use BlackBits\ApiConsumer\Support\ShapeResolver;
use Illuminate\Support\Str;

abstract class ApiConsumer
{

    protected function getOptions()
    {
        return config("api-consumers." . $this->getName()) ?? [];
    }

    protected function getName()
    {
        return class_basename($this);
    }

    /**
     * @param $endpointName
     * @param $arguments
     * @return Endpoint
     * @throws \Exception
     */
    public static function __callStatic($endpointName, $arguments)
    {
        $endpoint = self::resolveEndpointClass($endpointName);

        $shape = self::resolveEndpointShapeClass($endpointName);


        return new $endpoint(
            new ShapeResolver(new $shape),
            (new static)->getOptions(),
            $arguments
        );
    }

    private static function resolveEndpointClass($name)
    {
        $endpoint = __NAMESPACE__ . "\\Endpoints\\" . ucfirst($name);

        if (!class_exists($endpoint)) {
            throw new \Exception("Class $endpoint does not exist.");
        }

        return $endpoint;
    }

    private static function resolveEndpointShapeClass($name)
    {
        $shape = __NAMESPACE__ . "\\Shapes\\" . $name;

//        if (!class_exists($shape)) {
//            $shape = (new \ReflectionClass(get_called_class()))->getNamespaceName() . "\\Shapes\\" . $name . "Shape";
//        }
//
//        if (!class_exists($shape)) {
//            $shape = (new \ReflectionClass(get_called_class()))->getNamespaceName() . "\\Shapes\\" . ucfirst($name) . "Shape";
//        }
//
//        if (!class_exists($shape)) {
//            $name = Str::singular($name);
//            $shape = (new \ReflectionClass(get_called_class()))->getNamespaceName() . "\\Shapes\\" . ucfirst($name) . "Shape";
//        }
        if (!class_exists($shape)) {
            throw new \Exception("Class $shape does not exist.");
        }

        return $shape;
    }
}
