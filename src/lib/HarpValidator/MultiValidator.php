<?php
namespace Harp\lib\HarpValidator;

use Exception;

class MultiValidator
{
    private $data = [];
    private $rules = [];
    private $Validator;
    private $errors = [];
    private $defaultErrors = [
        'isEmail' => 'E-mail {%s} is invalid!',
        'isEmpty' => 'Parameter {%s} with value {%s} is invalid for validator {%s}!',
        'isNotEmpty' => 'Parameter {%s} with value {%s} is invalid for validator {%s}!',
        'valueIsNullOrEmpty' => 'Parameter {%s} with value {%s} is invalid for validator {%s}!',
        'valueIsNotNullOrEmpty' => 'Parameter {%s} with value {%s} is invalid for validator {%s}!',
        'isCurrency' => 'Parameter {%s} with value {%s} is invalid for validator {%s}!',
        'isBoolean' => 'Parameter {%s} with value {%s} is invalid for validator {%s}!',
        'isOnlyLetters' => 'Parameter {%s} with value {%s} is invalid for validator {%s}!',
        'isIntegerNumber' => 'Parameter {%s} with value {%s} is invalid for validator {%s}!',
        'isNaturalNumber' => 'Parameter {%s} with value {%s} is invalid for validator {%s}!',
        'isZipCode' => 'Parameter {%s} with value {%s} is invalid for validator {%s}!',
        'maxlength' => 'Parameter {%s} with value {%s} is invalid for validator {%s}!',
        'minlength' => 'Parameter {%s} with value {%s} is invalid for validator {%s}!',
        'isNumeric' => 'Parameter {%s} with value {%s} is invalid for validator {%s}!',
        'isDate' => 'Parameter {%s} with value {%s} is invalid for validator {%s}!',
        'isTime' => 'Parameter {%s} with value {%s} is invalid for validator {%s}!',
        'isDatetime' => 'Parameter {%s} with value {%s} is invalid for validator {%s}!',
        'isCnpj' => 'Parameter {%s} with value {%s} is invalid for validator {%s}!',
        'isCpf' => 'Parameter {%s} with value {%s} is invalid for validator {%s}!',
    ];

    public function __construct(Array $data = [],Array $rules = [])
    {
        $this->data = $data;
        $this->rules = $rules;

        $this->validator = new Validator();
        
        if(!empty($this->data) && !empty($this->rules))
        {
            $this->execRules($this->data,$this->rules);
        }

        return $this;
    }
    

    private function validate($data,$property,$rule)
    {
        $err = false;

        if(!array_key_exists($property,$data))
        {
            throw new \Exception(sprintf('property {%s} does not exists in data!',$property),404);
        }

        $args = explode('=>',$rule);
        $fnArgs = explode('|',$args[0]);
        $method = trim($fnArgs[0]);

        if(!method_exists($this->validator,$method))
        {
            throw new \Exception(sprintf('{%s} validation is not supported!',$method),400);
        }

        $value = $data[$property];
        $fnArgs[0] = $value;

        foreach($fnArgs as $k => $v)
        {
            $fnArgs[$k] = trim($v);
        }

        $Reflection = new \ReflectionMethod($this->validator, $method);
        $result = $Reflection->invokeArgs($this->validator,$fnArgs);

        if(!$result)
        {
            //%p = property
            //%v = value
            //%m = method
            //%1 = argument extra 1
            //%2 = argument extra 2
            //%3 = argument extra 3
            $this->errors[$method] = !empty($args[1]) ? str_ireplace(['%p','%v','%m','%1','%2','%3'],[$property,$value,...$fnArgs],$args[1]) : $this->defaultErrors[$method];

            $err = true;
        }

        return $err;
    }

    private function execRules(Array $data,Array $rules)
    {

        if(empty($rules))
        {
            throw new \Exception('Validation rules were not informed',404);
        }

        foreach($rules as $property => $rule)
        {
            $foundError = false;
            if(is_array($rule))
            {
                foreach($rule as $r)
                {
                    $foundError = $this->validate($data,$property,$r);
                }
            }
            else
            {
                $foundError = $this->validate($data,$property,$rule);
            }

            if($foundError)
            {
                break;
            }
        }
    }

    public function setData(Array $data)
    {
        $this->data = $data;

        return $this;
    }

    public function addRule(string $key,Array $rule)
    {
        if(!array_key_exists($key,$this->rules))
        {
            $this->rules[$key] = $rule;
        }

        return $this;
    }

    public function exec()
    {
        if(empty($this->data) || empty($this->rules))
        {
            throw new Exception('It is necessary to inform the data and rules to validate!');
        }
        
        $this->execRules($this->data,$this->rules);
    }

    public function hasError()
    {
        return count($this->errors);
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function toString()
    {
        return implode(PHP_EOL,$this->getErrors());
    }
}

