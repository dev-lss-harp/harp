<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Harp\lib\HarpTemplate;

class HarpRepeater
{
    private $Template;
    
    public function __construct(HarpTemplate $Template)
    {
        $this->Template = $Template;
    }

    private function extractAll($result)
    {
        $elements = [];

        foreach($result as $item)
        {
            if(is_array($item))
            {
                foreach($item as $k => $item2)
                {
                    if(!isset($elements[$k]))
                    {
                        $elements[$k] = [];
                    } 

                    array_push($elements[$k],$item2);
                }

            }
        }

        return $elements;
    }
    
    public function build($keyFile,$key,$list)
    {
        $symbolFirst = $this->Template->getFirstInterpolationSymbol();
        $symbolLast = $this->Template->getLastInterpolationSymbol();

        $re = '`%s(Repeater\b@countries\b)%s(.*?)%s\/(Repeater\b@countries\b)%s`is';
        $pattern = sprintf
        (
            $re,
            $symbolFirst,
            $symbolLast,
            $symbolFirst,
            $symbolLast
        );


        /*$pattern = sprintf
                    (
                        '`[%s]{2}(Repeater\b@'.$key.'\b)[%s]{2}(.*?)[%s]{2}[/]{1}(Repeater\b@'.$key.'\b)[%s]{2}`is',
                        $symbolFirst,
                        $symbolLast,
                        $symbolFirst,
                        $symbolLast
                    );*/

        $result = [];

        $fKey = $this->Template->getTemplateID($keyFile);

        preg_match_all($pattern,$this->Template->getProperties()->{$fKey},$result);
        dd($pattern,$result);            
        $elements = $this->extractAll($result);
//dd($elements);
    
        $file = $this->Template->getProperties()->{$fKey};
   
        foreach($elements as $element)
        {
            if(isset($element[2]))
            {
                $frag = '';
                
                foreach($list as $item)
                {
                        $frag .= $element[2];
                   
                        foreach($item as $k => $val)
                        {
                            
                            $kr = sprintf
                            (
                                '%s'.$element[1].':'.$k.'%s',
                                $this->Template->getFirstInterpolationSymbol(),
                                $this->Template->getLastInterpolationSymbol()
                            );
                            dump($kr,$val,$frag);
                            $frag = str_ireplace([$kr],[$val],$frag);
                        }
                }

                $file = str_ireplace($element[0], $frag, $file);   
                 
            }
        }

        if(!empty($elements))
        {
            $this->Template->setProperty($fKey,$file);
        }

        return $this;

    }    
    
}