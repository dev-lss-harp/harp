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
use stdClass;
use Symfony\Polyfill\Intl\Idn\Resources\unidata\Regex;

class HarpReplacer
{
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

    private function propExec()
    {
        $args = func_get_args();

        $k = $args[0];
        $p1 = $args[1]['p1'];
        $p2 = $args[1]['p2'];

        $data = $this->Template->getView()->getProperty($p1);

        $data = is_array($data) ? $data : (($data instanceof stdClass) ? (array)$data : $data);

        $v = is_scalar($data) 
        ? $data 
        : 
        (
            !is_scalar($data) && !empty($p2) && array_key_exists($p2,$data)
            ? $data[$p2]
            : null
        );

        if(is_null($v))
        {
            throw new Exception('Failed to perform substitution for property {%s}, for this substitution primitive data or simple array are allowed!');
        }

        array_push($this->Template->getProperty('replacement')->replaceValues[$k],$v); 
    }

    private function constExec()
    {
        $args = func_get_args();
    
        $consts = $this->Template->getView()->getProperty(ViewEnum::ServerVar->value);
   
        $k = $args[0];
        $p1 = $args[1]['p1'];

        if(isset($consts[$p1]))
        { 
             array_push($this->Template->getProperty('replacement')->replaceValues[$k],$consts[$p1]); 
        }
    }

    private function pathExec()
    {
        $args = func_get_args();
        $relativePath = $args[1]['p1'];

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

        $ext = $args[1]['p1'] ?? '';

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

    private function findValue($key,$prop,$value)
    {        
        $this->Template->getProperty('replacement')->replaceValues[$key] = 
           !empty($this->Template->getProperty('replacement')->replaceValues[$key]) ?
           $this->Template->getProperty('replacement')->replaceValues[$key] : 
           [];
        
            if(in_array($prop,$this->replacementMethods))
            {
                $method = sprintf('%s%s',$prop,'Exec');

                if(is_callable([$this,$method]))
                {
                    $complex = explode(':',$value);
                    $RefMethod = new \ReflectionMethod($this,$method);
                    $RefMethod->invokeArgs($this,[$key,['p1' => $complex[0],'p2' => $complex[1] ?? null]]);
                }
            }
           
    }
    
    //private function parseTerm($key,$prop,$value)
    //{
       // $this->findValue($key,$prop,$value);
        /*$firstOcurrence = stristr($term, '@');

        if($firstOcurrence !== false)
        {
            $parts = explode(':',$firstOcurrence);
      dd($parts,$key);
            $this->findValue($key,$parts);
        }*/
   // }

    private function getKeys($result)
    {
        $keys = $result[0];

        if(empty($result[0]))
        {
            $keys = [
                        sprintf
                        (
                            '%swithout-replacement%s',
                            $this->Template->getFirstInterpolationSymbol(),
                            $this->Template->getLastInterpolationSymbol()
                        )
                    ];
        }

        return $keys;
    }
    
    public function parseFile($key)
    {
        $symbolFirst = $this->Template->getFirstInterpolationSymbol();
        $symbolLast = $this->Template->getLastInterpolationSymbol();

        $params = mb_substr(str_repeat('%s|',count($this->replacementMethods)),0,-1);

        $ptn = sprintf
        (
            '`%s(Replacer[@](%s).*?):(.*?)%s{1}`',
            $symbolFirst,
            $params,
            $symbolLast
        );

        $pattern = sprintf
        (
            $ptn,...array_values($this->replacementMethods)
        );


        $result = [];
     
        preg_match_all($pattern,$this->Template->getProperties()->{$key},$result);

        if(isset($result[3]))
        {
            $this->Template->getProperty('replacement')->replaceKeys[$key] = [];
            $keys = $this->getKeys($result);
            $this->Template->getProperty('replacement')->replaceKeys[$key] = $keys;

            for($i = 0; $i < count($result[2]);++$i)
            {
                $this->findValue($key,$result[2][$i],$result[3][$i]);
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
            $pattern = sprintf
            (
                '`%sReplacer%s:%s%s`',
                $this->Template->getFirstInterpolationSymbol(),
                self::WORD_KEY_ANY,
                $key,
                $this->Template->getLastInterpolationSymbol()
            );
      
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