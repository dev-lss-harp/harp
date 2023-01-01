<?php
namespace Harp\lib\HarpValidator;

use Exception;
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

    private function validate($strParam,$method,$property,$value,$extraParams = [])
    {
        $err = false;
        
        try 
        {
            $p = explode("=>",$strParam);
            $fn = trim($p[0]);
            $msg = trim($p[1]);

            $result = false;
  
            eval($fn.";");

            if(!$result)
            {
                $this->errors[$method] = $this->defaultErrors[$method];

                //%p = property
                //%v = value
                //%m = method
                //%1 = argument extra 1
                //%2 = argument extra 2
                //%3 = argument extra 3

                if(!empty($msg))
                {
                    $this->errors[$method] = 
                    str_ireplace(
                        ['%p','%v','%m','%1','%2','%3'],
                        [$property,$value,$method,...$extraParams],
                        $msg
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

    private function getValue($value)
    {
        $type = gettype($value);

        switch($type)
        {
            case 'boolean':
                    return $value ? 'true' : 'false';
            break;
            case 'string':
                    return sprintf('%s%s%s',"'",Sanitizer::filterSanitizeString($value),"'");
            break;
            case 'integer':
            case 'double':    
                    return $value;
            break;
            case 'NULL':
                    return '';
            break;
            default:
                throw new Exception(sprintf('The data type {%s} is invalid to validate!',$type),400);

        }
      
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
     
            $rulesEnabled = ['isEquals'];

            foreach($rule as $r)
            {
                $mRule = explode('=>',$r);
                $pRule = explode('|',$mRule[0]);

                $m = trim($pRule[0]);
                $msg = trim($mRule[1]);

                $staticParams = '';
                $countP = count($pRule) - 1;
                if($countP > 0)
                {
                    for($i = 1; $i <= $countP;++$i)
                    {
                        $val = trim($pRule[$i]);
                  
                        $staticParams .= $i != $countP ? sprintf('"%s",',$val) : sprintf('"%s"',$val); 
                    }
                   
                }
    
                $vParams = '';
                $kParams = '';
      
                if($tk == '|' && in_array($m,$rulesEnabled))
                {
                    $data[$kp[0]] = $this->getValue($data[$kp[0]]);
                    $data[$kp[1]] = $this->getValue($data[$kp[1]]);
                    $type = gettype($data[$kp[1]]);
                    $vParams = sprintf('%s,%s',$data[$kp[0]],$data[$kp[1]]);
                    $kParams = sprintf('%s,%s',$kp[0],$kp[1]);

                    $overWriteRule = sprintf('$result = $this->validator->%s(%s,%s%s) => %s',$m,$data[$kp[0]],$data[$kp[1]],(mb_strlen($staticParams) != 0 ? sprintf(',%s',$staticParams) : $staticParams),$msg); 
                }
                else if($tk == '.')
                {
                    $data[$kp[0]][$kp[1]] = $this->getValue($data[$kp[0]][$kp[1]]);
                    $vParams = sprintf('%s',$data[$kp[0]][$kp[1]]);
                    $kParams = sprintf('%s.%s',$kp[0],$kp[1]);
                    $type = gettype($data[$kp[0]][$kp[1]]);
                    $overWriteRule = sprintf('$result = $this->validator->%s(%s%s) => %s',$m,$data[$kp[0]][$kp[1]],(mb_strlen($staticParams) != 0 ? sprintf(',%s',$staticParams) : $staticParams),$msg);
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
                            $data[$kp[0]][$k] = $this->getValue($data[$kp[0]][$k]);
                            $vParams = sprintf('%s',$data[$kp[0]][$k]);
                            $kParams = sprintf('%s',$k);
                            $overWriteRule = sprintf('$result = $this->validator->%s(%s%s) => %s',$m,$data[$kp[0]][$k],(mb_strlen($staticParams) != 0 ? sprintf(',%s',$staticParams) : $staticParams),$msg);
                            $foundError = $this->validate($overWriteRule,$m,$kParams,$vParams);
                            if($foundError) { break 2;}
                        }
                    }
                    else
                    {
                  
                        $data[$kp[0]] = $this->getValue($data[$kp[0]]);
                
                        $vParams = sprintf('%s',$data[$kp[0]]);
                        $kParams = sprintf('%s',$kp[0]);

                        $overWriteRule = sprintf('$result = $this->validator->%s(%s%s) => %s',$m,$data[$kp[0]],(mb_strlen($staticParams) != 0 ? sprintf(',%s',$staticParams) : $staticParams),$msg);

                    }

                }
                
                $foundError = $this->validate($overWriteRule,$m,$kParams,$vParams);
                
                if($foundError){ break 2; }
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
