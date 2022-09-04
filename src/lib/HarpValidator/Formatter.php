<?php
namespace Harp\lib\HarpValidator;

use DateTime;
use Exception;

class HarpFormatter
{   
   public static function formatOnlyNumber($number)
   {
        $onlyNumber = preg_replace('#[^0-9]#i','',$number);
        
        return trim($onlyNumber);       
   }
   
   public static function formatPis($numberPIS)
   {
       $n = false;
       
       $numberPIS = self::formatOnlyNumber($numberPIS);
       
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

   public static function formatCpf($numberCPF)
   {
       $n = false;
       
       $numberCPF = self::formatOnlyNumber($numberCPF);
       
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
   
   public static function formatFloatNumber($num,$precision = 2,$format = 3)
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
   
   public static function currencyFormat($number,$lcMonetary = 'pt_BR',$symbol = 'RS',$precision = 2,$format = 2)
   {
        $formatter = new \NumberFormatter($lcMonetary,\NumberFormatter::CURRENCY);
        return $formatter->formatCurrency($number,$symbol);
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

}
