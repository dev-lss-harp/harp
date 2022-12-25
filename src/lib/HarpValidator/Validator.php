<?php
namespace Harp\lib\HarpValidator;

class Validator
{
    public static function isEmail($email)
    {
        $expr = '/^[a-zA-Z0-9][a-zA-Z0-9\._-]+@([a-zA-Z0-9\._-]+\.)[a-zA-Z-0-9]{2}/';
        
        $status = preg_match($expr,$email);
        
        return $status;
    }  
    
    public function isCellphone($value)
    {
        $expr = '`^(\+|\(|\()(\+?[0-9]{2,3})?\(?[0-9]{2}\).*[0-9]{4,5}\-?[0-9]{4,5}`is';
        
        return preg_match($expr,$value);
    }   
          
    public static function isEmpty($value)
    {
        $status = mb_strlen($value) < 1 ? true : false; 

        return $status;
    }   

    public static function isNotEmpty($value)
    {
        return !self::isEmpty($value);
    }  
    
    public static function valueIsNullOrEmpty($value)
    {
        $value = trim($value);
        
        $s = ($value == '' || (empty($value) && $value != 0) || $value == null);
        
        return $s;
    }
    
    public static function valueIsNotNullOrEmpty($value)
    {
        $s = !self::valueIsNullOrEmpty($value);
        
        return $s;
    }     
        
    
    public static function isCurrency($value)
    {
        $status = preg_match("/\b\d{1,3}(?:,?\d{3})*(?:\.\d{2})?\b/",$value) ? true : false; 
        
        return $status;
    }    
    
    public static function isTypeBool($value)
    {
        return gettype($value) == 'boolean';
    }  
      
    public static function isBoolean($value,$requiredValue)
    {
        $s = is_bool($value) && $value === $requiredValue;
        
        return $s;
    }  
    
    public static function isOnlyLetters($value)
    {
        $v = Sanitizer::replaceAccentsString($value);

        $status = ctype_alpha(str_replace(chr(32),'',$v));

        return $status;
    }
    
    public static function isIntegerNumber($value)
    {
        $status = preg_match('#^\s*-?[0-9]{1,45}\s*$#',(string) $value);

        return $status;
    } 

    public static function isSignedInteger($value)
    {
        return self::isIntegerNumber($value);
    }

    public static function isUnsignedInteger($value)
    {
        return self::isNaturalNumber($value);
    }
    
    public static function isNaturalNumber($value)
    {
        $status = preg_match('#^\s*?[0-9]{1,45}\s*$#',(string) $value);

        return $status;        
    }
    
    public static function isZipCode($value,$format = '[0-9]{5}-[0-9]{3}')
    {
        $s = false;

        if(is_string($format))
        {
            $s = preg_match('`^'.$format.'$`',$value);
        }
                
        return $s;        
    }

    public static function isUrl($url)
    {
        return filter_var($url,FILTER_VALIDATE_URL);
    }
  
    public static function maxlength($value,$maxLength)
    {
        $count = mb_strlen($value);

        $status = ($maxLength >= $count);

        return $status;
    }  
        
    public static function minlength($value,$minLength)
    {   
        $count = mb_strlen($value);

        $status = ($count >= $minLength);
        
        return $status;
    } 

    public static function isNumeric($value)
    {
        $status = (is_numeric($value));

        return $status;
    }     
        
    public static function isDate($date,$format = 'Y-m-d')
    {      
        $d = \DateTime::createFromFormat($format, $date);

        $s = $d && $d->format($format) == $date;
        
        return $s;
    }  
    
    public static function isTime($time)
    {
        $format = 'Y-m-d H:i:s';
         
        $time = str_pad($time,8,'0',STR_PAD_LEFT);
         
        $time = date('Y-m-d').' '.$time;

        $d = \DateTime::createFromFormat($format,$time);
         
        $s = $d && $d->format($format) == $time;
        
        return $s;
    }
    
    public static function isDatetime($date,$format = 'Y-m-d H:i:s')
    {      
        $d = \DateTime::createFromFormat($format,$date);

        $s = $d && $d->format($format) == $date;
        
        return $s;
    }

    public static function biggerThan($val1,$val2)
    {
        return $val1 > $val2;
    }
    
    public static function isCnpj($cnpj)
    {
            $s = false;

            $cnpj = preg_replace('/[^0-9]/','',(string) $cnpj);

            $lcnpj = strlen($cnpj);

            $s = ($lcnpj == 14);

            if($s)
            {
                for($i = 0, $j = 5, $sum = 0; $i < 12; $i++)
                {
                        $sum += $cnpj[$i] * $j;
                        $j = ($j == 2) ? 9 : $j - 1;
                }

                $rest = $sum % 11;
                
                $s = $cnpj[12] == ($rest < 2 ? 0 : 11 - $rest);
                
                if($s)
                {
                    for ($i = 0, $j = 6, $sum = 0; $i < 13; $i++)
                    {
                            $sum += $cnpj[$i] * $j;
                            $j = ($j == 2) ? 9 : $j - 1;
                    }

                    $rest = $sum % 11;

                    $s = $cnpj[13] == ($rest < 2 ? 0 : 11 - $rest);
                }
            }
            
            return $s;
    }   
    
    public static function isCpf($cpf)
    {
        $s = false;
        
        $cpf = preg_replace('/[^0-9]/','',(string)$cpf);
        
        $invalids = 
                Array
                (       '00000000000',
                        '11111111111',
                        '22222222222',
                        '33333333333',
                        '44444444444',
                        '55555555555',
                        '66666666666',
                        '77777777777',
                        '88888888888',
                        '99999999999'
                );
                
        if(in_array($cpf,$invalids)){ return false;}

        if(strlen($cpf) != 11){ return false; }
                
        for ($i = 0, $j = 10, $sum = 0; $i < 9; $i++, $j--){ $sum += $cpf[$i] * $j;}
                
        $r  = $sum % 11;
        
        if ($cpf[9] != ($r < 2 ? 0 : 11 - $r)){  return false; }

        for ($i = 0, $j = 11, $sum = 0; $i < 10; $i++, $j--){ $sum += $cpf[$i] * $j;}
        
        $r = $sum  % 11;
        
        $s = $cpf[10] == ($r < 2 ? 0 : 11 - $r);
        
        return $s;
    }

    public static function isEquals($v1,$v2)
    {
        return $v1 === $v2;
    }

    public static function validateByRegex($value,$regex)
    {
        return preg_match('`'.$regex.'`is',(string) $value);
    }
}


