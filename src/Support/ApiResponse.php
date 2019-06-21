<?php


namespace BlackBits\ApiConsumer\Support;


use Illuminate\Support\Traits\Macroable;
use Zttp\ZttpResponse;

class ApiResponse
{
    use Macroable;
    protected $data;
    private $response;

    public function __construct(ZttpResponse $response)
    {
        $this->response = $response;
    }

    /**
     * @return array
     */
    public function data(){
        return $this->response->json();
    }
}
