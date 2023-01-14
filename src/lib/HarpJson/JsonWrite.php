<?php 
namespace Harp\lib\HarpJson;

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use stdClass;

class JsonWrite
{
    private const directory = 'json'; 
    private const filename = 'db.json';
    private const root = 'root';
    public const SCALAR = 0;
    public const ARRAY = 1;
    public const OBJECT = 2;
    private Filesystem $FileSystem;
    private string $path;
    private string $filename;
    private $list;
    private $listNode = [];


    public function __construct($path,$directory = null,$filename = null)
    {
        $directory = $directory ?? self::directory;

        $this->filename = $filename ?? self::filename;

        $this->path = sprintf
                        (
                            '%s%s%s',
                            $path,
                            DIRECTORY_SEPARATOR,
                            $directory
                        );

        $this->FileSystem = new Filesystem(new LocalFilesystemAdapter($this->path));

        $this->list = new stdClass();
        $this->listNode[self::root] = $this->list;

    }

    private function getNode(string $key)
    {
        $p = explode('.',$key);


        $obj = $this->listNode[self::root];
        

        foreach($p as $k)
        {
            if(array_key_exists($k,$this->listNode))
            {
                $obj = &$this->listNode[$k];
            }
        }

        $ret = new stdClass();
        $ret->obj = &$obj;
        $ret->key = $p[count($p) - 1];

        return $ret;
    }

    public function normalizeValue($value,$dataType = self::SCALAR)
    {
        switch($dataType)
        {
            case self::ARRAY:
                    return (Array)$value;
                break;
            case self::OBJECT:
                    return (object)$value;
                break;
            default:
                return $value;
        }
    }

    public function set(string $key,mixed $value,$dataType = self::SCALAR)
    {
        $value = $this->normalizeValue($value,$dataType);

        $node = $this->getNode($key);

        if(is_array($node->obj))
        {
            if(!is_numeric($node->key))
            {
                array_push($node->obj,$value);
            }
            else
            {
                $node->obj[intval($node->key)] = $value;
            }
        }
        else
        {
            $node->obj->{$node->key} = &$value;
        }

        if($value instanceof stdClass || is_array($value))
        {
            $this->listNode[$node->key] = &$value;
        }

        return $this;
    }

    public function delete(string $key)
    {
        $node = $this->getNode($key);
        
        $node->obj = (array)$node->obj;
        unset($node->obj[$node->key]);

        return $this;
    }
    public function get()
    {
        return json_encode($this->list,JSON_PRETTY_PRINT);
    }
    
    public function save()
    {
        $this->FileSystem->write(
            sprintf('%s%s',DIRECTORY_SEPARATOR,$this->filename),
            $this->get()
        ); 
    }
}