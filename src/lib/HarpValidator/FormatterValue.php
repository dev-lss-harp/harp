<?php
/*
 * Copyright 2010 Leonardo Souza da Silva <allezo.lss@gmail.com>.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace Harp\lib\HarpValidator;

use DateTime;

class FormatterValue
{   
   protected $lcMonetary;
   
   public function __construct()
   {
        $this->lcMonetary = Array
        (
            'pt_BR' => 'R$',
            'en_US' => 'US$',
            'de_DE' => 'EU$',
        );  
   }

   public function formatOnlyNumber($number)
   {
        $onlyNumber = preg_replace('#[^0-9]#i',null,$number);
        
        return trim($onlyNumber);       
   }
   
   public function formatPis($numberPIS)
   {
       $n = false;
       
       $numberPIS = $this->FormatOnlyNumber($numberPIS);
       
       if(strlen($numberPIS) == 11)
       {
          $p1 = substr($numberPIS,0,3);
          $p2 = substr($numberPIS,3,5);
          $p3 = substr($numberPIS,8,2);
          $p4 = substr($numberPIS,10,1);
          $n = $p1.'.'.$p2.'.'.$p3.'-'.$p4;
       }
       
       return $n;
   }

   public function formatCpf($numberCPF)
   {
       $n = false;
       
       $numberCPF = $this->FormatOnlyNumber($numberCPF);
       
       if(strlen($numberCPF) == 11)
       {
          $p1 = substr($numberCPF,0,3);
          $p2 = substr($numberCPF,3,3);
          $p3 = substr($numberCPF,6,3);
          $p4 = substr($numberCPF,9,2);
          $n = $p1.'.'.$p2.'.'.$p3.'-'.$p4;
       }

       return $n;
   } 
   
   public function formatFloatNumber($num,$precision = 2,$format = 3)
   {
       $number = $num;
       
       if($format != 5 && substr_count($num,',') > 0)
       {   
           $number = str_ireplace(Array('.',','),Array(null,'.'),$num);
       }
       
       switch ($format) 
       {
           case 1:
               $number = sprintf('%0.'.$precision.'f',number_format($number,(int) $precision,",",""));
           break;
           case 2:
               $number = sprintf('%0.'.$precision.'f',number_format($number,(int) $precision,",","."));
           break; 
           case 3:
               $number = sprintf('%0.'.$precision.'f',number_format($number,(int) $precision,".",""));
           break;  
           case 4:
               $number = sprintf('%0.'.$precision.'f',number_format($number,(int) $precision,".",","));
           break;   
           case 5:
                $number = number_format($num,$precision,',','.');
           break;    
           default:
               $number = sprintf('%0.'.$precision.'f',number_format($number,(int) $precision,".",""));
           break;
       }

       return $number;       
   }
   
   public function currencyFormat($number,$typeMonetary = 'pt_BR',$precision = 2,$format = 2)
   {
       if(isset($this->lcMonetary[$typeMonetary]))
       {            
            $value = $this->FormatFloatNumber($number,$precision,$format);
           
            return $this->lcMonetary[$typeMonetary].' '.$value;
       }
       
       return $number;
   }
   
   public function dateTimeFormat($date,$currentFormatDate,$newFormatDate)
   {
       $DateTimeFormat = DateTime::createFromFormat($currentFormatDate,$date);
      
       return $DateTimeFormat->format($newFormatDate);
   }
   
   public function globalFormatter($value,$format)
   {
        $valueF = null;

        if(substr_count($format,'#') > 0)
        {
            $digitCount = 0;
            
            $lFormat = mb_strlen($format);
            $lValue = mb_strlen($value);
        
            for($i = 0;$i < $lFormat;++$i)
            {
                $tmpItem = $format[$i];

                if($tmpItem == '#')
                {
                   if(isset($value[$digitCount]))
                   {
                       $valueF .= $value[$digitCount]; 
                       $digitCount++;
                   }
                }
                else
                {
                    if($lValue < $lFormat)
                    {
                         $valueF .= $tmpItem;
                    }
                    else
                    {
                        $digitCount++;
                        
                        $valueF .= $tmpItem;
                    }                    
                }  
            }
                 
        }

        return ($valueF == null) ? $value : $valueF;       
   }


   public function formatCEP($value,$format)
   {
        $valueF = null;

        $value = preg_replace('`\D`','',$value);

        if(substr_count($format,'#') > 0)
        {
            $digitCount = 0;
            
            $lFormat = mb_strlen($format);
            $lValue = mb_strlen($value);
        
            for($i = 0;$i < $lFormat;++$i)
            {
                $tmpItem = $format[$i];

                if($tmpItem == '#')
                {
                   if(isset($value[$digitCount]))
                   {
                       $valueF .= $value[$digitCount]; 
                       $digitCount++;
                   }
                }
                else
                {
                    if($lValue < $lFormat)
                    {
                         $valueF .= $tmpItem;
                    }
                    else
                    {
                        $digitCount++;
                        
                        $valueF .= $tmpItem;
                    }                    
                }  
            }
                 
        }

        return ($valueF == null) ? $value : $valueF;       
   }

}
