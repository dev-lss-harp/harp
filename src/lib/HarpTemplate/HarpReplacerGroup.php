<?php
namespace Harp\lib\HarpTemplate;

class HarpReplacerGroup
{
    public const WORD_KEY_GROUP = '@group';

    private $Template;
    
    public function __construct(HarpTemplate $Template)
    {
        $this->Template = $Template;
    }

    public function build($keyFile,$nameGroup,$list)
    {
        
        $pattern = '`(?:{{(Replacer@group:'.preg_quote($nameGroup).'\b)}})(.*?)(?:{{[\/]Replacer@group:'.preg_quote($nameGroup).'\b}})`is';

        $result = [];

        $fKey = $this->Template->getTemplateID($keyFile);

        preg_match($pattern,$this->Template->getProperties()->{$fKey},$result);

        if(isset($result[2]))
        {
            $frag = $result[2];
            
            foreach($list as $k => $item)
            {
                $sKey = '';
                
               if(is_array($item))
               {
                   foreach($item as $key => $val)
                   {
                       $sKey = '{{'.$result[1].':'.$key.'}}';
                       $frag = str_ireplace($sKey,$val,$frag); 
                   }
               }
               else
               {
                       $sKey = '{{'.$result[1].':'.$k.'}}';
                       $frag = str_ireplace($sKey,$item,$frag); 
               }

            }
            $file = str_ireplace($result[0], $frag, $this->Template->getProperties()->{$fKey});
            $this->Template->setProperty($fKey,$file);
        }
    }
}
