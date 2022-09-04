<?php
namespace Harp\bin;
use Harp\lib\HarpTemplate\HarpTemplate;

/**
 * Description of View
 *
 * @author t-lsilva
 */
class View extends HarpView
{
    public $Template;
    
    public function __construct($viewName)
    {
        parent::__construct($viewName);
        
        $this->Template = new HarpTemplate($this);
        
        return $this;
    }
    
    public static function defaultAction($value)
    {
        exit(print(json_encode([$value])));
    }
}
