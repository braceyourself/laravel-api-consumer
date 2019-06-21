<?php

namespace BlackBits\ApiConsumer\Contracts;

use Zttp\ZttpResponse;

interface ShapeContract
{
    static function createFromResponse(ZttpResponse $response);

    /*
    function isReturnShapeDataOnly(): bool;
    function isRequireShapeStructure(): bool;
    function getFields(): array;
    function getTransformations(): array;
    function set($key, $value);
    function validateStructure();
    */
}
