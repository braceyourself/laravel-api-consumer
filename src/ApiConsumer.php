<?php

namespace BlackBits\ApiConsumer;

use BlackBits\ApiConsumer\Support\ShapeResolver;
use Illuminate\Support\Str;

abstract class ApiConsumer
{
    abstract protected function getEndpoint();

    public static function __callStatic($name, $arguments)
    {

        $endpoint = (new \ReflectionClass(get_called_class()))->getNamespaceName() . "\\Endpoints\\" . $name;

        if (! class_exists($endpoint)) {
            $endpoint = (new \ReflectionClass(get_called_class()))->getNamespaceName() . "\\Endpoints\\" . $name . "Endpoint";
        }

        if (! class_exists($endpoint)) {
            $endpoint = (new \ReflectionClass(get_called_class()))->getNamespaceName() . "\\Endpoints\\" . ucfirst($name) . "Endpoint";
        }

        if (! class_exists($endpoint)) {
            throw new \Exception("Class $endpoint does not exist.");
        }

        $name = Str::singular($name);
        $shape = (new \ReflectionClass(get_called_class()))->getNamespaceName() . "\\Shapes\\" . $name;

        if (! class_exists($shape)) {
            $shape = (new \ReflectionClass(get_called_class()))->getNamespaceName() . "\\Shapes\\" . $name . "Shape";
        }

        if (! class_exists($shape)) {
            $shape = (new \ReflectionClass(get_called_class()))->getNamespaceName() . "\\Shapes\\" . ucfirst($name) . "Shape";
        }

        if (! class_exists($shape)) {
            throw new \Exception("Class $shape does not exist.");
        }

        return new $endpoint((new static)->getEndpoint(), new ShapeResolver(new $shape));
    }
}
