<?php
namespace Harp\lib\HarpValidator;

use Exception;
use ReflectionMethod;
use Throwable;

class MultiValidator
{
    private $data = [];
    private $rules = [];
    private $errors = [];
    private $validator;
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

    public function __construct(Array &$data = [],Array $rules = [])
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

    private function validate($execParams)
    {
        $err = false;

        try 
        {
            $result = false;

            $Reflection = new ReflectionMethod($this->validator,$execParams['method']);
            $result = $Reflection->invokeArgs($this->validator,$execParams['parameters']);

            if(!$result)
            {
                $this->errors[$execParams['method']] = $this->defaultErrors[$execParams['method']];

                //%p = property
                //%v = value
                //%m = method
                //%1 = argument extra 1
                //%2 = argument extra 2
                //%3 = argument extra 3

                if(!empty($execParams['message']))
                {
                    $this->errors[$execParams['method']] = 
                    str_ireplace(
                        ['%p','%v','%m','%1','%2','%3'],
                        [$execParams['p'],$execParams['v'],$execParams['method'],...$execParams['extraArguments']],
                        $execParams['message']
                    );
                }

                $err = true;
            }
 
        } 
        catch (Throwable $th) 
        {
            throw $th;
        }

        return $err;
    }

    private function execRules(Array &$data,Array $rules)
    {
        if(empty($rules))
        {
            throw new \Exception('Validation rules were not informed',404);
        }

        foreach($rules as $key => $rule)
        {
            $tk = str_contains($key,'.') ? '.' : (str_contains($key,'|') ? '|' : ''); 
            $kp = str_contains($key,'.') ? explode('.',$key) : explode('|',$key);

            if($tk == '.' && (!array_key_exists($kp[0],$data) || !array_key_exists($kp[1],$data[$kp[0]])) )
            {
                throw new Exception(sprintf('There property {%s} does not exists in data!',$key),400);
            }
            else if($tk == '|' && (!array_key_exists($kp[0],$data) || !array_key_exists($kp[1],$data)))
            {
                throw new Exception(sprintf('There properties {%s} or {%s} does not exists in data!',$kp[0],$kp[1]),400);
            }
            else if(empty($tk) && !array_key_exists($kp[0],$data))
            {
                throw new Exception(sprintf('There property {%s} does not exists in data!',$kp[0]),400);
            }
     
            foreach($rule as $r)
            {
                $mRule = explode('=>',$r);
                $pRule = explode('|',$mRule[0]);

                $m = trim($pRule[0]);
                $msg = trim($mRule[1]);

                $parameters = [];
                $countP = count($pRule) - 1;
                if($countP > 0)
                {
                    for($i = 1; $i <= $countP;++$i)
                    {
                        $val = trim($pRule[$i]);
                        $parameters[] = $val;
                    }
                }
    
                $extraArguments = array_map('trim',array_slice($pRule,1));

                $vParams = '';
                $kParams = '';
      
                if($tk == '|')
                {
                    $vParams = sprintf('%s,%s',$data[$kp[0]],$data[$kp[1]]);
                    $kParams = sprintf('%s,%s',$kp[0],$kp[1]);

                    //merge parameters
                    array_unshift($parameters,$data[$kp[0]],$data[$kp[1]]); 

                    $execParams = [
                        'method' => $m,
                        'message' => $msg,
                        'v' => $vParams,
                        'p' => $kParams,
                        'extraArguments' => $extraArguments,
                        'parameters' => $parameters
                    ];

                    $foundError = $this->validate($execParams);
                    
                    if($foundError){ break 2; }
                }
                else if($tk == '.')
                {  
                    $vParams = sprintf('%s',$data[$kp[0]][$kp[1]]);
                    $kParams = sprintf('%s.%s',$kp[0],$kp[1]);

                    array_unshift($parameters,$data[$kp[0]][$kp[1]]); 

                    $execParams = [
                        'method' => $m,
                        'message' => $msg,
                        'v' => $vParams,
                        'p' => $kParams,
                        'extraArguments' => $extraArguments,
                        'parameters' => $parameters
                    ];

                    $foundError = $this->validate($execParams);
                    
                    if($foundError){ break 2; }
                }
                else
                {
                    if(is_array($data[$kp[0]]))
                    { 
                        if(count($data[$kp[0]]) < 1)
                        {
                            throw new Exception(sprintf('There property {%s} is a empty collection!',$kp[0]),400);
                        }

                        foreach($data[$kp[0]] as $k => $v)
                        {
                            $vParams = sprintf('%s',$data[$kp[0]][$k]);
                            $kParams = sprintf('%s',$k);

                            array_unshift($parameters,$data[$kp[0]][$k]); 

                            $execParams = [
                                'method' => $m,
                                'message' => $msg,
                                'v' => $vParams,
                                'p' => $kParams,
                                'extraArguments' => $extraArguments,
                                'parameters' => $parameters
                            ];
                            
                            $foundError = $this->validate($execParams);
                    
                            if($foundError) { break 3;}
                        }
                    }
                    else
                    {
                        $vParams = sprintf('%s',$data[$kp[0]]);
                        $kParams = sprintf('%s',$kp[0]);
                        array_unshift($parameters,$data[$kp[0]]); 

                        $execParams = [
                            'method' => $m,
                            'message' => $msg,
                            'v' => $vParams,
                            'p' => $kParams,
                            'extraArguments' => $extraArguments,
                            'parameters' => $parameters
                        ];
                        
                        $foundError = $this->validate($execParams);
                
                        if($foundError){ break 2; }

                    }

                }
            }
        }

        return $foundError;
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

    public function exec($throwWithErrors = true)
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

        if($throwWithErrors)
        {
            if($this->hasError())
            {
                throw new Exception($this->toString(),400);
            }
        } 
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
