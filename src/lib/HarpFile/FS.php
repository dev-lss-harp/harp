<?php 
namespace Harp\lib\HarpFile;

use ricwein\FileSystem\FileSystem;
use ricwein\FileSystem\Directory;
use ricwein\FileSystem\Storage;
use ricwein\FileSystem\Enum\Hash;

class FS
{
    public const LOCAL = 'LOCAL';
    public const FILE = 'FILE';
    public const DIR = 'DIR';
    private $Directory = [];
    private $File = [];

    public function __construct($pathDirectory = null,$name = self::LOCAL)
    {
        if(!empty($pathDirectory) && !empty($name))
        {
            $this->loadDirectory($pathDirectory = null,$name = self::LOCAL);
        }

        return $this;
    }

    public function loadDirectory($pathDirectory,$name)
    {
        $Storage = new Storage\Disk($pathDirectory);

        $this->Directory[$name] = new Directory($Storage);

        return $this;
    }

   // public function getDirec

    public function getCurrentPath($name = self::LOCAL,$type = self::DIR) : string
    {
        $Path = null;

       if($type == self::DIR && !empty($this->Directory[$name]))
       {
        dd($this->Directory[$name]->path()->getDetails());exit;
            $Path = $this->Directory[$name]->path()->getDetails();
       }
       else if($type == self::FILE && !empty($this->File[$name]))
       {
            $Path = $this->File[$name]->path()->getDetails();
       }

       return $Path['path']['realpath'] ?? $Path;
    }

}
