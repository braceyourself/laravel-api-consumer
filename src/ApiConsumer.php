<?php

namespace BlackBits\ApiConsumer;

use BlackBits\ApiConsumer\Support\Endpoint;
use BlackBits\ApiConsumer\Support\ShapeResolver;
use Illuminate\Support\Str;

abstract class ApiConsumer
{
    protected $shape;

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

        $endpoint = self::buildFullyQualifiedName("Endpoints", ucfirst($name));

        if (!class_exists($endpoint)) {
            throw new \Exception("Class $endpoint does not exist.");
        }

        return $endpoint;
    }

    /**
     * @param $endpointName
     * @return string
     * @throws \Exception
     */
    private static function resolveEndpointShapeClass($endpointName)
    {

        $shape = self::buildFullyQualifiedName("Shapes", ucfirst($endpointName));

        if (!class_exists($shape)) {
            throw new \Exception("Endpoint Shape $shape does not exist.");
        }

        return $shape;
    }

    /**
     * @param mixed ...$append
     * @return string
     * @throws \ReflectionException
     */
    private static function buildFullyQualifiedName(...$append)
    {
        $namespaceName = (new \ReflectionClass(get_called_class()))->getNamespaceName();

        foreach ($append as $arg) {
            $namespaceName .= "\\$arg";
        }

        return $namespaceName;
    }

}
