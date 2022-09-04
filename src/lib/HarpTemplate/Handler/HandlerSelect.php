<?php
namespace Harp\lib\HarpTemplate\Handler;

use BadMethodCallException;
use Exception;
use DOMDocument;
use DOMXPath;
use Harp\bin\ArgumentException;
use Harp\lib\HarpTemplate\HarpReplacer;
use Harp\lib\HarpTemplate\HarpTemplate;
use Throwable;

class HandlerSelect
{
    private $Replacer;
    private $Template;

    public function __construct(HarpTemplate $Template) 
    {      
        $this->Template = $Template;
    }  
  
    public function setOptions($name,$keyValue,$keyDisplay,$options,Array $selectAttributes = Array(),Array $optionsAttributes = Array())
    {
        $element = $this->findByAttr('name',$name);
        
        if(isset($element[0][0]))
        {
            $dropDown = $element[0][0];

            $Dom = $this->HandleHtmlAttribute->getDomDocument();

            $Dom->loadHtml($this->HarpFileComponent->getFile()->file);

            $Xpath = new DOMXPath($Dom);

            $pathSelect = '//'.$this->getName().'[@name="'.$name.'"]';

            $nodesSelect = $Xpath->query($pathSelect);

            if($nodesSelect->length > 0)
            {
                $node = $nodesSelect->item(0);

                foreach($options as $v)
                {
                    if(!empty($v[$keyValue]) && !empty($v[$keyDisplay]))
                    {
                        $option = $Dom->createElement('option',$v[$keyDisplay]);
                        $option->setAttribute('value',$v[$keyValue]);
                        foreach($optionsAttributes as $k => $a)
                        {
                            if(isset($v[$a]))
                            {
                                 $option->setAttribute($k,$v[$a]);
                            }
                        }
                        
                        $node->appendChild($option);
                    }
                }
                
                $this->HarpFileComponent->getFile()->file = str_ireplace($dropDown,$Dom->saveHTML($node),$this->HarpFileComponent->getFile()->file); 

            }
        }
        
        return $this;
    }
    
    public function addAttrOption(Array $args)
    {       

        try
        {
            if(empty($args['attr']) || !is_numeric($args['key']) || empty($args['value']) || empty($args['newAttr']))
            {
                throw new ArgumentException(
                        '!Argument attr or key or value or newAttr not found!',
                        404
                );
            }

            $fKey = $this->Template->getTemplateID($args['key']);

            $attr = preg_quote($args['attr']);
            $regexp = '`(?:\<select*[^>]*'.$attr.'*[^>]*\>)(.*?)(?:<\/select\>)`is';
            preg_match($regexp,$this->Template->getProperties()->{$fKey},$m);
           
            if(!empty($m[1]))
            {

                $htm = preg_replace('`selected[^>]=[^>]"selected"`','',$m[1]);
                $htm = preg_replace('`\bselected\b"`','',$m[1]);

                $value = preg_quote($args['value']);
                $regexp = '`\<(option*[^>]*value="'.$value.'"*[^>]*)\>`';
                
                preg_match($regexp,$htm,$opt);

                $newtag = '';

                if(isset($opt[1]))
                {
                    $newtag = $opt[1].' %s ';
                    $newtag = sprintf(
                            $newtag, 
                            $args['newAttr']
                    );
                }

                if(!empty($newtag))
                {
                    $element = str_ireplace([$opt[1]],[$newtag],$m[1]);
                    $this->Template->getReplacer()->replaceElement($args['key'],$m[1],$element);
                }
            }
 
        }
        catch(Throwable $th)
        {
            throw $th;
        }
        
        return $this->Template->getReplacer();
    }    
    
    public function setSelected($valueSelected = null)
    {       
        libxml_use_internal_errors(true) AND libxml_clear_errors();

        if(!empty($this->genericIdentifier))
        {
            $valueSelected = trim($valueSelected);

            $Doc = new DOMDocument('1.0','UTF-8');

            $Doc->preserveWhiteSpace = true;
            
            $Doc->formatOutput = true;

            $Doc->loadHTML($this->HarpFileComponent->getFile()->file);
            
            $Xpath = new DOMXPath($Doc);
            
            $query = $Xpath->query('//*[@name="'.$this->genericIdentifier.'"]/option');

            if($query->length > 0)
            {                
                $select = $this->element;

                foreach($query as $node) 
                {
                    if(trim($valueSelected) == trim($node->getAttribute('value')))
                    {
                        $previousNode = $node->C14N();
                        
                        $node->setAttribute("selected","selected");

                        $select = str_ireplace($previousNode,$Doc->saveHTML($node),$select);
                    }
                    else if($node->hasAttributes())
                    {
                        $previousNode = $node->C14N();
                        
                        $node->removeAttribute("selected");
                        
                        $select = str_ireplace($previousNode,$Doc->saveHTML($node),$select);
                    }
                }
                
                $this->HarpFileComponent->getHandleReplacement()->insertAfter($this->genericIdentifier,$this->element,$select);
            }
            
            return $this;
        }
        
        throw new Exception('Para realizar a inserção do atributo selected é necessário buscar um elemento do tipo select antes!'); 
    }
    
    public function getCurrentKey()
    {
        return $this->currentKey;
    }
    
    public function getName()
    {
        return HandleHtmlEnum::SELECT;
    }    
}
