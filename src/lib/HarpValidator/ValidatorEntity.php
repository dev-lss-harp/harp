<?php
namespace Harp\lib\HarpValidator;

use Exception;
use Harp\lib\HarpDatabase\orm\EntityHandlerInterface;
use Harp\bin\ArgumentException;
use ReflectionClass;
use ReflectionMethod;

class ValidatorEntity
{
    private $Entity;
    private $Reflection;
    private $ValidatorValue;
    private $FormatterValue;

    public function __construct(EntityHandlerInterface  $Entity)
    {
        $this->Entity = $Entity;
        $this->Reflection = new \ReflectionClass($Entity);
        $eval  =  '$this->ValidatorValue = new Harp\\lib\\HarpValidator\\ValidatorValue();';
        eval($eval);
  
        $evalF = '$this->FormatterValue = new Harp\\lib\\HarpValidator\\FormatterValue();';
        eval($evalF);

        return $this;
    }

    private function getMethodValue($Entity,$mtd)
    {
        $RefMethod = new ReflectionMethod($Entity,$mtd);

        $val = $RefMethod->invoke($Entity);

        return $val;
    }

    private function setMethodValue($Entity,$mtd,$val)
    {
        $RefMethod = new ReflectionMethod($Entity,$mtd);

        $RefMethod->invokeArgs($Entity,[$val]);
    }

    private function execMethodFormat($method,$val,$Entity,$mtdSet)
    {
        $ps = preg_match('`\((.*?)\)`',$method,$params);
      
        if(!$ps)
        {
            throw new ArgumentException('Formatter configuration on entity {'.$this->Entity->getEntityName().'} invalid!');
        }

        $expParams = explode(',',$params[1]); 
    
        foreach($expParams as $p)
        {
            if(mb_substr($p,0,1) == '@')
            {
                 $strVal = sprintf(
                        '%s%s%s',
                        "'", 
                        $p === '@value' ? $val : substr($p,1),
                        "'"
                );

                $method = str_ireplace([$p],[$strVal],$method);
            }
        }
 
        $retV = null;
        $eval = '$retV = $this->FormatterValue->'.$method.';';
        eval($eval);

        $this->setMethodValue($Entity,$mtdSet,$retV);
    }

    private function execMethodValidate($method,$exception,$val)
    {
        $this->ValidatorValue->setException($exception,$val);
        $ps = preg_match('`\((.*?)\)`',$method,$params);

        if(!$ps)
        {
            throw new ArgumentException('Validation configuration on entity {'.$this->Entity->getEntityName().'} invalid, no arguments found!');
        }

        $expParams = explode(',',$params[1]); 

        foreach($expParams as $p)
        {
            if(mb_substr($p,0,1) == '@')
            {
                 $strVal = sprintf(
                        '%s%s%s',
                        "'", 
                        $p === '@value' ? $val : substr($p,1),
                        "'"
                );

                $method = str_ireplace([$p],[$strVal],$method);
            }
        }
  
        $retV = null;
        $eval = '$retV = $this->ValidatorValue->'.$method.';';
        eval($eval);
        
        return $retV;
    }

    private function getTreeEntity($entity)
    {
        $ents = explode('.',$entity);
        $last = $ents[count($ents) - 1];
        unset($ents[count($ents) - 1]);

        return ['tree' => $ents, 'last' => $last != $this->Entity->getEntityName() ? $last : null];
    }


    private function getEntity($tree)
    {
        $objRef = $this->Reflection;
        $objEntity = $this->Entity;

        foreach($tree['tree'] as $ent)
        {
            $method = 'get'.ucfirst($ent);

            if(!$objRef->hasMethod($method))
            {
                throw new ArgumentException(
                    'This entity {'.$ent.'} not found!', 
                    'Not Found'
                );
            }

            $Ref = $objRef->getMethod($method);
  
            $objEntity = $Ref->invoke($objEntity,$method);
            $objRef = new ReflectionClass($objEntity);
        }

        if(!empty($tree['last']))
        {
            $method = 'get'.ucfirst($tree['last']);
  
            $Ref = $objRef->getMethod($method);
            $objEntity = $Ref->invoke($objEntity,$method);
        }

        return $objEntity;
    }

    private function addResponse($responseSource,$response,$Ref)
    {
        foreach($responseSource as $key => $val)
        {
            if(!isset($response[$Ref->getEntityName()]))
            {
                $response[$Ref->getEntityName()] = [];
            }

            
            $response[$Ref->getEntityName()][$key] = $val;
        }

        return $response;
    }

    public function validateAll(Array $methods)
    {
        $response = [];
        try 
        {
           foreach($methods as $entity => $attrs)
           {
               $tree = $this->getTreeEntity($entity);
               $Ref = $this->getEntity($tree);

               $rValid = $this->validate($attrs,$Ref);

               $response = $this->addResponse($rValid,$response,$Ref);
           }
        } 
        catch (\Throwable $th) 
        {
            throw $th;
        }

        return $response;
    }

    private function parseMethods($regexResult)
    {
        //fnv - validate
        //fne - exception
        //fnf - format
        $prefixes = [
            'fnv',
            'fne',
            'fnf'
        ];

        $methodsList = [
            'fnv' => [],
            'fne' => [],
            'fnf' => [],
        ];

        foreach($regexResult[0] as $method)
        {
            $prefix = mb_substr($method,0,3);

            if(!in_array($prefix,$prefixes))
            {
                throw new Exception('Prefix: {'.$prefix.'} not valid, valid prefixes are: {'.implode(',',$prefixes).'}');
            }
            
            array_push($methodsList[$prefix],mb_substr($method,4));
        }

        return $methodsList;
    }

    public function validate(Array $methods,$objEntity = null)
    {
        $response = [];
       
        try 
        {
            $objRef = empty($objEntity) ? $this->Reflection : new ReflectionClass($objEntity);
            $objEntity = empty($objEntity) ? $this->Entity : $objEntity;

            foreach($methods as $m)
            {

                $mtd = 'get'.ucfirst($m);
                $mtdSet = 'set'.ucfirst($m);
                $Ref = $objRef->getMethod($mtd);
                $doc = $Ref->getDocComment();
               
                if(empty($doc))
                {  
                    throw new ArgumentException('Method {'.$m.'} has not been configured on entity {'.$objEntity->getEntityName().'} to be validated!');
                }

                $pms = preg_match_all('`[A-z0-9]*\(.*?\)`mi',$doc,$result);

                if(!$pms)
                {
                    throw new ArgumentException('Sintax error in configuration method {'.$m.'} on entity {'.$objEntity->getEntityName().'} to be validated!');
                }

                $parsedMethods = $this->parseMethods($result);

                $val = $this->getMethodValue($objEntity,$mtd);
           
                if(count($parsedMethods['fnv']) != count($parsedMethods['fne']))
                {  
                    throw new ArgumentException('Validation expects two arguments: {method to validate value} and {Exception to throw}!');
                }

                for($j = 0; $j < count($parsedMethods['fnv']);++$j)
                {
                    $exeMtd = $parsedMethods['fnv'][$j];
                    $excMtd = $parsedMethods['fne'][$j];
                    $ret = $this->execMethodValidate($exeMtd,$excMtd,$val);
                }

                for($j = 0; $j < count($parsedMethods['fnf']);++$j)
                {
                    $exeMtd = $parsedMethods['fnf'][$j];
                    $this->execMethodFormat($exeMtd,$val,$objEntity,$mtdSet);
                }

               /* if(!empty($result[0][2]))
                {
                    $this->execMethodFormat($result[0][2],$val,$objEntity,$mtdSet);
                }*/

                $response[$m] = $ret;
            }
        } 
        catch (\Throwable $th) 
        {
            throw $th;
        }

        return $response;
    }
}