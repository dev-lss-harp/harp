<?php
namespace etc\HarpDesignTemplate\components\HandleHtml;

use etc\HarpDesignTemplate\components\HarpFileComponent;

class HandleHTags
{
    private $HarpFileComponent;
    
    public function __construct(HarpFileComponent &$HarpFileComponent)
    {
        $this->HarpFileComponent = &$HarpFileComponent;
    }
    
    /***
     *@todo tags de remoção explicita, ao ser chamado na view remove todos os elementos envolvidos nessa tag
     */
    public function execExRem()
    {
        try
        {       
            $regex = '`<H:exRem>(.*?)<\/H:exRem>`is';

            $this->HarpFileComponent->getFile()->file = preg_replace($regex,'',$this->HarpFileComponent->getFile()->file);

        }
        catch(Exception $ex)
        {
            throw $ex;
        }
    }    
    /***
     * @todo executa um código php
     */
    public function execExpr($str = null)
    {
        try
        {
            $str = !empty($str) ? $str : $this->HarpFileComponent->getFile()->file; 

            if(preg_match_all('`<H:code>(.*?)<\/H:code>`is',$str,$r))
            {

                foreach($r[1] as $i => $expr)
                {
                   $expr = trim($expr);
                   
                    $result =  eval("return ".$expr);
                    
                    $str = str_ireplace($r[0][$i], $result,$str);
                    $s1 = preg_match('`(return)`i',$expr);
                    $s2 = preg_match('`(while|foreach|for|if)`i',$expr);
                    $s3 = preg_match('`(class).*{`i',$expr);
                    
                    $result =  eval("return ".$expr);
                  //  var_dump($result);
                    $str = str_ireplace($r[0][$i], $result,$str);
                  // var_dump($s1,$s2,$s3);exit;
                 /*  if(preg_match('`(else)`i',$expr) || (!preg_match('`(while|foreach|for|if)`i',$expr) && !preg_match('`(class).*{`i',$expr)))
                   {
                       $result =  eval("return ".$expr);
                       $str = str_ireplace($r[0][$i], $result,$str);
                   }
                   else
                   {
                       $result =  eval($expr);
                   } */
                   
                }
            } 
        }
        catch(Exception $ex)
        {
            throw $ex;
        }
      
        return $str;
    }
}
