<?php


namespace BlackBits\ApiConsumer\Support;


use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Traits\Macroable;
use Zttp\ZttpResponse;

class ApiResponse
{
    use Macroable;
    protected $data;
    public $errors;
    private $response;

    public function __construct(ZttpResponse $response)
    {
        $this->response = $response;
    }

    /**
     * @return array
     */
    public function data()
    {
        return $this->response->json();
    }

    public function withErrors(array $errors)
    {
        $this->errors = $errors;
        return $this;
    }

}
