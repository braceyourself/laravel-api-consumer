<?php

namespace BlackBits\ApiConsumer;

use BlackBits\ApiConsumer\Support\BaseEndpoint;
use BlackBits\ApiConsumer\Support\ShapeResolver;
use Illuminate\Support\Str;

abstract class ApiConsumer
{

    protected function getConfig()
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
     * @return BaseEndpoint
     * @throws \Exception
     */
    public static function __callStatic($endpointName, $arguments)
    {
        $endpoint = self::resolveEndpointClass($endpointName);

        $config = (new static)->getConfig();

        return new $endpoint($arguments, $config);
    }


    /**
     * @param $name
     * @return string
     * @throws \ReflectionException
     */
    private static function resolveEndpointClass($name)
    {

        $endpoint = self::buildFullyQualifiedName("Endpoints", ucfirst($name));

        if (!class_exists($endpoint)) {
            throw new \Exception("Class $endpoint does not exist.");
        }

        return $endpoint;
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
