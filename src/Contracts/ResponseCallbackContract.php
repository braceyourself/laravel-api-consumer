<?php

namespace BlackBits\ApiConsumer\Contracts;


use Zttp\ZttpResponse;

interface ResponseCallbackContract
{
    function applyTo(ZttpResponse &$response) : ZttpResponse;
}
