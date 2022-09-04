<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Harp\lib\HarpTemplate;

use Harp\bin\Enum;

class HarpReplacer
{
    private const  WORD_KEY_PROP = '@prop';
    private const  WORD_KEY_CONST = '@const';
    private const  WORD_KEY_PATH = '@path';
    private const  WORD_KEY_VIEW = '@view';
    private const  WORD_KEY_ACTION = '@action';
    private const  WORD_KEY_ANY = '@any';
    
    private $Template;
    
    public function __construct(HarpTemplate $Template)
    {
        $this->Template = $Template;
        
        $replacement = new \stdClass();
        $replacement->replaceValues = [];
        $replacement->replaceKeys = [];
        $this->Template->setProperty('replacement',$replacement);
    }
    
    
    public function getReplacerGroup()
    {
        return new HarpReplacerGroup($this->Template);
    }

    private function findDynamicValue($dynamic)
    {
        $value = '';

        $term = stristr($dynamic,self::WORD_KEY_ACTION);
        
        if($term !== false)
        {
            $val = $this->Template->getView()->getProperty('viewName');
            
            $value = str_ireplace([self::WORD_KEY_ACTION],[$val],$dynamic);
        }
        
        return $value;
    }
    
    private function findValue($key,Array $parts)
    {        

        $this->Template->getProperty('replacement')->replaceValues[$key] = 
           !empty($this->Template->getProperty('replacement')->replaceValues[$key]) ?
           $this->Template->getProperty('replacement')->replaceValues[$key] : 
           [];
     
           if(!empty($parts[1]))
           {
               if($parts[0] == self::WORD_KEY_PROP)
               {
                   $prop = $this->Template->getView()->getProperty($parts[1]);

                   array_push($this->Template->getProperty('replacement')->replaceValues[$key],(!is_null($prop) ? $prop : '')); 
               }
               else if($parts[0] == self::WORD_KEY_CONST)
               {
                   $consts = $this->Template->getView()->getProperty(HarpTemplate::__SERVER_VARIABLES);

                   if(isset($consts[$parts[1]]))
                   {
                       
                        array_push($this->Template->getProperty('replacement')->replaceValues[$key],$consts[$parts[1]]); 
                   }
               }
               else if($parts[0] == self::WORD_KEY_PATH)
               {
                   $fileName =  basename($parts[1]);
                   $relativePath = $parts[1];

                   $file = '';

                   if
                   (
                       (
                                $this->Template->verifyFileFound(PATH_APP,$relativePath)
                            &&
                                $this->Template->verifyExtension($fileName)
                       )
                   )
                   {
                        $path = PATH_APP.'/'.$relativePath;
                        $dirnName = dirname($path);
                        $fileName = basename($path);

                        $k = $this->Template->load($fileName,$dirnName);
                        $fileKey = $this->Template->getTemplateID($k);
                        $this->Template->getReplacer()->build($k);                   
                        $file = $this->Template->getProperty($fileKey);
                        //echo 'aqui';exit;
                   }
                   else
                   {
                        $dirnName = dirname($relativePath);
                        $fileName = basename($relativePath);
                        $k = $this->Template->load($fileName,$dirnName);
                        $fileKey = $this->Template->getTemplateID($k);
                        $this->Template->getReplacer()->build($k);                   
                        $file = $this->Template->getProperty($fileKey);

                   }

                   array_push($this->Template->getProperty('replacement')->replaceValues[$key],$file);
               }
               else if($parts[0] == self::WORD_KEY_VIEW)
               {
                   $path = PATH_VIEW_APP.'/'.$this->findDynamicValue($parts[1]);

                   if(file_exists($path))
                   {
                       array_push($this->Template->getProperty('replacement')->replaceValues[$key],file_get_contents($path)); 
                   }
               }
               
           }
    }
    
    private function parseTerm($key,$term)
    {
        $firstOcurrence = stristr($term, '@');

        if($firstOcurrence !== false)
        {
            $parts = explode(':',$firstOcurrence);

            $this->findValue($key,$parts);
        }
    }

    private function getKeys($result)
    {
        $keys = $result[0];

        if(empty($result[0]))
        {
            $keys = ['{{without-replacement}}'];
        }

        return $keys;
    }
    
    public function parseFile($key)
    {
        //$pattern = '`[{]{2}(Replacer[@A-z0-9].*?)[:[^}]{2}`is';

        $p = mb_substr(self::WORD_KEY_PATH,1);
        $c = mb_substr(self::WORD_KEY_CONST,1);
        $v = mb_substr(self::WORD_KEY_VIEW,1);
        $a = mb_substr(self::WORD_KEY_ACTION,1);

        $pattern = sprintf('`[{]{2}(Replacer[@](%s|%s|%s|%s).*?)[:[^}]{2}`is',$p,$c,$v,$a);
        $result = [];
     
        preg_match_all($pattern,$this->Template->getProperties()->{$key},$result);

        //var_dump($result);
        /*echo '<br/>';echo $key;echo '<br/>';
        echo $this->Template->getProperties()->{$key};*/
        if(isset($result[1]))
        {

                $this->Template->getProperty('replacement')->replaceKeys[$key] = [];
                $keys = $this->getKeys($result);
                $this->Template->getProperty('replacement')->replaceKeys[$key] = $keys;
                
                foreach($result[1] as $term)
                {
                    $this->parseTerm($key,$term);
                }
        }

    }

    public function replaceElement($k,$sourceElement,$destElement)
    {
        if(!empty($sourceElement) && !empty($destElement))
        {
            $fKey = $this->Template->getTemplateID($k);
            $file = $this->Template->getProperty($fKey);
            $file = str_ireplace($sourceElement,$destElement,$file);
            $this->Template->setProperty($fKey,$file);
            $this->Template->sanitizeFiles($fKey);
        }
    }
    

    public function replaceByKey(int $k,string $key,string $value)
    {
        $fKey = $this->Template->getTemplateID($k);

        $file = $this->Template->getProperty($fKey);

        $value = !is_null($value) ? $value : '';

        if(!empty($key))
        {
            $pattern = sprintf('`{{Replacer%s:%s}}`',self::WORD_KEY_ANY,$key);
            preg_match_all($pattern,$file,$result);

            if(isset($result[0]))
            {
                foreach($result[0] as $key)
                {
                    array_push($this->Template->getProperty('replacement')->replaceKeys[$fKey],$key); 
                    array_push($this->Template->getProperty('replacement')->replaceValues[$fKey],$value); 
                }
            }
        }

        return $this;
    }

    public function build($key)
    {
        $fKey = $this->Template->getTemplateID($key);

        $file = $this->Template->getProperty($fKey);

        $keys = $this->Template->getProperty('replacement')->replaceKeys[$fKey]
                    ?? [];

        $values = $this->Template->getProperty('replacement')->replaceValues[$fKey]
                    ?? [];

        if(!empty($file))
        {
            $file = str_ireplace($keys,
                                 $values, 
                                $file);
                                
            $this->Template->setProperty($fKey,$file);
            $this->Template->sanitizeFiles($fKey);
        }
    }
}
