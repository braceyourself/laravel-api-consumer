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
     * @return \Illuminate\Support\Collection|\Tightenco\Collect\Support\Collection
     */
    public function data()
    {
        return collect([
            'data' => $this->response->json()
        ]);
    }

    public function addErrors(array $errors)
    {
        $this->errors = $errors;
    }

    public function withErrors(){
        return $this->data()->add('errors', $this->errors);
    }

    public function validate(array $rules, array $messages = [], array $customAttributes = []){
        Validator::make(
            $this->data()->toArray(),
            $rules,
            $messages,
            $customAttributes
        )->validate();
    }

}
