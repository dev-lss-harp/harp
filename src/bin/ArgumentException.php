<?php
namespace Harp\bin;

class ArgumentException extends \Exception
{  
    const KEY_TYPE_EXCEPTION = 'type';
    const KEY_TITLE_EXCEPTION = 'title';

    const WARNING_TYPE_EXCEPTION = 'warning';
    const ERROR_TYPE_EXCEPTION = 'error';
    const INFO_TYPE_EXCEPTION = 'info';

    const DEFAULT_TYPE_EXCEPTION = 'error';
    const DEFAULT_TITLE = 'An Problem Ocurred!';

    const NOT_FOUND_TITLE = 'Not Found!';
    const INTERNAL_SERVER_ERROR_TITLE = 'Internal Server Error!';
    
    protected $extraArguments;
    
    public function __construct($message = "", $code = 0,Array  $extraArguments = [],\Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    
        $this->extraArguments = [];
        $this->extraArguments[self::KEY_TYPE_EXCEPTION] = self::DEFAULT_TYPE_EXCEPTION;
        $this->extraArguments[self::KEY_TITLE_EXCEPTION] = self::DEFAULT_TITLE;
        $this->extraArguments = array_merge($this->extraArguments,$extraArguments);
    }

    public function getArgs($key)
    {
        return (!empty($key) && isset($this->extraArguments[$key])) ? $this->extraArguments[$key] : null;
    }
}
