<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Harp\lib\HarpTemplate;

use Exception;
use Harp\bin\Enum;
use Harp\enum\ViewEnum;
use ReflectionMethod;

class HarpReplacer
{
    /*private const  WORD_KEY_PROP = '@prop';
    private const  WORD_KEY_CONST = '@const';
    private const  WORD_KEY_PATH = '@path';
    private const  WORD_KEY_VIEW = '@view';
    private const  WORD_KEY_ACTION = '@action';
    private const  WORD_KEY_ANY = '@any';
    private const FLAG_CONTENT = '@content';*/
    
    private $Template;
    private $replacementMethods = [];
    
    public function __construct(HarpTemplate $Template)
    {
        
        $this->Template = $Template;

        $this->replacementMethods = [
            ViewEnum::FlagProp->name => ViewEnum::FlagProp->value ,
            ViewEnum::FlagConst->name => ViewEnum::FlagConst->value ,
            ViewEnum::FlagPath->name => ViewEnum::FlagPath->value ,
            ViewEnum::FlagContent->name =>  ViewEnum::FlagContent->value ,
        ];

        $replacement = new \stdClass();
        $replacement->replaceValues = [];
        $replacement->replaceKeys = [];
        $this->Template->setProperty('replacement',$replacement);


    }
    
    
    public function getReplacerGroup()
    {
        return new HarpReplacerGroup($this->Template);
    }

    /*private function findDynamicValue($dynamic)
    {
        $value = '';

        $term = stristr($dynamic,self::WORD_KEY_ACTION);

        dd(self::WORD_KEY_ACTION,$dynamic);
        preg_match('`\b'.preg_quote(self::WORD_KEY_ACTION).'`is',$dynamic);
        if(preg_match('`\b'.preg_quote(self::WORD_KEY_ACTION).'`is',$dynamic))
        {
            dd('ok');
        }
        if($term !== false)
        {
            $val = $this->Template->getView()->getProperty('viewName');
           
            $value = str_ireplace([self::WORD_KEY_ACTION],[$val],$dynamic);
        }

        return $value;
    }*/
    
    private function propExec()
    {
        $args = func_get_args();

        $prop = $this->Template->getView()->getProperty($args[2]);

        array_push($this->Template->getProperty('replacement')->replaceValues[$args[0]],(!is_null($prop) ? $prop : '')); 
    }

    private function constExec()
    {
        $args = func_get_args();
        
        $consts = $this->Template->getView()->getProperty(ViewEnum::ServerVar->value);

        if(isset($consts[$args[2]]))
        {
            
             array_push($this->Template->getProperty('replacement')->replaceValues[$args[0]],$consts[$args[2]]); 
        }
    }

    private function pathExec()
    {
        $args = func_get_args();
        $relativePath = $args[2];

        $fileName =  basename($relativePath);
    
        $file = '';

        if ( 
             $this->Template->verifyFileFound(PATH_APP,$relativePath)
                 &&
             $this->Template->verifyExtension($fileName)
        )
        {
            $path = PATH_APP.'/'.$relativePath;
            $dirnName = dirname($path);
            $fileName = basename($path);

            $k = $this->Template->load($fileName,$dirnName);
            $fileKey = $this->Template->getTemplateID($k);
            $this->Template->getReplacer()->build($k);                   
            $file = $this->Template->getProperty($fileKey);
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

        array_push($this->Template->getProperty('replacement')->replaceValues[$args[0]],$file);
    }

    private function contentExec()
    {
        $args = func_get_args();

        $ext = $args[2] ?? '';

        $routeCurrent = $this->Template->getView()->getProperty(ViewEnum::RouteCurrent->value);

        if(empty($routeCurrent[ViewEnum::Action->value]))
        {
            throw new Exception('View action not found for load content!');
        }

        $fileName = sprintf('%s.%s',$routeCurrent[ViewEnum::Action->value],$ext);

        $path = sprintf('%s%s%s',PATH_PUBLIC_LAYOUTS_GROUP,DIRECTORY_SEPARATOR,$fileName);
    
        if(file_exists($path))
        {
            array_push($this->Template->getProperty('replacement')->replaceValues[$args[0]],file_get_contents($path)); 
        }
    }

    private function findValue($key,Array $parts)
    {        
        $this->Template->getProperty('replacement')->replaceValues[$key] = 
           !empty($this->Template->getProperty('replacement')->replaceValues[$key]) ?
           $this->Template->getProperty('replacement')->replaceValues[$key] : 
           [];

           if(!empty($parts[1]))
           {
           
                $prop = mb_substr(trim($parts[0]),1); 
       
                if(in_array($prop,$this->replacementMethods))
                {
                    $method = sprintf('%s%s',$prop,'Exec');
                
                    if(is_callable([$this,$method]))
                    {
                        $RefMethod = new \ReflectionMethod($this,$method);
                        $RefMethod->invokeArgs($this,[$key,...$parts]);
                    }
                }

              /*  dd('ok');
                exit;

      
               if($parts[0] == self::WORD_KEY_PROP)
               {
                   $prop = $this->Template->getView()->getProperty($parts[1]);

                   array_push($this->Template->getProperty('replacement')->replaceValues[$key],(!is_null($prop) ? $prop : '')); 
               }
               else if($parts[0] == self::WORD_KEY_CONST)
               {
                   $consts = $this->Template->getView()->getProperty(ViewEnum::ServerVar->value);

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

                //Não utilizado para nada verificar se não deve tirar
                    $ext = $parts[2] ?? '';
                    $prop = $parts[1] ?? null;

                    if(empty($prop))
                    {
                        throw new Exception('To load a view it is necessary to inform a dynamic property!',500);
                    }

                    $prop = trim($prop);

                    $view = $this->Template->getView()->getProperty($prop);

                    if(empty($view))
                    {
                        throw new Exception(sprintf('View not found for dynamic property %s!',$prop));
                    }

                    $view .= sprintf('.%s',$ext);


                    $path = sprintf('%s%s%s',PATH_PUBLIC_LAYOUTS_GROUP,DIRECTORY_SEPARATOR,$view);
                   
                   if(file_exists($path))
                   {
                       array_push($this->Template->getProperty('replacement')->replaceValues[$key],file_get_contents($path)); 
                   }
               }
               else if($parts[0] == self::FLAG_CONTENT)
               {
                    $ext = $parts[1] ?? '';

                    $routeCurrent = $this->Template->getView()->getProperty(ViewEnum::RouteCurrent->value);

                    if(empty($routeCurrent[ViewEnum::Action->value]))
                    {
                        throw new Exception('View action not found for load content!');
                    }

                    $fileName = sprintf('%s.%s',$routeCurrent[ViewEnum::Action->value],$ext);
 
                    $path = sprintf('%s%s%s',PATH_PUBLIC_LAYOUTS_GROUP,DIRECTORY_SEPARATOR,$fileName);
                
                    if(file_exists($path))
                    {
                        array_push($this->Template->getProperty('replacement')->replaceValues[$key],file_get_contents($path)); 
                    }
               }*/
               
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
        $params = mb_substr(str_repeat('%s|',count($this->replacementMethods)),0,-1);

        $pattern = sprintf('`[{]{2}(Replacer[@]('.$params.').*?)[:[^}]{2}`is',...array_values($this->replacementMethods));
        
        $result = [];
     
        preg_match_all($pattern,$this->Template->getProperties()->{$key},$result);

       /* dd($result);
       
        

        $p = mb_substr(self::WORD_KEY_PATH,1);
        $c = mb_substr(self::WORD_KEY_CONST,1);
        $v = mb_substr(self::WORD_KEY_VIEW,1);
        $cn = mb_substr(self::FLAG_CONTENT,1);
        $a = mb_substr(self::WORD_KEY_ACTION,1);

        $pattern = sprintf('`[{]{2}(Replacer[@](%s|%s|%s|%s|%s).*?)[:[^}]{2}`is',$p,$c,$v,$a,$cn);
        $result = [];
     
        preg_match_all($pattern,$this->Template->getProperties()->{$key},$result);
        dd($pattern,$pattern2,$result,$result2);*/
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
