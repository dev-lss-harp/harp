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
        'isTypeBool' => 'Parameter {%s} with value {%s} is invalid for validator {%s}!',
        'isOnlyLetters' => 'Parameter {%s} with value {%s} is invalid for validator {%s}!',
        'isIntegerNumber' => 'Parameter {%s} with value {%s} is invalid for validator {%s}!',
        'isUnsignedInteger' => 'Parameter {%s} with value {%s} is invalid for validator {%s}!',
        'isSignedInteger' => 'Parameter {%s} with value {%s} is invalid for validator {%s}!',
        'biggerThan' => 'Parameter {%s} with value {%s} is smaller than with value {%s}!',
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

    private function parseMultiProperty($property,$data)
    {
        $multipleProperty = explode('|',$property);

        foreach($multipleProperty as $prop)
        {
            if(!array_key_exists($prop,$data))
            {
                throw new \Exception(sprintf('property {%s} does not exists in data!',$prop),404);
            }
        }

        return $multipleProperty;
    }
    

    private function validate($data,$property,$rule)
    {
        $err = false;

        $multipleProperty = $this->parseMultiProperty($property,$data);
        
        $args = explode('=>',$rule);
        $fnArgs = explode('|',$args[0]);
        $method = trim($fnArgs[0]);

        if(!method_exists($this->validator,$method))
        {
            throw new \Exception(sprintf('{%s} validation is not supported!',$method),400);
        }

        $t = 0;
        $value = '';
        
        foreach($multipleProperty as $prop)
        {
            $fnArgs[$t] = $data[$prop];
            $value = empty($value) ? $data[$prop] : sprintf('%s,%s',$value,$data[$prop]);
            ++$t;
        }

        foreach($fnArgs as $k => $v)
        {
            if(is_scalar($v))
            {
                $fnArgs[$k] = is_string($v) ? trim($v) : $v;
            }
            else if(is_object($v) || is_array($v))
            {
                foreach($v as $k2 => $v2)
                {
                    $fnArgs[$k] = is_string($v2) ? trim($v2) : $v2;
                }
            }
            
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
            $this->errors[$method] = !empty($args[1]) ? str_ireplace(['%p','%v','%m','%1','%2','%3'],[$property,$value,...$fnArgs],trim($args[1])) : $this->defaultErrors[$method];

            $err = true;
        }

        return $err;
 
    }

    private function fnValidateCall($data,$property,$r)
    {
        $this->parseMultiProperty($property,$data);


        if(is_array($data[$property]) && empty($data[$property]))
        {
            throw new Exception(sprintf('There are no properties for data validation in array {%s}!',$property),400);
        }

        if(!is_array($data[$property]))
        {
            $foundError = $this->validate($data,$property,$r);

            if($foundError)
            {
                return $foundError;
            }
        }
        else
        {
            foreach($data[$property] as $p => $val)
            {
                $foundError = $this->validate($data[$property],$p,$r);
                if($foundError)
                {
                    break;
                }
            }
        }

        return $foundError;
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
                    $foundError = $this->fnValidateCall($data,$property,$r);

                    if($foundError){ break 2; }
                }
            }
            else
            {
                $foundError = $this->fnValidateCall($data,$property,$rule);
                if($foundError){ break; }
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
        if(empty($this->data))
        {
            throw new Exception('Validation data is empty!');
        }
        else  if(empty($this->rules))
        {
            throw new Exception('validation rules not given!');
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

    public function throwErrors($code = 400,$first = false)
    {
        if($this->hasError())
        {
            $errors = $this->getErrors();
        
            throw new MultiValidatorException(!$first ? $this->toString() : $errors[key($errors)],$code);
        }
    }
}


